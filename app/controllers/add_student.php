<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';


$user = User::getById($_SESSION['user_id']);
$notifications = Notification::getForUser($_SESSION['user_id'], 15);
$unread_count = Notification::countUnread($_SESSION['user_id']);
$departments = User::getAllDepartments();
$message = '';
$message_type = '';
$import_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'import_csv') {
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
                    
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        $row_number++;
                        
                        if ($row_number === 1) {
                            continue;
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
                        
                        $result = User::createStudent($student_data);
                        
                        if ($result['success']) {
                            $actUrl = $result['activation_url'] ?? '';
                            $msgText = 'Student created successfully. ';
                            if ($actUrl) {
                                $msgText .= 'Activation link: ' . $actUrl;
                            } elseif (isset($result['password'])) {
                                $msgText .= 'Password: ' . $result['password'];
                            } else {
                                $msgText .= 'Activation link dispatched to student email.';
                            }
                            $import_results[] = [
                                'row' => $row_number,
                                'status' => 'success',
                                'message' => $msgText
                            ];
                            $success_count++;
                        } else {
                            $import_results[] = [
                                'row' => $row_number,
                                'status' => 'error',
                                'message' => $result['message']
                            ];
                            $error_count++;
                        }
                    }
                    
                    fclose($handle);
                    
                    $message = "Import completed. Success: {$success_count}, Errors: {$error_count}";
                    $message_type = $error_count === 0 ? 'success' : 'warning';
                } else {
                    $message = 'Failed to read CSV file.';
                    $message_type = 'error';
                }
            }
        } else {
            $message = 'Please select a CSV file to upload.';
            $message_type = 'error';
        }
    } else {
        $required_fields = ['matric_number', 'full_name', 'level', 'session', 'programme', 'department_id'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = ucfirst(str_replace('_', ' ', $field));
            }
        }
        
        if (!empty($missing_fields)) {
            $message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
            $message_type = 'error';
        } else {
            $student_data = [
                'matric_number' => trim($_POST['matric_number']),
                'full_name' => trim($_POST['full_name']),
                'level' => trim($_POST['level']),
                'session' => trim($_POST['session']),
                'programme' => trim($_POST['programme']),
                'program_type' => $_POST['program_type'] ?? 'Undergraduate',
                'mode' => $_POST['mode'] ?? 'Regular',
                'department_id' => (int)$_POST['department_id'],
                'email' => trim($_POST['email'] ?? '')
            ];
            
            $result = User::createStudent($student_data);
            
            if ($result['success']) {
                $message = $result['message'];
                $message_type = 'success';
                
                $_POST = [];
            } else {
                $message = $result['message'];
                $message_type = 'error';
            }
        }
    }
}

$page_title = 'Add Students';
$active_page = 'add_student';
$css_files = ['admin.css', 'add_student.css'];
$js_files = ['admin.js'];

if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    ob_start();
    require_once __DIR__ . '/../views/add_student.php';
    $html = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html, 
        'title' => $page_title,
        'css_files' => $css_files,
        'js_files' => $js_files
    ]);
    exit();
}

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/add_student.php';
require_once __DIR__ . '/../views/partials/footer.php';
