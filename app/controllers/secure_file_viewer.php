<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../services/FileDeliveryService.php';

$docIdRaw = $_GET['id'] ?? $_GET['doc_id'] ?? null;
if (!$docIdRaw) {
    http_response_code(400);
    exit('Document ID required');
}

$currentUser = isset($_SESSION['user_id']) ? User::getById((int)$_SESSION['user_id']) : null;
if (!$currentUser) {
    http_response_code(401);
    exit('Authentication required');
}

FileDeliveryService::deliver($docIdRaw, $currentUser, 'inline');
