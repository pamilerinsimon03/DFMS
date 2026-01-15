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

$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : '2020-01-01';
$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 50;
$level = $_GET['level'] ?? null;
$mode = $_GET['mode'] ?? null;
$program_type = $_GET['program_type'] ?? null;
$sort_by = $_GET['sort_by'] ?? 'full_name';
$sort_order = $_GET['sort_order'] ?? 'asc';

$filter_department_id = $department_id;
if ($user['matric_number'] === 'SUPER_ADMIN' && isset($_GET['show_all']) && $_GET['show_all'] == '1') {
    $filter_department_id = null; // Show all students
}

$students = User::getAllStudents($search, $page, $per_page, $filter_department_id, $level, $mode, $program_type, $sort_by, $sort_order, $start_date, $end_date);
$total = User::countAllStudents($search, $filter_department_id, $level, $mode, $program_type, $start_date, $end_date);

$total_pages = ceil($total / $per_page);

$page_title = 'Admin Students';
$active_page = 'students';
$css_files = ['admin.css', 'students.css', 'datepicker.css'];
$js_files = ['admin.js'];


if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
    $html = '';
    
    if (empty($students)) {
        $html .= '<div class="no-students"><p>No students found matching your criteria.</p>';
        if ($user['department_id'] && $user['matric_number'] !== 'SUPER_ADMIN') {
            $html .= '<p>You are currently viewing students from your department only.</p>';
        } elseif ($user['matric_number'] === 'SUPER_ADMIN' && $filter_department_id !== null) {
            $html .= '<p>You are currently viewing students from your department only. <a href="?' . http_build_query(array_merge($_GET, ['show_all' => '1'])) . '">View all students</a></p>';
        }
        $html .= '</div>';
    } else {
        foreach ($students as $student) {
            $department_html = $student['department_name'] ? '<p class="department-name">' . htmlspecialchars($student['department_name']) . '</p>' : '';
            $html .= sprintf(
                '<a href="/app/controllers/student_profile.php?id=%s" class="student-card">
                    <img src="/app/src/assets/images/admin-avatar.png" alt="Student Avatar">
                    <h3>%s</h3>
                    <p>Matric No: %s</p>
                    <p>%s Level</p>
                    %s
                </a>',
                $student['id'],
                htmlspecialchars($student['full_name']),
                htmlspecialchars($student['matric_number']),
                htmlspecialchars($student['level']),
                $department_html
            );
        }
    }

    $info_html = '';
    if (!empty($search)) {
        $info_html = '<div class="search-results-info">';
        $info_html .= '<span>' . $total . ' results found for "' . htmlspecialchars($search) . '"</span>';
        $info_html .= '<a href="students.php" class="clear-search">Clear search</a>';
        $info_html .= '</div>';
    }

    header('Content-Type: application/json');
    echo json_encode(['html' => $html, 'info_html' => $info_html]);
    exit;
}
require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/students.php';
require_once __DIR__ . '/../views/partials/footer.php';