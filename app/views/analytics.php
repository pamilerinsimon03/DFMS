<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon">
            <img src="/app/src/assets/images/Menu.svg" alt="Menu Icon">
        </span>
    </button>
    <div class="welcome">
        <h1>System Analytics</h1>
    </div>
    <div class="header-right">
        <div class="search-container" style="opacity: 0.5; pointer-events: none;">
            <div class="search-box">
                <div class="search-icon">
                    <img src="/app/src/assets/images/search-normal.svg" alt="Search Icon">
                </div>
                <input type="text" placeholder="Search" class="search-input" disabled>
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

<div class="analytics-container">
    <!-- Glassmorphic Grid Cards -->
    <div class="stats-grid">
        <div class="stat-card glass-card">
            <div class="stat-icon students-icon"><i data-lucide="users"></i></div>
            <div class="stat-details">
                <span class="stat-label">Total Students</span>
                <h2 class="stat-value"><?php echo number_format($total_students); ?></h2>
            </div>
        </div>

        <div class="stat-card glass-card">
            <div class="stat-icon uploads-icon"><i data-lucide="file-text"></i></div>
            <div class="stat-details">
                <span class="stat-label">Total Uploads</span>
                <h2 class="stat-value"><?php echo number_format($total_uploads); ?></h2>
            </div>
        </div>

        <div class="stat-card glass-card">
            <div class="stat-icon approved-icon"><i data-lucide="shield-check"></i></div>
            <div class="stat-details">
                <span class="stat-label">Approved Files</span>
                <h2 class="stat-value text-success"><?php echo number_format($approved_count); ?></h2>
            </div>
        </div>

        <div class="stat-card glass-card">
            <div class="stat-icon pending-icon"><i data-lucide="clock"></i></div>
            <div class="stat-details">
                <span class="stat-label">Pending Reviews</span>
                <h2 class="stat-value text-warning"><?php echo number_format($pending_count); ?></h2>
            </div>
        </div>
    </div>

    <!-- Charts Grid Section -->
    <div class="charts-grid">
        <div class="chart-card glass-card">
            <div class="chart-header">
                <h3>Monthly Upload Trend</h3>
                <p>Track student document submissions over the last 6 months</p>
            </div>
            <div class="chart-wrapper">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <div class="chart-card glass-card">
            <div class="chart-header">
                <h3>Verification Distribution</h3>
                <p>Status breakdown of all uploaded academic records</p>
            </div>
            <div class="chart-wrapper doughnut-wrapper">
                <canvas id="verificationRateChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Security Logs (Super Admin Only) -->
    <?php if ($is_super_admin): ?>
    <div class="audit-card glass-card">
        <div class="audit-header">
            <h3>System Security Audit Log</h3>
            <p>Real-time security events, administrator actions, and access requests</p>
        </div>
        <div class="audit-table-wrapper">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Description</th>
                        <th>User</th>
                        <th>IP Address</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($audit_logs)): ?>
                    <tr>
                        <td colspan="5" class="no-logs">No security events logged yet.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($audit_logs as $log): ?>
                    <tr>
                        <td>
                            <span class="badge badge-event <?php echo htmlspecialchars($log['event_type']); ?>">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $log['event_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['event_description'] ?? 'No description'); ?></td>
                        <td>
                            <?php if ($log['user_id']): ?>
                                <strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($log['matric_number']); ?></small>
                            <?php else: ?>
                                <span class="text-muted">System / Guest</span>
                            <?php endif; ?>
                        </td>
                        <td><code class="ip-code"><?php echo htmlspecialchars($log['ip_address'] ?? '127.0.0.1'); ?></code></td>
                        <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    function initAnalyticsCharts() {
        const trendCtx = document.getElementById('monthlyTrendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months_list); ?>,
                    datasets: [{
                        label: 'Document Uploads',
                        data: <?php echo json_encode($monthly_data); ?>,
                        borderColor: '#01017A',
                        backgroundColor: 'rgba(1, 1, 122, 0.05)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#FAA62C',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { 
                                stepSize: 1,
                                font: { family: 'Poppins' }
                            }
                        },
                        x: {
                            ticks: { font: { family: 'Poppins' } }
                        }
                    }
                }
            });
        }

        const distCtx = document.getElementById('verificationRateChart');
        if (distCtx) {
            new Chart(distCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Approved', 'Pending', 'Rejected'],
                    datasets: [{
                        data: [
                            <?php echo $approved_count; ?>,
                            <?php echo $pending_count; ?>,
                            <?php echo $rejected_count; ?>
                        ],
                        backgroundColor: ['#54C242', '#F3CA12', '#F21B3F'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: { family: 'Poppins', size: 12 }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }
    }

    // Run charts initialization
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initAnalyticsCharts, 100);
    } else {
        document.addEventListener("DOMContentLoaded", initAnalyticsCharts);
    }
    
    // Bind to window to allow manual re-initialization if SPA transitions load
    window.initAnalyticsCharts = initAnalyticsCharts;
    
    if (window.lucide) {
        window.lucide.createIcons();
    }
})();
</script>
