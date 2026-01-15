<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PasswordReset.php';

$mode = isset($_GET['token']) ? 'reset' : 'request';
$error = '';
$success = '';

if ($mode === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $matric_number = $_POST['matric_number'];
    $user = User::getByMatricNumber($matric_number);

    if ($user) {
        $token = bin2hex(random_bytes(32));
        PasswordReset::create($user['id'], $token);
        error_log("Password reset requested for user {$user['id']}: /app/controllers/reset_password.php?token=$token");
        $success = "Password reset link has been sent to your registered email address.";
    } else {
        $error = "Invalid matric number.";
    }
} elseif ($mode === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_GET['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $reset = PasswordReset::getByToken($token);

    if ($reset && strtotime($reset['expires_at']) > time()) {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            User::updatePassword($reset['user_id'], $new_hashed_password);
            PasswordReset::delete($token);
            $success = "Password reset successfully. <a href='/public/index.php'>Login</a>";
        }
    } else {
        $error = "Invalid or expired reset token.";
    }
}

$page_title = 'Reset Password';

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/reset_password.php';
require_once __DIR__ . '/../views/partials/footer.php';