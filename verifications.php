<?php
require_once 'config.php';

// Prevent redirect loop
if (isset($_SESSION['redirect_count']) && $_SESSION['redirect_count'] > 5) {
    die("Error: Too many redirects. Please contact support.");
}
$_SESSION['redirect_count'] = isset($_SESSION['redirect_count']) ? $_SESSION['redirect_count'] + 1 : 1;

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_count'] = 0;
    header("Location: index.php");
    exit();
}

// Fetch user data to check admin status
$user_id = (int)$_SESSION['user_id'];
$sql = "SELECT full_name, is_admin FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $_SESSION['error'] = "Database error. Please contact support.";
    $_SESSION['redirect_count'] = 0;
    header("Location: index.php");
    exit();
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !$user['is_admin']) {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    $_SESSION['redirect_count'] = 0;
    header("Location: dashboard.php");
    exit();
}

// Reset redirect count
$_SESSION['redirect_count'] = 0;

// Handle filters
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
$status_filter = '';
switch ($tab) {
    case 'pending':
        $status_filter = "AND d.status = 'Pending'";
        break;
    case 'verified':
        $status_filter = "AND d.status = 'Verified'";
        break;
    case 'rejected':
        $status_filter = "AND d.status = 'Failed'";
        break;
    default:
        $tab = 'all';
        $status_filter = '';
}

// Date filter
$sql_min_date = "SELECT MIN(created_at) as min_date FROM documents";
$result_min_date = $conn->query($sql_min_date);
$min_date = $result_min_date ? $result_min_date->fetch_assoc()['min_date'] : date('Y-m-01');
$default_start_date = $min_date ? date('Y-m-d', strtotime($min_date)) : date('Y-m-01');
$default_end_date = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $default_start_date;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $default_end_date;

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_filter = '';
$search_params = [];
if ($search) {
    $search_escaped = '%' . $conn->real_escape_string($search) . '%';
    $search_filter = "AND (u.full_name LIKE ? OR u.programme LIKE ? OR d.document_name LIKE ?)";
    $search_params = [$search_escaped, $search_escaped, $search_escaped];
}

// Pagination
$per_page = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Count total documents
$sql_count = "SELECT COUNT(d.id) as total 
              FROM documents d 
              JOIN users u ON d.user_id = u.id 
              WHERE DATE(d.created_at) BETWEEN ? AND ? $status_filter $search_filter";
$stmt_count = $conn->prepare($sql_count);
$count_params = array_merge([$start_date, $end_date], $search_params);
$count_types = 'ss' . str_repeat('s', count($search_params));
$stmt_count->bind_param($count_types, ...$count_params);
$stmt_count->execute();
$total = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();
$total_pages = ceil($total / $per_page);

// Documents query
$sql_docs = "SELECT d.id, d.user_id, d.document_name, d.file_path, d.status, u.full_name, u.programme as department, u.level, u.session 
             FROM documents d 
             JOIN users u ON d.user_id = u.id 
             WHERE DATE(d.created_at) BETWEEN ? AND ? $status_filter $search_filter
             ORDER BY d.created_at DESC 
             LIMIT ? OFFSET ?";
$stmt_docs = $conn->prepare($sql_docs);
$params = array_merge([$start_date, $end_date], $search_params, [$per_page, $offset]);
$types = 'ss' . str_repeat('s', count($search_params)) . 'ii';
$stmt_docs->bind_param($types, ...$params);
$stmt_docs->execute();
$documents = $stmt_docs->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_docs->close();

// Handle Verification Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['doc_id'])) {
    $doc_id = (int)$_POST['doc_id'];
    $action = $_POST['action'] === 'verify' ? 'Verified' : 'Failed';
    
    $sql_update = "UPDATE documents SET status = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $action, $doc_id);
    $stmt_update->execute();
    $stmt_update->close();

    $redirect_url = "verifications.php?tab=$tab&start_date=$start_date&end_date=$end_date&search=" . urlencode($search) . "&page=$page";
    header("Location: $redirect_url");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Verifications - University of Ibadan DFMS</title>
    <link rel="stylesheet" href="src/css/global.css">
    <link rel="stylesheet" href="src/css/styleguide.css">
    <link rel="stylesheet" href="src/css/dashboard.css">
    <link rel="stylesheet" href="src/css/admin.css">
    <link rel="stylesheet" href="src/css/uploads.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
    .admin-message {
        margin: 10px 0;
        font-size: 14px;
        text-align: center;
    }

    .admin-message.success {
        color: green;
    }

    .admin-message.error {
        color: red;
    }

    .action-menu {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .action-menu-content {
        display: none;
        position: absolute;
        background: #fff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 1;
        min-width: 100px;
    }



    .action-menu-content a,
    .action-menu-content button {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 8px;
        border: none;
        background: none;
        text-align: left;
        cursor: pointer;
        text-decoration: none;
        color: #000;
    }

    .action-menu-content .action-item {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        width: 100%;
        text-decoration: none;
        background: none;
        border: none;
        cursor: pointer;
        font-family: Poppins, sans-serif;
        font-size: 14px;
        color: #000;
    }

    /* spacing for the icons */
    .action-menu-content .action-item img {
        width: 16px;
        margin-right: 8px;
    }

    /* specific colors */
    .action-menu-content .action-item.verify {
        color: #009A49;
    }

    .action-menu-content .action-item.reject {
        color: #FF3B30;
    }

    /* hover state */
    .action-menu-content .action-item:hover {
        background: #f0f0f0;
    }



    .action-menu-content img {
        width: 16px;
        margin-right: 5px;
    }

    .status-pending {
        background: #fff3cd;
    }

    .status-verified {
        background: #d4edda;
    }

    .status-rejected {
        background: #f8d7da;
    }

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

    .filter-button:hover {
        background: #01017A;
        color: white;
    }

    .doc-link {
        color: black;
        text-decoration: underline;
    }

    .doc-link:hover {
        text-decoration: underline;
    }

    .pagination {
        margin: 20px 0;
        text-align: center;
    }

    .pagination a {
        padding: 8px 12px;
        margin: 0 4px;
        border: 1px solid #ddd;
        text-decoration: none;
        color: #01017A;
    }

    .pagination a.active {
        background: #01017A;
        color: #fff;
        border: 1px solid #01017A;
    }

    .pagination a:hover {
        background: #f0f0f0;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .main-content {
            margin-left: 0;
        }

        .mobile-menu-btn {
            display: block;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .docs-table {
            min-width: 600px;
        }

        .date-filter {
            flex-direction: column;
        }

        .date-input {
            margin-bottom: 5px;
        }

        .filter-button {
            width: 100%;
        }
    }

    @media (min-width: 769px) {

        .mobile-menu-btn,
        .sidebar-overlay {
            display: none;
        }

        .sidebar {
            transform: translateX(0);
        }
    }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div id="mobile-sidebar-overlay" class="sidebar-overlay"></div>
        <div id="sidebar" class="sidebar">
            <div class="sidebar-content">
                <img class="logo" src="src/assets/images/ui-logo.png" alt="University of Ibadan Logo">
                <div class="sidebar-menu">
                    <div class="menu-items">
                        <a href="admin.php" class="menu-item">
                            <div class="menu-icon"><img src="src/assets/images/home white.svg" alt="Dashboard Icon">
                            </div>
                            <div class="menu-text">Dashboard</div>
                        </a>
                        <a href="verifications.php" class="menu-item active">
                            <div class="menu-icon"><img src="src/assets/images/verification purple.svg"
                                    alt="Verifications Icon"></div>
                            <div class="menu-text active-text">Verifications</div>
                        </a>
                        <a href="analytics.php" class="menu-item">
                            <div class="menu-icon"><img src="src/assets/images/analytics.svg" alt="Analytics Icon">
                            </div>
                            <div class="menu-text">Analytics</div>
                        </a>
                        <a href="settings.php" class="menu-item">
                            <div class="menu-icon"><img src="src/assets/images/settings.svg" alt="Settings Icon"></div>
                            <div class="menu-text">Settings</div>
                        </a>
                    </div>
                    <a href="logout.php" class="menu-item logout">
                        <div class="menu-icon"><img src="src/assets/images/log-out.svg" alt="Logout Icon"></div>
                        <div class="menu-text">Log out</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
                    <span class="mobile-menu-icon"><img src="src/assets/images/Menu.svg" alt="Menu Icon"></span>
                </button>
                <div class="search-container">
                    <div class="search-box">
                        <div class="search-icon"><img src="src/assets/images/search-normal.svg" alt="Search Icon"></div>
                        <input type="text" placeholder="Search here" class="search-input" id="search-input"
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="date-filter">
                        <form id="date-filter-form" action="verifications.php" method="GET">
                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                            <input type="hidden" name="search" id="form-search"
                                value="<?php echo htmlspecialchars($search); ?>">
                            <div class="date-input">
                                <input type="date" name="start_date" id="start-date"
                                    value="<?php echo htmlspecialchars($start_date); ?>">
                                <img src="src/assets/images/calendar.svg" alt="Calendar">
                            </div>
                            <span>-</span>
                            <div class="date-input">
                                <input type="date" name="end_date" id="end-date"
                                    value="<?php echo htmlspecialchars($end_date); ?>">
                                <img src="src/assets/images/calendar.svg" alt="Calendar">
                            </div>
                            <button type="submit" class="filter-button">Apply Filter</button>
                        </form>
                    </div>
                </div>
                <div class="header-right">
                    <div class="notification-icon"><img src="src/assets/images/notification-bing.svg"
                            alt="Notification Icon"></div>
                    <div class="user-profile">
                        <img class="avatar" src="src/assets/images/admin-avatar.png" alt="Admin Avatar">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="user-id"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-divider"></div>

            <div class="uploads-content">
                <div class="uploads-tabs">
                    <div class="tabs-left">
                        <a href="verifications.php?tab=all&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>"
                            class="tab-item <?php echo $tab === 'all' ? 'active' : ''; ?>">Documents Uploaded</a>
                        <a href="verifications.php?tab=pending&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>"
                            class="tab-item <?php echo $tab === 'pending' ? 'active' : ''; ?>">Documents to be
                            Verified</a>
                        <a href="verifications.php?tab=verified&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>"
                            class="tab-item <?php echo $tab === 'verified' ? 'active' : ''; ?>">Documents Verified</a>
                        <a href="verifications.php?tab=rejected&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>"
                            class="tab-item <?php echo $tab === 'rejected' ? 'active' : ''; ?>">Rejected Documents</a>
                    </div>
                </div>
                <div class="table-section">
                    <div class="table-wrapper">
                        <table class="docs-table">
                            <thead>
                                <tr>
                                    <th>SN</th>
                                    <th>Student's Name</th>
                                    <th>Department</th>
                                    <th>Level</th>
                                    <th>Document Name</th>
                                    <th>Session</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documents)): ?>
                                <tr>
                                    <td colspan="8">No documents found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($documents as $index => $doc): ?>
                                <tr>
                                    <td><?php echo ($page - 1) * $per_page + $index + 1; ?>.</td>
                                    <td><?php echo htmlspecialchars($doc['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['department']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['level']); ?></td>
                                    <td><a href="verification_detail.php?doc_id=<?php echo $doc['id']; ?>"
                                            class="doc-link"><?php echo htmlspecialchars($doc['document_name']); ?></a>
                                    </td>
                                    <td><?php echo htmlspecialchars($doc['session']); ?></td>
                                    <td><?php echo htmlspecialchars($doc['status']); ?></td>
                                    <td class="td-action">
                                        <div class="action-menu">
                                            <img src="src/assets/images/more.svg" alt="More" class="action-btn">
                                            <div class="action-menu-content">
                                                <a href="verification_detail.php?doc_id=<?php echo $doc['id']; ?>"
                                                    class="action-item">
                                                    <img src="src/assets/images/Bold Eye.svg" alt="View"> View
                                                </a>
                                                <?php if ($doc['status'] === 'Pending'): ?>
                                                <form
                                                    action="verifications.php?tab=<?php echo urlencode($tab); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>"
                                                    method="POST">
                                                    <input type="hidden" name="doc_id"
                                                        value="<?php echo $doc['id']; ?>">
                                                    <input type="hidden" name="action" value="verify">
                                                    <button type="submit" class="action-item verify">
                                                        <img src="src/assets/images/Check.svg" alt="Verify"> Verify
                                                    </button>
                                                </form>
                                                <form
                                                    action="verifications.php?tab=<?php echo urlencode($tab); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>"
                                                    method="POST">
                                                    <input type="hidden" name="doc_id"
                                                        value="<?php echo $doc['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="action-item reject">
                                                        <img src="src/assets/images/Trash.svg" alt="Reject"> Reject
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a
                            href="verifications.php?tab=<?php echo urlencode($tab); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="verifications.php?tab=<?php echo urlencode($tab); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"
                            class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                        <a
                            href="verifications.php?tab=<?php echo urlencode($tab); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">Next</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="admin_verifications.js"></script>
    <script src="admin.js"></script>
</body>

</html>