<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT full_name, matric_number, level, session, programme FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$sql = "SELECT document_name, file_path, status, created_at FROM documents WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$documents = $stmt->get_result();

$total_docs = $documents->num_rows;
$required_docs = [
    'Admission Letter' => '2025-06-10',
    'Course Registration Form' => '2025-06-10',
    'NACOSS Receipt' => '2025-06-10',
    'Faculty Registration Receipt' => '2025-06-10',
    'Medical Laboratory Receipt' => '2025-06-10',
    'Hostel Accommodation' => '2025-06-10'
];
$uploaded_docs = [];
$documents->data_seek(0);
while ($doc = $documents->fetch_assoc()) {
    $uploaded_docs[] = $doc['document_name'];
}
$documents->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - University of Ibadan DFMS</title>
    <link rel="stylesheet" href="src/css/global.css">
    <link rel="stylesheet" href="src/css/styleguide.css">
    <link rel="stylesheet" href="src/css/style.css">
    <link rel="stylesheet" href="src/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .upload-message { margin-bottom: 10px; font-size: 14px; }
        .upload-message.success { color: green; }
        .upload-message.error { color: red; }
        .upload-input { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .upload-file-input { display: none; }
        .custom-file-upload {
            display: flex;
            align-items: center;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f9f9f9;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .custom-file-upload img {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }
        .custom-file-upload span {
            font-size: 14px;
            color: #333;
        }
        .file-name {
            font-size: 12px;
            color: #666;
            margin-left: 8px;
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.active { transform: translateX(0); }
            .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; }
            .mobile-menu-btn { display: block; }
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
                        <a href="dashboard.php" class="menu-item active">
                            <div class="menu-icon dashboard-icon">
                                <img src="src/assets/images/home.svg" alt="Dashboard Icon">
                            </div>
                            <div class="menu-text active-text">Dashboard</div>
                        </a>
                        <a href="uploads.php" class="menu-item">
                            <div class="menu-icon uploads-icon">
                                <img src="src/assets/images/Document.svg" alt="Uploads Icon">
                            </div>
                            <div class="menu-text">My Uploads</div>
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

            <div class="main-body-content">
                <!-- Main Dashboard Content -->
                <div class="dashboard-content">
                    <!-- User Info Card -->
                    <div class="user-info-card">
                        <img src="src/assets/images/Big Avatar.svg" alt="User Photo" class="user-photo">
                        <div class="user-details">
                            <div class="user-detail-row">
                                <div class="detail-item">
                                    <div class="detail-title">Matric No</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($user['matric_number']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-title">Level</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($user['level']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-title">Session</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($user['session']); ?></div>
                                </div>
                            </div>
                            <div class="user-program">
                                <div class="detail-title">Programme</div>
                                <div class="detail-value"><?php echo htmlspecialchars($user['programme']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Uploaded Files Section -->
                    <div class="files-section">
                        <div class="files-header">
                            <div class="files-title">Uploaded Files</div>
                            <a href="uploads.php" class="see-more">See more</a>
                        </div>
                        <div class="files-grid">
                            <?php if ($documents->num_rows == 0): ?>
                                <div class="no-files">No files uploaded yet.</div>
                            <?php else: ?>
                                <?php while ($doc = $documents->fetch_assoc()): ?>
                                    <div class="file-card">
                                        <div class="file-image">
                                            <img src="src/assets/images/PDF Icon Pic.png" alt="<?php echo htmlspecialchars($doc['document_name']); ?>">
                                        </div>
                                        <div class="file-details">
                                            <div class="file-name"><?php echo htmlspecialchars($doc['document_name']); ?></div>
                                            <div class="file-status <?php echo strtolower($doc['status']); ?>">
                                                <div class="status-dot"></div>
                                                <div class="status-text"><?php echo htmlspecialchars($doc['status']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="right-sidebar">
                    <!-- Documents Counter -->
                    <div class="documents-counter">
                        <div class="counter-container">
                            <div class="counter-number"><?php echo $total_docs; ?></div>
                            <div class="counter-label">Total Documents Uploaded</div>
                        </div>
                    </div>

                    <!-- Upload Files Section -->
                    <div class="upload-section">
                        <div class="upload-container">
                            <?php if (isset($_SESSION['upload_message'])): ?>
                                <div class="upload-message <?php echo strpos($_SESSION['upload_message'], 'successfully') !== false ? 'success' : 'error'; ?>">
                                    <?php echo htmlspecialchars($_SESSION['upload_message']); unset($_SESSION['upload_message']); ?>
                                </div>
                            <?php endif; ?>
                            <form action="upload_file.php" method="POST" enctype="multipart/form-data">
                                <div class="upload-icon">
                                    <img src="src/assets/images/humbleicons_upload.svg" alt="Upload Icon">
                                </div>
                                <div class="upload-text">Upload files</div>
                                <div class="upload-note">File size not more than 5mb, file format pdf/jpeg/png</div>
                                <input type="text" name="document_name" placeholder="Document Name" required class="upload-input">
                                <input type="file" name="file" id="file-upload" accept=".pdf,.jpg,.jpeg,.png" required class="upload-file-input">
                                <label for="file-upload" class="custom-file-upload">
                                    <img src="src/assets/images/humbleicons_upload.svg" alt="Upload Icon">
                                    <span>Select File</span>
                                    <span class="file-name" id="file-name">No file selected</span>
                                </label>
                                <button type="submit" class="upload-btn">Upload files</button>
                            </form>
                        </div>
                    </div>

                    <!-- Files to Upload Section -->
                    <div class="todo-files">
                        <h2 class="todo-title">Files to be Uploaded</h2>
                        <div class="todo-items">
                            <?php foreach ($required_docs as $doc_name => $due_date): ?>
                                <?php if (!in_array($doc_name, $uploaded_docs)): ?>
                                    <div class="todo-item">
                                        <div class="todo-name"><?php echo htmlspecialchars($doc_name); ?></div>
                                        <div class="todo-due">
                                            <div class="todo-dot"></div>
                                            <div class="todo-date">Due Date: <?php echo date('jS F, Y', strtotime($due_date)); ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="src/js/dashboard.js"></script>
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

        document.getElementById('file-upload').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>
</body>
</html>