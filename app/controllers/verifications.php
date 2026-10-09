<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Notification.php';


$user = User::getById($_SESSION['user_id']);
$notifications = Notification::getForUser($_SESSION['user_id'], 15);
$unread_count = Notification::countUnread($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['doc_id'])) {
    // Validate CSRF token
    requireCSRFToken();
    
    Document::updateStatus($_POST['doc_id'], $_POST['action'], $_POST['rejection_reason'] ?? null, $user);
    $url = "/app/controllers/verifications.php?" . http_build_query($_GET);
    header("Location: $url");
    exit();
}

$tab = $_GET['tab'] ?? 'all';
$start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : '2025-01-01';
$end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 50;
$level = $_GET['level'] ?? null;
$requested_dept = isset($_GET['department']) ? (int)$_GET['department'] : null;
$mode = $_GET['mode'] ?? null;
$program_type = $_GET['program_type'] ?? null;
$sort_by = $_GET['sort_by'] ?? 'upload_date';
$sort_order = $_GET['sort_order'] ?? 'desc';

$status_map = [
    'all' => null, // No status filter
    'pending' => 'Pending Approval',
    'approved' => 'Approved',
    'rejected' => 'Rejected'
];
$status_filter = $status_map[$tab] ?? null;

$sort_links = [];
$sort_indicators = [];
$columns_to_sort = ['full_name', 'department', 'level', 'upload_date'];

foreach ($columns_to_sort as $column) {
    $current_order = ($sort_by === $column && $sort_order === 'asc') ? 'desc' : 'asc';
    $query_params = array_merge($_GET, ['sort_by' => $column, 'sort_order' => $current_order]);
    $sort_links[$column] = '?' . http_build_query($query_params);
    
    if ($sort_by === $column) {
        $sort_indicators[$column] = ($sort_order === 'asc') ? ' ▲' : ' ▼';
    } else {
        $sort_indicators[$column] = '';
    }
}

$documents = Document::getAllDocuments($status_filter, $start_date, $end_date, $search, $page, $per_page, $requested_dept, $level, $mode, $program_type, $sort_by, $sort_order, $user);
$total = Document::countAllDocuments($status_filter, $start_date, $end_date, $search, $requested_dept, $level, $mode, $program_type, $user);

$total_pages = ceil($total / $per_page);

$page_title = 'Admin Verifications';
$active_page = 'verifications';
$css_files = ['admin.css', 'uploads.css'];
$js_files = ['admin_verifications.js', 'admin.js'];

if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    ob_start();
    require_once __DIR__ . '/../views/verifications.php';
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

if (isset($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
    ob_start();
    if (empty($documents)): ?>
        <tr><td colspan="7">No documents found.</td></tr>
    <?php else:
        foreach ($documents as $index => $doc): 
            $sn = ($page - 1) * $per_page + $index + 1;
            $status_slug = strtolower(str_replace(' ', '-', $doc['status']));
        ?>
        <tr>
            <td><?php echo $sn; ?>.</td>
            <td><?php echo htmlspecialchars($doc['full_name']); ?></td>
            <td><?php echo htmlspecialchars($doc['department'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($doc['level']); ?></td>
            <td><a href="/app/controllers/verification_detail.php?doc_id=<?php echo htmlspecialchars($doc['public_id'] ?? $doc['id']); ?>" class="doc-link"><?php echo htmlspecialchars($doc['document_name']); ?></a></td>
            <td><?php echo date('M d, Y', strtotime($doc['upload_date'])); ?></td>
            <td class="td-status <?php echo $status_slug; ?>">
                <div class="status-wrapper">
                    <span class="status-dot"></span>
                    <span class="status-text"><?php echo htmlspecialchars($doc['status']); ?></span>
                </div>
            </td>
            <td class="td-action">
                <div class="action-menu">
                    <img src="/app/src/assets/images/more.svg" alt="More" class="action-btn">
                    <div class="action-menu-content">
                        <a href="/app/controllers/verification_detail.php?doc_id=<?php echo htmlspecialchars($doc['public_id'] ?? $doc['id']); ?>" class="action-item">
                            <img src="/app/src/assets/images/Bold Eye.svg" alt="View"> View
                        </a>
                    </div>
                </div>
            </td>
        </tr>
    <?php endforeach;
    endif;
    $html = ob_get_clean();

    ob_start();
    if (!empty($search)): ?>
        <div class="search-results-info">
            <span class="results-count"><?php echo number_format($total); ?> result<?php echo $total !== 1 ? 's' : ''; ?> found</span>
            <span class="search-term">for "<?php echo htmlspecialchars($search); ?>"</span>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="clear-search">Clear search</a>
        </div>
    <?php endif;
    $info_html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html,
        'info_html' => $info_html
    ]);
    exit();
}

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/verifications.php';
require_once __DIR__ . '/../views/partials/footer.php';