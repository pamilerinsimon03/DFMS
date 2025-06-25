<?php
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Debug: Log session
file_put_contents('debug.txt', "Admin ID: {$_SESSION['user_id']} (Type: " . gettype($_SESSION['user_id']) . ")\n", FILE_APPEND);

// Fetch user data to check admin status
$user_id = (int)$_SESSION['user_id'];
$sql = "SELECT full_name, is_admin FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $error = "Prepare failed: " . $conn->error . " | SQL: $sql";
    file_put_contents('debug.txt', $error . "\n", FILE_APPEND);
    $_SESSION['error'] = "Database error. Please contact support.";
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
    header("Location: dashboard.php");
    exit();
}

// Debug: Log user data
file_put_contents('debug.txt', "User: " . print_r($user, true) . "\n", FILE_APPEND);

// Handle date filter
// Set default to earliest document date to today
$sql_min_date = "SELECT MIN(created_at) as min_date FROM documents";
$result_min_date = $conn->query($sql_min_date);
$min_date = $result_min_date ? $result_min_date->fetch_assoc()['min_date'] : date('Y-m-01');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : ($min_date ? date('Y-m-d', strtotime($min_date)) : date('Y-m-01'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$date_filter = "WHERE d.created_at BETWEEN ? AND ?";

// KPI Queries
$sql_total = "SELECT COUNT(*) as count FROM documents d $date_filter";
$stmt_total = $conn->prepare($sql_total);
if ($stmt_total === false) {
    file_put_contents('debug.txt', "Total Prepare failed: " . $conn->error . "\n", FILE_APPEND);
    $total_docs = 0;
} else {
    $stmt_total->bind_param("ss", $start_date, $end_date);
    $stmt_total->execute();
    $total_docs = $stmt_total->get_result()->fetch_assoc()['count'];
    $stmt_total->close();
}

$sql_verified = "SELECT COUNT(*) as count FROM documents d $date_filter AND d.status = 'Verified'";
$stmt_verified = $conn->prepare($sql_verified);
if ($stmt_verified === false) {
    file_put_contents('debug.txt', "Verified Prepare failed: " . $conn->error . "\n", FILE_APPEND);
    $verified_docs = 0;
} else {
    $stmt_verified->bind_param("ss", $start_date, $end_date);
    $stmt_verified->execute();
    $verified_docs = $stmt_verified->get_result()->fetch_assoc()['count'];
    $stmt_verified->close();
}

$sql_pending = "SELECT COUNT(*) as count FROM documents d $date_filter AND d.status = 'Pending'";
$stmt_pending = $conn->prepare($sql_pending);
if ($stmt_pending === false) {
    file_put_contents('debug.txt', "Pending Prepare failed: " . $conn->error . "\n", FILE_APPEND);
    $pending_docs = 0;
} else {
    $stmt_pending->bind_param("ss", $start_date, $end_date);
    $stmt_pending->execute();
    $pending_docs = $stmt_pending->get_result()->fetch_assoc()['count'];
    $stmt_pending->close();
}

// Chart Data: Uploads by Month
$sql_uploads = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                FROM documents 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY month 
                ORDER BY month";
$stmt_uploads = $conn->prepare($sql_uploads);
$months = [];
$upload_counts = [];
if ($stmt_uploads === false) {
    file_put_contents('debug.txt', "Uploads Prepare failed: " . $conn->error . "\n", FILE_APPEND);
} else {
    $stmt_uploads->bind_param("ss", $start_date, $end_date);
    $stmt_uploads->execute();
    $uploads_data = $stmt_uploads->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_uploads->close();

    // Generate all months in range
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = new DateInterval('P1M');
    $period = new DatePeriod($start, $interval, $end->modify('+1 month'));
    $month_map = [];
    foreach ($period as $dt) {
        $month_key = $dt->format('Y-m');
        $month_map[$month_key] = ['label' => $dt->format('M Y'), 'count' => 0];
    }

    // Fill counts from query
    foreach ($uploads_data as $row) {
        if (isset($month_map[$row['month']])) {
            $month_map[$row['month']]['count'] = (int)$row['count'];
        }
    }

    // Build arrays for chart
    foreach ($month_map as $data) {
        $months[] = $data['label'];
        $upload_counts[] = $data['count'];
    }
}

// Debug: Log chart data
file_put_contents('debug.txt', "Bar Chart: Months: " . print_r($months, true) . "\nCounts: " . print_r($upload_counts, true) . "\n", FILE_APPEND);

// Chart Data: Verification Breakdown
$sql_breakdown = "SELECT status, COUNT(*) as count 
                  FROM documents 
                  WHERE created_at BETWEEN ? AND ?
                  GROUP BY status";
$stmt_breakdown = $conn->prepare($sql_breakdown);
$statuses = ['Pending', 'Verified', 'Failed'];
$breakdown_counts = [0, 0, 0];
if ($stmt_breakdown === false) {
    file_put_contents('debug.txt', "Breakdown Prepare failed: " . $conn->error . "\n", FILE_APPEND);
} else {
    $stmt_breakdown->bind_param("ss", $start_date, $end_date);
    $stmt_breakdown->execute();
    $breakdown_data = $stmt_breakdown->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_breakdown->close();
    foreach ($breakdown_data as $row) {
        $index = array_search($row['status'], $statuses);
        if ($index !== false) {
            $breakdown_counts[$index] = (int)$row['count'];
        }
    }
}

// Debug: Log pie chart data
file_put_contents('debug.txt', "Pie Chart: Statuses: " . print_r($statuses, true) . "\nCounts: " . print_r($breakdown_counts, true) . "\n", FILE_APPEND);

// Documents Table: Pending Documents
$sql_docs = "SELECT d.id, d.document_name, d.file_path, u.full_name, u.programme as department, u.level, u.session 
             FROM documents d 
             JOIN users u ON d.user_id = u.id 
             WHERE d.status = 'Pending' AND d.created_at BETWEEN ? AND ?
             ORDER BY d.created_at DESC 
             LIMIT 10";
$stmt_docs = $conn->prepare($sql_docs);
$documents = [];
if ($stmt_docs === false) {
    file_put_contents('debug.txt', "Docs Prepare failed: " . $conn->error . "\n", FILE_APPEND);
} else {
    $stmt_docs->bind_param("ss", $start_date, $end_date);
    $stmt_docs->execute();
    $documents = $stmt_docs->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_docs->close();
}

// Debug: Log queries
file_put_contents('debug.txt', "Total Docs: $total_docs, Verified: $verified_docs, Pending: $pending_docs\nDocuments: " . print_r($documents, true) . "\n", FILE_APPEND);

// Handle Verification Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['doc_id'])) {
    $doc_id = (int)$_POST['doc_id'];
    $action = $_POST['action'] === 'verify' ? 'Verified' : 'Failed';
    
    $sql_update = "UPDATE documents SET status = ? WHERE id = ? AND status = 'Pending'";
    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update === false) {
        file_put_contents('debug.txt', "Update Prepare failed: " . $conn->error . "\n", FILE_APPEND);
        $_SESSION['admin_message'] = "Database error during update.";
    } else {
        $stmt_update->bind_param("si", $action, $doc_id);
        $stmt_update->execute();
        $affected = $stmt_update->affected_rows;
        $stmt_update->close();
        $_SESSION['admin_message'] = $affected > 0 ? "Document $action successfully." : "Failed to update document status.";
    }
    header("Location: admin.php?start_date=$start_date&end_date=$end_date");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University of Ibadan DFMS</title>
    <link rel="stylesheet" href="src/css/global.css">
    <link rel="stylesheet" href="src/css/dashboard.css">
    <link rel="stylesheet" href="src/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        .admin-message { margin: 10px 0; font-size: 14px; text-align: center; }
        .admin-message.success { color: green; }
        .admin-message.error { color: red; }
        .action-menu { position: relative; display: inline-block; }
        .action-menu-content { display: none; position: absolute; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 1; }
        .action-menu:hover .action-menu-content { display: block; }
        .action-menu-content button { display: block; width: 100%; padding: 8px; border: none; background: none; text-align: left; cursor: pointer; }
        .action-menu-content button:hover { background: #f0f0f0; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.active { transform: translateX(0); }
            .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .kpi-cards { flex-direction: column; }
            .charts-row { flex-direction: column; }
            .table-wrapper { overflow-x: auto; }
            .docs-table { min-width: 600px; }
        }
        @media (min-width: 769px) {
            .mobile-menu-btn, .sidebar-overlay { display: none; }
            .sidebar { transform: translateX(0); }
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
                        <a href="admin.php" class="menu-item active">
                            <div class="menu-icon"><img src="src/assets/images/home.svg" alt="Dashboard Icon"></div>
                            <div class="menu-text active-text">Dashboard</div>
                        </a>
                        <a href="verifications.php" class="menu-item">
                            <div class="menu-icon"><img src="src/assets/images/verification.svg" alt="Verifications Icon"></div>
                            <div class="menu-text">Verifications</div>
                        </a>
                        <a href="analytics.php" class="menu-item">
                            <div class="menu-icon"><img src="src/assets/images/analytics.svg" alt="Analytics Icon"></div>
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
                        <input type="text" placeholder="Search here" class="search-input">
                    </div>
                    <div class="date-filter">
                        <form action="admin.php" method="GET">
                            <div class="date-input">
                                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                                <img src="src/assets/images/calendar.svg" alt="Calendar">
                            </div>
                            <span>-</span>
                            <div class="date-input">
                                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                                <img src="src/assets/images/calendar.svg" alt="Calendar">
                            </div>
                            <button type="submit" style="display: none;"></button>
                        </form>
                    </div>
                </div>
                <div class="header-right">
                    <div class="notification-icon"><img src="src/assets/images/notification-bing.svg" alt="Notification Icon"></div>
                    <div class="user-profile">
                        <img class="avatar" src="src/assets/images/admin-avatar.png" alt="Admin Avatar">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="user-id"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Feedback -->
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="admin-message <?php echo strpos($_SESSION['admin_message'], 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($_SESSION['admin_message']); unset($_SESSION['admin_message']); ?>
                </div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="kpi-cards">
                <div class="kpi-card bg-blue">
                    <div>
                        <div class="kpi-number"><?php echo number_format($total_docs); ?></div>
                        <div class="kpi-label">Total Documents</div>
                    </div>
                    <div class="kpi-icon"><img src="src/assets/images/Document Purple.svg" alt="Documents Icon"></div>
                </div>
                <div class="kpi-card bg-green">
                    <div>
                        <div class="kpi-number"><?php echo number_format($verified_docs); ?></div>
                        <div class="kpi-label">Verified Documents</div>
                    </div>
                    <div class="kpi-icon"><img src="src/assets/images/Check.svg" alt="Check Icon"></div>
                </div>
                <div class="kpi-card bg-yellow">
                    <div>
                        <div class="kpi-number"><?php echo number_format($pending_docs); ?></div>
                        <div class="kpi-label">Pending Documents</div>
                    </div>
                    <div class="kpi-icon"><img src="src/assets/images/Loading.svg" alt="Loading Icon"></div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row">
                <div class="chart-container">
                    <h3>Uploads This Session</h3>
                    <canvas id="barChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3>Verification Breakdown</h3>
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="table-section">
                <div class="table-header">
                    <h3>Documents to be Verified</h3><a href="verifications.php">See all</a>
                </div>
                <div class="table-wrapper">
                    <table class="docs-table">
                        <thead>
                            <tr>
                                <th>SN</th>
                                <th>Name</th>
                                <th>Dept</th>
                                <th>Level</th>
                                <th>File</th>
                                <th>Session</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documents)): ?>
                                <tr><td colspan="7">No pending documents found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($documents as $index => $doc): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?>.</td>
                                        <td><?php echo htmlspecialchars($doc['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['department']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['level']); ?></td>
                                        <td><?php echo htmlspecialchars(basename($doc['file_path'])); ?></td>
                                        <td><?php echo htmlspecialchars($doc['session']); ?></td>
                                        <td>
                                            <div class="action-menu">
                                                <img src="src/assets/images/more.svg" alt="More Options">
                                                <div class="action-menu-content">
                                                    <form action="admin.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" method="POST" style="display: inline;">
                                                        <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                        <input type="hidden" name="action" value="verify">
                                                        <button type="submit">Verify</button>
                                                    </form>
                                                    <form action="admin.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" method="POST" style="display: inline;">
                                                        <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit">Reject</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="admin.js"></script>
    <script>
        // Sidebar Toggle
        const toggleBtn = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-sidebar-overlay');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // Bar Chart
        const barChart = new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Document Uploads',
                    data: <?php echo json_encode($upload_counts); ?>,
                    backgroundColor: '#4e73df',
                    borderColor: '#4e73df',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Number of Uploads' }
                    },
                    x: {
                        title: { display: true, text: 'Month' }
                    }
                },
                plugins: {
                    legend: { display: true },
                    tooltip: { enabled: true }
                }
            }
        });

        // Pie Chart
        const pieChart = new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: {
                labels: ['Pending', 'Verified', 'Failed'],
                datasets: [{
                    data: <?php echo json_encode($breakdown_counts); ?>,
                    backgroundColor: ['#f6c23e', '#1cc88a', '#e74a3b'],
                    borderColor: ['#ffffff', '#ffffff', '#ffffff'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { enabled: true }
                }
            }
        });

        // Date Filter Auto-Submit
        document.querySelectorAll('.date-input input').forEach(input => {
            input.addEventListener('change', () => input.closest('form').submit());
        });
    </script>
</body>
</html>