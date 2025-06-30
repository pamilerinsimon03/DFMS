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
$documents = Document::getByUserId($user_id);

$total_docs = count($documents);
$required_docs = [
    'Admission Letter' => '2025-06-10',
    'Course Registration Form' => '2025-06-10',
    'NACOSS Receipt' => '2025-06-10',
    'Faculty Registration Receipt' => '2025-06-10',
    'Medical Laboratory Receipt' => '2025-06-10',
    'Hostel Accommodation' => '2025-06-10'
];
$uploaded_docs = array_map(fn($doc) => $doc['document_name'], $documents);

$page_title = 'Dashboard';
$active_page = 'dashboard';
$css_files = ['style.css', 'styleguide.css', 'dashboard.css'];
$js_files = ['dashboard.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/dashboard.php';
require_once __DIR__ . '/../views/partials/footer.php';