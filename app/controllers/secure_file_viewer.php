<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$doc_id = $_GET['id'] ?? null;
if (!$doc_id) {
    http_response_code(400);
    exit('Document ID required');
}

$doc = Document::getById($doc_id);
if (!$doc) {
    http_response_code(404);
    exit('Document not found');
}

$current_user = User::getById($_SESSION['user_id']);
$is_admin = User::isAdmin($_SESSION['user_id']);

if (!$is_admin && $doc['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    exit('Access denied');
}

if ($is_admin && $current_user['matric_number'] !== 'SUPER_ADMIN') {
    $doc_owner = User::getById($doc['user_id']);
    if ($doc_owner['department_id'] !== $current_user['department_id']) {
        http_response_code(403);
        exit('Access denied: You can only view documents from your department.');
    }
}

$file_path = $doc['file_path'];
$full_path = __DIR__ . '/../../' . $file_path;

if (!file_exists($full_path)) {
    http_response_code(404);
    exit('File not found');
}

$file_info = pathinfo($full_path);
$mime_type = mime_content_type($full_path);

header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($full_path));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

if ($mime_type === 'application/pdf') {
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
} else {
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
}

require_once __DIR__ . '/../utils/SecurityUtils.php';
SecurityUtils::logSecurityEvent('secure_file_access', [
    'user_id' => $_SESSION['user_id'],
    'doc_id' => $doc_id,
    'filename' => basename($file_path),
    'file_size' => filesize($full_path)
]);

readfile($full_path);
exit;
?>
