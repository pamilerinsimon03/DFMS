<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="welcome">
        <h1>Dashboard</h1>
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
                        <a href="/app/controllers/verification_detail.php?doc_id=<?php echo htmlspecialchars($doc['public_id'] ?? $doc['id']); ?>"
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
(function() {
    function initDashboardCharts() {
        const barCanvas = document.getElementById('uploads-by-month');
        if (barCanvas) {
            const barChartCtx = barCanvas.getContext('2d');
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
        }
        
        const pieCanvas = document.getElementById('uploads-by-status');
        if (pieCanvas) {
            const pieChartCtx = pieCanvas.getContext('2d');
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
        }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initDashboardCharts, 100);
    } else {
        document.addEventListener('DOMContentLoaded', initDashboardCharts);
    }
})();
</script>