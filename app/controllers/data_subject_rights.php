<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';
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
            
            // Format personal data packet under NDPR data portability rules
            $exportData = [
                'metadata' => [
                    'organization' => 'University of Ibadan',
                    'portal' => 'Document File Management System',
                    'regulation' => 'Nigeria Data Protection Regulation (NDPR) 2019',
                    'export_date' => date('c'),
                    'request_type' => 'Right to Data Portability'
                ],
                'profile' => [
                    'matric_number' => $userData['matric_number'],
                    'full_name' => $userData['full_name'],
                    'email' => $userData['email'],
                    'phone_number' => $userData['phone_number'],
                    'gender' => $userData['gender'] ?? 'N/A',
                    'date_of_birth' => $userData['date_of_birth'] ?? 'N/A',
                    'state_of_origin' => $userData['state_of_origin'] ?? 'N/A',
                    'marital_status' => $userData['marital_status'] ?? 'N/A',
                    'address' => $userData['address'] ?? 'N/A',
                    'academic' => [
                        'level' => $userData['level'],
                        'programme' => $userData['programme'],
                        'session' => $userData['session'],
                        'program_type' => $userData['program_type'] ?? 'Undergraduate',
                        'mode' => $userData['mode'] ?? 'Regular'
                    ],
                    'emergency_contact' => [
                        'name' => $userData['emergency_contact_name'] ?? 'N/A',
                        'phone' => $userData['emergency_contact_phone'] ?? 'N/A',
                        'relationship' => $userData['emergency_contact_relationship'] ?? 'N/A'
                    ]
                ],
                'uploaded_documents' => []
            ];
            
            foreach ($documents as $doc) {
                $exportData['uploaded_documents'][] = [
                    'id' => $doc['id'],
                    'document_name' => $doc['document_name'],
                    'file_name' => basename($doc['file_path']),
                    'upload_date' => $doc['upload_date'],
                    'status' => $doc['status'],
                    'quality_scan_status' => $doc['file_scan_status'] ?? 'pending',
                    'file_hash' => $doc['file_hash'] ?? 'N/A'
                ];
            }
            
            // Clear buffer, set response headers, and output portable JSON
            ob_clean();
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="personal_data_export.json"');
            echo json_encode($exportData, JSON_PRETTY_PRINT);
            exit();
            
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
            require_once __DIR__ . '/../utils/DataRetention.php';
            $result = DataRetention::scheduleDataDeletion($_SESSION['user_id'], 'Student requested account and record erasure under NDPR Right to Erasure');
            if ($result) {
                $message = "Your personal data deletion request has been submitted and scheduled (delayed by 2 years due to academic retention regulations).";
                SecurityUtils::logSecurityEvent('data_erasure_request', [
                    'user_id' => $_SESSION['user_id'],
                    'action' => 'delete_data',
                    'scheduled' => true
                ]);
            } else {
                $error = "Failed to submit data deletion request. Please try again.";
            }
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
