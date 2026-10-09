<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';

// Method gate: Logout must be a POST request to prevent CSRF via GET links
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    die('Method Not Allowed: Logout must be performed via POST.');
}

// CSRF validation is globally enforced on POST by config.php

$userId = $_SESSION['user_id'] ?? null;
SecurityUtils::logSecurityEvent('user_logout', ['user_id' => $userId]);

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => '/public/index.php']);
    exit();
}

header("Location: /public/index.php");
exit();