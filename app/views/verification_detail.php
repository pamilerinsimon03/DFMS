<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="search-container">
        <div class="search-box">
            <div class="search-icon"><img src="/app/src/assets/images/search-normal.svg" alt="Search Icon"></div>
            <input type="text" placeholder="Search here" class="search-input">
        </div>
        <div class="date-filter">
            <form id="date-filter-form" action="/app/controllers/verifications.php" method="GET">
                <input type="hidden" name="tab" value="all">
                <div class="date-input">
                    <input type="date" name="start_date" id="start-date"
                        value="<?php echo date('Y-m-d', strtotime($doc['created_at'])); ?>">
                    <img src="/app/src/assets/images/calendar.svg" alt="Calendar">
                </div>
                <span>-</span>
                <div class="date-input">
                    <input type="date" name="end_date" id="end-date" value="<?php echo date('Y-m-d'); ?>">
                    <img src="/app/src/assets/images/calendar.svg" alt="Calendar">
                </div>
                <button type="submit" class="filter-button">Apply Filter</button>
            </form>
        </div>
    </div>
    <div class="header-right">
        <div class="notification-icon"><img src="/app/src/assets/images/notification-bing.svg" alt="Notification Icon">
        </div>
        <div class="user-profile">
            <img class="avatar" src="/app/src/assets/images/admin-avatar.png" alt="Admin Avatar">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="user-id"><?php echo htmlspecialchars($user['full_name']); ?></div>
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
                <object class="doc-embed"
                    data="/public/uploads/<?php echo htmlspecialchars(basename($doc['file_path'])); ?>"
                    type="application/pdf">
                    <img src="/app/src/assets/images/PDF Icon Pic.png" alt="Document Preview">
                </object>
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
    <?php if ($doc['status'] === 'Pending'): ?>
    <div class="action-row">
        <form action="/app/controllers/verification_detail.php?doc_id=<?php echo $doc_id; ?>" method="POST"
            style="display: inline;">
            <input type="hidden" name="action" value="approve">
            <button type="submit" class="approve-btn"><img src="/app/src/assets/images/Check.svg" class="btn-icon"
                    alt="">Approve</button>
        </form>
        <form action="/app/controllers/verification_detail.php?doc_id=<?php echo $doc_id; ?>" method="POST"
            style="display: inline;">
            <input type="hidden" name="action" value="reject">
            <button type="submit" class="reject-btn"><img src="/app/src/assets/images/Close.svg" class="btn-icon"
                    alt="">Reject</button>
        </form>
    </div>
    <?php endif; ?>
</div>
<div class="doc-modal">
    <div class="doc-modal-content">
        <img id="modal-img" src="" alt="Full Document">
        <div class="close-modal"><img src="/app/src/assets/images/Close.svg" alt="Close"></div>
    </div>
</div>