<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon">
            <img src="/app/src/assets/images/Menu.svg" alt="Menu Icon">
        </span>
    </button>
    <div class="welcome">
        <h1>My Profile</h1>
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
            
            <div class="profile-dropdown" id="profile-dropdown-menu">
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
<div class="main-body-content">
    <div class="dashboard-content">
        <div class="profile-container">
    <?php if ($mandatory_update_required): ?>
        <div class="mandatory-update-alert">
            <div class="alert-icon">
                <img src="/app/src/assets/images/notification-bing.svg" alt="Alert Icon">
            </div>
            <div class="alert-content">
                <h3>Profile Update Required</h3>
                <p>Your account was created more than 48 hours ago. Please complete your profile information to continue using the system.</p>
            </div>
        </div>
    <?php endif; ?>

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

    <div class="profile-grid">
        <div class="profile-card">
            <div class="card-header">
                <div class="card-icon">
                    <img src="/app/src/assets/images/User-Circle.svg" alt="Profile Icon">
                </div>
                <div class="card-title">
                    <h3>Personal Information</h3>
                    <p>Update your personal details and contact information</p>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="POST" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-section">
                        <h4>Basic Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="matric_number">Matric Number</label>
                                <input type="text" id="matric_number" 
                                       value="<?php echo htmlspecialchars($user['matric_number']); ?>" 
                                       class="form-control" disabled>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number *</label>
                                <input type="tel" id="phone_number" name="phone_number" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender *</label>
                                <select id="gender" name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo ($user['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($user['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="state_of_origin">State of Origin *</label>
                                <input type="text" id="state_of_origin" name="state_of_origin" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($user['state_of_origin'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="marital_status">Marital Status</label>
                                <select id="marital_status" name="marital_status" class="form-control">
                                    <option value="Single" <?php echo ($user['marital_status'] ?? 'Single') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo ($user['marital_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                    <option value="Divorced" <?php echo ($user['marital_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="Widowed" <?php echo ($user['marital_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address *</label>
                            <textarea id="address" name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Emergency Contact</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="emergency_contact_name">Emergency Contact Name *</label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($user['emergency_contact_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="emergency_contact_phone">Emergency Contact Phone *</label>
                                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" 
                                       class="form-control" required
                                       value="<?php echo htmlspecialchars($user['emergency_contact_phone'] ?? ''); ?>">
            </div>
            </div>
                        
                        <div class="form-group">
                            <label for="emergency_contact_relationship">Relationship to Emergency Contact *</label>
                            <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" 
                                   class="form-control" required placeholder="e.g., Parent, Spouse, Sibling"
                                   value="<?php echo htmlspecialchars($user['emergency_contact_relationship'] ?? ''); ?>">
            </div>
            </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $mandatory_update_required ? 'Complete Profile' : 'Update Profile'; ?>
                        </button>
            </div>
        </form>
    </div>
</div>

<div class="profile-sidebar">
<div class="profile-card">
    <div class="card-header">
        <div class="card-icon">
            <img src="/app/src/assets/images/User-Circle-Purple.svg" alt="Photo Icon">
        </div>
        <div class="card-title">
            <h3>Profile Photo</h3>
            <p>Update your profile picture</p>
        </div>
    </div>
    <div class="card-body" style="text-align: center;">
        <div class="profile-photo-preview" style="margin-bottom: 20px;">
            <img src="<?php echo User::getProfilePicture($user['id']); ?>" alt="Current Profile Photo" 
                 style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #f3f4f6;">
        </div>
        
        <form action="" method="POST" enctype="multipart/form-data" id="photo-upload-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="upload_photo">
            
            <input type="file" name="profile_photo" id="profile_photo_input" accept="image/*" style="display: none;" onchange="document.getElementById('photo-upload-form').submit();">
            
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('profile_photo_input').click();">
                Change Photo
            </button>
            <p style="margin-top: 10px; font-size: 0.8rem; color: #666;">Max size: 2MB. Formats: JPG, PNG.</p>
        </form>
    </div>
</div>

<div class="profile-card">
            <div class="card-header">
                <div class="card-icon">
                    <img src="/app/src/assets/images/Document.svg" alt="Account Icon">
                </div>
                <div class="card-title">
                    <h3>Academic Information</h3>
                    <p>Your academic details (read-only)</p>
                </div>
            </div>
            <div class="card-body">
                <div class="info-grid">
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
                    <?php if (!empty($user['profile_updated_at'])): ?>
                    <div class="info-item">
                        <label>Last Updated</label>
                        <span><?php echo date('M d, Y', strtotime($user['profile_updated_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-status">
                    <div class="status-indicator <?php echo $profile_complete ? 'complete' : 'incomplete'; ?>">
                        <div class="status-icon">
                            <img src="/app/src/assets/images/<?php echo $profile_complete ? 'Check.svg' : 'Loading.svg'; ?>" alt="Status Icon">
                        </div>
                        <div class="status-text">
                            <h4>Profile Status</h4>
                            <p><?php echo $profile_complete ? 'Complete' : 'Incomplete'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notifBell = document.getElementById('notif-bell');
        const notifDropdown = document.getElementById('notif-dropdown');
        
        if (notifBell && notifDropdown) {
            const newBell = notifBell.cloneNode(true);
            notifBell.parentNode.replaceChild(newBell, notifBell);
            
            const newDropdown = document.getElementById('notif-dropdown');
            
            newBell.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isVisible = newDropdown.style.display === 'block';
                newDropdown.style.display = isVisible ? 'none' : 'block';
            });
            
            document.addEventListener('click', function(e) {
                if (!newBell.contains(e.target)) {
                    newDropdown.style.display = 'none';
                }
            });
        }
        
        const userProfile = document.querySelector('.user-profile');
        const profileDropdown = document.getElementById('profile-dropdown-menu'); // targeted by ID
        
        if (userProfile && profileDropdown) {
             const newProfile = userProfile.cloneNode(true);
             userProfile.parentNode.replaceChild(newProfile, userProfile);
             
             const reFoundDropdown = newProfile.querySelector('.profile-dropdown');
             
             newProfile.addEventListener('click', function(e) {
                 e.stopPropagation();
                 const notifDD = document.getElementById('notif-dropdown');
                 if(notifDD) notifDD.style.display = 'none';
                 
                 reFoundDropdown.classList.toggle('active');
             });
             
             document.addEventListener('click', function(e) {
                if (!newProfile.contains(e.target)) {
                    reFoundDropdown.classList.remove('active');
                }
             });
        }
    });
</script>

