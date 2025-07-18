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

if (User::isAdmin($user_id)) {
    $department_id = $user['department_id'] ?? null;
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    // Fetch stats based on department, ensuring they default to 0
    $total_docs = Document::countAllDocuments('all', $start_date, $end_date, null, $department_id, null, null, null) ?? 0;
    $pending_docs = Document::countAllDocuments('pending', $start_date, $end_date, null, $department_id, null, null, null) ?? 0;
    $approved_docs = Document::countAllDocuments('approved', $start_date, $end_date, null, $department_id, null, null, null) ?? 0;
    $rejected_docs = Document::countAllDocuments('rejected', $start_date, $end_date, null, $department_id, null, null, null) ?? 0;
    
    $recent_pending = Document::getPendingDocuments($start_date, $end_date, 5, $department_id) ?? [];
    
    $page_title = 'Admin Dashboard';
    $active_page = 'dashboard';
    $css_files = ['admin.css', 'dashboard.css'];
    $js_files = ['admin.js'];
    
    require_once __DIR__ . '/../views/partials/header.php';
    require_once __DIR__ . '/../views/partials/admin_sidebar.php';
    require_once __DIR__ . '/../views/admin.php'; // Admin dashboard view
    require_once __DIR__ . '/../views/partials/footer.php';
} else {
    // Student view remains the same
    $documents = Document::getByUserId($user_id);
    $total_docs = count($documents);
    $document_types = require __DIR__ . '/../config/document_types.php';
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
}