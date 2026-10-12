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
            $consents = User::getUserConsents($_SESSION['user_id']);
            
            SecurityUtils::logSecurityEvent('data_access_request', [
                'user_id'          => $_SESSION['user_id'],
                'action'           => 'access_data',
                'statutory_clause' => 'NDPA 2023 s.34 & s.38'
            ]);
            
            // Format personal data packet under NDPA 2023 s.34 (Access) & s.38 (Portability)
            $exportData = [
                'metadata' => [
                    'organization'  => 'University of Ibadan',
                    'portal'        => 'Digital File Management System (DFMS)',
                    'regulation'    => 'Nigeria Data Protection Act (NDPA) 2023 & GAID 2025',
                    'export_date'   => date('c'),
                    'statutory_ref' => 'NDPA 2023 Section 34 & Section 38'
                ],
                'profile' => [
                    'public_id'     => $userData['public_id'] ?? null,
                    'matric_number' => $userData['matric_number'] ?? null,
                    'full_name'     => $userData['full_name'] ?? null,
                    'email'         => $userData['email'] ?? null,
                    'phone_number'  => $userData['phone_number'] ?? null,
                    'gender'        => $userData['gender'] ?? 'N/A',
                    'date_of_birth' => $userData['date_of_birth'] ?? 'N/A',
                    'state_of_origin' => $userData['state_of_origin'] ?? 'N/A',
                    'marital_status' => $userData['marital_status'] ?? 'N/A',
                    'address'       => $userData['address'] ?? 'N/A',
                    'processing_restricted' => !empty($userData['processing_restricted']),
                    'academic' => [
                        'level'        => $userData['level'] ?? null,
                        'programme'    => $userData['programme'] ?? null,
                        'session'      => $userData['session'] ?? null,
                        'program_type' => $userData['program_type'] ?? 'Undergraduate',
                        'mode'         => $userData['mode'] ?? 'Regular'
                    ],
                    'emergency_contact' => [
                        'name'         => $userData['emergency_contact_name'] ?? 'N/A',
                        'phone'        => $userData['emergency_contact_phone'] ?? 'N/A',
                        'relationship' => $userData['emergency_contact_relationship'] ?? 'N/A'
                    ]
                ],
                'consent_history' => $consents,
                'uploaded_documents' => []
            ];
            
            foreach ($documents as $doc) {
                $exportData['uploaded_documents'][] = [
                    'id'                  => $doc['id'],
                    'public_id'           => $doc['public_id'] ?? null,
                    'document_name'       => $doc['document_name'],
                    'file_name'           => basename($doc['file_path'] ?? ''),
                    'upload_date'         => $doc['upload_date'],
                    'status'              => $doc['status'],
                    'quality_scan_status' => $doc['file_scan_status'] ?? 'pending',
                    'file_hash'           => $doc['file_hash'] ?? 'N/A'
                ];
            }
            
            // Clear buffer, set response headers, and output portable JSON
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="personal_data_export_' . ($_SESSION['user_id']) . '.json"');
            echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit();
            
        case 'rectify_data':
            if (User::isProcessingRestricted($_SESSION['user_id'])) {
                $error = "Cannot rectify data: Account data processing is currently restricted under NDPA 2023 Section 37.";
                break;
            }

            $field = $_POST['field'] ?? '';
            $new_value = trim($_POST['new_value'] ?? '');
            
            if (in_array($field, ['full_name', 'email', 'phone_number', 'address'], true)) {
                $result = User::updateSingleField($_SESSION['user_id'], $field, $new_value);
                if ($result) {
                    $message = "Your personal data has been rectified successfully with cryptographic audit logging.";
                } else {
                    $error = "Failed to update your data. Please verify the value and try again.";
                }
            } else {
                $error = "Invalid field specified for rectification.";
            }
            break;

        case 'withdraw_consent':
            $consentType = trim($_POST['consent_type'] ?? '');
            if (empty($consentType)) {
                $error = "Please specify a consent type to withdraw.";
                break;
            }

            $withdrawRes = User::withdrawConsent($_SESSION['user_id'], $consentType);
            if ($withdrawRes['success']) {
                $message = $withdrawRes['message'];
            } else {
                $error = $withdrawRes['message'];
            }
            break;
            
        case 'delete_data':
            // Right to Erasure under NDPA 2023 s.36
            // Check statutory exception under NDPA 2023 s.36(2) and s.25(1)(b)
            $isStudent = ($user['role'] ?? '') === 'student' || !empty($user['matric_number']);
            if ($isStudent) {
                $error = "Statutory Refusal (NDPA 2023 Section 36(2) & Section 25(1)(b)): Erasure of core academic student records is precluded by university legal obligations and academic governance regulations. Non-core supplemental files are subject to automated retention purging.";
                SecurityUtils::logSecurityEvent('data_erasure_statutory_refusal', [
                    'user_id'          => $_SESSION['user_id'],
                    'statutory_clause' => 'NDPA 2023 s.36(2)'
                ]);
            } else {
                require_once __DIR__ . '/../utils/DataRetention.php';
                $result = DataRetention::scheduleDataDeletion($_SESSION['user_id'], 'Data subject erasure requested under NDPA 2023 Section 36');
                if ($result) {
                    $message = "Your non-core data deletion request has been submitted and scheduled.";
                } else {
                    $error = "Failed to submit data deletion request.";
                }
            }
            break;
            
        case 'restrict_processing':
            $currentRestricted = User::isProcessingRestricted($_SESSION['user_id']);
            $newRestricted = !$currentRestricted;
            $res = User::setProcessingRestriction(
                $_SESSION['user_id'],
                $newRestricted,
                'Data subject toggled restriction under NDPA 2023 Section 37'
            );
            if ($res['success']) {
                $message = $newRestricted
                    ? "Processing restriction applied under NDPA 2023 Section 37. Profile modifications and document uploads are now locked."
                    : "Processing restriction lifted successfully.";
                $user['processing_restricted'] = $newRestricted ? 1 : 0;
            } else {
                $error = $res['message'];
            }
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
