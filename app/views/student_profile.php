<div class="header">
    <div class="back-link">
        <a href="/app/controllers/students.php">
            <img src="/app/src/assets/images/Arrow - Left.svg" alt="Back">
            <span>Student Profiles</span>
        </a>
    </div>
    <div class="user-profile">
        <img class="avatar" src="/app/src/assets/images/admin-avatar.png" alt="Admin Avatar">
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
            <div class="user-id"><?php echo htmlspecialchars($user['matric_number']); ?></div>
        </div>
    </div>
</div>
<div class="breadcrumb">
    <a href="/app/controllers/students.php">Students</a> <img src="/app/src/assets/images/arrow-right-small.png"
        alt="arrow right" class="breadcrumb-arrow">
    <span id="student-full-name"><?php echo htmlspecialchars($student['full_name']); ?></span>
</div>

<div class=" student-profile-container">
    <div class="student-profile-header">
        <div class="student-details-card">
            <img class="avatar" src="/app/src/assets/images/admin-avatar.png" alt="Student Avatar">
            <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
            <p class="student-id" data-student-id="<?= htmlspecialchars($student_id) ?>">Matric No:
                <?php echo htmlspecialchars($student['matric_number']); ?>
            </p>
            <p><?php echo htmlspecialchars($student['level']); ?> Level</p>
        </div>
        <div class=" student-info-card">
            <div class="info-grid">
                <div class="info-item">
                    <span>Department</span>
                    <p><?php echo htmlspecialchars($student['department_name']); ?></p>
                </div>
                <div class="info-item">
                    <span>Gender</span>
                    <p><?php echo htmlspecialchars($student['gender']); ?></p>
                </div>
                <div class="info-item">
                    <span>State of Origin</span>
                    <p><?php echo htmlspecialchars($student['state_of_origin']); ?></p>
                </div>
                <div class="info-item">
                    <span>Session of entry</span>
                    <p><?php echo htmlspecialchars($student['session_of_entry']); ?></p>
                </div>
                <div class="info-item">
                    <span>Phone Number</span>
                    <p><?php echo htmlspecialchars($student['phone_number']); ?></p>
                </div>
                <div class="info-item">
                    <span>Marital Status</span>
                    <p><?php echo htmlspecialchars($student['marital_status']); ?></p>
                </div>
                <div class="info-item">
                    <span>Date of birth</span>
                    <p><?php echo htmlspecialchars($student['date_of_birth']); ?></p>
                </div>
                <div class="info-item">
                    <span>E-mail</span>
                    <p><?php echo htmlspecialchars($student['email']); ?></p>
                </div>
                <div class="info-item address">
                    <span>Address</span>
                    <p><?php echo htmlspecialchars($student['address']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="documents-section">
        <div class="tabs">
            <button class="tab-link active" data-tab="all">All Documents</button>
            <button class="tab-link" data-tab="Approved">Verified Documents</button>
            <button class="tab-link" data-tab="Pending Approval">Pending Documents</button>
            <button class="tab-link" data-tab="Rejected">Rejected Documents</button>
            <button id="download-all" class="download-all-btn">
                Download All Documents
            </button>
        </div>

        <div class="tab-content active" id="all-docs">
            <table class="documents-table">
                <thead>
                    <tr>
                        <th>SN</th>
                        <th>Document Name</th>
                        <th>Level</th>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th class="action-cell"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $index => $doc):
                        $status_slug = strtolower(str_replace(' ', '-', $doc['status']));
                    ?>
                    <tr class="doc-row" data-status="<?php echo htmlspecialchars($doc['status']); ?>">
                        <td><?php echo $index + 1; ?>.</td>
                        <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                        <td><?php echo htmlspecialchars($doc['level']); ?>L</td>
                        <td><?php echo htmlspecialchars($doc['session'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($doc['semester']); ?></td>
                        <td><span
                                class="status <?php echo $status_slug; ?>"><?php echo htmlspecialchars($doc['status']); ?></span>
                        </td>
                        <td class="action-cell">
                            <div class="dropdown-wrapper">
                                <!-- the “more” icon -->
                                <img src="/app/src/assets/images/more.svg" alt="More" class="dropdown-toggle" />

                                <!-- hidden by default -->
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="/app/controllers/download_document.php?id=<?= $doc['id'] ?>">
                                            <img src="/app/src/assets/images/download-icon.png" alt="Download icon" />
                                            Download
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>