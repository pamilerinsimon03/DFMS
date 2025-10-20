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
    
    // Check rate limiting
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!SecurityUtils::checkRateLimit($clientIP)) {
        SecurityUtils::logSecurityEvent('rate_limit_exceeded', ['ip' => $clientIP]);
        $_SESSION['error'] = "Too many login attempts. Please wait 15 minutes before trying again.";
        header("Location: /public/index.php");
        exit();
    }
    
    // Validate input
    if (!$matric_number) {
        SecurityUtils::logSecurityEvent('invalid_input', ['field' => 'matric_number', 'ip' => $clientIP]);
        $_SESSION['error'] = "Invalid matric number format. Please check your input and try again.";
        header("Location: /public/index.php");
        exit();
    }

    $user = User::getByMatricNumber($matric_number);

    if ($user && password_verify($password, $user['password'])) {
        // Log successful login
        SecurityUtils::logSecurityEvent('successful_login', ['user_id' => $user['id'], 'matric' => $matric_number]);
        
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
        
        // Provide more specific error message
        if ($user) {
            $_SESSION['error'] = "Incorrect password. Please check your password and try again.";
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