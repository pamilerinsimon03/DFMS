<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/FilenameGenerator.php';
require_once __DIR__ . '/../services/AcademicCalendarService.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $document_name = SecurityUtils::validateInput($_POST['document_name'] ?? '', 'string', 100);
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file'] ?? null;
    
    function sendUploadResponse($success, $message, $userId = null, $data = []) {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $message,
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

    if (!$document_name) {
        sendUploadResponse(false, 'Invalid document name.', $user_id);
    }

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        sendUploadResponse(false, 'Please select a valid file to upload.', $user_id);
    }

    $user = User::getById($user_id);
    if (!$user) {
        sendUploadResponse(false, "Upload failed: User data not found.", $user_id);
    }

    $semester = AcademicCalendarService::getCurrentSemester();
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $generatedFilename = FilenameGenerator::generate($user_id, $document_name, $semester, $extension);
    
    if (!$generatedFilename) {
        sendUploadResponse(false, "Upload failed: Could not generate filename. Ensure profile is complete.", $user_id);
    }

    $classificationData = [
        'level' => preg_replace('/[^0-9]/', '', $user['level']),
        'semester' => $semester,
        'program_type' => $user['program_type'],
        'mode' => $user['mode'],
        'department_id' => $user['department_id']
    ];

    // Transactional upload + push to background processing queue
    $result = Document::upload($user_id, $document_name, $generatedFilename, $file, $classificationData);
    
    if ($result['success']) {
        sendUploadResponse(
            true, 
            "File uploaded successfully. Document quality verification has been queued in the background.", 
            $user_id, 
            [
                'document_id' => $result['id'],
                'document_name' => $document_name,
                'status' => 'processing'
            ]
        );
    } else {
        sendUploadResponse(false, "Upload failed: " . $result['message'], $user_id);
    }
}