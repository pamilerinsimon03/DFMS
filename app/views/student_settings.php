<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon">
            <img src="/app/src/assets/images/Menu.svg" alt="Menu Icon">
        </span>
    </button>
    <div class="welcome">
        <h1>Settings</h1>
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
<div class="header-divider"></div>

<div class="settings-container">
    <div class="settings-header">
        <h2>Account Settings</h2>
        <p>Manage your account preferences and security settings</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <div class="alert-icon">
                <img src="/app/src/assets/images/Close.svg" alt="Error Icon">
            </div>
            <div class="alert-message"><?php echo htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <div class="alert-icon">
                <img src="/app/src/assets/images/Check.svg" alt="Success Icon">
            </div>
            <div class="alert-message"><?php echo htmlspecialchars($success); ?></div>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        <div class="settings-card">
            <div class="card-header">
                <div class="card-icon">
                    <img src="/app/src/assets/images/settings.svg" alt="Password Icon">
                </div>
                <div class="card-title">
                    <h3>Password Management</h3>
                    <p>Change your account password</p>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="POST" class="settings-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="input-group">
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-control" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                <img src="/app/src/assets/images/eye.svg" alt="Toggle Password">
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="input-group">
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-control" required minlength="8">
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                <img src="/app/src/assets/images/eye.svg" alt="Toggle Password">
                            </button>
                        </div>
                        <small class="form-text">Password must be at least 8 characters long</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" required minlength="8">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <img src="/app/src/assets/images/eye.svg" alt="Toggle Password">
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <div class="card-icon">
                    <img src="/app/src/assets/images/User-Circle.svg" alt="Account Icon">
                </div>
                <div class="card-title">
                    <h3>Account Information</h3>
                    <p>View your account details</p>
                </div>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Matric Number</label>
                        <span><?php echo htmlspecialchars($user['matric_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Full Name</label>
                        <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Level</label>
                        <span><?php echo htmlspecialchars($user['level']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Session</label>
                        <span><?php echo htmlspecialchars($user['session']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Programme</label>
                        <span><?php echo htmlspecialchars($user['programme']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Program Type</label>
                        <span><?php echo htmlspecialchars($user['program_type']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Mode</label>
                        <span><?php echo htmlspecialchars($user['mode']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Account Created</label>
                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
                <div class="card-actions">
                    <a href="/app/controllers/student_profile.php" class="btn btn-secondary">Update Profile</a>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <div class="card-icon">
                    <img src="/app/src/assets/images/Bold Eye.svg" alt="Security Icon">
                </div>
                <div class="card-title">
                    <h3>Security Tips</h3>
                    <p>Keep your account secure</p>
                </div>
            </div>
            <div class="card-body">
                <div class="tips-list">
                    <div class="tip-item">
                        <div class="tip-icon">
                            <img src="/app/src/assets/images/Check.svg" alt="Tip Icon">
                        </div>
                        <div class="tip-content">
                            <h4>Use a Strong Password</h4>
                            <p>Include uppercase, lowercase, numbers, and special characters</p>
                        </div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon">
                            <img src="/app/src/assets/images/Check.svg" alt="Tip Icon">
                        </div>
                        <div class="tip-content">
                            <h4>Never Share Your Password</h4>
                            <p>Keep your password confidential and don't share it with anyone</p>
                        </div>
                    </div>
                    <div class="tip-item">
                        <div class="tip-icon">
                            <img src="/app/src/assets/images/Check.svg" alt="Tip Icon">
                        </div>
                        <div class="tip-content">
                            <h4>Log Out When Done</h4>
                            <p>Always log out when you're finished using the system</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <div class="card-icon">
                    <img src="/app/src/assets/images/Document Purple.svg" alt="Data Protection Icon">
                </div>
                <div class="card-title">
                    <h3>Data Protection & Privacy Rights</h3>
                    <p>Manage your rights under NDPA / NDPR 2019</p>
                </div>
            </div>
            <div class="card-body">
                <div class="tips-list" style="margin-bottom: 20px;">
                    <div class="tip-item">
                        <div class="tip-icon">
                            <img src="/app/src/assets/images/Check.svg" alt="Check Icon">
                        </div>
                        <div class="tip-content">
                            <h4>Self-Service Data Rights</h4>
                            <p>Request data access exports, rectify personal details, or request data deletion under institutional retention policies.</p>
                        </div>
                    </div>
                </div>
                <div class="card-actions">
                    <a href="/app/controllers/data_subject_rights.php" class="btn btn-primary" style="display: inline-flex; align-items: center; text-decoration: none;">
                        Manage Privacy & Data Rights
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('img');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.src = '/app/src/assets/images/eye-slash.svg';
    } else {
        input.type = 'password';
        icon.src = '/app/src/assets/images/eye.svg';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});
</script>
