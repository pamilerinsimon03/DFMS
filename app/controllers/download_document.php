<?php
// download_document.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

// only admins:
if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

if (empty($_GET['id']) || !($doc = Document::getById((int)$_GET['id']))) {
    header("HTTP/1.1 404 Not Found");
    exit("Document not found");
}

// resolve the path on disk
$file = __DIR__ . '/../../public/' . $doc['file_path'];
if (!is_file($file) || !is_readable($file)) {
    header("HTTP/1.1 404 Not Found");
    exit("File not found");
}

// kill any output buffering & make sure nothing else echoes
while (ob_get_level()) { ob_end_clean(); }

// figure out content‐type from extension
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$contentType = $ext === 'pdf'
    ? 'application/pdf'
    : ($ext === 'png' ? 'image/png' : 'application/octet-stream');

// send headers
header('Content-Description: File Transfer');
header("Content-Type: $contentType");
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));

// stream it
readfile($file);
exit;