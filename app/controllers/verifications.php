<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user = User::getById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['doc_id'])) {
    Document::updateStatus($_POST['doc_id'], $_POST['action']);
    $url = "/app/controllers/verifications.php?" . http_build_query($_GET);
    header("Location: $url");
    exit();
}

// --- Parameters for Filtering and Sorting ---
$tab = $_GET['tab'] ?? 'all';
$start_date = $_GET['start_date'] ?? '1970-01-01';
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 50;
$level = $_GET['level'] ?? null;
$department_id = $user['department_id'] ?? ($_GET['department'] ?? null); // Use user's dept or query param
$mode = $_GET['mode'] ?? null;
$program_type = $_GET['program_type'] ?? null;
$sort_by = $_GET['sort_by'] ?? 'upload_date';
$sort_order = $_GET['sort_order'] ?? 'desc';

// --- Status Mapping ---
// Maps the 'tab' parameter from the URL to the actual status string in the database.
$status_map = [
    'all' => null, // No status filter
    'pending' => 'Pending Approval',
    'approved' => 'Approved',
    'rejected' => 'Rejected'
];
$status_filter = $status_map[$tab] ?? null;

// --- Prepare Sorting Links and Indicators for the View ---
$sort_links = [];
$sort_indicators = [];
$columns_to_sort = ['full_name', 'department', 'level', 'upload_date'];

foreach ($columns_to_sort as $column) {
    $current_order = ($sort_by === $column && $sort_order === 'asc') ? 'desc' : 'asc';
    $query_params = array_merge($_GET, ['sort_by' => $column, 'sort_order' => $current_order]);
    $sort_links[$column] = '?' . http_build_query($query_params);
    
    if ($sort_by === $column) {
        $sort_indicators[$column] = ($sort_order === 'asc') ? ' ▲' : ' ▼';
    } else {
        $sort_indicators[$column] = '';
    }
}

// --- Fetch Data from Model ---
$documents = Document::getAllDocuments($status_filter, $start_date, $end_date, $search, $page, $per_page, $department_id, $level, $mode, $program_type, $sort_by, $sort_order);
$total = Document::countAllDocuments($status_filter, $start_date, $end_date, $search, $department_id, $level, $mode, $program_type);

// --- Pagination ---
$total_pages = ceil($total / $per_page);

// --- Page Setup ---
$page_title = 'Admin Verifications';
$active_page = 'verifications';
$css_files = ['admin.css', 'uploads.css'];
$js_files = ['admin_verifications.js', 'admin.js'];

// --- Render View ---
require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/verifications.php';
require_once __DIR__ . '/../views/partials/footer.php';