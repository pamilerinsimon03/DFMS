<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Notification.php';


$user = User::getById($_SESSION['user_id']);
$notifications = Notification::getForUser($_SESSION['user_id'], 15);
$unread_count = Notification::countUnread($_SESSION['user_id']);
$requested_dept = isset($_GET['department']) ? (int)$_GET['department'] : null;

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 months'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$total_docs = Document::countAllDocuments(null, $start_date, $end_date, null, $requested_dept, null, null, null, $user);
$approved_docs = Document::countAllDocuments('Approved', $start_date, $end_date, null, $requested_dept, null, null, null, $user);
$pending_docs = Document::countAllDocuments('Pending Approval', $start_date, $end_date, null, $requested_dept, null, null, null, $user);
$rejected_docs = Document::countAllDocuments('Rejected', $start_date, $end_date, null, $requested_dept, null, null, null, $user);

if ($total_docs == 0) {
    require_once __DIR__ . '/../utils/AccessScope.php';
    $scope = AccessScope::buildDocumentScopeFilter($user, $requested_dept, 'u.');
    $all_docs_sql = "SELECT COUNT(d.id) as total FROM documents d JOIN users u ON d.user_id = u.id WHERE " . $scope['sql'];
    $params = $scope['params'];
    $types = $scope['types'];
    
    global $conn;
    $stmt = $conn->prepare($all_docs_sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $all_docs_count = $stmt->get_result()->fetch_assoc()['total'];
    
    if ($all_docs_count > 0) {
        $date_range_sql = "SELECT MIN(d.created_at) as earliest, MAX(d.created_at) as latest FROM documents d JOIN users u ON d.user_id = u.id WHERE " . $scope['sql'];
        $stmt = $conn->prepare($date_range_sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $date_range = $stmt->get_result()->fetch_assoc();
        
        $start_date = date('Y-m-d', strtotime($date_range['earliest']));
        $end_date = date('Y-m-d', strtotime($date_range['latest']));
        
        $total_docs = Document::countAllDocuments(null, $start_date, $end_date, null, $requested_dept, null, null, null, $user);
        $approved_docs = Document::countAllDocuments('Approved', $start_date, $end_date, null, $requested_dept, null, null, null, $user);
        $pending_docs = Document::countAllDocuments('Pending Approval', $start_date, $end_date, null, $requested_dept, null, null, null, $user);
        $rejected_docs = Document::countAllDocuments('Rejected', $start_date, $end_date, null, $requested_dept, null, null, null, $user);
    }
}

$uploads_by_month = Document::getUploadsByMonth($start_date, $end_date, $requested_dept, $user);
$verification_breakdown = Document::getVerificationBreakdown($start_date, $end_date, $requested_dept, $user);

$months = array_keys($uploads_by_month);
$upload_counts = array_values($uploads_by_month);

$breakdown_labels = array_keys($verification_breakdown);
$breakdown_counts = array_values($verification_breakdown);

$recent_pending = Document::getPendingDocuments($start_date, $end_date, 5, $requested_dept, $user);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['doc_id'])) {
    requireCSRFToken();
    
    require_once __DIR__ . '/../utils/SecurityUtils.php';
    
    $doc_id = $_POST['doc_id'];
    $action = $_POST['action'];
    
    SecurityUtils::logSecurityEvent('document_status_change', [
        'user_id' => $_SESSION['user_id'],
        'doc_id' => $doc_id,
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    Document::updateStatus($doc_id, $action, $_POST['rejection_reason'] ?? null, $user);
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

$page_title = 'Admin Dashboard';
$active_page = 'admin';
$css_files = ['admin.css', 'dashboard.css'];
$js_files = ['admin.js', 'https://cdn.jsdelivr.net/npm/chart.js'];


if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    ob_start();
    require_once __DIR__ . '/../views/admin.php';
    $html = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html, 
        'title' => $page_title,
        'css_files' => $css_files,
        'js_files' => $js_files
    ]);
    exit();
}

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/admin.php';
require_once __DIR__ . '/../views/partials/footer.php';