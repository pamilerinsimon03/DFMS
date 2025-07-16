<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user = User::getById($_SESSION['user_id']);

$start_date = $_GET['start_date'] ?? '1970-01-01';
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$total_docs = Document::countByDateRange($start_date, $end_date);
$verified_docs = Document::countByDateRangeAndStatus($start_date, $end_date, 'Approved');
$pending_docs = Document::countByDateRangeAndStatus($start_date, $end_date, 'Pending Approval');

$uploads_data = Document::getUploadsByMonth($start_date, $end_date);
$months = array_keys($uploads_data);
$upload_counts = array_values($uploads_data);

$breakdown_data = Document::getVerificationBreakdown($start_date, $end_date);
$statuses = ['Pending Approval', 'Approved', 'Rejected'];
$breakdown_counts = array_map(fn($status) => $breakdown_data[$status] ?? 0, $statuses);

$documents = Document::getPendingDocuments($start_date, $end_date, 5);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['doc_id'])) {
    Document::updateStatus($_POST['doc_id'], $_POST['action']);
    header("Location: /app/controllers/admin.php?start_date=$start_date&end_date=$end_date");
    exit();
}

$page_title = 'Admin Dashboard';
$active_page = 'admin';
$css_files = ['admin.css'];
$js_files = ['admin.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/admin.php';
require_once __DIR__ . '/../views/partials/footer.php';