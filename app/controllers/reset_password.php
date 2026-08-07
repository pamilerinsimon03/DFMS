<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PasswordReset.php';
require_once __DIR__ . '/../services/MailService.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';

$mode = isset($_GET['token']) ? 'reset' : 'request';
$error = '';
$success = '';

if ($mode === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $matric_number = SecurityUtils::validateInput($_POST['matric_number'] ?? '', 'matric');
    $user = User::getByMatricNumber($matric_number);

    if ($user) {
        $raw_token = bin2hex(random_bytes(32));
        $hashed_token = hash('sha256', $raw_token);
        
        PasswordReset::create($user['id'], $hashed_token);
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $reset_link = $protocol . $host . "/app/controllers/reset_password.php?token=" . $raw_token;

        $subject = "Password Reset Request - DFMS Portal";
        $html_content = "
            <p>Dear " . htmlspecialchars($user['full_name']) . ",</p>
            <p>You requested a password reset for your DFMS portal account.</p>
            <p>Click the link below to set a new password. This link will expire in 15 minutes:</p>
            <p><a href='{$reset_link}' style='padding: 10px 20px; background: #01017A; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
            <p>If you did not request this, please ignore this email.</p>
        ";

        if (!empty($user['email'])) {
            MailService::send($user['email'], $subject, $html_content);
        }
        
        SecurityUtils::logSecurityEvent('password_reset_requested', ['user_id' => $user['id']]);
        $success = "If your account exists, a password reset link has been sent to your registered email address.";
    } else {
        $error = "Invalid matric number.";
    }
} elseif ($mode === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $raw_token = SecurityUtils::validateInput($_GET['token'] ?? '', 'string');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $reset = PasswordReset::getByToken($raw_token);

    if ($reset && strtotime($reset['expires_at']) > time()) {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            User::updatePassword($reset['user_id'], $new_hashed_password);
            PasswordReset::delete($raw_token);
            
            SecurityUtils::logSecurityEvent('password_reset_completed', ['user_id' => $reset['user_id']]);
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
