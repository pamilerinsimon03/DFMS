<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = User::getById($user_id);

$tab = isset($_GET['tab']) ? strtolower($_GET['tab']) : 'total';
$documents = Document::getByUserIdAndStatus($user_id, $tab);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reupload_id'])) {
    // Handle reupload logic here
    // ...
    header("Location: /app/controllers/uploads.php?tab=$tab");
    exit();
}

$page_title = 'My Uploads';
$active_page = 'uploads';
$css_files = ['uploads.css'];
$js_files = ['uploads.js', 'dashboard.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/uploads.php';
require_once __DIR__ . '/../views/partials/footer.php';