<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';


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


$file_path = $doc['file_path'];
$full_path = __DIR__ . '/../../' . $file_path;

if (!file_exists($full_path)) {
    http_response_code(404);
    exit('File not found');
}

// Anti-tampering file integrity validation
if (empty($doc['file_hash'])) {
    // Proactively backfill missing file hash
    $calculated_hash = hash_file('sha256', $full_path);
    global $conn;
    $update_stmt = $conn->prepare("UPDATE documents SET file_hash = ? WHERE id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param("si", $calculated_hash, $doc_id);
        $update_stmt->execute();
    }
} else {
    $calculated_hash = hash_file('sha256', $full_path);
    if (!hash_equals($doc['file_hash'], $calculated_hash)) {
        require_once __DIR__ . '/../utils/SecurityUtils.php';
        SecurityUtils::logSecurityEvent('file_integrity_check_failed', [
            'user_id' => $_SESSION['user_id'],
            'doc_id' => $doc_id,
            'expected_hash' => $doc['file_hash'],
            'calculated_hash' => $calculated_hash,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        http_response_code(500);
        exit('File integrity check failed: Content has been tampered with or corrupted.');
    }
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
