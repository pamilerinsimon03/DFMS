<?php
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Debug: Log session
file_put_contents('debug.txt', "User ID: " . $_SESSION['user_id'] . "\n", FILE_APPEND);

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT full_name, matric_number FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle tab filtering
$tab = isset($_GET['tab']) ? strtolower($_GET['tab']) : 'total';
$status_filter = '';
$valid_statuses = ['Pending', 'Verified', 'Failed'];
if ($tab !== 'total' && in_array(ucfirst($tab), $valid_statuses)) {
    $status_filter = "AND status = ?";
}

// Fetch documents
if ($status_filter) {
    $sql = "SELECT id, document_name, file_path, status, created_at 
            FROM documents 
            WHERE user_id = ? $status_filter 
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, ucfirst($tab));
} else {
    $sql = "SELECT id, document_name, file_path, status, created_at 
            FROM documents 
            WHERE user_id = ? 
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

// Debug: Log query
file_put_contents('debug.txt', "Query: $sql\nParams: user_id=$user_id, status=" . ($status_filter ? ucfirst($tab) : 'none') . "\n", FILE_APPEND);

$stmt->execute();
$result = $stmt->get_result();
$documents = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Debug: Log results
file_put_contents('debug.txt', "Documents: " . print_r($documents, true) . "\n", FILE_APPEND);

// Handle reupload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reupload_id'])) {
    $doc_id = (int)$_POST['reupload_id'];
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_name = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        $file_type = mime_content_type($file_tmp);

        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        if ($file_size > 5 * 1024 * 1024) {
            $_SESSION['upload_message'] = "File size exceeds 5MB limit.";
        } elseif (!in_array($file_type, $allowed_types)) {
            $_SESSION['upload_message'] = "Invalid file type. Only PDF, JPEG, and PNG are allowed.";
        } else {
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_path = $upload_dir . time() . '_' . $file_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                $sql = "UPDATE documents SET file_path = ?, status = 'Pending', created_at = NOW() WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sii", $file_path, $doc_id, $user_id);
                $stmt->execute();
                $_SESSION['upload_message'] = "File reuploaded successfully.";
                $stmt->close();
            } else {
                $_SESSION['upload_message'] = "Failed to upload file.";
            }
        }
    }
    header("Location: uploads.php?tab=$tab");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Uploads - University of Ibadan DFMS</title>
    <link rel="stylesheet" href="src/css/global.css">
    <link rel="stylesheet" href="src/css/styleguide.css">
    <link rel="stylesheet" href="src/css/uploads.css">
    <link rel="stylesheet" href="src/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .upload-message { margin-bottom: 10px; font-size: 14px; text-align: center; }
        .upload-message.success { color: green; }
        .upload-message.error { color: red; }
        .reupload-input { display: none; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.active { transform: translateX(0); }
            .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
            .uploads-table { overflow-x: auto; }
            .table-header, .table-row { grid-template-columns: 50px 100px 150px 100px 100px 120px; min-width: 620px; }
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
                <img class="logo" src="src/assets/images/ui-logo.png" alt="University of Ibadan Logo" />
                <div class="sidebar-menu">
                    <div class="menu-items">
                        <a href="dashboard.php" class="menu-item">
                            <div class="menu-icon dashboard-icon">
                                <img src="src/assets/images/home white.svg" alt="Dashboard Icon">
                            </div>
                            <div class="menu-text">Dashboard</div>
                        </a>
                        <a href="uploads.php" class="menu-item active">
                            <div class="menu-icon uploads-icon">
                                <img src="src/assets/images/Document Purple.svg" alt="Uploads Icon">
                            </div>
                            <div class="menu-text active-text">My Uploads</div>
                        </a>
                        <a href="profile.php" class="menu-item">
                            <div class="menu-icon profile-icon">
                                <img src="src/assets/images/User-Circle.svg" alt="Profile Icon">
                            </div>
                            <div class="menu-text">Profile</div>
                        </a>
                        <a href="settings.php" class="menu-item">
                            <div class="menu-icon settings-icon">
                                <img src="src/assets/images/settings.svg" alt="Settings Icon">
                            </div>
                            <div class="menu-text">Settings</div>
                        </a>
                    </div>
                    <a href="logout.php" class="menu-item logout">
                        <div class="menu-icon logout-icon">
                            <img src="src/assets/images/log-out.svg" alt="Logout Icon">
                        </div>
                        <div class="menu-text">Log out</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
                    <span class="mobile-menu-icon">
                        <img src="src/assets/images/Menu.svg" alt="Menu Icon">
                    </span>
                </button>
                <div class="welcome">
                    <h1>Welcome <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></h1>
                </div>
                <div class="header-right">
                    <div class="search-container">
                        <div class="search-box">
                            <div class="search-icon">
                                <img src="src/assets/images/search-normal.svg" alt="Search Icon">
                            </div>
                            <input type="text" placeholder="Search here" class="search-input">
                        </div>
                        <div class="notification-icon">
                            <img src="src/assets/images/notification-bing.svg" alt="Notification Icon">
                            <div class="notification-dot"></div>
                        </div>
                    </div>
                    <div class="user-profile">
                        <img src="src/assets/images/Small Avatar.svg" alt="User Profile" class="avatar">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="user-id">Matric No: <?php echo htmlspecialchars($user['matric_number']); ?></div>
                        </div>
                        <div class="dropdown-icon">
                            <img src="src/assets/images/arrow-down.svg" alt="Dropdown Icon">
                        </div>
                    </div>
                </div>
            </div>

            <div class="header-divider"></div>

            <!-- Main Uploads Content -->
            <div class="uploads-content">
                <!-- Upload Feedback -->
                <?php if (isset($_SESSION['upload_message'])): ?>
                    <div class="upload-message <?php echo strpos($_SESSION['upload_message'], 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($_SESSION['upload_message']); unset($_SESSION['upload_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Tab Navigation -->
                <div class="uploads-tabs">
                    <div class="tabs-left">
                        <a href="uploads.php?tab=total" class="tab-item <?php echo $tab === 'total' ? 'active' : ''; ?>">Total Uploads</a>
                        <a href="uploads.php?tab=pending" class="tab-item <?php echo $tab === 'pending' ? 'active' : ''; ?>">Pending Verification</a>
                        <a href="uploads.php?tab=verified" class="tab-item <?php echo $tab === 'verified' ? 'active' : ''; ?>">Verified</a>
                        <a href="uploads.php?tab=failed" class="tab-item <?php echo $tab === 'failed' ? 'active' : ''; ?>">Failed</a>
                    </div>
                </div>

                <!-- Uploads Table -->
                <div class="uploads-table">
                    <!-- Table Header -->
                    <div class="table-header">
                        <div class="th-sn">
                            <span>SN</span>
                            <img src="src/assets/images/arrow-down-small.svg" alt="Sort">
                        </div>
                        <div class="th-name">Name</div>
                        <div class="th-document">Document Name</div>
                        <div class="th-date">
                            <span>Date</span>
                            <img src="src/assets/images/arrow-down-small.svg" alt="Sort">
                        </div>
                        <div class="th-status">Status</div>
                        <div class="th-action">Action</div>
                    </div>

                    <!-- Table Rows -->
                    <?php if (empty($documents)): ?>
                        <div class="table-row">
                            <div class="td-sn" colspan="6">
                                No documents found. 
                                <?php if ($tab !== 'total'): ?>
                                    Try switching to "Total Uploads".
                                <?php else: ?>
                                    Upload documents via the Dashboard.
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($documents as $index => $doc): ?>
                            <div class="table-row">
                                <div class="td-sn"><?php echo $index + 1; ?>.</div>
                                <div class="td-name"><?php echo htmlspecialchars($doc['document_name']); ?></div>
                                <div class="td-document"><?php echo htmlspecialchars(basename($doc['file_path'])); ?></div>
                                <div class="td-date"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></div>
                                <div class="td-status <?php echo strtolower($doc['status']); ?>">
                                    <div class="status-dot"></div>
                                    <div class="status-text"><?php echo htmlspecialchars($doc['status']); ?></div>
                                </div>
                                <div class="td-action">
                                    <?php if ($doc['status'] === 'Failed'): ?>
                                        <form action="uploads.php?tab=<?php echo htmlspecialchars($tab); ?>" method="POST" enctype="multipart/form-data" style="display: inline;">
                                            <input type="hidden" name="reupload_id" value="<?php echo $doc['id']; ?>">
                                            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="reupload-input" id="reupload-<?php echo $doc['id']; ?>" onchange="this.form.submit()">
                                            <button type="button" class="btn-danger" onclick="document.getElementById('reupload-<?php echo $doc['id']; ?>').click()">Reupload Document</button>
                                        </form>
                                    <?php elseif ($doc['status'] === 'Verified'): ?>
                                        <button class="btn-success" disabled>Successful</button>
                                    <?php else: ?>
                                        <button class="btn-warning" disabled>Awaiting Verification</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="src/js/dashboard.js"></script>
    <script src="src/js/uploads.js"></script>
    <script>
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
    </script>
</body>
</html>