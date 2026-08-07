<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/AccessControlMiddleware.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';
require_once __DIR__ . '/../models/User.php';

AccessControlMiddleware::checkAccess();

$user = User::getById($_SESSION['user_id']);

global $conn;

$rawSearch = $_GET['search'] ?? '';
$rawEventType = $_GET['event_type'] ?? '';

$search = is_string($rawSearch) ? trim($rawSearch) : '';
$event_type = is_string($rawEventType) ? trim($rawEventType) : '';

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$per_page = 30;
$offset = ($page - 1) * $per_page;

$sql = "SELECT a.*, u.full_name, u.matric_number FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE 1=1";
$params = [];
$types = '';

if ($event_type !== '') {
    $sql .= " AND a.event_type = ?";
    $params[] = $event_type;
    $types .= 's';
}

if ($search !== '') {
    $sql .= " AND (a.event_description LIKE ? OR a.ip_address LIKE ? OR u.full_name LIKE ? OR u.matric_number LIKE ?)";
    $sp = "%{$search}%";
    $params[] = $sp;
    $params[] = $sp;
    $params[] = $sp;
    $params[] = $sp;
    $types .= 'ssss';
}

$sql .= " ORDER BY a.id DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count total
$countSql = "SELECT COUNT(*) as total FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE 1=1";
$cParams = [];
$cTypes = '';

if ($event_type !== '') {
    $countSql .= " AND a.event_type = ?";
    $cParams[] = $event_type;
    $cTypes .= 's';
}

if ($search !== '') {
    $countSql .= " AND (a.event_description LIKE ? OR a.ip_address LIKE ? OR u.full_name LIKE ? OR u.matric_number LIKE ?)";
    $sp = "%{$search}%";
    $cParams[] = $sp;
    $cParams[] = $sp;
    $cParams[] = $sp;
    $cParams[] = $sp;
    $cTypes .= 'ssss';
}

$cStmt = $conn->prepare($countSql);
if ($cTypes) {
    $cStmt->bind_param($cTypes, ...$cParams);
}
$cStmt->execute();
$totalLogs = $cStmt->get_result()->fetch_assoc()['total'] ?? 0;
$totalPages = max(1, ceil($totalLogs / $per_page));

$page_title = 'Security & Audit Logs';
$active_page = 'audit_log_viewer';
$css_files = ['admin.css', 'analytics.css'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
?>

<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="welcome">
        <h1>Security & Compliance Audit Logs</h1>
    </div>
    <div class="header-right">
        <?php include __DIR__ . '/../views/partials/notification_bell.php'; ?>
        <div class="user-profile">
            <img src="<?php echo User::getProfilePicture($user['id']); ?>" alt="User Profile" class="avatar" style="object-fit:cover;">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="user-id">Role: Super Admin</div>
            </div>
            <div class="dropdown-icon">
                <img src="/app/src/assets/images/arrow-down.svg" alt="Dropdown Icon">
            </div>
            <div class="profile-dropdown">
                <a href="/app/controllers/student_profile.php" class="dropdown-item">
                    <img src="/app/src/assets/images/User-Circle.svg" alt="Profile"> Profile
                </a>
                <a href="/app/controllers/settings.php" class="dropdown-item">
                    <img src="/app/src/assets/images/settings.svg" alt="Settings"> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="/app/controllers/logout.php" class="dropdown-item logout">
                    <img src="/app/src/assets/images/log-out.svg" alt="Logout"> Log out
                </a>
            </div>
        </div>
    </div>
</div>
<div class="header-divider"></div>

<div class="analytics-container">
    <p style="color: #64748b; font-size: 14px; margin-top: -12px; margin-bottom: 4px;">
        System-wide audit trail of security events, administrative actions, and access attempts (NDPR compliance).
    </p>

    <div class="glass-card" style="padding: 24px;">
        <form method="GET" style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:220px;">
                <label style="display:block; color:#475569; font-size:13px; font-weight:500; margin-bottom:6px;">Search Logs</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="IP Address, User, Description..." style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; color:#1e293b; font-size:14px; outline:none;">
            </div>
            <div style="width:220px;">
                <label style="display:block; color:#475569; font-size:13px; font-weight:500; margin-bottom:6px;">Filter Event Type</label>
                <input type="text" name="event_type" value="<?= htmlspecialchars($event_type) ?>" placeholder="e.g. failed_login, upload_success" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; color:#1e293b; font-size:14px; outline:none;">
            </div>
            <button type="submit" style="background:#01017A; color:#fff; border:none; padding:10px 24px; border-radius:8px; font-weight:600; cursor:pointer; height:42px; font-size:14px; box-shadow:0 3px 10px rgba(1,1,122,0.2);">Filter Logs</button>
            <a href="/app/controllers/audit_log_viewer.php" style="color:#64748b; text-decoration:none; line-height:42px; font-size:14px; padding:0 8px;">Reset</a>
        </form>
    </div>

    <div class="glass-card" style="padding: 24px;">
        <div class="table-wrapper" style="overflow-x:auto;">
            <table class="docs-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Event Type</th>
                        <th>IP Address</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6" style="padding:24px; text-align:center; color:#64748b;">No audit log records matching filter criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td style="font-family:monospace; color:#64748b;"><?= $log['id'] ?></td>
                                <td style="white-space:nowrap; color:#334155;"><?= htmlspecialchars($log['created_at']) ?></td>
                                <td><?= $log['full_name'] ? htmlspecialchars($log['full_name']) . " (" . htmlspecialchars($log['matric_number']) . ")" : "<span style='color:#94a3b8; font-style:italic;'>System / Unauth</span>" ?></td>
                                <td><span style="background:#e0e7ff; color:#01017A; padding:4px 10px; border-radius:6px; font-size:12px; font-weight:600; font-family:monospace;"><?= htmlspecialchars($log['event_type']) ?></span></td>
                                <td style="font-family:monospace; color:#475569;"><?= htmlspecialchars($log['ip_address'] ?: 'N/A') ?></td>
                                <td style="word-break:break-all; max-width:400px; color:#334155;"><?= htmlspecialchars($log['event_description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; color:#64748b; font-size:13px;">
                <div>Showing Page <?= $page ?> of <?= $totalPages ?> (Total Records: <?= $totalLogs ?>)</div>
                <div style="display:flex; gap:8px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&event_type=<?= urlencode($event_type) ?>" style="color:#01017A; font-weight:600; text-decoration:none; padding:6px 14px; border:1px solid #cbd5e1; border-radius:6px; background:#f8fafc;">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&event_type=<?= urlencode($event_type) ?>" style="color:#01017A; font-weight:600; text-decoration:none; padding:6px 14px; border:1px solid #cbd5e1; border-radius:6px; background:#f8fafc;">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../views/partials/footer.php'; ?>
