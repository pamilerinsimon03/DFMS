<div class="add-student-container">
    <div class="page-header">
        <h1>Add Students</h1>
        <p>Add students individually or import multiple students using a CSV file. Each student will get a password generated from their surname.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-button active" data-tab="individual">Individual Student</button>
        <button class="tab-button" data-tab="bulk">Bulk Import</button>
    </div>

    <!-- Individual Student Tab -->
    <div class="tab-content active" id="individual-tab">
        <div class="form-container">
            <form method="POST" class="add-student-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="matric_number">Matric Number *</label>
                        <input type="text" id="matric_number" name="matric_number" 
                               value="<?php echo htmlspecialchars($_POST['matric_number'] ?? ''); ?>" 
                               required placeholder="e.g., U123456">
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                               required placeholder="e.g., John Doe">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               placeholder="e.g., john.doe@student.edu">
                    </div>

                    <div class="form-group">
                        <label for="level">Level *</label>
                        <select id="level" name="level" required>
                            <option value="">Select Level</option>
                            <option value="100L" <?php echo (isset($_POST['level']) && $_POST['level'] === '100L') ? 'selected' : ''; ?>>100 Level</option>
                            <option value="200L" <?php echo (isset($_POST['level']) && $_POST['level'] === '200L') ? 'selected' : ''; ?>>200 Level</option>
                            <option value="300L" <?php echo (isset($_POST['level']) && $_POST['level'] === '300L') ? 'selected' : ''; ?>>300 Level</option>
                            <option value="400L" <?php echo (isset($_POST['level']) && $_POST['level'] === '400L') ? 'selected' : ''; ?>>400 Level</option>
                            <option value="500L" <?php echo (isset($_POST['level']) && $_POST['level'] === '500L') ? 'selected' : ''; ?>>500 Level</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="session">Session *</label>
                        <input type="text" id="session" name="session" 
                               value="<?php echo htmlspecialchars($_POST['session'] ?? ''); ?>" 
                               required placeholder="e.g., 2023/2024">
                    </div>

                    <div class="form-group">
                        <label for="department_id">Department *</label>
                        <select id="department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="programme">Programme *</label>
                        <input type="text" id="programme" name="programme" 
                               value="<?php echo htmlspecialchars($_POST['programme'] ?? ''); ?>" 
                               required placeholder="e.g., Bachelor of Science (Computer Science)">
                    </div>

                    <div class="form-group">
                        <label for="program_type">Program Type</label>
                        <select id="program_type" name="program_type">
                            <option value="Undergraduate" <?php echo (isset($_POST['program_type']) && $_POST['program_type'] === 'Undergraduate') ? 'selected' : ''; ?>>Undergraduate</option>
                            <option value="Postgraduate" <?php echo (isset($_POST['program_type']) && $_POST['program_type'] === 'Postgraduate') ? 'selected' : ''; ?>>Postgraduate</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mode">Mode</label>
                        <select id="mode" name="mode">
                            <option value="Regular" <?php echo (isset($_POST['mode']) && $_POST['mode'] === 'Regular') ? 'selected' : ''; ?>>Regular</option>
                            <option value="Distance Learning" <?php echo (isset($_POST['mode']) && $_POST['mode'] === 'Distance Learning') ? 'selected' : ''; ?>>Distance Learning</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Student</button>
                    <a href="/app/controllers/students.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Import Tab -->
    <div class="tab-content" id="bulk-tab">
        <div class="import-section">
            <div class="upload-container">
                <h3>Upload CSV File</h3>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="import_csv">
                    <div class="file-input-wrapper">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                        <label for="csv_file" class="file-label">
                            <span class="file-icon"><i data-lucide="file-spreadsheet"></i></span>
                            <span class="file-text">Choose CSV file or drag and drop</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">Import Students</button>
                </form>
            </div>

            <div class="csv-template">
                <h3>CSV Template</h3>
                <p>Download the template and fill in your student data:</p>
                <a href="/app/controllers/download_template.php" class="btn btn-secondary">Download Template</a>
                
                <div class="template-preview">
                    <h4>Expected CSV Format:</h4>
                    <div class="csv-preview">
                        <code>matric_number,full_name,level,session,programme,department_id,program_type,mode,email</code><br>
                        <code>U123456,John Doe,100L,2023/2024,Bachelor of Science (Computer Science),1,Undergraduate,Regular,john.doe@student.edu</code><br>
                        <code>U123457,Jane Smith,200L,2023/2024,Bachelor of Arts (Psychology),2,Undergraduate,Regular,jane.smith@student.edu</code>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($import_results)): ?>
            <div class="import-results">
                <h3>Import Results</h3>
                <div class="results-summary">
                    <?php
                    $success_count = count(array_filter($import_results, function($result) { return $result['status'] === 'success'; }));
                    $error_count = count(array_filter($import_results, function($result) { return $result['status'] === 'error'; }));
                    ?>
                    <div class="summary-item success">
                        <span class="summary-icon"><i data-lucide="check-circle" class="text-success"></i></span>
                        <span class="summary-text">Success: <?php echo $success_count; ?></span>
                    </div>
                    <div class="summary-item error">
                        <span class="summary-icon"><i data-lucide="x-circle" class="text-error"></i></span>
                        <span class="summary-text">Errors: <?php echo $error_count; ?></span>
                    </div>
                </div>
                
                <div class="results-details">
                    <?php foreach ($import_results as $result): ?>
                        <div class="result-item result-<?php echo $result['status']; ?>">
                            <span class="result-icon">
                                <?php echo $result['status'] === 'success' ? '<i data-lucide="check" class="text-success"></i>' : '<i data-lucide="x" class="text-error"></i>'; ?>
                            </span>
                            <span class="result-row">Row <?php echo $result['row']; ?></span>
                            <span class="result-message"><?php echo htmlspecialchars($result['message']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="info-sections">
            <div class="info-card">
                <h3>CSV Requirements</h3>
                <ul>
                    <li>File must be in CSV format</li>
                    <li>First row should contain headers</li>
                    <li>Required columns: matric_number, full_name, level, session, programme, department_id</li>
                    <li>Optional columns: program_type, mode, email</li>
                    <li>Department ID must match existing department IDs</li>
                </ul>
            </div>

            <div class="info-card">
                <h3>Department IDs</h3>
                <div class="department-list">
                    <?php foreach ($departments as $dept): ?>
                        <div class="department-item">
                            <strong><?php echo htmlspecialchars($dept['name']); ?></strong>: <?php echo $dept['id']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="info-card">
                <h3>Password Generation</h3>
                <p>Each student will automatically get a password in the format: <strong>surname12345</strong></p>
                <p><strong>Example:</strong> "John Doe" → password: "doe12345"</p>
            </div>
        </div>
    </div>

    <div class="info-box">
        <h3>Password Generation</h3>
        <p>The system will automatically generate a password for each student using the format: <strong>surname12345</strong></p>
        <p><strong>Example:</strong> For a student named "John Doe", the password will be "doe12345"</p>
    </div>
</div>

<script>
// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
});
</script>
