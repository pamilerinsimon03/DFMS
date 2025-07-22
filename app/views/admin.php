<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="search-container">
        <form action="/app/controllers/verifications.php" method="GET" class="search-box">
            <div class="search-icon"><img src="/app/src/assets/images/search-normal.svg" alt="Search Icon"></div>
            <input type="text" placeholder="Search here" class="search-input" name="search" id="search-input"
                value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        </form>
        <div class="date-filter">
            <form action="" method="GET">
                <div class="date-input">
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    <img src="/app/src/assets/images/calendar.svg" alt="Calendar">
                </div>
                <span>-</span>
                <div class="date-input">
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    <img src="/app/src/assets/images/calendar.svg" alt="Calendar">
                </div>
                <button type="submit" class="filter-button">Filter</button>
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
                <div class="user-id"><?php echo htmlspecialchars($user['matric_number']); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="kpi-cards">
    <div class="kpi-card bg-blue">
        <div>
            <div class="kpi-number"><?php echo number_format($total_docs ?? 0); ?></div>
            <div class="kpi-label">Total Documents<?php echo $user['department_id'] ? ' (My Dept)' : ''; ?></div>
        </div>
        <div class="kpi-icon"><img src="/app/src/assets/images/Document Purple.svg" alt="Documents Icon"></div>
    </div>
    <div class="kpi-card bg-green">
        <div>
            <div class="kpi-number"><?php echo number_format($approved_docs ?? 0); ?></div>
            <div class="kpi-label">Approved Documents<?php echo $user['department_id'] ? ' (My Dept)' : ''; ?></div>
        </div>
        <div class="kpi-icon"><img src="/app/src/assets/images/Check.svg" alt="Check Icon"></div>
    </div>
    <div class="kpi-card bg-yellow">
        <div>
            <div class="kpi-number"><?php echo number_format($pending_docs ?? 0); ?></div>
            <div class="kpi-label">Pending Documents<?php echo $user['department_id'] ? ' (My Dept)' : ''; ?></div>
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
        <h3>Documents to be Verified <?php echo $user['department_id'] ? '(My Dept)' : ''; ?></h3>
        <a href="/app/controllers/verifications.php?tab=pending">See all</a>
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
                    <th>Upload Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_pending)): ?>
                <tr>
                    <td colspan="7">No pending documents found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($recent_pending as $index => $doc): ?>
                <tr>
                    <td><?php echo $index + 1; ?>.</td>
                    <td><?php echo htmlspecialchars($doc['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($doc['department']); ?></td>
                    <td><?php echo htmlspecialchars($doc['level']); ?></td>
                    <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($doc['upload_date'] ?? 'now')); ?></td>
                    <td class="td-action">
                        <a href="/app/controllers/verification_detail.php?doc_id=<?php echo $doc['id']; ?>"
                            class="action-item">
                            <img src="/app/src/assets/images/Bold Eye.svg" alt="View"> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const barChartCtx = document.getElementById('uploads-by-month').getContext('2d');
    new Chart(barChartCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months ?? []); ?>,
            datasets: [{
                label: 'Document Uploads',
                data: <?php echo json_encode($upload_counts ?? []); ?>,
                backgroundColor: '#01017A',
                borderRadius: 4,
                barThickness: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    const pieChartCtx = document.getElementById('uploads-by-status').getContext('2d');
    new Chart(pieChartCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($breakdown_labels ?? []); ?>,
            datasets: [{
                data: <?php echo json_encode($breakdown_counts ?? []); ?>,
                backgroundColor: ['#FAA62C', '#009A49', '#FF3B30'],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>