<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user = User::getById($_SESSION['user_id']);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'access_data':
            $userData = User::getById($_SESSION['user_id']);
            $documents = Document::getDocumentsByUserId($_SESSION['user_id']);
            
            SecurityUtils::logSecurityEvent('data_access_request', [
                'user_id' => $_SESSION['user_id'],
                'action' => 'access_data'
            ]);
            
            $message = "Your personal data has been prepared. Please contact the Data Protection Officer to receive your data.";
            break;
            
        case 'rectify_data':
            $field = $_POST['field'] ?? '';
            $new_value = $_POST['new_value'] ?? '';
            
            if (in_array($field, ['full_name', 'email', 'phone_number', 'address'])) {
                $result = User::updateSingleField($_SESSION['user_id'], $field, $new_value);
                if ($result) {
                    $message = "Your data has been updated successfully.";
                    SecurityUtils::logSecurityEvent('data_rectification', [
                        'user_id' => $_SESSION['user_id'],
                        'field' => $field
                    ]);
                } else {
                    $error = "Failed to update your data. Please try again.";
                }
            } else {
                $error = "Invalid field specified.";
            }
            break;
            
        case 'delete_data':
            SecurityUtils::logSecurityEvent('data_erasure_request', [
                'user_id' => $_SESSION['user_id'],
                'action' => 'delete_data'
            ]);
            
            $message = "Your data deletion request has been submitted. Note: Some academic records may be retained for legal and educational purposes.";
            break;
            
        case 'restrict_processing':
            SecurityUtils::logSecurityEvent('processing_restriction_request', [
                'user_id' => $_SESSION['user_id'],
                'action' => 'restrict_processing'
            ]);
            
            $message = "Your request to restrict data processing has been submitted. The Data Protection Officer will review your request.";
            break;
    }
}

$page_title = 'Data Subject Rights';
$active_page = 'data_rights';
$css_files = ['dashboard.css'];
$js_files = ['data_rights.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/data_subject_rights.php';
require_once __DIR__ . '/../views/partials/footer.php';
?>
