<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon">
            <img src="/app/src/assets/images/Menu.svg" alt="Menu Icon">
        </span>
    </button>
    <div class="welcome">
        <h1>Analytics</h1>
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

<div class="analytics-container">
    <div class="coming-soon-content">
        <div class="coming-soon-icon">📊</div>
        <h2>Analytics Dashboard</h2>
        <p class="coming-soon-message">
            The analytics dashboard is currently under development. 
            This page will provide comprehensive insights and statistics about the system usage, 
            document uploads, verifications, and other key metrics.
        </p>
    </div>
</div>
