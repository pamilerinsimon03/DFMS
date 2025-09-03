<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Notification.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
$ok = Notification::markAllRead((int)$_SESSION['user_id']);
echo json_encode(['success' => $ok]);


