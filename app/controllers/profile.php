<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = User::getById($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $level = $_POST['level'];
    $programme = $_POST['programme'];
    $session = $_POST['session'];
    User::updateProfile($user_id, $full_name, $level, $programme, $session);
    $user = User::getById($user_id); // Refresh user data
    $_SESSION['full_name'] = $full_name;
    $success = "Profile updated successfully.";
}

$page_title = 'Profile';
$active_page = 'profile';

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/profile.php';
require_once __DIR__ . '/../views/partials/footer.php';