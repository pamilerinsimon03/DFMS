<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';


$user_id = $_SESSION['user_id'];
$user = User::getById($user_id);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    requireCSRFToken();
    
    require_once __DIR__ . '/../utils/SecurityUtils.php';
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    }
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if (User::updatePassword($user_id, $hashed_password)) {
            SecurityUtils::logSecurityEvent('password_changed', [
                'user_id' => $user_id,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $success = "Password changed successfully.";
            $user = User::getById($user_id);
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}

$page_title = 'Settings';
$active_page = 'settings';
$css_files = ['dashboard.css', 'student_settings.css'];
$js_files = ['dashboard.js', 'student_settings.js'];

if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    ob_start();
    require_once __DIR__ . '/../views/student_settings.php';
    $html = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html, 
        'title' => $page_title,
        'css_files' => $css_files
    ]);
    exit();
}

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/student_settings.php';
require_once __DIR__ . '/../views/partials/footer.php';
