<?php
// Prevent session errors in CLI mode
if (php_sapi_name() !== 'cli') {
    // Set session cookie to be valid for the entire domain
    session_set_cookie_params(['path' => '/']);
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
    // Live server
    $db_host = 'localhost';
    $db_user = 'otunbaaw_dfms';
    $db_pass = 'otunbaaw_dfms';
    $db_name = 'otunbaaw_dfms';
}

// For compatibility with the reconcile script that expects constants
define('DB_HOST', $db_host);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_NAME', $db_name);

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>