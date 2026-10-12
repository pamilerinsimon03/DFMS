<div class="add-student-container">
    <div class="page-header">
        <h1>Add Students</h1>
        <p>Add students individually or import multiple students using a CSV file. Each newly added student will be sent an account activation link via email to set their password.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="tab-navigation">
        <button class="tab-button active" data-tab="individual">Individual Student</button>
        <button class="tab-button" data-tab="bulk">Bulk Import</button>
    </div>

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
                <h3>Account Activation</h3>
                <p>Upon registration, an activation link will be automatically generated and sent to the student's email address.</p>
                <p>Students click the link to activate their account and choose a secure password.</p>
            </div>
        </div>

    <div class="info-box">
        <h3>Account Activation</h3>
        <p>The system automatically sends an account activation link to the student's registered email address.</p>
        <p>Students click the activation link to set their own password and access the DFMS portal.</p>
    </div>
</div>

<script>
(function() {
    function initAddStudentTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetTab = button.getAttribute('data-tab');
                
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                button.classList.add('active');
                const targetContent = document.getElementById(targetTab + '-tab');
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }

    function initCsvUploader() {
        const fileInput = document.getElementById('csv_file');
        const fileLabel = document.querySelector('.file-label');
        const uploadForm = document.querySelector('.upload-form');

        if (!fileInput || !fileLabel) return;

        const fileIcon = fileLabel.querySelector('.file-icon');
        const fileText = fileLabel.querySelector('.file-text');

        const defaultIconHTML = fileIcon ? fileIcon.innerHTML : '📊';
        const defaultText = 'Choose CSV file or drag and drop';

        function updateFileDisplay(file) {
            if (!file) {
                if (fileIcon) fileIcon.innerHTML = defaultIconHTML;
                if (fileText) fileText.innerHTML = defaultText;
                fileLabel.classList.remove('has-file');
                return;
            }

            const sizeFormatted = file.size > 1024 * 1024 
                ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' 
                : (file.size / 1024).toFixed(1) + ' KB';

            if (fileIcon) {
                fileIcon.innerHTML = '<span style="font-size: 2.2rem; color: #10b981;">📄</span>';
            }
            if (fileText) {
                fileText.innerHTML = `
                    <div style="text-align: center;">
                        <strong style="color: #4f46e5; font-size: 1.05rem; display: block; margin-bottom: 4px;">${escapeHtml(file.name)}</strong>
                        <span style="color: #6b7280; font-size: 0.85rem; background: #e0e7ff; padding: 2px 10px; border-radius: 12px; display: inline-block;">${sizeFormatted}</span>
                        <span style="color: #059669; font-size: 0.85rem; display: block; margin-top: 6px; font-weight: 500;">✓ Ready to import</span>
                    </div>
                `;
            }
            fileLabel.classList.add('has-file');
        }

        function escapeHtml(str) {
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
        }

        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                updateFileDisplay(this.files[0]);
            } else {
                updateFileDisplay(null);
            }
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            fileLabel.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                fileLabel.classList.add('drag-over');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                fileLabel.classList.remove('drag-over');
            }, false);
        });

        fileLabel.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files && files.length > 0) {
                const file = files[0];
                if (!file.name.toLowerCase().endsWith('.csv')) {
                    alert('Please upload a valid .csv file.');
                    return;
                }
                
                try {
                    const container = new DataTransfer();
                    container.items.add(file);
                    fileInput.files = container.files;
                } catch (err) {
                    console.error('DataTransfer assignment error:', err);
                }

                updateFileDisplay(file);
            }
        }, false);

        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                if (!fileInput.files || fileInput.files.length === 0) {
                    e.preventDefault();
                    alert('Please select or drag a CSV file before importing.');
                    return;
                }

                const submitBtn = uploadForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin 0.8s linear infinite;margin-right:8px;vertical-align:middle;"></span> Importing Students...';
                }
            });
        }
    }

    function initAll() {
        initAddStudentTabs();
        initCsvUploader();
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initAll();
    } else {
        document.addEventListener('DOMContentLoaded', initAll);
    }
    
    window.initAddStudentTabs = initAddStudentTabs;
    window.initCsvUploader = initCsvUploader;
})();
</script>
