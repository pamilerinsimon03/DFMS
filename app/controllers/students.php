<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Notification.php';


$user = User::getById($_SESSION['user_id']);
$notifications = Notification::getForUser($_SESSION['user_id'], 15);
$unread_count = Notification::countUnread($_SESSION['user_id']);
$requested_dept = isset($_GET['department']) ? (int)$_GET['department'] : null;

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

if ($user['matric_number'] === 'SUPER_ADMIN' && isset($_GET['show_all']) && $_GET['show_all'] == '1') {
    $requested_dept = null; // Show all students
}

$students = User::getAllStudents($search, $page, $per_page, $requested_dept, $level, $mode, $program_type, $sort_by, $sort_order, $start_date, $end_date, $user);
$total = User::countAllStudents($search, $requested_dept, $level, $mode, $program_type, $start_date, $end_date, $user);

$total_pages = ceil($total / $per_page);

$page_title = 'Admin Students';
$active_page = 'students';
$css_files = ['admin.css', 'students.css', 'datepicker.css'];
$js_files = ['admin.js'];

if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    ob_start();
    require_once __DIR__ . '/../views/students.php';
    $html = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html, 
        'title' => $page_title,
        'css_files' => $css_files,
        'js_files' => $js_files
    ]);
    exit();
}

if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
    $html = '';
    
    if (empty($students)) {
        $html .= '<div class="no-students"><p>No students found matching your criteria.</p>';
        if ($user['department_id'] && $user['matric_number'] !== 'SUPER_ADMIN') {
            $html .= '<p>You are currently viewing students from your department only.</p>';
        } elseif ($user['matric_number'] === 'SUPER_ADMIN' && $requested_dept !== null) {
            $html .= '<p>You are currently viewing students from your department only. <a href="?' . http_build_query(array_merge($_GET, ['show_all' => '1'])) . '">View all students</a></p>';
        }
        $html .= '</div>';
    } else {
        foreach ($students as $student) {
            $department_html = $student['department_name'] ? '<p class="department-name">' . htmlspecialchars($student['department_name']) . '</p>' : '';
            $studentPublicId = htmlspecialchars($student['public_id'] ?? $student['id']);
            $html .= sprintf(
                '<a href="/app/controllers/student_profile.php?id=%s" class="student-card">
                    <img src="/app/src/assets/images/admin-avatar.png" alt="Student Avatar">
                    <h3>%s</h3>
                    <p>Matric No: %s</p>
                    <p>%s Level</p>
                    %s
                </a>',
                $studentPublicId,
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