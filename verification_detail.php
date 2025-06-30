<?php
require_once 'config.php';

// Prevent redirect loop
if (isset($_SESSION['redirect_count']) && $_SESSION['redirect_count'] > 5) {
    file_put_contents('debug.txt', "Redirect loop detected: User ID {$_SESSION['user_id']}\n", FILE_APPEND);
    die("Error: Too many redirects. Please contact support.");
}
$_SESSION['redirect_count'] = isset($_SESSION['redirect_count']) ? $_SESSION['redirect_count'] + 1 : 1;

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    file_put_contents('debug.txt', "No session user_id\n", FILE_APPEND);
    $_SESSION['redirect_count'] = 0;
    header("Location: index.php");
    exit();
}

// Debug: Log session
file_put_contents('debug.txt', "Verification Detail ID: {$_SESSION['user_id']} (Type: " . gettype($_SESSION['user_id']) . ")\n", FILE_APPEND);

// Check admin status
$user_id = (int)$_SESSION['user_id'];
$sql = "SELECT full_name, is_admin FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    file_put_contents('debug.txt', "Prepare failed: " . $conn->error . "\n", FILE_APPEND);
    $_SESSION['error'] = "Database error. Please contact support.";
    $_SESSION['redirect_count'] = 0;
    header("Location: index.php");
    exit();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || (!$user['is_admin'] && $user_id != 1)) {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    file_put_contents('debug.txt', "Access Denied: User ID $user_id, is_admin: " . ($user['is_admin'] ?? 'N/A') . "\n", FILE_APPEND);
    $_SESSION['redirect_count'] = 0;
    header("Location: dashboard.php");
    exit();
}

// Reset redirect count
$_SESSION['redirect_count'] = 0;

// Get document ID
$doc_id = isset($_GET['doc_id']) ? (int)$_GET['doc_id'] : 0;
if ($doc_id <= 0) {
    $_SESSION['error'] = "Invalid document ID.";
    file_put_contents('debug.txt', "Invalid doc_id: $doc_id\n", FILE_APPEND);
    header("Location: verifications.php");
    exit();
}

// Fetch document and student data
$sql = "SELECT d.id, d.document_name, d.file_path, d.status, d.created_at, 
               u.full_name, u.matric_number, u.programme, u.level, u.session
        FROM documents d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    file_put_contents('debug.txt', "Doc Prepare failed: " . $conn->error . "\n", FILE_APPEND);
    $_SESSION['error'] = "Database error.";
    header("Location: verifications.php");
    exit();
}
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doc) {
    $_SESSION['error'] = "Document not found.";
    file_put_contents('debug.txt', "Doc not found: $doc_id\n", FILE_APPEND);
    header("Location: verifications.php");
    exit();
}

// Format dates
$upload_date = date('d/m/Y', strtotime($doc['created_at']));
$upload_time = date('h:ia', strtotime($doc['created_at']));

// Handle Approve/Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'] === 'approve' ? 'Verified' : 'Failed';
    $sql = "UPDATE documents SET status = ? WHERE id = ? AND status = 'Pending'";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        file_put_contents('debug.txt', "Update Prepare failed: " . $conn->error . "\n", FILE_APPEND);
        $_SESSION['admin_message'] = "Database error during update.";
    } else {
        $stmt->bind_param("si", $action, $doc_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $_SESSION['admin_message'] = $affected > 0 ? "Document $action successfully." : "Failed to update document status.";
    }
    $redirect_url = "verifications.php?t=" . time();
    file_put_contents('debug.txt', "Redirecting to: $redirect_url\n", FILE_APPEND);
    header("Location: $redirect_url");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Detail - UI DFMS</title>
    <link rel="stylesheet" href="src/css/global.css">
    <link rel="stylesheet" href="src/css/styleguide.css">
    <link rel="stylesheet" href="src/css/dashboard.css">
    <link rel="stylesheet" href="src/css/admin.css">
    <link rel="stylesheet" href="src/css/uploads.css">
    <link rel="stylesheet" href="src/css/verifications_detail.css">
    <style>
    .filter-button {
        padding: 10px 16px;
        background: none;
        color: #01017A;
        border: none;
        cursor: pointer;
        border-radius: 10px;
        border: 2px solid #01017A;
        font-weight: 700;
    }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div id="mobile-sidebar-overlay" class="sidebar-overlay"></div>
        <div id="sidebar" class="sidebar">
            <div class="sidebar-content">
                <img class="logo" src="src/assets/images/ui-logo.png" alt="Logo">
                <div class="sidebar-menu">
                    <div class="menu-items">
                        <a href="admin.php" class="menu-item">
                            <div class="menu-icon"><img src="src/assets/images/home white.svg"></div>
                            <div class="menu-text">Dashboard</div>
                        </a>
                        <a href="verifications.php" class="menu-item active">
                            <div class="menu-icon"><img src="src/assets/images/verification purple.svg"></div>
                            <div class="menu-text active-text">Verifications</div>
                        </a>
                        <a href="analytics.php" class="menu-item">
                            <div class="menu-icon"><img src="src/assets/images/analytics.svg"></div>
                            <div class="menu-text">Analytics</div>
                        </a>
                        <a href="settings.php" class="menu-item">
                            <div class="menu-icon"><img src="src/assets/images/settings.svg"></div>
                            <div class="menu-text">Settings</div>
                        </a>
                    </div>
                    <a href="logout.php" class="menu-item logout">
                        <div class="menu-icon"><img src="src/assets/images/log-out.svg"></div>
                        <div class="menu-text">Log out</div>
                    </a>
                </div>
            </div>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <button id="mobile-menu-toggle" class="mobile-menu-btn">☰</button>
                <div class="search-container">
                    <div class="search-box">
                        <div class="search-icon"><img src="src/assets/images/search-normal.svg" alt=""></div>
                        <input type="text" placeholder="Search here" class="search-input">
                    </div>
                    <div class="date-filter">
                        <form id="date-filter-form" action="verifications.php" method="GET">
                            <input type="hidden" name="tab" value="all">
                            <div class="date-input">
                                <input type="date" name="start_date" id="start-date"
                                    value="<?php echo date('Y-m-d', strtotime($doc['created_at'])); ?>">
                                <img src="src/assets/images/calendar.svg" alt="">
                            </div>
                            <span>-</span>
                            <div class="date-input">
                                <input type="date" name="end_date" id="end-date" value="<?php echo date('Y-m-d'); ?>">
                                <img src="src/assets/images/calendar.svg" alt="">
                            </div>
                            <button type="submit" class="filter-button">Apply Filter</button>
                        </form>
                    </div>
                </div>
                <div class="header-right">
                    <div class="notification-icon"><img src="src/assets/images/notification-bing.svg" alt=""></div>
                    <div class="user-profile">
                        <img class="avatar" src="src/assets/images/admin-avatar.png" alt="Admin">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="user-id"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-divider"></div>
            <?php if (isset($_SESSION['admin_message'])): ?>
            <div
                class="admin-message <?php echo strpos($_SESSION['admin_message'], 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($_SESSION['admin_message']); unset($_SESSION['admin_message']); ?>
            </div>
            <?php endif; ?>
            <div class="subheader">
                <a href="verifications.php" class="back-btn">
                    <img src="src/assets/images/Arrow - Left.svg" alt="Back" class="back-icon">
                </a>
            </div>
            <div class="detail-page-content">
                <div class="detail-row">
                    <div class="doc-preview-container">
                        <div class="doc-preview">
                            <object class="doc-embed"
                                data="src/Uploads/<?php echo htmlspecialchars(basename($doc['file_path'])); ?>"
                                type="application/pdf">
                                <img src="src/assets/images/PDF Icon Pic.png" alt="Document Preview">
                            </object>
                            <div class="zoom-icon"><img src="src/assets/images/Zoom - In.svg" alt="Zoom"></div>
                        </div>
                    </div>
                    <div class="cards-wrapper">
                        <div class="student-card">
                            <img class="avatar-large" src="src/assets/images/Small Avatar.svg" alt="Student">
                            <div class="student-info">
                                <div class="info-group">
                                    <div class="label">Name</div>
                                    <div class="value"><?php echo htmlspecialchars($doc['full_name']); ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="label">Department</div>
                                    <div class="value"><?php echo htmlspecialchars($doc['programme']); ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="label">Matric No</div>
                                    <div class="value"><?php echo htmlspecialchars($doc['matric_number']); ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="label">Level</div>
                                    <div class="value"><?php echo htmlspecialchars($doc['level']); ?></div>
                                </div>
                                <div class="info-group">
                                    <div class="label">Session</div>
                                    <div class="value"><?php echo htmlspecialchars($doc['session']); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="details-card">
                            <div class="details-header">Document Details</div>
                            <div class="details-row">
                                <div class="label">Document Name:</div>
                                <div class="value"><?php echo htmlspecialchars($doc['document_name']); ?></div>
                            </div>
                            <div class="details-row">
                                <div class="label">File Uploaded:</div>
                                <div class="value"><?php echo htmlspecialchars(basename($doc['file_path'])); ?></div>
                            </div>
                            <div class="details-row">
                                <div class="label">Date Uploaded:</div>
                                <div class="value"><?php echo $upload_date; ?></div>
                            </div>
                            <div class="details-row">
                                <div class="label">Time Uploaded:</div>
                                <div class="value"><?php echo $upload_time; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($doc['status'] === 'Pending'): ?>
                <div class="action-row">
                    <form action="verification_detail.php?doc_id=<?php echo $doc_id; ?>" method="POST"
                        style="display: inline;">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="approve-btn"><img src="src/assets/images/Check.svg"
                                class="btn-icon" alt="">Approve</button>
                    </form>
                    <form action="verification_detail.php?doc_id=<?php echo $doc_id; ?>" method="POST"
                        style="display: inline;">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="reject-btn"><img src="src/assets/images/Close.svg" class="btn-icon"
                                alt="">Reject</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="doc-modal">
        <div class="doc-modal-content">
            <img id="modal-img" src="" alt="Full Document">
            <div class="close-modal"><img src="src/assets/images/Close.svg" alt="Close"></div>
        </div>
    </div>
    <script src="src/js/verification_detail.js"></script>
    <script src="src/js/admin.js"></script>
</body>

</html>