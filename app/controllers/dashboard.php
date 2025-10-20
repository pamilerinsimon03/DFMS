<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/FileRequirement.php';

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
    // Student view
    $search = $_GET['search'] ?? null;
    $documents = Document::getByUserId($user_id, $search);
    $notifications = Notification::getForUser($user_id, 15);
    $unread_count = Notification::countUnread($user_id);
    
    $total_docs = count(Document::getByUserId($user_id));
    $document_types = require __DIR__ . '/../config/document_types.php';
    
    // Get file requirements from database for this student's department
    $file_requirements = FileRequirement::getActiveForDepartment($user['department_id']);
    $required_docs = [];
    foreach ($file_requirements as $req) {
        $required_docs[$req['document_name']] = $req['due_date'];
    }
    $uploaded_docs = array_map(fn($doc) => $doc['document_name'], Document::getByUserId($user_id));

    if ($search) {
        $documents = array_filter($documents, function($doc) use ($search) {
            return stripos($doc['document_name'], $search) !== false;
        });
    }

    $page_title = 'Dashboard';
    $active_page = 'dashboard';
    $css_files = ['style.css', 'styleguide.css', 'dashboard.css'];
    $js_files = ['dashboard.js'];

    require_once __DIR__ . '/../views/partials/header.php';
    require_once __DIR__ . '/../views/partials/sidebar.php';
    require_once __DIR__ . '/../views/dashboard.php';
    require_once __DIR__ . '/../views/partials/footer.php';
}