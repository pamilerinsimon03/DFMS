<?php
// Prevent session errors in CLI mode
if (php_sapi_name() !== 'cli') {
    // Set secure session parameters BEFORE starting session
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

// Check if running from CLI or on a local web server
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

// For compatibility with the reconcile script that expects constants
define('DB_HOST', $db_host);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_NAME', $db_name);

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// CSRF Protection Functions
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

// Session security is now configured above before session_start()

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>