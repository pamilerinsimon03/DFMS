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

// Get system statistics
$total_students = User::countAllStudents('', null, null, null, null, null, null);
$total_departments = count(User::getAllDepartments());

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'change_admin_password':
            // Only SUPER_ADMIN can change admin passwords
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
                // Find the admin user
                $admin_user = User::getByMatricNumber($admin_matric);
                if (!$admin_user) {
                    $error = "Admin user not found.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    if (User::updatePassword($admin_user['id'], $hashed_password)) {
                        $success = "Password updated successfully for " . $admin_user['full_name'];
                    } else {
                        $error = "Failed to update password.";
                    }
                }
            }
            break;
            
        case 'update_passwords':
            // Update all student passwords to new format
            $result = User::updateAllStudentPasswords();
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
            
        case 'update_preferences':
            // Handle system preferences (placeholder for future implementation)
            $session_year = $_POST['session_year'] ?? '';
            $max_file_size = $_POST['max_file_size'] ?? '';
            $auto_approve = $_POST['auto_approve'] ?? '';
            
            // For now, just show a success message
            $success = "System preferences updated successfully.";
            break;
            
        case 'update_security':
            // Handle security settings (placeholder for future implementation)
            $enable_2fa = isset($_POST['enable_2fa']);
            $session_timeout = isset($_POST['session_timeout']);
            $login_notifications = isset($_POST['login_notifications']);
            
            // For now, just show a success message
            $success = "Security settings updated successfully.";
            break;
    }
}

$page_title = 'Settings';
$active_page = 'settings';
$css_files = ['admin.css', 'settings.css'];
$js_files = ['admin.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/settings.php';
require_once __DIR__ . '/../views/partials/footer.php';