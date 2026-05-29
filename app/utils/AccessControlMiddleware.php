<?php
/**
 * DFMS Access Control Layer (ACL) Middleware
 * Production-grade security interceptor for centralized RBAC and IDOR prevention.
 */
class AccessControlMiddleware {
    private static $roleHierarchy = [
        'super_admin'      => ['department_admin', 'student'],
        'department_admin' => ['student'],
        'student'          => []
    ];

    // Centralized route permission registry
    private static $routePermissions = [
        '/app/controllers/admin.php'               => ['super_admin', 'department_admin'],
        '/app/controllers/analytics.php'           => ['super_admin', 'department_admin'],
        '/app/controllers/students.php'            => ['super_admin', 'department_admin'],
        '/app/controllers/add_student.php'        => ['super_admin', 'department_admin'],
        '/app/controllers/verifications.php'      => ['super_admin', 'department_admin'],
        '/app/controllers/verification_detail.php' => ['super_admin', 'department_admin'],
        '/app/controllers/backup.php'              => ['super_admin'],
        '/app/controllers/file_management.php'     => ['department_admin'],
        '/app/controllers/student_profile.php'     => ['super_admin', 'department_admin'],
        '/app/controllers/bulk_import.php'         => ['super_admin', 'department_admin'],
        '/app/controllers/settings.php'            => ['super_admin', 'department_admin'],
        '/app/controllers/update_passwords.php'    => ['super_admin', 'department_admin'],
        '/app/controllers/download_document.php'   => ['super_admin', 'department_admin'],
        
        '/app/controllers/dashboard.php'           => ['super_admin', 'department_admin', 'student'],
        '/app/controllers/profile.php'             => ['student'],
        '/app/controllers/uploads.php'             => ['student'],
        '/app/controllers/student_settings.php'    => ['student'],
        '/app/controllers/upload_file.php'         => ['student'],
        '/app/controllers/data_subject_rights.php' => ['super_admin', 'department_admin', 'student'],
        '/app/controllers/secure_file_viewer.php'  => ['super_admin', 'department_admin', 'student'],
        '/app/controllers/notifications_mark_read.php' => ['super_admin', 'department_admin', 'student']
    ];

    /**
     * Inspect the current request against session state and route rules.
     */
    public static function checkAccess() {
        if (php_sapi_name() === 'cli') return;

        // Parse target script path
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Find permission requirements
        $requiredRoles = null;
        foreach (self::$routePermissions as $route => $roles) {
            if (stripos($requestUri, $route) !== false) {
                $requiredRoles = $roles;
                break;
            }
        }

        // Public route or assets - pass through
        if ($requiredRoles === null) {
            return;
        }

        // 1. Authenticated Check
        if (!isset($_SESSION['user_id'])) {
            self::handleUnauthorized("Session expired or unauthenticated.");
        }

        // Fetch authenticated user
        require_once __DIR__ . '/../models/User.php';
        $user = User::getById($_SESSION['user_id']);
        if (!$user) {
            self::handleUnauthorized("Invalid session user.");
        }

        // Determine active role
        $userRole = 'student';
        if ($user['is_admin'] == 1) {
            $userRole = ($user['matric_number'] === 'SUPER_ADMIN') ? 'super_admin' : 'department_admin';
        }

        // 2. RBAC Role Match Check
        $hasRole = false;
        foreach ($requiredRoles as $role) {
            if ($userRole === $role || in_array($role, self::$roleHierarchy[$userRole])) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            self::handleForbidden($user['id'], $requestUri, "Role mismatch: User role '{$userRole}' lacks permission.");
        }

        // 3. Granular Data Scope Check (Prevent IDOR / Cross-Department leaks)
        self::enforceDataScoping($user, $userRole);
    }

    /**
     * Enforce strict departmental boundaries on specific target documents or students
     */
    private static function enforceDataScoping($currentUser, $role) {
        if ($role === 'super_admin') return; // Super admin has global scope

        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Scoping case A: Document inspection / download / verification
        $docId = null;
        if (isset($_GET['doc_id'])) {
            $docId = (int)$_GET['doc_id'];
        } elseif (isset($_GET['id']) && (
            stripos($requestUri, 'secure_file_viewer.php') !== false ||
            stripos($requestUri, 'download_document.php') !== false
        )) {
            $docId = (int)$_GET['id'];
        }

        if ($docId !== null) {
            require_once __DIR__ . '/../models/Document.php';
            $doc = Document::getById($docId);
            
            if ($doc) {
                if ($role === 'student') {
                    // Students can only view their own documents
                    if ($doc['user_id'] != $currentUser['id']) {
                        self::handleForbidden($currentUser['id'], $_SERVER['REQUEST_URI'], "IDOR: Student tried accessing another user's document.");
                    }
                } elseif ($role === 'department_admin') {
                    // Admins can only view their department's documents
                    $owner = User::getById($doc['user_id']);
                    if ($owner['department_id'] !== $currentUser['department_id']) {
                        self::handleForbidden($currentUser['id'], $_SERVER['REQUEST_URI'], "IDOR: Department Admin tried accessing student record from another department.");
                    }
                }
            }
        }

        // Scoping case B: Student Profile Inspection (student_profile.php?id=...)
        $studentId = null;
        if (stripos($requestUri, 'student_profile.php') !== false && isset($_GET['id'])) {
            $studentId = (int)$_GET['id'];
        }

        if ($studentId !== null) {
            $targetStudent = User::getById($studentId);
            if ($targetStudent && $role === 'department_admin') {
                if ($targetStudent['department_id'] !== $currentUser['department_id']) {
                    self::handleForbidden($currentUser['id'], $_SERVER['REQUEST_URI'], "IDOR: Department Admin tried inspecting cross-department student profile.");
                }
            }
        }
    }

    private static function handleUnauthorized($reason) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['redirect' => '/public/index.php', 'error' => $reason]);
            exit();
        }
        header("Location: /public/index.php?error=" . urlencode($reason));
        exit();
    }

    private static function handleForbidden($userId, $uri, $reason) {
        require_once __DIR__ . '/../utils/SecurityUtils.php';
        SecurityUtils::logSecurityEvent('unauthorized_access_attempt', [
            'user_id' => $userId,
            'target_uri' => $uri,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        require_once __DIR__ . '/../models/User.php';
        $dashboard_url = User::isAdmin($userId) ? '/app/controllers/admin.php' : '/app/controllers/dashboard.php';

        http_response_code(403);
        
        $tracking_code = 'SEC-' . strtoupper(bin2hex(random_bytes(4)));

        if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'html' => '
                <div style="display:flex;align-items:center;justify-content:center;height:80vh;width:100%;">
                    <div class="glass-card" style="background:rgba(255,255,255,0.85);border:1px solid rgba(0,0,0,0.08);padding:40px;border-radius:16px;text-align:center;max-width:500px;box-shadow:0 10px 30px rgba(0,0,0,0.06);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);">
                        <div style="font-size:4rem;margin-bottom:20px;">🛡️</div>
                        <h1 style="color:#FF3B30;font-size:24px;margin-bottom:10px;font-family:\'Poppins\',sans-serif;font-weight:700;">Access Denied</h1>
                        <p style="color:#4a5568;line-height:1.6;margin-bottom:12px;font-family:\'Poppins\',sans-serif;font-size:14px;">You do not have the required privileges to access this department or resource. This event has been logged for security review.</p>
                        <p style="color:#718096;font-size:0.85rem;margin-bottom:24px;font-family:monospace;">Tracking Code: ' . $tracking_code . '</p>
                        <a href="' . $dashboard_url . '" data-no-spa="true" style="background:#01017A;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:500;display:inline-block;transition:all 0.2s;font-family:\'Poppins\',sans-serif;box-shadow:0 4px 10px rgba(1,1,122,0.25);">Return Dashboard</a>
                    </div>
                </div>',
                'title' => 'Forbidden Access'
            ]);
            exit();
        }
        
        // Serve a beautiful, standalone premium glassmorphic 403 page
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - DFMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #01017A;
            --accent: #FF3B30;
            --text-light: #f3f4f6;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: \'Poppins\', sans-serif;
            background: radial-gradient(circle at center, #180d1b 0%, #05020c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: var(--text-light);
        }

        .error-container {
            position: relative;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 48px 40px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-sizing: border-box;
            z-index: 10;
            animation: containerFadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes containerFadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon-wrapper {
            position: relative;
            width: 90px;
            height: 90px;
            margin: 0 auto 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 59, 48, 0.08);
            border-radius: 50%;
            border: 1px solid rgba(255, 59, 48, 0.2);
            box-shadow: 0 0 20px rgba(255, 59, 48, 0.1);
        }

        .security-icon {
            font-size: 40px;
            filter: drop-shadow(0 0 10px rgba(255, 59, 48, 0.3));
        }

        h1 {
            color: #ffffff;
            font-size: 26px;
            font-weight: 700;
            margin: 0 0 12px 0;
            letter-spacing: -0.5px;
        }

        p {
            font-size: 14px;
            line-height: 1.6;
            color: #a0aec0;
            margin: 0 0 20px 0;
        }

        .tracking-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-family: monospace;
            color: #718096;
            margin-bottom: 32px;
            display: inline-block;
        }

        .tracking-box span {
            color: var(--accent);
            font-weight: 600;
        }

        .btn-home {
            display: inline-block;
            background: linear-gradient(135deg, #01017A 0%, #0a0a9c 100%);
            color: #ffffff;
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border: none;
            box-shadow: 0 4px 14px rgba(1, 1, 122, 0.4);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(1, 1, 122, 0.6);
            background: linear-gradient(135deg, #020296 0%, #1212bd 100%);
        }

        .btn-home:active {
            transform: translateY(0);
        }

        /* Ambient glows */
        .glow {
            position: absolute;
            border-radius: 50%;
            z-index: 1;
            filter: blur(60px);
            opacity: 0.15;
        }

        .glow-1 { width: 350px; height: 350px; top: -100px; left: -100px; background: #FF3B30; }
        .glow-2 { width: 300px; height: 300px; bottom: -80px; right: -50px; background: #01017A; }
    </style>
</head>
<body>
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>

    <div class="error-container">
        <div class="icon-wrapper">
            <span class="security-icon">🛡️</span>
        </div>
        <h1>Access Denied</h1>
        <p>
            You do not have the required privileges to access this department or resource. 
            This unauthorized access attempt has been logged for security review.
        </p>
        <div class="tracking-box">
            Tracking Code: <span>' . $tracking_code . '</span>
        </div>
        <div>
            <a href="' . $dashboard_url . '" class="btn-home">Return Dashboard</a>
        </div>
    </div>
</body>
</html>';
        exit();
    }
}
