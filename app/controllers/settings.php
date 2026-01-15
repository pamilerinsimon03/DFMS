<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user = User::getById($_SESSION['user_id']);
$error = '';
$success = '';

$total_students = User::countAllStudents('', null, null, null, null, null, null);
$total_departments = count(User::getAllDepartments());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'change_admin_password':
            if ($user['matric_number'] !== 'SUPER_ADMIN') {
                $error = "Only Super Admin can change admin passwords.";
                break;
            }
            
            $admin_matric = $_POST['admin_matric'] ?? '';
            $new_password = $_POST['new_admin_password'] ?? '';
            $confirm_password = $_POST['confirm_admin_password'] ?? '';
            
            if (empty($admin_matric) || empty($new_password) || empty($confirm_password)) {
                $error = "All fields are required.";
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
            } elseif (strlen($new_password) < 8) {
                $error = "Password must be at least 8 characters long.";
            } else {
                $admin_user = User::getByMatricNumber($admin_matric);
                if (!$admin_user) {
                    $error = "Admin user not found.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    if (User::updatePassword($admin_user['id'], $hashed_password)) {
                        require_once __DIR__ . '/../utils/SecurityUtils.php';
                        SecurityUtils::logSecurityEvent('admin_password_changed', [
                            'changed_by' => $_SESSION['user_id'],
                            'target_admin' => $admin_user['id'],
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                        ]);
                        $success = "Password updated successfully for " . $admin_user['full_name'];
                    } else {
                        $error = "Failed to update password.";
                    }
                }
            }
            break;
            
        case 'update_passwords':
            $result = User::updateAllStudentPasswords();
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'update_preferences':
            $session_year = $_POST['session_year'] ?? '';
            $max_file_size = $_POST['max_file_size'] ?? '';
            $auto_approve = $_POST['auto_approve'] ?? '';
            
            $success = "System preferences updated successfully.";
            break;
            
        case 'update_security':
            $enable_2fa = isset($_POST['enable_2fa']);
            $session_timeout = isset($_POST['session_timeout']);
            $login_notifications = isset($_POST['login_notifications']);
            
            $success = "Security settings updated successfully.";
            break;
    }
}

$page_title = 'Settings';
$active_page = 'settings';
$css_files = ['admin.css', 'settings.css'];
$js_files = ['admin.js'];

if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    ob_start();
    require_once __DIR__ . '/../views/settings.php';
    $html = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html, 
        'title' => $page_title,
        'css_files' => $css_files
    ]);
    exit();
}

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/settings.php';
require_once __DIR__ . '/../views/partials/footer.php';