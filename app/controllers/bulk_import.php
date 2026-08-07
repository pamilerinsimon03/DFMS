<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';

$user = User::getById($_SESSION['user_id']);
$departments = User::getAllDepartments();
$message = '';
$message_type = '';
$import_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_csv') {
    requireCSRFToken();
    
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file'];
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            $message = 'Please upload a valid CSV file.';
            $message_type = 'error';
        } else {
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle) {
                $row_number = 0;
                $success_count = 0;
                $error_count = 0;
                $chunk_size = 500;
                $student_chunk = [];
                
                global $conn;
                $conn->begin_transaction();

                try {
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        $row_number++;
                        
                        if ($row_number === 1) {
                            continue; // Skip CSV Header row
                        }
                        
                        if (count($data) < 6) {
                            $import_results[] = [
                                'row' => $row_number,
                                'status' => 'error',
                                'message' => 'Insufficient columns. Expected: matric_number, full_name, level, session, programme, department_id'
                            ];
                            $error_count++;
                            continue;
                        }
                        
                        $student_data = [
                            'matric_number' => trim($data[0]),
                            'full_name' => trim($data[1]),
                            'level' => trim($data[2]),
                            'session' => trim($data[3]),
                            'programme' => trim($data[4]),
                            'department_id' => (int)trim($data[5]),
                            'program_type' => trim($data[6] ?? 'Undergraduate'),
                            'mode' => trim($data[7] ?? 'Regular'),
                            'email' => trim($data[8] ?? '')
                        ];
                        
                        if (empty($student_data['matric_number']) || empty($student_data['full_name'])) {
                            $import_results[] = [
                                'row' => $row_number,
                                'status' => 'error',
                                'message' => 'Matric number and full name are required'
                            ];
                            $error_count++;
                            continue;
                        }

                        $student_chunk[] = $student_data;

                        // Batch insert in chunks to balance memory and DB connection limits
                        if (count($student_chunk) >= $chunk_size) {
                            $res = User::bulkCreateStudents($student_chunk);
                            if ($res['success']) {
                                $success_count += $res['count'];
                            } else {
                                throw new Exception($res['message']);
                            }
                            $student_chunk = [];
                        }
                    }

                    if (!empty($student_chunk)) {
                        $res = User::bulkCreateStudents($student_chunk);
                        if ($res['success']) {
                            $success_count += $res['count'];
                        } else {
                            throw new Exception($res['message']);
                        }
                    }

                    fclose($handle);
                    $conn->commit();

                    SecurityUtils::logSecurityEvent('bulk_csv_import_success', [
                        'user_id' => $_SESSION['user_id'],
                        'success_count' => $success_count,
                        'error_count' => $error_count
                    ]);

                    $message = "Import completed successfully. Imported: {$success_count} students. (Password generation deferred to Student Activation Flow).";
                    $message_type = $error_count === 0 ? 'success' : 'warning';

                } catch (Exception $e) {
                    $conn->rollback();
                    fclose($handle);
                    SecurityUtils::logSecurityEvent('bulk_csv_import_failed', ['error' => $e->getMessage()]);
                    $message = "Import failed and was rolled back: " . $e->getMessage();
                    $message_type = 'error';
                }
            } else {
                $message = 'Failed to read CSV file.';
                $message_type = 'error';
            }
        }
    } else {
        $message = 'Please select a CSV file to upload.';
        $message_type = 'error';
    }
}

$page_title = 'Bulk Import Students';
$active_page = 'bulk_import';
$css_files = ['admin.css', 'bulk_import.css'];
$js_files = ['admin.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/bulk_import.php';
require_once __DIR__ . '/../views/partials/footer.php';
