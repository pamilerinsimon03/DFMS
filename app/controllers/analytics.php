<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../services/AuditService.php';


$user = User::getById($_SESSION['user_id']);
$notifications = Notification::getForUser($_SESSION['user_id'], 15);
$unread_count = Notification::countUnread($_SESSION['user_id']);
$is_super_admin = ($user['matric_number'] === 'SUPER_ADMIN');
$requested_dept = isset($_GET['department']) ? (int)$_GET['department'] : null;

// 1. Fetch Total Students Scoped properly (fixing argument order and passing $user)
$total_students = User::countAllStudents('', $requested_dept, null, null, null, null, null, $user);

// 2. Fetch Document status breakdown for the past year
$start_date = date('Y-m-d', strtotime('-1 year'));
$end_date = date('Y-m-d');
$breakdown = Document::getVerificationBreakdown($start_date, $end_date, $requested_dept, $user);

$approved_count = $breakdown['Approved'] ?? 0;
$pending_count = ($breakdown['Pending Quality Check'] ?? 0) + ($breakdown['Pending Approval'] ?? 0);
$rejected_count = $breakdown['Rejected'] ?? 0;
$total_uploads = $approved_count + $pending_count + $rejected_count;

// 3. Fetch Monthly uploads for the past 6 months
$trend_start = date('Y-m-d', strtotime('-6 months'));
$trend_end = date('Y-m-d');
$monthly_uploads = Document::getUploadsByMonth($trend_start, $trend_end, $requested_dept, $user);

// Prepare labels and datasets for Chart.js
$months_list = [];
$monthly_data = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $display_m = date('F Y', strtotime("-$i months"));
    $months_list[] = $display_m;
    $monthly_data[] = $monthly_uploads[$m] ?? 0;
}

// 4. Fetch Audit Logs for Security Panel (Super Admin ONLY)
$audit_logs = [];
if ($is_super_admin) {
    $audit_logs = AuditService::getRecentEvents(10);
}

$page_title = 'Analytics';
$active_page = 'analytics';
$css_files = ['admin.css', 'analytics.css'];
$js_files = ['admin.js'];

if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    ob_start();
    require_once __DIR__ . '/../views/analytics.php';
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
require_once __DIR__ . '/../views/analytics.php';
require_once __DIR__ . '/../views/partials/footer.php';
?>
