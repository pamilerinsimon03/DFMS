<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="search-container">
        <form action="" method="GET" class="search-box">
            <div class="search-icon"><img src="/app/src/assets/images/search-normal.svg" alt="Search Icon"></div>
            <input type="text" placeholder="Search here" class="search-input" name="search" id="search-input"
                value="<?php echo htmlspecialchars($search); ?>">
        </form>
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
<div class="students-container">
    <div class="students-header">
        <h2>Students</h2>
        <a href="/app/controllers/add_student.php" class="btn btn-primary add-student-btn">
            <span class="btn-icon">+</span>
            Add New Student
        </a>
    </div>
    
    <?php if ($user['matric_number'] === 'SUPER_ADMIN' && $filter_department_id === null): ?>
        <div class="filter-indicator">
            <span class="filter-badge">Showing all students from all departments</span>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['show_all' => '0'])); ?>" class="view-all-link">View My Department Only</a>
        </div>
    <?php elseif ($user['matric_number'] === 'SUPER_ADMIN' && $filter_department_id !== null): ?>
        <div class="filter-indicator">
            <span class="filter-badge">Showing students from your department only</span>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['show_all' => '1'])); ?>" class="view-all-link">View All Students</a>
        </div>
    <?php elseif ($user['department_id']): ?>
        <div class="filter-indicator">
            <span class="filter-badge">Showing students from your department only</span>
        </div>
    <?php endif; ?>
    
    <form id="filter-sort-form" action="" method="GET">
        <input type="hidden" name="search" id="form-search" value="<?php echo htmlspecialchars($search); ?>">

        <div class="filters">
            <div class="date-filter">
                <div class="date-input">
                    <input type="text" name="start_date" id="start_date" placeholder="Start Date"
                        value="<?php echo htmlspecialchars($start_date); ?>">
                    <img src="/app/src/assets/images/calendar.svg" alt="Calendar">
                </div>
                <span>-</span>
                <div class="date-input">
                    <input type="text" name="end_date" id="end_date" placeholder="End Date"
                        value="<?php echo htmlspecialchars($end_date); ?>">
                    <img src="/app/src/assets/images/calendar.svg" alt="Calendar">
                </div>
            </div>

            <select name="level">
                <option value="">All Levels</option>
                <option value="100" <?php echo ($level == '100') ? 'selected' : ''; ?>>100 Level</option>
                <option value="200" <?php echo ($level == '200') ? 'selected' : ''; ?>>200 Level</option>
                <option value="300" <?php echo ($level == '300') ? 'selected' : ''; ?>>300 Level</option>
                <option value="400" <?php echo ($level == '400') ? 'selected' : ''; ?>>400 Level</option>
                <option value="500" <?php echo ($level == '500') ? 'selected' : ''; ?>>500 Level</option>
            </select>

            <select name="mode">
                <option value="">All Modes</option>
                <option value="Regular" <?php echo ($mode == 'Regular') ? 'selected' : ''; ?>>Regular</option>
                <option value="Distance Learning" <?php echo ($mode == 'Distance Learning') ? 'selected' : ''; ?>>
                    Distance Learning</option>
            </select>

            <select name="program_type">
                <option value="">All Program Types</option>
                <option value="Undergraduate" <?php echo ($program_type == 'Undergraduate') ? 'selected' : ''; ?>>
                    Undergraduate</option>
                <option value="Postgraduate" <?php echo ($program_type == 'Postgraduate') ? 'selected' : ''; ?>>
                    Postgraduate</option>
            </select>

            <button type="submit" class="filter-button">Apply Filters</button>
        </div>
    </form>
    
    <?php if (empty($students)): ?>
        <div class="no-students">
            <p>No students found matching your criteria.</p>
            <?php if ($user['department_id'] && $user['matric_number'] !== 'SUPER_ADMIN'): ?>
                <p>You are currently viewing students from your department only.</p>
            <?php elseif ($user['matric_number'] === 'SUPER_ADMIN' && $filter_department_id !== null): ?>
                <p>You are currently viewing students from your department only. 
                   <a href="?<?php echo http_build_query(array_merge($_GET, ['show_all' => '1'])); ?>">View all students</a>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="students-grid">
            <?php foreach ($students as $student): ?>
            <a href="/app/controllers/student_profile.php?id=<?php echo htmlspecialchars($student['public_id'] ?? $student['id']); ?>" class="student-card">
                <img src="/app/src/assets/images/admin-avatar.png" alt="Student Avatar">
                <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
                <p>Matric No: <?php echo htmlspecialchars($student['matric_number']); ?></p>
                <p><?php echo htmlspecialchars($student['level']); ?> Level</p>
                <?php if ($student['department_name']): ?>
                    <p class="department-name"><?php echo htmlspecialchars($student['department_name']); ?></p>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>