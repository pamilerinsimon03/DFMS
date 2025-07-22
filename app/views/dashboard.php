<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="welcome">
        <h1>Welcome <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></h1>
    </div>
    <div class="header-right">
        <div class="search-container" id="search-container">
            <form action="/app/controllers/dashboard.php" method="GET" class="search-form">
                <div class="search-box">
                    <button type="button" class="search-icon-btn" id="search-icon-btn" aria-label="Open Search">
                        <img src="/app/src/assets/images/search-normal.svg" alt="Search Icon" class="search-icon-img">
                    </button>
                    <input type="text" name="search" placeholder="Search here" class="search-input"
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
            </form>
        </div>
        <div class="notification-icon">
            <img src="/app/src/assets/images/notification-bing.svg" alt="Notification Icon">
            <div class="notification-dot"></div>
        </div>
        <div class="user-profile">
            <img src="/app/src/assets/images/Small Avatar.svg" alt="User Profile" class="avatar">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="user-id">Matric No: <?php echo htmlspecialchars($user['matric_number']); ?></div>
            </div>
            <div class="dropdown-icon"><img src="/app/src/assets/images/arrow-down.svg" alt="Dropdown Icon"></div>
        </div>
    </div>
</div>
<div class="header-divider"></div>
<div class="main-body-content">
    <div class="dashboard-content">
        <div class="user-info-card">
            <img src="/app/src/assets/images/Big Avatar.svg" alt="User Photo" class="user-photo">
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
        <div class="files-section">
            <div class="files-header">
                <div class="files-title">Uploaded Files</div>
                <a href="/app/controllers/uploads.php" class="see-more">See more</a>
            </div>
            <div class="files-grid">
                <?php if (empty($documents)): ?>
                <div class="no-files">No files uploaded yet.</div>
                <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                <div class="file-card">
                    <div class="file-image"><img src="/app/src/assets/images/PDF Icon Pic.png"
                            alt="<?php echo htmlspecialchars($doc['document_name']); ?>"></div>
                    <div class="file-details">
                        <div class="file-name"><?php echo htmlspecialchars($doc['document_name']); ?></div>
                        <div class="file-status <?php echo strtolower($doc['status']); ?>">
                            <div class="status-dot"></div>
                            <div class="status-text"><?php echo htmlspecialchars($doc['status']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="right-sidebar">
        <div class="documents-counter">
            <div class="counter-container">
                <div class="counter-number"><?php echo $total_docs; ?></div>
                <div class="counter-label">Total Documents Uploaded</div>
            </div>
        </div>
        <div class="upload-section">
            <div class="upload-container" id="upload-container">
                <?php if (isset($_SESSION['upload_message'])): ?>
                <div
                    class="upload-message <?php echo (isset($_SESSION['upload_status']) && $_SESSION['upload_status'] === 'success') ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($_SESSION['upload_message']); unset($_SESSION['upload_message']); unset($_SESSION['upload_status']); ?>
                </div>
                <?php endif; ?>
                <form action="/app/controllers/upload_file.php" method="POST" enctype="multipart/form-data"
                    id="upload-form">
                    <div class="upload-icon"><img src="/app/src/assets/images/humbleicons_upload.svg" alt="Upload Icon">
                    </div>
                    <div class="upload-text">Upload files</div>
                    <div class="upload-note">File size not more than 5mb, file format pdf/jpeg/png</div>
                    <div class="custom-select-wrapper">
                        <select name="document_name" required>
                            <option value="" disabled selected>Select Document Type</option>
                            <?php foreach ($document_types as $key => $value): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>">
                                <?php echo htmlspecialchars($value); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="file" name="file" id="file-upload" accept=".pdf,.jpg,.jpeg,.png" required
                        class="upload-file-input" style="display: none;">
                    <label for="file-upload" class="custom-file-upload">Choose File</label>
                    <span id="file-name-display" class="file-name-display"></span>
                    <button type="submit" class="upload-btn">Upload files</button>
                </form>
            </div>
        </div>
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

<script src="../src/js/dashboard.js"></script>