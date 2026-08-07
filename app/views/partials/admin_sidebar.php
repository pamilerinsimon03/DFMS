<div id="mobile-sidebar-overlay" class="sidebar-overlay"></div>
<div id="sidebar" class="sidebar">
    <div class="sidebar-content">
        <img class="logo" src="/app/src/assets/images/ui-logo.png" alt="University of Ibadan Logo">
        <div class="sidebar-menu">
            <div class="menu-items">
                <a href="/app/controllers/admin.php"
                    class="menu-item <?php echo $active_page === 'admin' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/home<?php echo $active_page === 'admin' ? '' : ' white'; ?>.svg"
                            data-active-src="/app/src/assets/images/home.svg"
                            data-inactive-src="/app/src/assets/images/home white.svg"
                            alt="Dashboard Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'admin' ? 'active-text' : ''; ?>">
                        Dashboard</div>
                </a>
                <a href="/app/controllers/verifications.php"
                    class="menu-item <?php echo $active_page === 'verifications' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/verification<?php echo $active_page === 'verifications' ? ' purple' : ''; ?>.svg"
                            data-active-src="/app/src/assets/images/verification purple.svg"
                            data-inactive-src="/app/src/assets/images/verification.svg"
                            alt="Verifications Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'verifications' ? 'active-text' : ''; ?>">
                        Verifications</div>
                </a>
                <a href="/app/controllers/students.php"
                    class="menu-item <?php echo $active_page === 'students' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/User-Circle<?php echo $active_page === 'students' ? '-Purple' : ''; ?>.svg"
                            data-active-src="/app/src/assets/images/User-Circle-Purple.svg"
                            data-inactive-src="/app/src/assets/images/User-Circle.svg"
                            alt="Students Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'students' ? 'active-text' : ''; ?>">
                        Students</div>
                </a>
                <a href="/app/controllers/add_student.php"
                    class="menu-item <?php echo $active_page === 'add_student' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/User-Circle<?php echo $active_page === 'add_student' ? '-Purple' : ''; ?>.svg"
                            data-active-src="/app/src/assets/images/User-Circle-Purple.svg"
                            data-inactive-src="/app/src/assets/images/User-Circle.svg"
                            alt="Add Student Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'add_student' ? 'active-text' : ''; ?>">
                        Add Students</div>
                </a>
                <a href="/app/controllers/analytics.php" class="menu-item <?php echo $active_page === 'analytics' ? 'active' : ''; ?>">
                    <div class="menu-icon"><img src="/app/src/assets/images/actual-analytics<?php echo $active_page === 'analytics' ? '' : ''; ?>.svg" alt="Analytics Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'analytics' ? 'active-text' : ''; ?>">Analytics</div>
                </a>
                <?php if (isset($user['matric_number']) && $user['matric_number'] !== 'SUPER_ADMIN'): ?>
                <a href="/app/controllers/file_management.php"
                    class="menu-item <?php echo $active_page === 'file_management' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/folder-outline<?php echo $active_page === 'file_management' ? '' : ' white'; ?>.svg"
                            alt="File Management Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'file_management' ? 'active-text' : ''; ?>">
                        File Management</div>
                </a>
                <?php endif; ?>
                <?php if (isset($user['matric_number']) && $user['matric_number'] === 'SUPER_ADMIN'): ?>
                <a href="/app/controllers/session_rollover.php"
                    class="menu-item <?php echo $active_page === 'session_rollover' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/folder-outline.svg" alt="Session Roll-Over Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'session_rollover' ? 'active-text' : ''; ?>">
                        Session Roll-Over</div>
                </a>
                <a href="/app/controllers/audit_log_viewer.php"
                    class="menu-item <?php echo $active_page === 'audit_log_viewer' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/verification.svg" alt="Audit Logs Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'audit_log_viewer' ? 'active-text' : ''; ?>">
                        Audit Logs</div>
                </a>
                <?php endif; ?>
                <a href="/app/controllers/settings.php"
                    class="menu-item <?php echo $active_page === 'settings' ? 'active' : ''; ?>">
                    <div class="menu-icon"><img src="/app/src/assets/images/settings.svg" 
                        data-active-src="/app/src/assets/images/settings.svg"
                        data-inactive-src="/app/src/assets/images/settings.svg"
                        alt="Settings Icon"></div>
                    <div class="menu-text <?php echo $active_page === 'settings' ? 'active-text' : ''; ?>">Settings</div>
                </a>

            </div>
            <a href="/app/controllers/logout.php" class="menu-item logout">
                <div class="menu-icon"><img src="/app/src/assets/images/log-out.svg" alt="Logout Icon"></div>
                <div class="menu-text">Log out</div>
            </a>
        </div>
    </div>
</div>
<div class="main-content">