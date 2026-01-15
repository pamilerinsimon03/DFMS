<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

if (empty($_GET['id']) || !($doc = Document::getById((int)$_GET['id']))) {
    header("HTTP/1.1 404 Not Found");
    exit("Document not found");
}

$current_user = User::getById($_SESSION['user_id']);
if ($current_user['matric_number'] !== 'SUPER_ADMIN') {
    $doc_owner = User::getById($doc['user_id']);
    if ($doc_owner['department_id'] !== $current_user['department_id']) {
        header("HTTP/1.1 403 Forbidden");
        exit("Access denied: You can only download documents from your department.");
    }
}

$file = __DIR__ . '/../../' . $doc['file_path'];
if (!is_file($file) || !is_readable($file)) {
    header("HTTP/1.1 404 Not Found");
    exit("File not found");
}

require_once __DIR__ . '/../utils/SecurityUtils.php';
SecurityUtils::logSecurityEvent('document_download', [
    'user_id' => $_SESSION['user_id'],
    'doc_id' => $doc['id'],
    'filename' => basename($file),
    'file_size' => filesize($file)
]);

while (ob_get_level()) { ob_end_clean(); }

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$contentType = $ext === 'pdf'
    ? 'application/pdf'
    : ($ext === 'png' ? 'image/png' : 'application/octet-stream');

header('Content-Description: File Transfer');
header("Content-Type: $contentType");
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));

readfile($file);
exit;