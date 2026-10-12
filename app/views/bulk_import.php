<div class="bulk-import-container">
    <div class="page-header">
        <h1>Bulk Import Students</h1>
        <p>Import multiple students at once using a CSV file. Each student will receive an account activation link via email to set their password.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="import-section">
        <div class="upload-container">
            <h3>Upload CSV File</h3>
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="import_csv">
                <div class="file-input-wrapper">
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    <label for="csv_file" class="file-label">
                        <span class="file-icon">📁</span>
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
                    <span class="summary-icon">✅</span>
                    <span class="summary-text">Success: <?php echo $success_count; ?></span>
                </div>
                <div class="summary-item error">
                    <span class="summary-icon">❌</span>
                    <span class="summary-text">Errors: <?php echo $error_count; ?></span>
                </div>
            </div>
            
            <div class="results-details">
                <?php foreach ($import_results as $result): ?>
                    <div class="result-item result-<?php echo $result['status']; ?>">
                        <span class="result-icon">
                            <?php echo $result['status'] === 'success' ? '✅' : '❌'; ?>
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
            <p>Each imported student account will receive an <strong>Account Activation Token</strong> via email to set a strong custom password.</p>
        </div>
    </div>
</div>

<script>
(function() {
    function initCsvUploader() {
        const fileInput = document.getElementById('csv_file');
        const fileLabel = document.querySelector('.file-label');
        const uploadForm = document.querySelector('.upload-form');

        if (!fileInput || !fileLabel) return;

        const fileIcon = fileLabel.querySelector('.file-icon');
        const fileText = fileLabel.querySelector('.file-text');

        const defaultIconHTML = fileIcon ? fileIcon.innerHTML : '📁';
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

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initCsvUploader();
    } else {
        document.addEventListener('DOMContentLoaded', initCsvUploader);
    }
    
    window.initCsvUploader = initCsvUploader;
})();
</script>
