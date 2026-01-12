<div id="mobile-sidebar-overlay" class="sidebar-overlay"></div>
<div id="sidebar" class="sidebar">
    <div class="sidebar-content">
        <button id="mobile-sidebar-close" class="mobile-close-btn">&times;</button>
        <img class="logo" src="/app/src/assets/images/ui-logo.png" alt="University of Ibadan Logo">
        <div class="sidebar-menu">
            <div class="menu-items">
                <a href="/app/controllers/dashboard.php"
                    class="menu-item <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/<?php echo $active_page === 'dashboard' ? 'home.svg' : 'home white.svg'; ?>"
                            data-active-src="/app/src/assets/images/home.svg"
                            data-inactive-src="/app/src/assets/images/home white.svg"
                            alt="Dashboard Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'dashboard' ? 'active-text' : ''; ?>">Dashboard
                    </div>
                </a>
                <a href="/app/controllers/uploads.php"
                    class="menu-item <?php echo $active_page === 'uploads' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/<?php echo $active_page === 'uploads' ? 'Document Purple.svg' : 'Document.svg'; ?>"
                            data-active-src="/app/src/assets/images/Document Purple.svg"
                            data-inactive-src="/app/src/assets/images/Document.svg"
                            alt="Uploads Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'uploads' ? 'active-text' : ''; ?>">My Uploads
                    </div>
                </a>
                <a href="<?php echo User::isAdmin($_SESSION['user_id'] ?? 0) ? '/app/controllers/student_profile.php' : '/app/controllers/profile.php'; ?>"
                    class="menu-item <?php echo $active_page === 'profile' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/<?php echo $active_page === 'profile' ? 'User-Circle-Purple.svg' : 'User-Circle.svg'; ?>"
                            data-active-src="/app/src/assets/images/User-Circle-Purple.svg"
                            data-inactive-src="/app/src/assets/images/User-Circle.svg"
                            alt="Profile Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'profile' ? 'active-text' : ''; ?>">Profile</div>
                </a>
                <a href="<?php echo User::isAdmin($_SESSION['user_id'] ?? 0) ? '/app/controllers/settings.php' : '/app/controllers/student_settings.php'; ?>"
                    class="menu-item <?php echo $active_page === 'settings' ? 'active' : ''; ?>">
                    <div class="menu-icon">
                        <img src="/app/src/assets/images/settings.svg" 
                            data-active-src="/app/src/assets/images/settings.svg"
                            data-inactive-src="/app/src/assets/images/settings.svg"
                            alt="Settings Icon">
                    </div>
                    <div class="menu-text <?php echo $active_page === 'settings' ? 'active-text' : ''; ?>">Settings
                    </div>
                </a>
            </div>
            <a href="/app/controllers/logout.php" class="menu-item logout"
                onclick="return confirm('Are you sure you want to log out?');">
                <div class="menu-icon"><img src="/app/src/assets/images/log-out.svg" alt="Logout Icon"></div>
                <div class="menu-text">Log out</div>
            </a>
        </div>
    </div>
</div>
<div class="main-content">