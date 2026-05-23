<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

while (ob_get_level()) {
    ob_end_clean();
}


if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden"); exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$status  = $_GET['status'] ?? 'all';

// Enforce administrative access boundaries by department
$current_user = User::getById($_SESSION['user_id']);
if ($current_user['matric_number'] !== 'SUPER_ADMIN') {
    $doc_owner = User::getById($user_id);
    if (!$doc_owner || $doc_owner['department_id'] !== $current_user['department_id']) {
        header("HTTP/1.1 403 Forbidden");
        exit("Access denied: You can only download documents of students from your department.");
    }
}

$allDocs = Document::getDocumentsByUserId($user_id);

if ($status === 'all') {
    $docs = $allDocs;
} else {
    $docs = array_filter(
        $allDocs,
        fn($d) => $d['status'] === $status
    );
}

if (empty($docs)) {
    exit("No documents to download.");
}

$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'docs_') . '.zip';
if ($zip->open($tmpFile, ZipArchive::CREATE) !== true) {
    exit("Could not create ZIP archive.");
}

foreach ($docs as $doc) {
    // Fix: secure_uploads is at the root, not inside public/
    $path = __DIR__ . '/../../' . $doc['file_path'];
    if (is_file($path)) {
        $zip->addFile($path, basename($doc['file_path']));
    }
}

$zip->close();
if ($zip->status !== ZIPARCHIVE::ER_OK) {
    exit("ZIP close failed with status {$zip->status}");
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="student_'
    . $user_id . '_' . str_replace(' ', '_', $status) . '.zip"');
header('Content-Length: ' . filesize($tmpFile));
header('Pragma: public');
header('Cache-Control: must-revalidate');
header('Expires: 0');

flush();

readfile($tmpFile);
@unlink($tmpFile);
exit;