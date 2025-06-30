<div id="mobile-sidebar-overlay" class="sidebar-overlay"></div>
<div id="sidebar" class="sidebar">
    <div class="sidebar-content">
        <img class="logo" src="/app/src/assets/images/ui-logo.png" alt="University of Ibadan Logo">
        <div class="sidebar-menu">
            <div class="menu-items">
                <a href="/app/controllers/admin.php"
                    class="menu-item <?php echo $active_page === 'admin' ? 'active' : ''; ?>">
                    <div class="menu-icon"><img src="/app/src/assets/images/home.svg" alt="Dashboard Icon"></div>
                    <div class="menu-text">Dashboard</div>
                </a>
                <a href="/app/controllers/verifications.php"
                    class="menu-item <?php echo $active_page === 'verifications' ? 'active' : ''; ?>">
                    <div class="menu-icon"><img src="/app/src/assets/images/verification.svg" alt="Verifications Icon">
                    </div>
                    <div class="menu-text">Verifications</div>
                </a>
                <a href="#" class="menu-item">
                    <div class="menu-icon"><img src="/app/src/assets/images/analytics.svg" alt="Analytics Icon"></div>
                    <div class="menu-text">Analytics</div>
                </a>
                <a href="/app/controllers/settings.php"
                    class="menu-item <?php echo $active_page === 'settings' ? 'active' : ''; ?>">
                    <div class="menu-icon"><img src="/app/src/assets/images/settings.svg" alt="Settings Icon"></div>
                    <div class="menu-text">Settings</div>
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