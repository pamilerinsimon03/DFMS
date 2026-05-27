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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
    exit();
}

$doc = Document::getById($id);
if (!$doc) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Document not found']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$is_admin = User::isAdmin($current_user_id);

// Enforce security boundary: Standard student can only access their own documents
if (!$is_admin && $doc['user_id'] != $current_user_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit();
}

// Enforce Department Admin boundary: Scoped admins can only view their own department's files
if ($is_admin) {
    $current_user = User::getById($current_user_id);
    if ($current_user['matric_number'] !== 'SUPER_ADMIN') {
        if ($current_user['department_id'] != $doc['department_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access Denied: Scoped Department Boundary']);
            exit();
        }
    }
}

// Success - return data
echo json_encode([
    'success' => true,
    'data' => [
        'id' => $doc['id'],
        'document_name' => $doc['document_name'],
        'status' => $doc['status'],
        'rejection_reason' => $doc['rejection_reason'],
        'file_name' => basename($doc['file_path']),
        'upload_date' => $doc['upload_date'],
        'file_scan_status' => $doc['file_scan_status'] ?? 'pending',
        'file_scan_date' => $doc['file_scan_date'] ? date('M d, Y H:i', strtotime($doc['file_scan_date'])) : 'N/A',
        'level' => $doc['level'] ? $doc['level'] . 'L' : 'N/A',
        'semester' => $doc['semester'] ?? 'N/A',
        'program_type' => $doc['program_type'] ?? 'N/A',
        'mode' => $doc['mode'] ?? 'N/A'
    ]
]);
exit();
?>
