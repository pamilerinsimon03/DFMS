<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Notification.php';


if (!isset($_GET['id'])) {
    header("Location: /app/controllers/students.php");
    exit();
}

$rawStudentId = $_GET['id'];
$user = User::getById($_SESSION['user_id']);
$student = User::getByIdScoped($rawStudentId, $user);

if (!$student) {
    header("Location: /app/controllers/students.php");
    exit();
}

$notifications = Notification::getForUser($_SESSION['user_id'], 15);
$unread_count = Notification::countUnread($_SESSION['user_id']);

$documents = Document::getDocumentsByUserId((int)$student['id']);

$active_page = 'students';
$css_files = ['admin.css', 'student_profile.css'];
$js_files = ['admin.js', 'student_profile.js'];

include_once('../views/partials/header.php');
include_once('../views/partials/admin_sidebar.php');
include_once('../views/student_profile.php');
include_once('../views/partials/footer.php');