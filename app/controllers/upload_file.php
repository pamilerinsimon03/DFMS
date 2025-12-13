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
    // Validate CSRF token
    requireCSRFToken();
    
    // Include security utilities
    require_once __DIR__ . '/../utils/SecurityUtils.php';
    
    $document_name = SecurityUtils::validateInput($_POST['document_name'] ?? '', 'string', 100);
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file'];
    
    if (!$document_name) {
        $_SESSION['upload_message'] = 'Invalid document name.';
        $_SESSION['upload_status'] = 'error';
        header("Location: /app/controllers/dashboard.php");
        exit();
    }
    
    $filePath = $file['tmp_name'];
    $debug_messages = [];

    // QUALITY CHECKS
    define('MIN_BLURRINESS', 80.0); // Increased based on Laplacian variance testing
    define('MIN_OCR_CONFIDENCE', 60);

    // Helper for response
    function sendUploadResponse($success, $message, $debug_msg = [], $userId = null, $data = []) {
        $isAjax = false;
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $isAjax = true;
        }

        if ($userId) {
            QualityCheckService::logDebugMessages($userId, $debug_msg);
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $message,
                'debug' => $debug_msg,
                'data' => $data
            ]);
            exit();
        } else {
            $_SESSION['upload_message'] = $message;
            $_SESSION['upload_status'] = $success ? 'success' : 'error';
            header("Location: /app/controllers/dashboard.php");
            exit();
        }
    }

    // Run only for images or PDFs
    if (QualityCheckService::isImage($filePath)) {
        // 1) Blur check
        $blurriness = QualityCheckService::getBlurriness($filePath, $debug_messages);
        
        // Negative return indicates error in processing (ignore or fail? we'll fail to be safe)
        if ($blurriness < 0) {
            sendUploadResponse(false, "Upload failed: Could not analyze image quality.", $debug_messages, $user_id);
        }
        
        if ($blurriness < MIN_BLURRINESS) {
            sendUploadResponse(false, "Upload failed: Image is too blurry (Score: " . round($blurriness, 2) . "/80). Please upload a clearer image.", $debug_messages, $user_id);
        }

        // 2) OCR Check for Images
        $ocrConfidence = QualityCheckService::getOcrConfidence($filePath, $debug_messages);
        
        // Strictly enforce OCR. If 0 (no text) or < MIN (bad text), reject.
        if ($ocrConfidence < MIN_OCR_CONFIDENCE) {
             $msg = ($ocrConfidence <= 0) ? "Text not legible (No text detected)." : "Text not legible (Confidence: " . round($ocrConfidence) . "%).";
             sendUploadResponse(false, "Upload failed: $msg Please ensure document text is clear.", $debug_messages, $user_id);
        }

    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'pdf') {
        // Check PDF legibility
        $ocrConfidence = QualityCheckService::getOcrConfidence($filePath, $debug_messages);
        
        if ($ocrConfidence < MIN_OCR_CONFIDENCE) {
            $msg = ($ocrConfidence <= 0) ? "PDF text not readable." : "PDF text not clear enough (Confidence: " . round($ocrConfidence) . "%).";
             sendUploadResponse(false, "Upload failed: $msg", $debug_messages, $user_id);
        }
    }

    // Continue with Upload
    $user = User::getById($user_id);
    if (!$user) {
        sendUploadResponse(false, "Upload failed: Your user data could not be found.", [], $user_id);
    } else {
        // Automatically determine the current semester
        $semester = AcademicCalendarService::getCurrentSemester();
        // Get the original file's extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Generate the standardized filename
        $generatedFilename = FilenameGenerator::generate($user_id, $document_name, $semester, $extension);
        
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
            $result = Document::upload($user_id, $document_name, $generatedFilename, $file, $classificationData);
            
            // Send final response using the result from upload
            sendUploadResponse($result['success'], $result['message'], $debug_messages, $user_id, ['document_name' => $document_name]);
            
        } else {
            sendUploadResponse(false, "Upload failed: Could not generate a standard filename. Please ensure your profile is complete.", $debug_messages, $user_id);
        }
    }
}