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

        http_response_code(403);
        
        $tracking_code = 'SEC-' . strtoupper(bin2hex(random_bytes(4)));

        if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'html' => '
                <div style="display:flex;align-items:center;justify-content:center;height:80vh;">
                    <div class="glass-card" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);padding:40px;border-radius:16px;text-align:center;max-width:500px;backdrop-filter:blur(20px);">
                        <div style="font-size:4rem;margin-bottom:20px;">🛡️</div>
                        <h1 style="color:#FF3B30;font-size:24px;margin-bottom:10px;">Access Denied</h1>
                        <p style="color:#a0aec0;line-height:1.6;margin-bottom:12px;">You do not have the required privileges to access this department or resource. This event has been logged for security review.</p>
                        <p style="color:#718096;font-size:0.85rem;margin-bottom:24px;font-family:monospace;">Tracking Code: ' . $tracking_code . '</p>
                        <a href="/app/controllers/login.php" style="background:#01017A;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:500;display:inline-block;transition:background 0.2s;">Return Dashboard</a>
                    </div>
                </div>',
                'title' => 'Forbidden Access'
            ]);
            exit();
        }
        
        // Load custom elegant glassmorphic 403 view
        require_once __DIR__ . '/../views/partials/header.php';
        echo '<div class="main-content" style="display:flex;align-items:center;justify-content:center;height:80vh;">
                <div class="glass-card" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);padding:40px;border-radius:16px;text-align:center;max-width:500px;backdrop-filter:blur(20px);">
                    <div style="font-size:4rem;margin-bottom:20px;">🛡️</div>
                    <h1 style="color:#FF3B30;font-size:24px;margin-bottom:10px;">Access Denied</h1>
                    <p style="color:#a0aec0;line-height:1.6;margin-bottom:12px;">You do not have the required privileges to access this department or resource. This event has been logged for security review.</p>
                    <p style="color:#718096;font-size:0.85rem;margin-bottom:24px;font-family:monospace;">Tracking Code: ' . $tracking_code . '</p>
                    <a href="/app/controllers/login.php" style="background:#01017A;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:500;display:inline-block;transition:background 0.2s;">Return Dashboard</a>
                </div>
              </div>';
        require_once __DIR__ . '/../views/partials/footer.php';
        exit();
    }
}
