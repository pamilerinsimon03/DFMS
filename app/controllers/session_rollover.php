<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/AccessControlMiddleware.php';
require_once __DIR__ . '/../services/SessionRollOverService.php';
require_once __DIR__ . '/../services/AcademicCalendarService.php';
require_once __DIR__ . '/../models/User.php';

AccessControlMiddleware::checkAccess();

$user = User::getById($_SESSION['user_id']);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'execute_rollover') {
    requireCSRFToken();

    $new_session = SecurityUtils::validateInput($_POST['new_session'] ?? '', 'string', 20);

    if (empty($new_session) || !preg_match('/^\d{4}\/\d{4}$/', $new_session)) {
        $message = "Invalid session format. Expected format: YYYY/YYYY (e.g., 2025/2026).";
        $message_type = "error";
    } else {
        $res = SessionRollOverService::executeRollOver($new_session, $_SESSION['user_id']);
        if ($res['success']) {
            $message = $res['message'];
            $message_type = "success";
        } else {
            $message = $res['message'];
            $message_type = "error";
        }
    }
}

global $conn;
$historyRes = $conn->query("SELECT sh.*, u.full_name, u.matric_number, d.name as department_name 
                             FROM session_history sh 
                             JOIN users u ON sh.user_id = u.id 
                             JOIN departments d ON sh.department_id = d.id 
                             ORDER BY sh.id DESC LIMIT 50");
$historyRecords = $historyRes ? $historyRes->fetch_all(MYSQLI_ASSOC) : [];

$currentSession = AcademicCalendarService::getCurrentSession();

$page_title = 'Academic Session Roll-Over';
$active_page = 'session_rollover';
$css_files = ['admin.css', 'analytics.css'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
?>

<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="welcome">
        <h1>Academic Session Roll-Over</h1>
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
        Transition the portal to a new academic year, advance student levels, and archive historical records.
    </p>

    <?php if ($message): ?>
        <div class="alert" style="padding:16px; border-radius:10px; font-size:14px; font-weight:500; background: <?= $message_type === 'success' ? '#e6f4ea' : '#fce8e6' ?>; color: <?= $message_type === 'success' ? '#137333' : '#c5221f' ?>; border: 1px solid <?= $message_type === 'success' ? '#ceead6' : '#fad2cf' ?>;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="glass-card" style="padding: 28px;">
        <h3 style="margin-top:0; color:#01017A; font-size: 18px; font-weight: 600; margin-bottom: 12px;">Initiate Session Roll-Over</h3>
        <p style="color:#475569; font-size:14px; line-height:1.6; margin-bottom: 16px;">
            Current Active Session: <strong style="color:#01017A; background: #e0e7ff; padding: 4px 10px; border-radius: 6px; font-weight: 600;"><?= htmlspecialchars($currentSession) ?></strong>
        </p>
        <p style="color: #475569; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Executing a session roll-over will perform the following actions:</p>
        <ul style="color:#64748b; font-size:14px; line-height:1.8; margin-bottom: 24px; padding-left: 20px;">
            <li>Create an archived snapshot record in <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #01017A;">session_history</code> for all current active students.</li>
            <li>Advance student academic levels (100L → 200L, 200L → 300L, 300L → 400L, 400L → 500L, 500L → Graduated).</li>
            <li>Update the system's active academic calendar session setting for new course registration.</li>
        </ul>

        <form method="POST" style="max-width: 480px;">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="execute_rollover">
            <div style="margin-bottom: 20px;">
                <label style="display:block; color:#334155; font-size:14px; font-weight:500; margin-bottom:8px;">Target New Academic Session:</label>
                <input type="text" name="new_session" placeholder="e.g. 2025/2026" required style="width:100%; padding:12px 16px; border-radius:8px; border:1px solid #cbd5e1; font-size:14px; outline:none; transition: border-color 0.2s;">
            </div>
            <button type="submit" onclick="return confirm('Are you sure you want to roll over the academic session? This will advance all active student levels.');" style="background:#01017A; color:#fff; border:none; padding:12px 28px; border-radius:8px; font-weight:600; cursor:pointer; font-size: 14px; box-shadow: 0 4px 12px rgba(1, 1, 122, 0.25); transition: background 0.2s;">
                Execute Roll-Over
            </button>
        </form>
    </div>

    <div class="glass-card" style="padding: 28px;">
        <h3 style="margin-top:0; color:#01017A; font-size: 18px; font-weight: 600; margin-bottom: 16px;">Recent Archived Session Records</h3>
        <?php if (empty($historyRecords)): ?>
            <p style="color:#64748b; font-size:14px; padding: 12px 0;">No session roll-over history records found in the database archive.</p>
        <?php else: ?>
            <div class="table-wrapper" style="overflow-x:auto;">
                <table class="docs-table" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Matric Number</th>
                            <th>Department</th>
                            <th>Archived Session</th>
                            <th>Level at Archive</th>
                            <th>Date Archived</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historyRecords as $rec): ?>
                            <tr>
                                <td><?= htmlspecialchars($rec['full_name']) ?></td>
                                <td style="font-family:monospace;"><?= htmlspecialchars($rec['matric_number']) ?></td>
                                <td><?= htmlspecialchars($rec['department_name']) ?></td>
                                <td><span style="background: #e0e7ff; color: #01017A; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;"><?= htmlspecialchars($rec['session']) ?></span></td>
                                <td><?= htmlspecialchars($rec['level']) ?></td>
                                <td style="color: #64748b;"><?= date('M d, Y H:i', strtotime($rec['archived_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../views/partials/footer.php'; ?>
