<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$idRaw = $_GET['id'] ?? null;
if (!$idRaw) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
    exit();
}

$current_user = User::getById((int)$_SESSION['user_id']);
if (!$current_user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$doc = Document::getByIdScoped($idRaw, $current_user);
if (!$doc) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Document not found or access denied']);
    exit();
}

// Success - return data
echo json_encode([
    'success' => true,
    'data' => [
        'id' => $doc['public_id'] ?? $doc['id'],
        'public_id' => $doc['public_id'] ?? $doc['id'],
        'document_name' => $doc['document_name'],
        'status' => $doc['status'],
        'rejection_reason' => $doc['rejection_reason'],
        'file_name' => basename($doc['file_path']),
        'upload_date' => $doc['created_at'] ?? $doc['upload_date'] ?? null,
        'file_scan_status' => $doc['file_scan_status'] ?? 'pending',
        'file_scan_date' => (!empty($doc['file_scan_date'])) ? date('M d, Y H:i', strtotime($doc['file_scan_date'])) : 'N/A',
        'level' => (!empty($doc['level'])) ? $doc['level'] . 'L' : 'N/A',
        'semester' => $doc['semester'] ?? 'N/A',
        'program_type' => $doc['program_type'] ?? 'N/A',
        'mode' => $doc['mode'] ?? 'N/A'
    ]
]);
exit();
