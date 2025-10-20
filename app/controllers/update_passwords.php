<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$message = '';
$message_type = '';

// Handle password update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_passwords') {
    // Validate CSRF token
    requireCSRFToken();
    
    $result = User::updateAllStudentPasswords();
    
    if ($result['success']) {
        $message = $result['message'];
        $message_type = 'success';
    } else {
        $message = $result['message'];
        $message_type = 'error';
    }
}

$page_title = 'Update Student Passwords';
$active_page = 'update_passwords';
$css_files = ['admin.css', 'update_passwords.css'];
$js_files = ['admin.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/update_passwords.php';
require_once __DIR__ . '/../views/partials/footer.php';
