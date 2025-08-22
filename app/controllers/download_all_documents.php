<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

// drop any buffered output
while (ob_get_level()) {
    ob_end_clean();
}


// only admins
if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden"); exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$status  = $_GET['status'] ?? 'all';

// 1) grab everything
$allDocs = Document::getDocumentsByUserId($user_id);

// 2) filter in PHP
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

// 3) build zip
$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'docs_') . '.zip';
if ($zip->open($tmpFile, ZipArchive::CREATE) !== true) {
    exit("Could not create ZIP archive.");
}

foreach ($docs as $doc) {
    $path = __DIR__ . '/../../public/' . $doc['file_path'];
    if (is_file($path)) {
        $zip->addFile($path, basename($doc['file_path']));
    }
}

$zip->close();
if ($zip->status !== ZIPARCHIVE::ER_OK) {
    exit("ZIP close failed with status {$zip->status}");
}

// force‐download headers
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="student_'
    . $user_id . '_' . str_replace(' ', '_', $status) . '.zip"');
header('Content-Length: ' . filesize($tmpFile));
header('Pragma: public');
header('Cache-Control: must-revalidate');
header('Expires: 0');

// clear PHP’s internal buffers and push headers
flush();

// read the ZIP
readfile($tmpFile);
@unlink($tmpFile);
exit;