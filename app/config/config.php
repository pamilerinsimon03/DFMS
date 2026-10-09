<?php
if (php_sapi_name() !== 'cli') {
    date_default_timezone_set('Africa/Lagos');
    session_set_cookie_params([
        'lifetime' => 3600, // 1 hour
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']), // Use secure flag if HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

if (
    php_sapi_name() === 'cli'
    || (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'], true))
    || (isset($_SERVER['HTTP_HOST']) && in_array(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']), ['localhost', '127.0.0.1', '::1'], true))
) {
    // Local machine
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'dfms_local';
} else {
    // Live server - use environment variables
    $db_host = $_ENV['DB_HOST'] ?? 'localhost';
    $db_user = $_ENV['DB_USER'] ?? 'dfms_user';
    $db_pass = $_ENV['DB_PASS'] ?? '';
    $db_name = $_ENV['DB_NAME'] ?? 'dfms_prod';
}

if (!defined('DB_HOST')) define('DB_HOST', $db_host);
if (!defined('DB_USER')) define('DB_USER', $db_user);
if (!defined('DB_PASS')) define('DB_PASS', $db_pass);
if (!defined('DB_NAME')) define('DB_NAME', $db_name);

// Storage and Log Isolation Configuration
require_once __DIR__ . '/../utils/StoragePathResolver.php';

if (!defined('STORAGE_ROOT')) {
    define('STORAGE_ROOT', StoragePathResolver::getStorageRoot());
}

if (!defined('LOG_ROOT')) {
    define('LOG_ROOT', StoragePathResolver::getLogRoot());
}

if (!defined('STORAGE_SALT')) {
    define('STORAGE_SALT', StoragePathResolver::getStorageSalt());
}

// Storage isolation enforcement: STORAGE_ROOT must not be inside document root
$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;
if (!$docRoot && php_sapi_name() !== 'cli') {
    $docRoot = realpath(__DIR__ . '/../..');
}
if ($docRoot) {
    $normDocRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
    $storageRealPath = realpath(STORAGE_ROOT) ?: STORAGE_ROOT;
    $normStorageRoot = rtrim(str_replace('\\', '/', $storageRealPath), '/');

    if (str_starts_with($normStorageRoot, $normDocRoot . '/') || $normStorageRoot === $normDocRoot) {
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        $alert = "CRITICAL SECURITY WARNING: STORAGE_ROOT resolves inside web document root ({$docRoot}).";
        if ($appEnv === 'development' || php_sapi_name() === 'cli') {
            error_log($alert);
        } else {
            http_response_code(500);
            die("Critical Security Configuration Error: Storage root must reside outside web document root.");
        }
    }
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Intercept requests if Maintenance Mode is active in settings.json
$settings_file = __DIR__ . '/settings.json';
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    if (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] === true) {
        $bypass_token = getenv('MAINTENANCE_BYPASS_TOKEN') ?: null;
        $bypass_cookie_name = getenv('MAINTENANCE_BYPASS_COOKIE') ?: 'dfms_maintenance_bypass';
        $salt = getenv('MAINTENANCE_BYPASS_SALT') ?: null;

        // 1. Check if bypass token is provided in URL
        if ($bypass_token && $salt && isset($_GET['bypass_maintenance']) && hash_equals($bypass_token, $_GET['bypass_maintenance'])) {
            if (php_sapi_name() !== 'cli') {
                $_SESSION['maintenance_bypass'] = true;
                $cookie_value = hash_hmac('sha256', $bypass_token, $salt);
                setcookie($bypass_cookie_name, $cookie_value, time() + 86400, '/', '', isset($_SERVER['HTTPS']), true);
                header("Location: /app/controllers/login.php");
                exit();
            }
        }

        // 2. Verify if current request has a valid session or cookie bypass
        $has_bypass = false;
        if (isset($_SESSION['maintenance_bypass']) && $_SESSION['maintenance_bypass'] === true) {
            $has_bypass = true;
        } elseif ($bypass_token && $salt && isset($_COOKIE[$bypass_cookie_name])) {
            $expected_value = hash_hmac('sha256', $bypass_token, $salt);
            if (hash_equals($expected_value, $_COOKIE[$bypass_cookie_name])) {
                $_SESSION['maintenance_bypass'] = true; // Sync back to session
                $has_bypass = true;
            }
        }

        // 3. Determine if current logged-in user is Super Admin (Prepared Statement)
        $is_super_admin = false;
        if (isset($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT matric_number FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $db_res = $stmt->get_result();
                if ($db_res && $db_row = $db_res->fetch_assoc()) {
                    if ($db_row['matric_number'] === 'SUPER_ADMIN') {
                        $is_super_admin = true;
                    }
                }
                $stmt->close();
            }
        }

        // 4. If not Super Admin and no bypass, enforce maintenance redirect
        if (php_sapi_name() !== 'cli' && !$is_super_admin && !$has_bypass) {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            $allowed_uris = ['maintenance.php'];
            $is_allowed = false;
            foreach ($allowed_uris as $uri) {
                if (stripos($request_uri, $uri) !== false) {
                    $is_allowed = true;
                    break;
                }
            }
            if (stripos($request_uri, '/app/src/') !== false || stripos($request_uri, '/assets/') !== false) {
                $is_allowed = true;
            }

            if (!$is_allowed) {
                if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'redirect' => '/app/views/maintenance.php'
                    ]);
                    exit();
                }
                header("Location: /app/views/maintenance.php");
                exit();
            }
        }
    }
}

// Centralized Access Control Layer (ACL) Route Protection
require_once __DIR__ . '/../utils/AccessControlMiddleware.php';
AccessControlMiddleware::checkAccess();
?>
