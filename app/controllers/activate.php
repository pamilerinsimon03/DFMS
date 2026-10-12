<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$message = '';
$message_type = '';
$user_record = null;

if (empty($token)) {
    $message = 'Activation token is missing. Please check the link sent to your email.';
    $message_type = 'error';
} else {
    global $conn;
    $stmt = $conn->prepare("SELECT id, matric_number, full_name, email, is_activated FROM users WHERE activation_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user_record = $stmt->get_result()->fetch_assoc();

    if (!$user_record) {
        $message = 'Invalid or expired activation link.';
        $message_type = 'error';
    } elseif ((int)$user_record['is_activated'] === 1) {
        $message = 'Your account is already activated. Please proceed to login.';
        $message_type = 'info';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_record && (int)$user_record['is_activated'] === 0) {
    requireCSRFToken();

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match. Please re-enter your password.';
        $message_type = 'error';
    } else {
        $res = User::activateAccountByToken($token, null, $new_password);
        if ($res['success']) {
            SecurityUtils::logSecurityEvent('account_activated_success', [
                'user_id' => $user_record['id'],
                'matric_number' => $user_record['matric_number']
            ]);

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['success'] = 'Your account has been activated successfully! You can now log in.';
            header("Location: /public/index.php");
            exit();
        } else {
            $message = $res['message'];
            $message_type = 'error';
        }
    }
}

$page_title = 'Activate Student Account';
require_once __DIR__ . '/../views/activate.php';
