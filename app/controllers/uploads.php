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
// but you’d compare against the _lowercased_ real statuses:
$documents = Document::getByUserIdAndStatus($user_id, $tab);
$search = $_GET['search'] ?? null;

// Fetch documents based on status and search term
if ($tab === 'total') {
    $documents = Document::getByUserId($user_id, $search);
} else {
    $all_documents = Document::getByUserIdAndStatus($user_id, $tab);
    if ($search) {
        $documents = array_filter($all_documents, function($doc) use ($search) {
            return stripos($doc['document_name'], $search) !== false;
        });
    } else {
        $documents = $all_documents;
    }
}


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