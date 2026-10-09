<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="welcome">
        <h1>Verification Details</h1>
    </div>
    <div class="header-right">
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
            <div class="dropdown-icon">
                <img src="/app/src/assets/images/arrow-down.svg" alt="Dropdown Icon">
            </div>
            
            <div class="profile-dropdown">
                <a href="<?php echo User::isAdmin($_SESSION['user_id'] ?? 0) ? '/app/controllers/student_profile.php' : '/app/controllers/profile.php'; ?>" class="dropdown-item">
                    <img src="/app/src/assets/images/User-Circle.svg" alt="Profile"> Profile
                </a>
                <a href="<?php echo User::isAdmin($_SESSION['user_id'] ?? 0) ? '/app/controllers/settings.php' : '/app/controllers/student_settings.php'; ?>" class="dropdown-item">
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
<div class="subheader">
    <a href="/app/controllers/verifications.php" class="back-btn">
        <img src="/app/src/assets/images/Arrow - Left.svg" alt="Back" class="back-icon">
    </a>
</div>
<div class="detail-page-content">
    <div class="detail-row">
        <div class="doc-preview-container">
            <div class="doc-preview">
                <iframe class="doc-embed"
                    src="/app/controllers/secure_file_viewer.php?id=<?php echo htmlspecialchars($doc['public_id'] ?? $doc['id']); ?>"></iframe>
                <div class="zoom-icon"><img src="/app/src/assets/images/Zoom - In.svg" alt="Zoom"></div>
            </div>
        </div>
        <div class="cards-wrapper">
            <div class="student-card">
                <img class="avatar-large" src="/app/src/assets/images/Small Avatar.svg" alt="Student">
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
                    <div class="value"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></div>
                </div>
                <div class="details-row">
                    <div class="label">Time Uploaded:</div>
                    <div class="value"><?php echo date('h:ia', strtotime($doc['created_at'])); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php if ($doc['status'] === 'Pending Approval'): ?>
    <div class="action-row">
        <form action="/app/controllers/verification_detail.php?doc_id=<?php echo htmlspecialchars($doc_id); ?>" method="POST"
            style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="doc_id" value="<?php echo htmlspecialchars($doc_id); ?>">
            <input type="hidden" name="action" value="approve">
            <button type="submit" class="approve-btn"><img src="/app/src/assets/images/Check.svg" class="btn-icon"
                    alt="">Approve</button>
        </form>
        <form id="reject-form" action="/app/controllers/verification_detail.php?doc_id=<?php echo htmlspecialchars($doc_id); ?>" method="POST"
            style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="doc_id" value="<?php echo htmlspecialchars($doc_id); ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="rejection_reason" id="hidden-rejection-reason">
            <button type="button" id="reject-trigger-btn" class="reject-btn"><img src="/app/src/assets/images/Close.svg" class="btn-icon"
                    alt="">Reject</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<div id="doc-modal" class="doc-modal" data-doc-name="<?php echo htmlspecialchars(basename($doc['file_path'])); ?>">
    <span class="close-modal">
        <img src="/app/src/assets/images/Close.svg" alt="Close" />
    </span>
    <div class="doc-modal-main">
        <iframe id="modal-iframe" src=""></iframe>
    </div>
    <div class="doc-modal-caption">
        <p id="doc-modal-caption-text"></p>
    </div>
</div>

<div id="rejection-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center;">
    <div class="modal-content" style="background: white; padding: 25px; border-radius: 12px; width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        <h3 style="margin-top: 0; color: #333; font-size: 1.25rem;">Reject Document</h3>
        <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">Please provide a reason for rejecting this document.</p>
        <textarea id="rejection-textarea" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; resize: vertical; margin-bottom: 20px; font-family: inherit;" placeholder="Reason for rejection..."></textarea>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" id="cancel-rejection" class="btn-secondary" style="background: #f3f3f3; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; color: #333; font-weight: 500;">Cancel</button>
            <button type="button" id="confirm-rejection" class="btn-danger" style="background: #FF3B30; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; color: white; font-weight: 500;">Reject Document</button>
        </div>
    </div>
</div>