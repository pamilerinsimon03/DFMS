<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';


if (empty($_GET['id']) || !($doc = Document::getById((int)$_GET['id']))) {
    header("HTTP/1.1 404 Not Found");
    exit("Document not found");
}


$file = __DIR__ . '/../../' . $doc['file_path'];
if (!is_file($file) || !is_readable($file)) {
    header("HTTP/1.1 404 Not Found");
    exit("File not found");
}

// Anti-tampering file integrity validation
if (empty($doc['file_hash'])) {
    // Proactively backfill missing file hash
    $calculated_hash = hash_file('sha256', $file);
    global $conn;
    $update_stmt = $conn->prepare("UPDATE documents SET file_hash = ? WHERE id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param("si", $calculated_hash, $doc['id']);
        $update_stmt->execute();
    }
} else {
    $calculated_hash = hash_file('sha256', $file);
    if (!hash_equals($doc['file_hash'], $calculated_hash)) {
        require_once __DIR__ . '/../utils/SecurityUtils.php';
        SecurityUtils::logSecurityEvent('file_integrity_check_failed', [
            'user_id' => $_SESSION['user_id'],
            'doc_id' => $doc['id'],
            'expected_hash' => $doc['file_hash'],
            'calculated_hash' => $calculated_hash,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        header("HTTP/1.1 500 Internal Server Error");
        exit('File integrity check failed: Content has been tampered with or corrupted.');
    }
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