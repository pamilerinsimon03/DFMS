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
    }
}

$page_title = 'Profile';
$active_page = 'profile';
$css_files = ['profile.css', 'dashboard.css'];
$js_files = ['profile.js', 'dashboard.js'];

// Notifications for header dropdown
$notifications = Notification::getForUser($user_id, 15);
$unread_count = Notification::countUnread($user_id);

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/profile.php';
require_once __DIR__ . '/../views/partials/footer.php';