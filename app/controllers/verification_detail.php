<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user = User::getById($_SESSION['user_id']);
$doc_id = (int)$_GET['doc_id'];
$doc = Document::getById($doc_id);

if (!$doc) {
    header("Location: /app/controllers/verifications.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    Document::updateStatus($doc_id, $_POST['action']);
    header("Location: /app/controllers/verifications.php");
    exit();
}

$page_title = 'Verification Detail';
$active_page = 'verifications';
$css_files = ['admin.css', 'uploads.css', 'verifications_detail.css'];
$js_files = ['verification_detail.js', 'admin.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/verification_detail.php';
require_once __DIR__ . '/../views/partials/footer.php';