<?php
// Set session cookie to be valid for the entire domain
session_set_cookie_params(['path' => '/']);
session_start();

if ($_SERVER['SERVER_NAME'] === 'localhost') {
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

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>