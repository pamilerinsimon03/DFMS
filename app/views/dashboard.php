<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="welcome">
        <h1>Welcome <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></h1>
    </div>
    <div class="header-right">
        <div class="search-container" id="search-container">
            <form action="/app/controllers/dashboard.php" method="GET" class="search-form" onsubmit="return false;">
                <div class="search-box">
                    <button type="button" class="search-icon-btn" id="search-icon-btn" aria-label="Open Search">
                        <img src="/app/src/assets/images/search-normal.svg" alt="Search Icon" class="search-icon-img">
                    </button>
                    <input type="text" name="search" placeholder="Search here" class="search-input"
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
            </form>
        </div>
        <?php include __DIR__ . '/partials/notification_bell.php'; ?>
        <div class="user-profile">
            <img src="<?php echo User::getProfilePicture($user['id']); ?>" alt="User Profile" class="avatar" style="object-fit:cover;">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="user-id">
                    <?php if ($user['is_admin'] == 1): ?>
                        <?php if ($user['matric_number'] === 'SUPER_ADMIN'): ?>
                            Role: Super Admin
                        <?php else: ?>
                            Role: <?php echo htmlspecialchars($user['department_name'] ?? 'Psychology'); ?> Admin
                        <?php endif; ?>
                    <?php else: ?>
                        Matric No: <?php echo htmlspecialchars($user['matric_number']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dropdown-icon"><img src="/app/src/assets/images/arrow-down.svg" alt="Dropdown Icon"></div>
            
            <div class="profile-dropdown">
                <a href="<?php echo User::isAdmin($_SESSION['user_id'] ?? 0) ? '/app/controllers/student_profile.php' : '/app/controllers/profile.php'; ?>" class="dropdown-item">
                    <img src="/app/src/assets/images/User-Circle.svg" alt="Profile">
                    Profile
                </a>
                <a href="<?php echo User::isAdmin($_SESSION['user_id'] ?? 0) ? '/app/controllers/settings.php' : '/app/controllers/student_settings.php'; ?>" class="dropdown-item">
                    <img src="/app/src/assets/images/settings.svg" alt="Settings">
                    Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="/app/controllers/logout.php" class="dropdown-item logout">
                    <img src="/app/src/assets/images/log-out.svg" alt="Logout">
                    Log out
                </a>
            </div>
        </div>
    </div>
</div>
<div class="header-divider"></div>
<div class="main-body-content">
    <div class="dashboard-content">
        <div class="user-info-card">
            <img src="<?php echo User::getProfilePicture($user['id']); ?>" alt="User Photo" class="user-photo" style="object-fit:cover;">
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
                <div class="file-card" data-id="<?php echo $doc['id']; ?>">
                    <div class="file-image">
                        <?php 
                        $fileExt = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                        $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        if ($isImage): 
                        ?>
                        <img src="/app/controllers/secure_file_viewer.php?id=<?php echo $doc['id']; ?>" 
                             alt="<?php echo htmlspecialchars($doc['document_name']); ?>"
                             style="object-fit: cover; width: 100%; height: 100%; border-radius: 4px;">
                        <?php else: ?>
                        <img src="/app/src/assets/images/PDF Icon Pic.png"
                             alt="<?php echo htmlspecialchars($doc['document_name']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="file-details">
                        <div class="file-name"><?php echo htmlspecialchars($doc['document_name']); ?></div>
                        <div class="file-status <?php echo strtolower($doc['status']); ?>" 
                             <?php if ($doc['status'] === 'Rejected' && !empty($doc['rejection_reason'])): ?>
                             title="Reason: <?php echo htmlspecialchars($doc['rejection_reason']); ?>"
                             <?php endif; ?>>
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
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
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
                    
                    <div id="upload-progress-container" style="display: none; width: 100%; margin: 10px 0;">
                        <div style="width: 100%; background-color: #f3f3f3; border-radius: 4px;">
                            <div id="upload-progress-bar" style="width: 0%; height: 10px; background-color: #4caf50; border-radius: 4px; transition: width 0.3s;"></div>
                        </div>
                        <span id="upload-progress-text" style="font-size: 12px; color: #666;">0%</span>
                    </div>

                    <button type="button" id="upload-submit-btn" class="upload-btn">Upload files</button>
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

    <!-- Document Detail Modal -->
    <div id="document-detail-modal" class="modal-overlay" style="display: none;">
        <div class="modal-backdrop" onclick="closeDocumentDetailModal()"></div>
        <div class="modal-card">
            <button class="modal-close-btn" onclick="closeDocumentDetailModal()" aria-label="Close Modal">&times;</button>
            <div class="modal-header">
                <div class="modal-title-container">
                    <h3 id="modal-doc-name" class="modal-title">Document Details</h3>
                    <div id="modal-status-badge" class="modal-status-badge pending">
                        <span class="modal-status-dot"></span>
                        <span id="modal-status-text">Pending</span>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <div class="modal-info-grid">
                    <div class="modal-info-item">
                        <span class="modal-info-label">Level</span>
                        <span id="modal-doc-level" class="modal-info-value">N/A</span>
                    </div>
                    <div class="modal-info-item">
                        <span class="modal-info-label">Semester</span>
                        <span id="modal-doc-semester" class="modal-info-value">N/A</span>
                    </div>
                    <div class="modal-info-item">
                        <span class="modal-info-label">Program Type</span>
                        <span id="modal-doc-program" class="modal-info-value">N/A</span>
                    </div>
                    <div class="modal-info-item">
                        <span class="modal-info-label">Mode of Study</span>
                        <span id="modal-doc-mode" class="modal-info-value">N/A</span>
                    </div>
                    <div class="modal-info-item">
                        <span class="modal-info-label">Upload Date</span>
                        <span id="modal-doc-date" class="modal-info-value">N/A</span>
                    </div>
                    <div class="modal-info-item">
                        <span class="modal-info-label">Quality Scan Status</span>
                        <div class="scan-status-wrapper">
                            <span id="modal-scan-status" class="scan-badge pending">Pending</span>
                        </div>
                    </div>
                </div>
                
                <div id="modal-rejection-block" class="rejection-box" style="display: none;">
                    <div class="rejection-title-bar">
                        <span>Rejection Feedback</span>
                    </div>
                    <div id="modal-rejection-reason" class="rejection-reason-text">
                        This document was rejected.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDocumentDetailModal()">Close</button>
                <a id="modal-view-link" href="#" target="_blank" class="btn btn-primary">
                    View Secure Document
                </a>
                <a id="modal-download-link" href="#" class="btn btn-secondary">
                    Download
                </a>
            </div>
        </div>
    </div>

</div>

