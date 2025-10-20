<?php
/**
 * Secure File Viewer
 * Serves files from secure_uploads directory with proper authorization
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Get document ID from query parameter
$doc_id = $_GET['id'] ?? null;
if (!$doc_id) {
    http_response_code(400);
    exit('Document ID required');
}

// Get document details
$doc = Document::getById($doc_id);
if (!$doc) {
    http_response_code(404);
    exit('Document not found');
}

// Check authorization
$current_user = User::getById($_SESSION['user_id']);
$is_admin = User::isAdmin($_SESSION['user_id']);

// Users can only view their own documents, admins can view all documents
if (!$is_admin && $doc['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    exit('Access denied');
}

// For admins, check department access (unless super admin)
if ($is_admin && $current_user['matric_number'] !== 'SUPER_ADMIN') {
    $doc_owner = User::getById($doc['user_id']);
    if ($doc_owner['department_id'] !== $current_user['department_id']) {
        http_response_code(403);
        exit('Access denied: You can only view documents from your department.');
    }
}

// Get file path
$file_path = $doc['file_path'];
$full_path = __DIR__ . '/../../' . $file_path;

// Check if file exists
if (!file_exists($full_path)) {
    http_response_code(404);
    exit('File not found');
}

// Get file info
$file_info = pathinfo($full_path);
$mime_type = mime_content_type($full_path);

// Set appropriate headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($full_path));
header('Cache-Control: private, max-age=3600'); // Cache for 1 hour
header('X-Content-Type-Options: nosniff');

// For PDFs, allow inline viewing
if ($mime_type === 'application/pdf') {
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
} else {
    // For images, also allow inline viewing
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
}

// Log the file access
require_once __DIR__ . '/../utils/SecurityUtils.php';
SecurityUtils::logSecurityEvent('secure_file_access', [
    'user_id' => $_SESSION['user_id'],
    'doc_id' => $doc_id,
    'filename' => basename($file_path),
    'file_size' => filesize($full_path)
]);

// Output the file
readfile($full_path);
exit;
?>
