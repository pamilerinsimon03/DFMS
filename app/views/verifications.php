<div class="header">
    <button id="mobile-menu-toggle" class="mobile-menu-btn" aria-label="Open Menu">
        <span class="mobile-menu-icon"><img src="/app/src/assets/images/Menu.svg" alt="Menu Icon"></span>
    </button>
    <div class="search-container">
        <form method="GET" class="search-box">
            <div class="search-icon"><img src="/app/src/assets/images/search-normal.svg" alt="Search Icon"></div>
            <input type="text" placeholder="Search by name, matric number, document, status..." class="search-input" name="search" id="search-input"
                value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
            <div class="search-suggestions" id="search-suggestions" style="display: none;">
                <div class="suggestion-header">Search Tips:</div>
                <div class="suggestion-item">• Type "john" to find "John Doe"</div>
                <div class="suggestion-item">• Type "2021" to find matric numbers starting with 2021</div>
                <div class="suggestion-item">• Type "pend" to find pending documents</div>
                <div class="suggestion-item">• Type "cert" to find certificates</div>
                <div class="suggestion-item">• Type "300" to find 300 level students</div>
            </div>
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
<div class="uploads-content">
    <div class="uploads-tabs">
        <div class="tabs-left">
            <a href="?tab=all" class="tab-item <?php echo $tab === 'all' ? 'active' : ''; ?>">All Documents</a>
            <a href="?tab=pending" class="tab-item <?php echo $tab === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?tab=approved" class="tab-item <?php echo $tab === 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?tab=rejected" class="tab-item <?php echo $tab === 'rejected' ? 'active' : ''; ?>">Rejected</a>
        </div>
        <?php if (!empty($search)): ?>
        <div class="search-results-info">
            <span class="results-count"><?php echo number_format($total); ?> result<?php echo $total !== 1 ? 's' : ''; ?> found</span>
            <span class="search-term">for "<?php echo htmlspecialchars($search); ?>"</span>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="clear-search">Clear search</a>
        </div>
        <?php endif; ?>
    </div>
    <form id="filter-sort-form" action="" method="GET">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
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

            <?php if (!$user['department_id']): ?>
            <select name="department">
                <option value="">All Departments</option>
                <?php 
                $departments = [['id' => 1, 'name' => 'Computer Science'], ['id' => 2, 'name' => 'Psychology'], ['id' => 3, 'name' => 'English'], ['id' => 4, 'name' => 'Sociology'], ['id' => 5, 'name' => 'Linguistics']];
                foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['id']; ?>"
                    <?php echo ($department_id == $dept['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dept['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

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
    <div class="table-section">
        <div class="table-wrapper">
            <table class="docs-table">
                <thead>
                    <tr>
                        <th>SN</th>
                        <th>
                            <a href="<?php echo htmlspecialchars($sort_links['full_name']); ?>" class="sort-link">
                                Student's Name<?php echo htmlspecialchars($sort_indicators['full_name']); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo htmlspecialchars($sort_links['department']); ?>" class="sort-link">
                                Department<?php echo htmlspecialchars($sort_indicators['department']); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo htmlspecialchars($sort_links['level']); ?>" class="sort-link">
                                Level<?php echo htmlspecialchars($sort_indicators['level']); ?>
                            </a>
                        </th>
                        <th>Document Name</th>
                        <th>
                            <a href="<?php echo htmlspecialchars($sort_links['upload_date']); ?>" class="sort-link">
                                Upload Date<?php echo htmlspecialchars($sort_indicators['upload_date']); ?>
                            </a>
                        </th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                    <tr>
                        <td colspan="8">No documents found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($documents as $index => $doc):
                            $status_slug = strtolower(str_replace(' ', '-', $doc['status']));
                        ?>
                    <tr>
                        <td><?php echo ($page - 1) * $per_page + $index + 1; ?>.</td>
                        <td><?php echo htmlspecialchars($doc['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($doc['department']); ?></td>
                        <td><?php echo htmlspecialchars($doc['level']); ?></td>
                        <td><a href="/app/controllers/verification_detail.php?doc_id=<?php echo $doc['id']; ?>"
                                class="doc-link"><?php echo htmlspecialchars($doc['document_name']); ?></a></td>
                        <td><?php echo date('M d, Y', strtotime($doc['upload_date'] ?? 'now')); ?></td>
                        <td class="td-status <?php echo $status_slug; ?>">
                            <div class="status-wrapper">
                                <span class="status-dot"></span>
                                <span class="status-text"><?php echo htmlspecialchars($doc['status']); ?></span>
                            </div>
                        </td>
                        <td class="td-action">
                            <div class="action-menu">
                                <img src="/app/src/assets/images/more.svg" alt="More" class="action-btn">
                                <div class="action-menu-content">
                                    <a href="/app/controllers/verification_detail.php?doc_id=<?php echo $doc['id']; ?>"
                                        class="action-item"><img src="/app/src/assets/images/Bold Eye.svg" alt="View">
                                        View</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php 
                $query_params = $_GET;
                if ($page > 1) {
                    $query_params['page'] = $page - 1;
                    echo '<a href="?' . http_build_query($query_params) . '">Previous</a>';
                }
                for ($i = 1; $i <= $total_pages; $i++) {
                    $query_params['page'] = $i;
                    echo '<a href="?' . http_build_query($query_params) . '" class="' . ($i == $page ? 'active' : '') . '">' . $i . '</a>';
                }
                if ($page < $total_pages) {
                    $query_params['page'] = $page + 1;
                    echo '<a href="?' . http_build_query($query_params) . '">Next</a>';
                }
                ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<style>
.sort-link {
    color: inherit;
    text-decoration: none;
}

.sort-link:hover {
    text-decoration: none;
}
</style>