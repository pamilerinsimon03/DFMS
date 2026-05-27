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

// Load system preferences and security settings from JSON store
$settings_file = __DIR__ . '/../config/settings.json';
$settings = [];
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true) ?? [];
}

$session_year = $settings['session_year'] ?? '2023/2024';
$max_file_size = $settings['max_file_size'] ?? '5';
$auto_approve = $settings['auto_approve'] ?? '0';
$enable_2fa = $settings['enable_2fa'] ?? false;
$session_timeout = $settings['session_timeout'] ?? true;
$login_notifications = $settings['login_notifications'] ?? true;
$maintenance_mode = $settings['maintenance_mode'] ?? false;
$last_backup_date = $settings['last_backup_date'] ?? 'Never';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();
    
    $action = $_POST['action'] ?? '';
    
    // Strict Access Boundaries: ONLY Super Admin is allowed to submit these specific actions
    if (in_array($action, ['change_admin_password', 'update_preferences', 'update_security', 'update_maintenance'])) {
        if ($user['matric_number'] !== 'SUPER_ADMIN') {
            header("HTTP/1.1 403 Forbidden");
            exit("Access Denied: Super Admin role required.");
        }
    }
    
    switch ($action) {
        case 'change_admin_password':
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
            $session_year = $_POST['session_year'] ?? '2023/2024';
            $max_file_size = $_POST['max_file_size'] ?? '5';
            $auto_approve = $_POST['auto_approve'] ?? '0';
            
            $settings['session_year'] = $session_year;
            $settings['max_file_size'] = $max_file_size;
            $settings['auto_approve'] = $auto_approve;
            
            if (file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT))) {
                $success = "System preferences updated successfully.";
            } else {
                $error = "Failed to save system preferences. Please check file permissions.";
            }
            break;
            
        case 'update_security':
            $enable_2fa = isset($_POST['enable_2fa']);
            $session_timeout = isset($_POST['session_timeout']);
            $login_notifications = isset($_POST['login_notifications']);
            
            $settings['enable_2fa'] = $enable_2fa;
            $settings['session_timeout'] = $session_timeout;
            $settings['login_notifications'] = $login_notifications;
            
            if (file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT))) {
                $success = "Security settings updated successfully.";
            } else {
                $error = "Failed to save security settings. Please check file permissions.";
            }
            break;

        case 'update_maintenance':
            $maintenance_mode = isset($_POST['maintenance_mode']);
            $settings['maintenance_mode'] = $maintenance_mode;
            
            if (file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT))) {
                $success = "System maintenance settings updated successfully.";
                require_once __DIR__ . '/../utils/SecurityUtils.php';
                SecurityUtils::logSecurityEvent('system_maintenance_toggled', [
                    'toggled_by' => $_SESSION['user_id'],
                    'maintenance_mode' => $maintenance_mode,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                ]);
            } else {
                $error = "Failed to save maintenance settings. Please check file permissions.";
            }
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