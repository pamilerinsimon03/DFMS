<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user = User::getById($_SESSION['user_id']);
$department_id = $user['department_id'] ?? null;

// --- Parameters for Filtering and Sorting ---
$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : '2020-01-01'; // Use a much earlier date to include all students
$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 50;
$level = $_GET['level'] ?? null;
$mode = $_GET['mode'] ?? null;
$program_type = $_GET['program_type'] ?? null;
$sort_by = $_GET['sort_by'] ?? 'full_name';
$sort_order = $_GET['sort_order'] ?? 'asc';

// For SUPER_ADMIN (department_id = NULL), show all students from all departments
// For department admins, show only students from their department
// Allow SUPER_ADMIN to override with show_all parameter
$filter_department_id = $department_id;
if ($user['matric_number'] === 'SUPER_ADMIN' && isset($_GET['show_all']) && $_GET['show_all'] == '1') {
    $filter_department_id = null; // Show all students
}

// --- Fetch Data from Model ---
$students = User::getAllStudents($search, $page, $per_page, $filter_department_id, $level, $mode, $program_type, $sort_by, $sort_order, $start_date, $end_date);
$total = User::countAllStudents($search, $filter_department_id, $level, $mode, $program_type, $start_date, $end_date);

// --- Pagination ---
$total_pages = ceil($total / $per_page);

// --- Page Setup ---
$page_title = 'Admin Students';
$active_page = 'students';
$css_files = ['admin.css', 'students.css', 'datepicker.css'];
$js_files = ['admin.js'];

// --- Render View ---
require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/students.php';
require_once __DIR__ . '/../views/partials/footer.php';