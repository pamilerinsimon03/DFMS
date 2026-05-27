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

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 months'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$total_docs = Document::countAllDocuments(null, $start_date, $end_date, null, $department_id, null, null, null);
$approved_docs = Document::countAllDocuments('Approved', $start_date, $end_date, null, $department_id, null, null, null);
$pending_docs = Document::countAllDocuments('Pending Approval', $start_date, $end_date, null, $department_id, null, null, null);
$rejected_docs = Document::countAllDocuments('Rejected', $start_date, $end_date, null, $department_id, null, null, null);

if ($total_docs == 0) {
    $all_docs_sql = "SELECT COUNT(d.id) as total FROM documents d JOIN users u ON d.user_id = u.id";
    $params = [];
    $types = '';
    
    if ($department_id) {
        $all_docs_sql .= " WHERE u.department_id = ?";
        $params[] = $department_id;
        $types = 'i';
    }
    
    global $conn;
    $stmt = $conn->prepare($all_docs_sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $all_docs_count = $stmt->get_result()->fetch_assoc()['total'];
    
    if ($all_docs_count > 0) {
        $date_range_sql = "SELECT MIN(d.created_at) as earliest, MAX(d.created_at) as latest FROM documents d JOIN users u ON d.user_id = u.id";
        if ($department_id) {
            $date_range_sql .= " WHERE u.department_id = ?";
        }
        
        $stmt = $conn->prepare($date_range_sql);
        if ($department_id) {
            $stmt->bind_param("i", $department_id);
        }
        $stmt->execute();
        $date_range = $stmt->get_result()->fetch_assoc();
        
        $start_date = date('Y-m-d', strtotime($date_range['earliest']));
        $end_date = date('Y-m-d', strtotime($date_range['latest']));
        
        $total_docs = Document::countAllDocuments(null, $start_date, $end_date, null, $department_id, null, null, null);
        $approved_docs = Document::countAllDocuments('Approved', $start_date, $end_date, null, $department_id, null, null, null);
        $pending_docs = Document::countAllDocuments('Pending Approval', $start_date, $end_date, null, $department_id, null, null, null);
        $rejected_docs = Document::countAllDocuments('Rejected', $start_date, $end_date, null, $department_id, null, null, null);
    }
}

$uploads_by_month = Document::getUploadsByMonth($start_date, $end_date, $department_id);
$verification_breakdown = Document::getVerificationBreakdown($start_date, $end_date, $department_id);

$months = array_keys($uploads_by_month);
$upload_counts = array_values($uploads_by_month);

$breakdown_labels = array_keys($verification_breakdown);
$breakdown_counts = array_values($verification_breakdown);

$recent_pending = Document::getPendingDocuments($start_date, $end_date, 5, $department_id);

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
    
    Document::updateStatus($doc_id, $action);
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