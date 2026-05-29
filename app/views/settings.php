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
        <h2>System Settings</h2>
        <p>Manage system configuration and user settings</p>
    </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
    
        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        <div class="settings-card">
            <div class="card-header">
                <h3>Password Management</h3>
                <div class="card-icon"><i data-lucide="lock"></i></div>
            </div>
            <div class="card-content">
                <?php if ($user['matric_number'] === 'SUPER_ADMIN'): ?>
                    <form method="POST" class="password-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <div class="form-group">
                            <label for="admin_matric">Select Admin</label>
                            <select name="admin_matric" id="admin_matric" required>
                                <option value="">Choose an admin...</option>
                                <option value="ADMIN_PSYC">Psychology Admin</option>
                                <option value="ADMIN_ENG">English Admin</option>
                                <option value="ADMIN_SOC">Sociology Admin</option>
                                <option value="ADMIN_LING">Linguistics Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="new_admin_password">New Password</label>
                            <input type="password" name="new_admin_password" id="new_admin_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_admin_password">Confirm Password</label>
                            <input type="password" name="confirm_admin_password" id="confirm_admin_password" required>
                        </div>
                        <button type="submit" name="action" value="change_admin_password" class="btn btn-primary">
                            Update Admin Password
                        </button>
                    </form>
                <?php else: ?>
                    <div class="info-box">
                        <p><strong>Student Password Update</strong></p>
                        <p>Update all existing student passwords to the new format: <strong>surname12345</strong></p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to update all student passwords? This action cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <button type="submit" name="action" value="update_passwords" class="btn btn-warning">
                                <span class="btn-icon"><i data-lucide="refresh-cw"></i></span>
                                Update All Student Passwords
                            </button>
                        </form>
                    </div>
        <?php endif; ?>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h3>System Information</h3>
                <div class="card-icon"><i data-lucide="info"></i></div>
            </div>
            <div class="card-content">
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">System Version:</span>
                        <span class="info-value">DFMS v2.0</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">PHP Version:</span>
                        <span class="info-value"><?php echo phpversion(); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Database:</span>
                        <span class="info-value">MySQL</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Updated:</span>
                        <span class="info-value"><?php echo date('F j, Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h3>User Management</h3>
                <div class="card-icon"><i data-lucide="users"></i></div>
            </div>
            <div class="card-content">
                <div class="action-buttons">
                    <a href="/app/controllers/add_student.php" class="btn btn-primary">
                        <span class="btn-icon"><i data-lucide="plus"></i></span>
                        Add Individual Student
                    </a>
                    <a href="/app/controllers/bulk_import.php" class="btn btn-secondary">
                        <span class="btn-icon"><i data-lucide="file-down"></i></span>
                        Bulk Import Students
                    </a>
                </div>
                <div class="quick-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_students ?? 'N/A'; ?></span>
                        <span class="stat-label">Total Students</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_departments ?? '5'; ?></span>
                        <span class="stat-label">Departments</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($user['matric_number'] === 'SUPER_ADMIN'): ?>
        <div class="settings-card">
            <div class="card-header">
                <h3>System Preferences</h3>
                <div class="card-icon"><i data-lucide="settings"></i></div>
            </div>
            <div class="card-content">
                <form method="POST" class="preferences-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="form-group">
                        <label for="session_year">Current Academic Session</label>
                        <input type="text" name="session_year" id="session_year" 
                               value="<?php echo htmlspecialchars($session_year ?? '2023/2024'); ?>" 
                               placeholder="e.g., 2023/2024">
                    </div>
                    <div class="form-group">
                        <label for="max_file_size">Maximum File Size (MB)</label>
                        <input type="number" name="max_file_size" id="max_file_size" 
                               value="<?php echo htmlspecialchars($max_file_size ?? '5'); ?>" 
                               min="1" max="50">
                    </div>
                    <div class="form-group">
                        <label for="auto_approve">Auto-approve Documents</label>
                        <select name="auto_approve" id="auto_approve">
                            <option value="0" <?php echo ($auto_approve ?? '0') == '0' ? 'selected' : ''; ?>>Disabled</option>
                            <option value="1" <?php echo ($auto_approve ?? '0') == '1' ? 'selected' : ''; ?>>Enabled</option>
                        </select>
                    </div>
                    <button type="submit" name="action" value="update_preferences" class="btn btn-primary">
                        Save Preferences
                    </button>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h3>Backup & Maintenance</h3>
                <div class="card-icon"><i data-lucide="wrench"></i></div>
            </div>
            <div class="card-content">
                <form method="POST" class="maintenance-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="maintenance_mode" <?php echo ($maintenance_mode ?? false) ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Enable System Maintenance Mode
                        </label>
                    </div>
                    <div class="action-buttons" style="display: flex; gap: 12px; margin-top: 16px;">
                        <a href="/app/controllers/backup.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                            <span class="btn-icon"><i data-lucide="save"></i></span>
                            Create Backup
                        </a>
                        <button type="submit" name="action" value="update_maintenance" class="btn btn-warning" style="display: inline-flex; align-items: center; gap: 8px;">
                            <span class="btn-icon"><i data-lucide="alert-triangle"></i></span>
                            Update Status
                        </button>
                    </div>
                </form>
                <div class="info-box" style="margin-top: 20px; padding: 12px; background: rgba(1, 1, 122, 0.03); border-radius: 6px;">
                    <p style="margin: 0 0 8px 0;"><strong>Last Backup:</strong> <?php echo htmlspecialchars($last_backup_date ?? 'Never'); ?></p>
                    <p style="margin: 0;"><strong>System Status:</strong> 
                        <?php if ($maintenance_mode ?? false): ?>
                            <span class="status-error" style="color: #F21B3F; font-weight: 600;">Maintenance Mode Active</span>
                        <?php else: ?>
                            <span class="status-ok" style="color: #54C242; font-weight: 600;">Operational</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h3>Security Settings</h3>
                <div class="card-icon"><i data-lucide="shield-check"></i></div>
            </div>
            <div class="card-content">
                <form method="POST" class="security-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="enable_2fa" <?php echo ($enable_2fa ?? false) ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Enable Two-Factor Authentication
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="session_timeout" <?php echo ($session_timeout ?? true) ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Enable Session Timeout (30 minutes)
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="login_notifications" <?php echo ($login_notifications ?? false) ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Email Login Notifications
                        </label>
                    </div>
                    <button type="submit" name="action" value="update_security" class="btn btn-primary">
                        Update Security Settings
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>