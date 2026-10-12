<?php
/**
 * DFMS Synchronizer Token Pattern (CSRF Protection)
 * 
 * Enforces cryptographic synchronizer token on all state-changing requests (POST, PUT, DELETE, PATCH).
 * Accepts token via:
 *   1. HTTP Request Header: X-CSRF-TOKEN
 *   2. POST Form Field: csrf_token
 *   3. JSON Payload Field: csrf_token
 * Compares using constant-time hash_equals.
 */

class CSRFProtection
{
    /**
     * Retrieve or generate the session-bound CSRF token
     *
     * @return string 64-character hexadecimal token
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['csrf_token'];
    }

    /**
     * Validate a token against the session-bound token in constant time
     *
     * @param string|null $token
     * @return bool
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals((string)$_SESSION['csrf_token'], $token);
    }

    /**
     * Extract token from HTTP headers, POST parameters, or JSON body
     *
     * @return string|null
     */
    public static function extractToken(): ?string
    {
        // 1. Check X-CSRF-TOKEN HTTP header
        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // 2. Check form field in $_POST
        if (!empty($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            return (string)$_POST['csrf_token'];
        }

        // 3. Check JSON request payload
        $raw = @file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json) && !empty($json['csrf_token']) && is_string($json['csrf_token'])) {
                return (string)$json['csrf_token'];
            }
        }

        return null;
    }

    /**
     * Enforce CSRF protection on current request
     *
     * Safe methods (GET, HEAD, OPTIONS) bypass.
     * State-changing methods (POST, PUT, DELETE, PATCH) are verified or terminated with HTTP 403.
     */
    public static function enforce(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Safe methods bypass
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = self::extractToken();
        if (!$token || !self::validateToken($token)) {
            require_once __DIR__ . '/../utils/SecurityUtils.php';
            SecurityUtils::logSecurityEvent('csrf_validation_failed', [
                'method' => $method,
                'route'  => $_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? ''),
                'ip'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ], null, null, null, 'blocked');

            http_response_code(403);
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error'  => 'CSRF token validation failed.',
                    'status' => 403
                ]);
                exit();
            } else {
                die('Forbidden: CSRF token validation failed.');
            }
        }
    }
}
