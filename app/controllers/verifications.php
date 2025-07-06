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

$tab = $_GET['tab'] ?? 'all';
$start_date = $_GET['start_date'] ?? '1970-01-01';
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 50;

$documents = Document::getAllDocuments($tab, $start_date, $end_date, $search, $page, $per_page);
$total = Document::countAllDocuments($tab, $start_date, $end_date, $search);
if ($tab === 'rejected') {
    // temporary debugging: log or dump
    error_log("TAB=REJECTED → fetched ". count($documents) ." rows");
    // or, to see it in the HTML:
    echo "<pre>DEBUG REJECTED: ". htmlentities(print_r($documents, true)) ."</pre>";
}
$total_pages = ceil($total / $per_page);

$page_title = 'Admin Verifications';
$active_page = 'verifications';
$css_files = ['admin.css', 'uploads.css'];
$js_files = ['admin_verifications.js', 'admin.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/verifications.php';
require_once __DIR__ . '/../views/partials/footer.php';