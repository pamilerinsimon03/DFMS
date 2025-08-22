<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: /app/controllers/students.php");
    exit();
}

$student_id = $_GET['id'];
$student = User::getById($student_id);
$documents = Document::getDocumentsByUserId($student_id);

$user = User::getById($_SESSION['user_id']);

$active_page = 'students';
$css_files = ['admin.css', 'student_profile.css'];
$js_files = ['admin.js', 'student_profile.js'];

include_once('../views/partials/header.php');
include_once('../views/partials/admin_sidebar.php');
include_once('../views/student_profile.php');
include_once('../views/partials/footer.php');