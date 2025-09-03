<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user = User::getById($_SESSION['user_id']);
$message = '';
$message_type = '';

// Check if profile needs mandatory update (within 48 hours of account creation)
$account_created = strtotime($user['created_at']);
$current_time = time();
$hours_since_creation = ($current_time - $account_created) / 3600;
$requires_mandatory_update = $hours_since_creation <= 48;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $state_of_origin = trim($_POST['state_of_origin'] ?? '');
    $marital_status = trim($_POST['marital_status'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone = trim($_POST['emergency_contact_phone'] ?? '');
    $emergency_contact_relationship = trim($_POST['emergency_contact_relationship'] ?? '');
    
    // Validate required fields
    $required_fields = ['full_name', 'email', 'phone_number', 'date_of_birth', 'gender', 'state_of_origin', 'marital_status', 'address'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($$field)) {
            $missing_fields[] = ucfirst(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missing_fields)) {
        $message = 'Please fill in all required fields: ' . implode(', ', $missing_fields);
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Update profile
        $update_data = [
            'full_name' => $full_name,
            'email' => $email,
            'phone_number' => $phone_number,
            'date_of_birth' => $date_of_birth,
            'gender' => $gender,
            'state_of_origin' => $state_of_origin,
            'marital_status' => $marital_status,
            'address' => $address,
            'emergency_contact_name' => $emergency_contact_name,
            'emergency_contact_phone' => $emergency_contact_phone,
            'emergency_contact_relationship' => $emergency_contact_relationship,
            'profile_updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (User::updateStudentProfile($user['id'], $update_data)) {
            $message = 'Profile updated successfully!';
            $message_type = 'success';
            // Refresh user data
            $user = User::getById($_SESSION['user_id']);
            $requires_mandatory_update = false; // Profile has been updated
        } else {
            $message = 'Failed to update profile. Please try again.';
            $message_type = 'error';
        }
    }
}

// Page setup
$page_title = 'Profile';
$active_page = 'profile';
$css_files = ['dashboard.css', 'student_profile.css'];
$js_files = ['dashboard.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/student_profile.php';
require_once __DIR__ . '/../views/partials/footer.php';