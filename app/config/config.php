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

if (php_sapi_name() === 'cli' || (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost')) {
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

define('DB_HOST', $db_host);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_NAME', $db_name);

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
        $bypass_token = 'dfms_secure_bypass_key_2026';
        $bypass_cookie_name = 'dfms_maintenance_bypass';
        $salt = 'dfms_salt_secure_2026';

        // 1. Check if bypass token is provided in URL
        if (isset($_GET['bypass_maintenance']) && $_GET['bypass_maintenance'] === $bypass_token) {
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
        } elseif (isset($_COOKIE[$bypass_cookie_name])) {
            $expected_value = hash_hmac('sha256', $bypass_token, $salt);
            if (hash_equals($expected_value, $_COOKIE[$bypass_cookie_name])) {
                $_SESSION['maintenance_bypass'] = true; // Sync back to session
                $has_bypass = true;
            }
        }

        // 3. Determine if current logged-in user is Super Admin
        $is_super_admin = false;
        if (isset($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
            $db_res = $conn->query("SELECT matric_number FROM users WHERE id = $userId");
            if ($db_res && $db_row = $db_res->fetch_assoc()) {
                if ($db_row['matric_number'] === 'SUPER_ADMIN') {
                    $is_super_admin = true;
                }
            }
        }
        
        // 4. If not Super Admin and no bypass, enforce maintenance redirect
        if (php_sapi_name() !== 'cli' && !$is_super_admin && !$has_bypass) {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            // If the URI is not targeting maintenance.php, redirect to maintenance page
            $allowed_uris = ['maintenance.php'];
            $is_allowed = false;
            foreach ($allowed_uris as $uri) {
                if (stripos($request_uri, $uri) !== false) {
                    $is_allowed = true;
                    break;
                }
            }
            // Allow css and images assets so the maintenance screen renders beautifully
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
?>