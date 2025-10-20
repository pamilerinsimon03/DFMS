<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/FileRequirement.php';

// Check if user is admin (but not SUPER_ADMIN)
if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user = User::getById($_SESSION['user_id']);

// SUPER_ADMIN cannot access this page
if ($user['matric_number'] === 'SUPER_ADMIN') {
    header("Location: /app/controllers/admin.php");
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    requireCSRFToken();
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $document_name = trim($_POST['document_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $due_date = $_POST['due_date'] ?? '';
            
            if (empty($document_name) || empty($due_date)) {
                $message = "Document name and due date are required.";
                $message_type = 'error';
            } else {
                $result = FileRequirement::create(
                    $user['department_id'],
                    $document_name,
                    $description,
                    $due_date,
                    $user['id']
                );
                
                if ($result['success']) {
                    $message = "File requirement created successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error creating file requirement: " . $result['message'];
                    $message_type = 'error';
                }
            }
            break;
            
        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            $document_name = trim($_POST['document_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $due_date = $_POST['due_date'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($document_name) || empty($due_date)) {
                $message = "Document name and due date are required.";
                $message_type = 'error';
            } else {
                $result = FileRequirement::update($id, $document_name, $description, $due_date, $is_active);
                
                if ($result['success']) {
                    $message = "File requirement updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error updating file requirement: " . $result['message'];
                    $message_type = 'error';
                }
            }
            break;
            
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            
            $result = FileRequirement::delete($id);
            
            if ($result['success']) {
                $message = "File requirement deleted successfully!";
                $message_type = 'success';
            } else {
                $message = "Error deleting file requirement: " . $result['message'];
                $message_type = 'error';
            }
            break;
            
        case 'toggle_active':
            $id = (int)($_POST['id'] ?? 0);
            
            $result = FileRequirement::toggleActive($id);
            
            if ($result['success']) {
                $message = "File requirement status updated!";
                $message_type = 'success';
            } else {
                $message = "Error updating file requirement: " . $result['message'];
                $message_type = 'error';
            }
            break;
    }
}

// Get file requirements for this department
$file_requirements = FileRequirement::getByDepartment($user['department_id'], false);

$page_title = 'File Management';
$active_page = 'file_management';
$css_files = ['admin.css', 'file_management.css'];
$js_files = ['file_management.js'];

include_once('../views/partials/header.php');
include_once('../views/partials/admin_sidebar.php');
include_once('../views/file_management.php');
include_once('../views/partials/footer.php');
?>
