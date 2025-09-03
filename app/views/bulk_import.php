<div class="bulk-import-container">
    <div class="page-header">
        <h1>Bulk Import Students</h1>
        <p>Import multiple students at once using a CSV file. Each student will get a password generated from their surname.</p>
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
            <h3>Password Generation</h3>
            <p>Each student will automatically get a password in the format: <strong>surname12345</strong></p>
            <p><strong>Example:</strong> "John Doe" → password: "doe12345"</p>
        </div>
    </div>
</div>
