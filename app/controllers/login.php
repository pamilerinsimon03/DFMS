<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    requireCSRFToken();
    
    // Include security utilities
    require_once __DIR__ . '/../utils/SecurityUtils.php';
    
    $matric_number = SecurityUtils::validateInput($_POST['matric_number'], 'matric', 50);
    $password = $_POST['password'];
    
    // Check rate limiting (IP-based fallback log)
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!SecurityUtils::checkRateLimit($clientIP)) {
        SecurityUtils::logSecurityEvent('rate_limit_exceeded', ['ip' => $clientIP]);
    }
    
    // Validate input
    if (!$matric_number) {
        SecurityUtils::logSecurityEvent('invalid_input', ['field' => 'matric_number', 'ip' => $clientIP]);
        $_SESSION['error'] = "Invalid matric number format. Please check your input and try again.";
        header("Location: /public/index.php");
        exit();
    }

    $user = User::getByMatricNumber($matric_number);

    // Check persistent database-backed account lockout
    if ($user && !empty($user['account_locked_until']) && strtotime($user['account_locked_until']) > time()) {
        $remaining = ceil((strtotime($user['account_locked_until']) - time()) / 60);
        $_SESSION['error'] = "This account is temporarily locked due to too many failed login attempts. Please try again in {$remaining} minutes.";
        header("Location: /public/index.php");
        exit();
    }

    if ($user && password_verify($password, $user['password'])) {
        // Log successful login and reset failed login attempts
        SecurityUtils::logSecurityEvent('successful_login', ['user_id' => $user['id'], 'matric' => $matric_number]);
        User::resetFailedLoginAttempts($user['id']);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_matric'] = $user['matric_number'];
        $_SESSION['user_department_id'] = $user['department_id'];
        
        // Regenerate session ID for security
        session_regenerate_id(true);

        if ($user['is_admin'] == 1) {
            header("Location: /app/controllers/admin.php");
        } else {
            header("Location: /app/controllers/dashboard.php");
        }
        exit();
    } else {
        // Log failed login attempt
        SecurityUtils::logSecurityEvent('failed_login', ['matric' => $matric_number, 'ip' => $clientIP]);
        
        if ($user) {
            // Increment failed attempts and trigger lockout if they reach 5
            User::incrementFailedLoginAttempts($matric_number);
            
            $updated_user = User::getById($user['id']);
            if ($updated_user && !empty($updated_user['account_locked_until']) && strtotime($updated_user['account_locked_until']) > time()) {
                $_SESSION['error'] = "Too many failed login attempts. Your account has been locked for 15 minutes.";
            } else {
                $attempts_left = 5 - ($updated_user['failed_login_attempts'] ?? 0);
                $_SESSION['error'] = "Incorrect password. You have {$attempts_left} attempts remaining before account lockout.";
            }
        } else {
            $_SESSION['error'] = "Matric number not found. Please check your matric number and try again.";
        }
        
        header("Location: /public/index.php");
        exit();
    }
} else {
    header("Location: /public/index.php");
    exit();
}