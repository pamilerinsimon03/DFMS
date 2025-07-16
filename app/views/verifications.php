<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="search-container">
        <div class="search-box">
            <div class="search-icon"><img src="/app/src/assets/images/search-normal.svg" alt="Search Icon"></div>
            <input type="text" placeholder="Search here" class="search-input" id="search-input"
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="date-filter">
            <form id="date-filter-form" action="/app/controllers/verifications.php" method="GET">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                <input type="hidden" name="search" id="form-search" value="<?php echo htmlspecialchars($search); ?>">
                <div class="date-input">
                    <input type="date" name="start_date" id="start-date"
                        value="<?php echo htmlspecialchars($start_date); ?>">
                    <img src="/app/src/assets/images/calendar.svg" alt="Calendar">
                </div>
                <span>-</span>
                <div class="date-input">
                    <input type="date" name="end_date" id="end-date" value="<?php echo htmlspecialchars($end_date); ?>">
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
<div class="uploads-content">
    <div class="uploads-tabs">
        <div class="tabs-left">
            <a href="/app/controllers/verifications.php?tab=all"
                class="tab-item <?php echo $tab === 'all' ? 'active' : ''; ?>">Documents Uploaded</a>
            <a href="/app/controllers/verifications.php?tab=pending"
                class="tab-item <?php echo $tab === 'pending' ? 'active' : ''; ?>">Documents to be Verified</a>
            <a href="/app/controllers/verifications.php?tab=verified"
                class="tab-item <?php echo $tab === 'verified' ? 'active' : ''; ?>">Documents Verified</a>
            <a href="/app/controllers/verifications.php?tab=rejected"
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
                    <?php $status_slug = 'status-' . strtolower($doc['status']); ?>
                    <tr class="<?php echo $status_slug; ?>">
                        <td><?php echo ($page - 1) * $per_page + $index + 1; ?>.</td>
                        <td><?php echo htmlspecialchars($doc['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($doc['department']); ?></td>
                        <td><?php echo htmlspecialchars($doc['level']); ?></td>
                        <td><a href="/app/controllers/verification_detail.php?doc_id=<?php echo $doc['id']; ?>"
                                class="doc-link"><?php echo htmlspecialchars($doc['document_name']); ?></a></td>
                        <td><?php echo htmlspecialchars($doc['session']); ?></td>
                        <td><?php echo htmlspecialchars($doc['status']); ?></td>
                        <td class="td-action">
                            <div class="action-menu">
                                <img src="/app/src/assets/images/more.svg" alt="More" class="action-btn">
                                <div class="action-menu-content">
                                    <a href="/app/controllers/verification_detail.php?doc_id=<?php echo $doc['id']; ?>"
                                        class="action-item">
                                        <img src="/app/src/assets/images/Bold Eye.svg" alt="View"> View
                                    </a>
                                    <?php if ($doc['status'] === 'Pending Approval'): ?>
                                    <form
                                        action="/app/controllers/verifications.php?tab=<?php echo urlencode($tab); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>"
                                        method="POST">
                                        <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                        <input type="hidden" name="action" value="verify">
                                        <button type="submit" class="action-item verify"><img
                                                src="/app/src/assets/images/Check.svg" alt="Verify">Verify
                                        </button>
                                    </form>
                                    <form
                                        action="/app/controllers/verifications.php?tab=<?php echo urlencode($tab); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>"
                                        method="POST">
                                        <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="action-item reject">
                                            <img src="/app/src/assets/images/Trash.svg" alt="Reject"> Reject
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
                href="/app/controllers/verifications.php?tab=<?php echo urlencode($tab); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="/app/controllers/verifications.php?tab=<?php echo urlencode($tab); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"
                class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
            <a
                href="/app/controllers/verifications.php?tab=<?php echo urlencode($tab); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>