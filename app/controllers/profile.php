<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = User::getById($user_id);
$error = '';
$success = '';

// Check if profile is complete and if it's been more than 48 hours since account creation
$profile_complete = !empty($user['email']) && !empty($user['phone_number']) && !empty($user['date_of_birth']) && 
                   !empty($user['gender']) && !empty($user['state_of_origin']) && !empty($user['address']) &&
                   !empty($user['emergency_contact_name']) && !empty($user['emergency_contact_phone']);

$account_age_hours = (time() - strtotime($user['created_at'])) / 3600;
$mandatory_update_required = $account_age_hours > 48 && !$profile_complete;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    requireCSRFToken();
    
    $action = $_POST['action'] ?? 'update_profile';
    
    if ($action === 'update_profile') {
        // Validate required fields
        $required_fields = ['full_name', 'email', 'phone_number', 'date_of_birth', 'gender', 
                           'state_of_origin', 'address', 'emergency_contact_name', 
                           'emergency_contact_phone', 'emergency_contact_relationship'];
        
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = ucfirst(str_replace('_', ' ', $field));
            }
        }
        
        if (!empty($missing_fields)) {
            $error = "Please fill in all required fields: " . implode(', ', $missing_fields);
        } else {
            // Include security utilities
            require_once __DIR__ . '/../utils/SecurityUtils.php';
            
            // Prepare profile data with validation
            $profile_data = [
                'full_name' => SecurityUtils::validateInput($_POST['full_name'] ?? '', 'string', 100),
                'email' => SecurityUtils::validateInput($_POST['email'] ?? '', 'email', 255),
                'phone_number' => SecurityUtils::validateInput($_POST['phone_number'] ?? '', 'string', 20),
                'date_of_birth' => SecurityUtils::validateInput($_POST['date_of_birth'] ?? '', 'string', 10),
                'gender' => SecurityUtils::validateInput($_POST['gender'] ?? '', 'string', 10),
                'state_of_origin' => SecurityUtils::validateInput($_POST['state_of_origin'] ?? '', 'string', 50),
                'marital_status' => SecurityUtils::validateInput($_POST['marital_status'] ?? 'Single', 'string', 20),
                'address' => SecurityUtils::validateInput($_POST['address'] ?? '', 'string', 500),
                'emergency_contact_name' => SecurityUtils::validateInput($_POST['emergency_contact_name'] ?? '', 'string', 100),
                'emergency_contact_phone' => SecurityUtils::validateInput($_POST['emergency_contact_phone'] ?? '', 'string', 20),
                'emergency_contact_relationship' => SecurityUtils::validateInput($_POST['emergency_contact_relationship'] ?? '', 'string', 50),
                'profile_updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update profile
            if (User::updateStudentProfile($user_id, $profile_data)) {
                $success = "Profile updated successfully!";
                $user = User::getById($user_id); // Refresh user data
                $_SESSION['full_name'] = $profile_data['full_name'];
                
                // Re-check if profile is now complete
                $profile_complete = true;
                $mandatory_update_required = false;
            } else {
                $error = "Failed to update profile. Please try again.";
            }
        }
    } elseif ($action === 'upload_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_photo'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error = "File size too large. Maximum size is 2MB.";
            } else {
                // Determine extension and target path
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $target_dir = __DIR__ . '/../src/assets/uploads/profile_pictures/';
                
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                // Remove old files for this user
                foreach (['jpg', 'jpeg', 'png'] as $old_ext) {
                    $old_file = $target_dir . "user_{$user_id}.{$old_ext}";
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $target_file = $target_dir . "user_{$user_id}." . $ext;
                
                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    $success = "Profile photo updated successfully!";
                } else {
                    $error = "Failed to upload photo. Please check permissions.";
                }
            }
        } else {
            $error = "No file selected or upload failed.";
        }
    }
}

$page_title = 'Profile';
$active_page = 'profile';
$css_files = ['profile.css', 'dashboard.css'];
$js_files = ['profile.js', 'dashboard.js'];

// Notifications for header dropdown
$notifications = Notification::getForUser($user_id, 15);
$unread_count = Notification::countUnread($user_id);

// SPA Request Handling
if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    ob_start();
    require_once __DIR__ . '/../views/profile.php';
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
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/profile.php';
require_once __DIR__ . '/../views/partials/footer.php';