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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    require_once __DIR__ . '/../utils/SecurityUtils.php';
    
    $document_name = SecurityUtils::validateInput($_POST['document_name'] ?? '', 'string', 100);
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file'];
    $debug_messages = [];
    
    if (!$document_name) {
        $_SESSION['upload_message'] = 'Invalid document name.';
        $_SESSION['upload_status'] = 'error';
        header("Location: /app/controllers/dashboard.php");
        exit();
    }
    
    $filePath = $file['tmp_name'];
    define('MIN_OCR_CONFIDENCE', 60);
    define('MIN_BLURRINESS', 80);
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

    if (QualityCheckService::isImage($filePath)) {
        $blurriness = QualityCheckService::getBlurriness($filePath, $debug_messages);
        
        if ($blurriness < 0) {
            sendUploadResponse(false, "Upload failed: Could not analyze image quality.", $debug_messages, $user_id);
        }
        
        if ($blurriness < MIN_BLURRINESS) {
            sendUploadResponse(false, "Upload failed: Image is too blurry (Score: " . round($blurriness, 2) . "/80). Please upload a clearer image.", $debug_messages, $user_id);
        }

        $ocrConfidence = QualityCheckService::getOcrConfidence($filePath, $debug_messages);
        
        if ($ocrConfidence < MIN_OCR_CONFIDENCE) {
             $msg = ($ocrConfidence <= 0) ? "Text not legible (No text detected)." : "Text not legible (Confidence: " . round($ocrConfidence) . "%).";
             sendUploadResponse(false, "Upload failed: $msg Please ensure document text is clear.", $debug_messages, $user_id);
        }

    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'pdf') {
        $ocrConfidence = QualityCheckService::getOcrConfidence($filePath, $debug_messages);
        
        if ($ocrConfidence < MIN_OCR_CONFIDENCE) {
            $msg = ($ocrConfidence <= 0) ? "PDF text not readable." : "PDF text not clear enough (Confidence: " . round($ocrConfidence) . "%).";
             sendUploadResponse(false, "Upload failed: $msg", $debug_messages, $user_id);
        }
    }

    $user = User::getById($user_id);
    if (!$user) {
        sendUploadResponse(false, "Upload failed: Your user data could not be found.", [], $user_id);
    } else {
        $semester = AcademicCalendarService::getCurrentSemester();
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        $generatedFilename = FilenameGenerator::generate($user_id, $document_name, $semester, $extension);
        
        if ($generatedFilename) {
            $classificationData = [
                'level' => preg_replace('/[^0-9]/', '', $user['level']), // "500L" -> 500
                'semester' => $semester,
                'program_type' => $user['program_type'],
                'mode' => $user['mode'],
                'department_id' => $user['department_id']
            ];

            $result = Document::upload($user_id, $document_name, $generatedFilename, $file, $classificationData);
            
            if ($result['success'] && isset($result['id'])) {
                $settings_file = __DIR__ . '/../config/settings.json';
                $auto_approve = false;
                if (file_exists($settings_file)) {
                    $settings = json_decode(file_get_contents($settings_file), true);
                    if (isset($settings['auto_approve']) && $settings['auto_approve'] == '1') {
                        $auto_approve = true;
                    }
                }
                
                if ($auto_approve) {
                    Document::updateStatus($result['id'], 'verify');
                    $result['message'] = 'File uploaded and automatically approved by system policy clearance.';
                }
            }
            
            sendUploadResponse($result['success'], $result['message'], $debug_messages, $user_id, ['document_name' => $document_name]);
            
        } else {
            sendUploadResponse(false, "Upload failed: Could not generate a standard filename. Please ensure your profile is complete.", $debug_messages, $user_id);
        }
    }
}