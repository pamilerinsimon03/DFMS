<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon">
            <img src="/app/src/assets/images/Menu.svg" alt="Menu Icon">
        </span>
    </button>
    <div class="welcome">
        <h1>Welcome <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></h1>
    </div>
    <div class="header-right">
        <div class="search-container">
            <div class="search-box">
                <div class="search-icon">
                    <img src="/app/src/assets/images/search-normal.svg" alt="Search Icon">
                </div>
                <input type="text" placeholder="Search here" class="search-input">
            </div>
            <div class="notification-icon">
                <img src="/app/src/assets/images/notification-bing.svg" alt="Notification Icon">
                <div class="notification-dot"></div>
            </div>
        </div>
        <div class="user-profile">
            <img src="/app/src/assets/images/Small Avatar.svg" alt="User Profile" class="avatar">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="user-id">Matric No: <?php echo htmlspecialchars($user['matric_number']); ?></div>
            </div>
            <div class="dropdown-icon">
                <img src="/app/src/assets/images/arrow-down.svg" alt="Dropdown Icon">
            </div>
        </div>
    </div>
</div>
<div class="header-divider"></div>
<div class="uploads-content">
    <?php if (isset($_SESSION['upload_message'])): ?>
    <div
        class="upload-message <?php echo strpos($_SESSION['upload_message'], 'successfully') !== false ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($_SESSION['upload_message']); unset($_SESSION['upload_message']); ?>
    </div>
    <?php endif; ?>
    <div class="uploads-tabs">
        <div class="tabs-left">
            <a href="/app/controllers/uploads.php?tab=total"
                class="tab-item <?php echo $tab === 'total' ? 'active' : ''; ?>">Total Uploads</a>
            <a href="/app/controllers/uploads.php?tab=pending"
                class="tab-item <?php echo $tab === 'pending' ? 'active' : ''; ?>">Pending Verification</a>
            <a href="/app/controllers/uploads.php?tab=verified"
                class="tab-item <?php echo $tab === 'verified' ? 'active' : ''; ?>">Verified</a>
            <a href="/app/controllers/uploads.php?tab=failed"
                class="tab-item <?php echo $tab === 'failed' ? 'active' : ''; ?>">Failed</a>
        </div>
    </div>
    <div class="uploads-table">
        <div class="table-header">
            <div class="th-sn"><span>SN</span><img src="/app/src/assets/images/arrow-down-small.svg" alt="Sort"></div>
            <div class="th-name">Name</div>
            <div class="th-document">Document Name</div>
            <div class="th-date"><span>Date</span><img src="/app/src/assets/images/arrow-down-small.svg" alt="Sort">
            </div>
            <div class="th-status">Status</div>
            <div class="th-action">Action</div>
        </div>
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
                <form action="/app/controllers/uploads.php?tab=<?php echo htmlspecialchars($tab); ?>" method="POST"
                    enctype="multipart/form-data" style="display: inline;">
                    <input type="hidden" name="reupload_id" value="<?php echo $doc['id']; ?>">
                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required class="reupload-input"
                        id="reupload-<?php echo $doc['id']; ?>" onchange="this.form.submit()">
                    <button type="button" class="btn-danger"
                        onclick="document.getElementById('reupload-<?php echo $doc['id']; ?>').click()">Reupload
                        Document</button>
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