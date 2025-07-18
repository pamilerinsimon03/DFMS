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

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch KPI stats
$total_docs = Document::countAllDocuments(null, $start_date, $end_date, null, $department_id, null, null, null);
$approved_docs = Document::countAllDocuments('Approved', $start_date, $end_date, null, $department_id, null, null, null);
$pending_docs = Document::countAllDocuments('Pending Approval', $start_date, $end_date, null, $department_id, null, null, null);
$rejected_docs = Document::countAllDocuments('Rejected', $start_date, $end_date, null, $department_id, null, null, null);

// Fetch data for charts
$uploads_by_month = Document::getUploadsByMonth($start_date, $end_date, $department_id);
$verification_breakdown = Document::getVerificationBreakdown($start_date, $end_date, $department_id);

$months = array_keys($uploads_by_month);
$upload_counts = array_values($uploads_by_month);

$breakdown_labels = array_keys($verification_breakdown);
$breakdown_counts = array_values($verification_breakdown);

// Fetch recent pending documents for the table
$recent_pending = Document::getPendingDocuments($start_date, $end_date, 5, $department_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['doc_id'])) {
    Document::updateStatus($_POST['doc_id'], $_POST['action']);
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$page_title = 'Admin Dashboard';
$active_page = 'admin';
$css_files = ['admin.css', 'dashboard.css'];
$js_files = ['admin.js', 'https://cdn.jsdelivr.net/npm/chart.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/admin.php';
require_once __DIR__ . '/../views/partials/footer.php';