<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../services/FilenameGenerator.php';
require_once __DIR__ . '/../services/QualityCheckService.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = User::getById($user_id);
$tab = isset($_GET['tab']) ? strtolower($_GET['tab']) : 'total';

// Handle re-upload logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reupload_id'])) {
    $doc_id = $_POST['reupload_id'];
    $file = $_FILES['file'];
    $original_doc = Document::getById($doc_id);

    if ($original_doc && $original_doc['user_id'] == $user_id) {
        // --- Quality Validation ---
        $filePath = $file['tmp_name'];
        $debug_messages = [];
        define('MIN_BLURRINESS', 15.0);
        define('MIN_OCR_CONFIDENCE', 60);

        $isValid = true;
        if (QualityCheckService::isImage($filePath)) {
            $blurriness = QualityCheckService::getBlurriness($filePath, $debug_messages);
            if ($blurriness < MIN_BLURRINESS) {
                $_SESSION['upload_message'] = "Re-upload failed: The image is too blurry.";
                $isValid = false;
            }
        } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'pdf') {
            $ocrConfidence = QualityCheckService::getOcrConfidence($filePath, $debug_messages);
            if ($ocrConfidence < MIN_OCR_CONFIDENCE) {
                $_SESSION['upload_message'] = "Re-upload failed: PDF text has low confidence.";
                $isValid = false;
            }
        }
        QualityCheckService::logDebugMessages($user_id, $debug_messages);

        if ($isValid) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $generated_filename = FilenameGenerator::generate($user_id, $original_doc['document_name'], $original_doc['semester'], $extension);
            
            $result = Document::reupload($doc_id, $file, $generated_filename);
            $_SESSION['upload_message'] = $result['message'];
        }
    } else {
        $_SESSION['upload_message'] = 'Invalid document or permission denied.';
    }

    header("Location: /app/controllers/uploads.php?tab=" . urlencode($tab));
    exit();
}

// Fetch documents for the view
$search = $_GET['search'] ?? null;
if ($tab === 'total') {
    $documents = Document::getByUserId($user_id, $search);
} else {
    $documents = Document::getByUserIdAndStatus($user_id, $tab);
    if ($search) {
        $documents = array_filter($documents, function($doc) use ($search) {
            return stripos($doc['document_name'], $search) !== false;
        });
    }
}

// Prepare view variables
$page_title = 'My Uploads';
$active_page = 'uploads';
$css_files = ['uploads.css'];
$js_files = ['uploads.js', 'dashboard.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/uploads.php';
require_once __DIR__ . '/../views/partials/footer.php';