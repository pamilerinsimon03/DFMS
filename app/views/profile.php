<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon">
            <img src="/app/src/assets/images/Menu.svg" alt="Menu Icon">
        </span>
    </button>
    <div class="welcome">
        <h1>Welcome <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></h1>
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
<div class="card">
    <h5 class="card-header">Edit Profile</h5>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
        <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <form action="/app/controllers/profile.php" method="POST">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name"
                    value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="matric_number" class="form-label">Matric Number</label>
                <input type="text" class="form-control" id="matric_number"
                    value="<?php echo htmlspecialchars($user['matric_number']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="level" class="form-label">Level</label>
                <input type="text" class="form-control" id="level" name="level"
                    value="<?php echo htmlspecialchars($user['level']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="session" class="form-label">Session</label>
                <input type="text" class="form-control" id="session" name="session"
                    value="<?php echo htmlspecialchars($user['session']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="programme" class="form-label">Programme</label>
                <input type="text" class="form-control" id="programme" name="programme"
                    value="<?php echo htmlspecialchars($user['programme']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</div>