<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/FilenameGenerator.php';
require_once __DIR__ . '/../services/AcademicCalendarService.php';
require_once __DIR__ . '/../services/QualityCheckService.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

// --- Handle Upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_name = $_POST['document_name'];
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file'];
    $filePath = $file['tmp_name'];
    $debug_messages = [];

    // define('MIN_BLURRINESS', 15.0);
    // define('MIN_OCR_CONFIDENCE', 60);

    // Run only for images (not PDFs)
    // if (QualityCheckService::isImage($filePath)) {
    //     // 1) Blur check only
    //     $blurriness = QualityCheckService::getBlurriness($filePath, $debug_messages);
    //     if ($blurriness <= 0) {
    //         $_SESSION['upload_message']   = "Upload failed: Could not analyze image sharpness.";
    //         $_SESSION['upload_status']    = 'error';
    //         QualityCheckService::logDebugMessages($user_id, $debug_messages);
    //         header("Location: /app/controllers/dashboard.php");
    //         exit();
    //     }
    //     if ($blurriness < MIN_BLURRINESS) {
    //         $_SESSION['upload_message']   = "Upload failed: The image is too blurry. (Score: " . round($blurriness, 2) . ")";
    //         $_SESSION['upload_status']    = 'error';
    //         QualityCheckService::logDebugMessages($user_id, $debug_messages);
    //         header("Location: /app/controllers/dashboard.php");
    //         exit();
    //     }

    //     // 2) **Skip OCR for images**—assume if it’s not too blurry, it’s acceptable.
    //     $debug_messages[] = '[Info: Skipping OCR legibility check for image.]';

    // } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'pdf') {
    //     // If you still want to OCR‐check PDF uploads, do it here:
    //     $ocrConfidence = QualityCheckService::getOcrConfidence($filePath, $debug_messages);
    //     if ($ocrConfidence <= 0) {
    //         $_SESSION['upload_message']   = "Upload failed: PDF text not readable.";
    //         $_SESSION['upload_status']    = 'error';
    //         QualityCheckService::logDebugMessages($user_id, $debug_messages);
    //         header("Location: /app/controllers/dashboard.php");
    //         exit();
    //     }
    //     if ($ocrConfidence < MIN_OCR_CONFIDENCE) {
    //         $_SESSION['upload_message']   = "Upload failed: PDF text too low‐confidence. (OCR: " . round($ocrConfidence) . "%)";
    //         $_SESSION['upload_status']    = 'error';
    //         QualityCheckService::logDebugMessages($user_id, $debug_messages);
    //         header("Location: /app/controllers/dashboard.php");
    //         exit();
    //     }
    // }

    $userId = $_SESSION['user_id'];
    $documentName = $_POST['document_name'];
    $file = $_FILES['file'];

    $user = User::getById($userId);
    if (!$user) {
        $message = "Upload failed: Your user data could not be found.";
        $_SESSION['upload_status'] = 'error';
    } else {
        // Automatically determine the current semester
        $semester = AcademicCalendarService::getCurrentSemester();
        // Get the original file's extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Generate the standardized filename
        $generatedFilename = FilenameGenerator::generate($userId, $documentName, $semester, $extension);
        
        if ($generatedFilename) {
            // Prepare the classification data that will be stored with the document
            $classificationData = [
                'level' => preg_replace('/[^0-9]/', '', $user['level']), // "500L" -> 500
                'semester' => $semester,
                'program_type' => $user['program_type'],
                'mode' => $user['mode'],
                'department_id' => $user['department_id']
            ];

            // Upload the document with an initial status of 'Pending Approval'
            $result = Document::upload($userId, $documentName, $generatedFilename, $file, $classificationData);
            $message = $result['message'];
            $_SESSION['upload_status'] = $result['success'] ? 'success' : 'error';
        } else {
            $message = "Upload failed: Could not generate a standard filename. Please ensure your profile is complete.";
            $_SESSION['upload_status'] = 'error';
        }
    }
    
    // Provide feedback to the user and redirect back to the dashboard
    $_SESSION['upload_message'] = $message;
    header("Location: /app/controllers/dashboard.php");
    exit();
}