<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="welcome">
        <h1>Welcome <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></h1>
    </div>
    <div class="header-right">
        <div class="search-container" id="search-container">
            <form action="/app/controllers/uploads.php" method="GET" class="search-form">
                <div class="search-box">
                    <button type="button" class="search-icon-btn" id="search-icon-btn" aria-label="Open Search">
                        <img src="/app/src/assets/images/search-normal.svg" alt="Search Icon" class="search-icon-img">
                    </button>
                    <input type="text" name="search" placeholder="Search here" class="search-input"
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
            </form>
        </div>
        <div class="notification-icon" id="notif-bell">
            <img src="/app/src/assets/images/notification-bing.svg" alt="Notification Icon">
            <?php if (($unread_count ?? 0) > 0): ?>
            <div class="notification-dot"></div>
            <?php endif; ?>
            <div class="notifications-dropdown" id="notif-dropdown" style="display:none;">
                <div class="notif-header">
                    <span>Notifications</span>
                    <button id="mark-all-read" class="mark-read-btn">Mark all as read</button>
                </div>
                <?php if (empty($notifications)): ?>
                <div class="notif-empty">No notifications yet.</div>
                <?php else: ?>
                <ul class="notif-list">
                    <?php foreach ($notifications as $n): ?>
                    <li class="notif-item <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                        <div class="notif-title"><?php echo htmlspecialchars($n['title']); ?></div>
                        <div class="notif-message"><?php echo htmlspecialchars($n['message']); ?></div>
                        <div class="notif-time"><?php echo date('M d, Y H:i', strtotime($n['created_at'])); ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
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
<div class="uploads-content">
    <div class="uploads-tabs">
        <div class="tabs-left">
            <a href="/app/controllers/uploads.php?tab=total"
                class="tab-item <?php echo $tab === 'total' ? 'active' : ''; ?>">Total Uploads</a>
            <a href="/app/controllers/uploads.php?tab=Pending%20Approval"
                class="tab-item <?php echo $tab === 'pending approval' ? 'active' : ''; ?>">Pending Verification</a>
            <a href="/app/controllers/uploads.php?tab=Approved"
                class="tab-item <?php echo $tab === 'approved' ? 'active' : ''; ?>">Verified</a>
            <a href="/app/controllers/uploads.php?tab=Rejected"
                class="tab-item <?php echo $tab === 'rejected' ? 'active' : ''; ?>">Rejected</a>
        </div>
    </div>
    <div class="uploads-table-wrapper">
    <table class="uploads-table">
        <thead>
            <tr>
                <th>SN</th>
                <th>Name</th>
                <th>Document Name</th>
                <th>
                    <span>Date</span>
                    <img src="/app/src/assets/images/arrow-down-small.svg" alt="Sort">
                </th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($documents)): ?>
            <tr class="table-row">
                <td colspan="6" class="td-sn">
                    No documents found.
                    <?php if ($tab !== 'total'): ?>
                    Try switching to "Total Uploads".
                    <?php else: ?>
                    Upload documents via the Dashboard.
                    <?php endif; ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($documents as $index => $doc): ?>
            <tr class="table-row">
                <td class="td-sn" data-label="SN"><span class="cell-value"><?php echo $index + 1; ?>.</span></td>
                <td class="td-name" data-label="Name"><span class="cell-value"><?php echo htmlspecialchars($doc['document_name']); ?></span></td>
                <td class="td-document" data-label="Document"><span class="cell-value truncate"><?php echo htmlspecialchars(basename($doc['file_path'])); ?></span></td>
                <td class="td-date" data-label="Date"><span class="cell-value"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></span></td>
                <td class="td-status <?php echo strtolower(str_replace(' ', '-', $doc['status'])); ?>" data-label="Status">
                    <span class="status-wrapper">
                        <span class="status-dot"></span>
                        <span class="status-text">
                            <?php echo htmlspecialchars($doc['status']); ?>
                        </span>
                    </span>
                </td>
                <td class="td-action" data-label="Action">
                    <?php if ($doc['status'] === 'Rejected'): ?>
                    <form action="/app/controllers/uploads.php?tab=<?php echo htmlspecialchars($tab); ?>" method="POST"
                        enctype="multipart/form-data" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="reupload_id" value="<?php echo $doc['id']; ?>">
                        <input type="file" id="reupload-<?php echo $doc['id']; ?>" name="file"
                            accept=".pdf,.jpg,.jpeg,.png" required style="display: none;" onchange="this.form.submit()">
                        <label for="reupload-<?php echo $doc['id']; ?>" class="btn-danger">
                            Reupload Document
                        </label>
                    </form>
                    <?php elseif ($doc['status'] === 'Approved'): ?>
                    <button class="btn-success" disabled>Successful</button>
                    <?php else: ?>
                    <button class="btn-warning" disabled>
                        Awaiting Verification
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php if (isset($_SESSION['upload_message'])): ?>
    <div class="upload-message">
        <?php echo htmlspecialchars($_SESSION['upload_message']); unset($_SESSION['upload_message']); ?>
    </div>
    <?php endif; ?>
</div>