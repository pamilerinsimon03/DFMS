<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = User::getById($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if (User::updatePassword($user_id, $new_hashed_password)) {
            $success = "Password updated successfully.";
        } else {
            $error = "Failed to update password.";
        }
    }
}

$page_title = 'Settings';
$active_page = 'settings';

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/settings.php';
require_once __DIR__ . '/../views/partials/footer.php';