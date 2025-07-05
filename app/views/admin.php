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
            <form action="/app/controllers/admin.php" method="GET">
                <div class="date-input">
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    <img src="/app/src/assets/images/calendar.svg" alt="Calendar">
                </div>
                <span>-</span>
                <div class="date-input">
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    <img src="/app/src/assets/images/calendar.svg" alt="Calendar">
                </div>
                <button type="submit" style="display: none;"></button>
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
<div class="kpi-cards">
    <div class="kpi-card bg-blue">
        <div>
            <div class="kpi-number"><?php echo number_format($total_docs); ?></div>
            <div class="kpi-label">Total Documents</div>
        </div>
        <div class="kpi-icon"><img src="/app/src/assets/images/Document Purple.svg" alt="Documents Icon"></div>
    </div>
    <div class="kpi-card bg-green">
        <div>
            <div class="kpi-number"><?php echo number_format($verified_docs); ?></div>
            <div class="kpi-label">Verified Documents</div>
        </div>
        <div class="kpi-icon"><img src="/app/src/assets/images/Check.svg" alt="Check Icon"></div>
    </div>
    <div class="kpi-card bg-yellow">
        <div>
            <div class="kpi-number"><?php echo number_format($pending_docs); ?></div>
            <div class="kpi-label">Pending Documents</div>
        </div>
        <div class="kpi-icon"><img src="/app/src/assets/images/Loading.svg" alt="Loading Icon"></div>
    </div>
</div>
<div class="charts-row">
    <div class="chart-container">
        <h3>Uploads This Session</h3>
        <canvas id="uploads-by-month"></canvas>
    </div>
    <div class="chart-container">
        <h3>Verification Breakdown</h3>
        <canvas id="uploads-by-status"></canvas>
    </div>
</div>
<div class="table-section">
    <div class="table-header">
        <h3>Documents to be Verified</h3><a href="/app/controllers/verifications.php">See all</a>
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
                <tr>
                    <td colspan="7">No pending documents found.</td>
                </tr>
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
                            <img src="/app/src/assets/images/more.svg" alt="More Options">
                            <div class="action-menu-content">
                                <form
                                    action="/app/controllers/admin.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>"
                                    method="POST" style="display: inline;">
                                    <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                    <input type="hidden" name="action" value="verify">
                                    <button type="submit">Verify</button>
                                </form>
                                <form
                                    action="/app/controllers/admin.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>"
                                    method="POST" style="display: inline;">
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
<script>
const barChartData = {
    labels: <?php echo json_encode($months); ?>,
    datasets: [{
        label: 'Document Uploads',
        data: <?php echo json_encode($upload_counts); ?>,
        backgroundColor: '#01017A',
        borderRadius: 4,
        barThickness: 10
    }]
};

const pieChartData = {
    labels: ['Pending', 'Verified', 'Failed'],
    datasets: [{
        data: <?php echo json_encode($breakdown_counts); ?>,
        backgroundColor: ['#FAA62C', '#009A49', '#FF3B30'],
    }]
};
</script>