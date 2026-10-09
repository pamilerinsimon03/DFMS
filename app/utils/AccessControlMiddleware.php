<?php
/**
 * DFMS Centralized Access Control Layer (ACL) Middleware
 * 
 * Production-grade security interceptor enforcing:
 * 1. Fail-closed route authorization based on permissions.php
 * 2. Role hierarchy inheritance
 * 3. Exact script name matching (preventing substring bypasses)
 * 4. Two-stage object scoping inspecting GET, POST, and REQUEST parameters (UUID and ID)
 */

require_once __DIR__ . '/AccessScope.php';
require_once __DIR__ . '/SecurityUtils.php';

class AccessControlMiddleware
{
    private static $permissions = null;

    private static function loadPermissions(): array
    {
        if (self::$permissions === null) {
            $configPath = __DIR__ . '/../config/permissions.php';
            if (file_exists($configPath)) {
                self::$permissions = require $configPath;
            } else {
                self::$permissions = [
                    'hierarchy' => [
                        'super_admin'      => ['faculty_admin', 'department_admin', 'student'],
                        'faculty_admin'    => ['department_admin', 'student'],
                        'department_admin' => ['student'],
                        'student'          => []
                    ],
                    'public_allowlist' => ['login.php', 'reset_password.php', 'logout.php', 'activate.php', 'maintenance.php'],
                    'routes' => []
                ];
            }
        }
        return self::$permissions;
    }

    /**
     * Inspect request against session state and route permission matrix
     */
    public static function checkAccess(): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $scriptName = basename($requestUri);

        // Assets or non-controller routes pass through
        if (!str_contains($requestUri, '/app/controllers/')) {
            return;
        }

        $perms = self::loadPermissions();
        $publicAllowlist = $perms['public_allowlist'] ?? [];

        // Exact match for public allowlist
        if (in_array($scriptName, $publicAllowlist, true)) {
            return;
        }

        // 1. Fail-Closed Route Check: Must be explicitly registered in permissions.php
        $routes = $perms['routes'] ?? [];
        if (!isset($routes[$scriptName])) {
            self::handleForbidden(
                $_SESSION['user_id'] ?? null,
                $requestUri,
                "Fail-closed policy: Route '{$scriptName}' is not registered in the access control matrix."
            );
        }

        // 2. Authentication Check
        if (empty($_SESSION['user_id'])) {
            self::handleUnauthorized("Session expired or unauthenticated.");
        }

        require_once __DIR__ . '/../models/User.php';
        $user = User::getById((int)$_SESSION['user_id']);
        if (!$user) {
            self::handleUnauthorized("Invalid session user.");
        }

        // 3. Role Hierarchy & Route Permission Check
        $userRole = AccessScope::resolveRole($user);
        $allowedRoles = $routes[$scriptName];
        $hierarchy = $perms['hierarchy'] ?? [];

        $isAuthorized = false;
        foreach ($allowedRoles as $allowedRole) {
            if ($userRole === $allowedRole) {
                $isAuthorized = true;
                break;
            }
            // Check if userRole inherits allowedRole down the hierarchy
            if (isset($hierarchy[$userRole]) && in_array($allowedRole, $hierarchy[$userRole], true)) {
                $isAuthorized = true;
                break;
            }
        }

        if (!$isAuthorized) {
            self::handleForbidden(
                $user['id'],
                $requestUri,
                "Role mismatch: User role '{$userRole}' is not authorized for route '{$scriptName}'."
            );
        }

        // 4. Object-Level Scoping: Check $_GET, $_POST, and $_REQUEST
        self::enforceObjectScoping($user, $userRole, $scriptName);
    }

    /**
     * Enforce query-level and object-level scoping on submitted identifiers
     */
    private static function enforceObjectScoping(array $user, string $role, string $scriptName): void
    {
        if ($role === 'super_admin') {
            return; // Super admin has universal scope
        }

        // Check document identifier across GET, POST, REQUEST
        $docIdRaw = $_POST['doc_id'] 
            ?? $_GET['doc_id'] 
            ?? $_POST['id'] 
            ?? $_GET['id'] 
            ?? $_REQUEST['doc_id'] 
            ?? null;

        if ($docIdRaw !== null && $docIdRaw !== '') {
            require_once __DIR__ . '/../models/Document.php';
            $doc = null;
            if (is_numeric($docIdRaw)) {
                $doc = Document::getById((int)$docIdRaw);
            } else {
                $doc = Document::findByPublicId((string)$docIdRaw);
            }

            if ($doc) {
                if (!AccessScope::isDocumentInScope($user, $doc)) {
                    self::handleForbidden(
                        $user['id'],
                        $_SERVER['REQUEST_URI'],
                        "IDOR: Out-of-scope document access attempt on Doc ID {$doc['id']}."
                    );
                }
            }
        }

        // Check user/student identifier across GET, POST, REQUEST
        $targetUserIdRaw = $_GET['student_id'] 
            ?? $_POST['student_id'] 
            ?? (in_array($scriptName, ['student_profile.php', 'student_profile_update.php'], true) ? ($_GET['id'] ?? $_POST['id'] ?? null) : null);

        if ($targetUserIdRaw !== null && $targetUserIdRaw !== '') {
            require_once __DIR__ . '/../models/User.php';
            $targetUser = null;
            if (is_numeric($targetUserIdRaw)) {
                $targetUser = User::getById((int)$targetUserIdRaw);
            } else {
                $targetUser = User::findByPublicId((string)$targetUserIdRaw);
            }

            if ($targetUser) {
                if (!AccessScope::isUserInScope($user, $targetUser)) {
                    self::handleForbidden(
                        $user['id'],
                        $_SERVER['REQUEST_URI'],
                        "IDOR: Out-of-scope student profile access attempt on User ID {$targetUser['id']}."
                    );
                }
            }
        }
    }

    private static function handleUnauthorized(string $reason): void
    {
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

    private static function handleForbidden(?int $userId, string $uri, string $reason): void
    {
        SecurityUtils::logSecurityEvent('unauthorized_access_attempt', [
            'user_id' => $userId,
            'target_uri' => $uri,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        http_response_code(403);
        $trackingCode = 'SEC-' . strtoupper(bin2hex(random_bytes(4)));
        $dashboardUrl = ($userId && User::isAdmin($userId)) ? '/app/controllers/admin.php' : '/app/controllers/dashboard.php';

        if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'html' => '<div style="text-align:center;padding:40px;"><h2>403 Forbidden</h2><p>' . htmlspecialchars($reason) . '</p><p>Tracking Code: ' . $trackingCode . '</p></div>',
                'title' => 'Forbidden Access'
            ]);
            exit();
        }

        echo '<!DOCTYPE html><html><head><title>403 Forbidden - DFMS</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;">'
            . '<h1 style="color:#d9534f;">403 Forbidden</h1>'
            . '<p>' . htmlspecialchars($reason) . '</p>'
            . '<p>Tracking Code: <strong>' . $trackingCode . '</strong></p>'
            . '<p><a href="' . $dashboardUrl . '">Return to Dashboard</a></p>'
            . '</body></html>';
        exit();
    }
}
