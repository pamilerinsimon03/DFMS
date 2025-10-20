<div class="dashboard-content">
    <div class="content-header">
        <h1>Data Subject Rights</h1>
        <p>Exercise your rights under the Nigeria Data Protection Regulation (NDPR) 2019</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success">
        <div class="alert-icon">✓</div>
        <div class="alert-message"><?php echo htmlspecialchars($message); ?></div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <div class="alert-icon">✗</div>
        <div class="alert-message"><?php echo htmlspecialchars($error); ?></div>
    </div>
    <?php endif; ?>

    <div class="rights-grid">
        <!-- Right to Access -->
        <div class="right-card">
            <h3>Right to Access</h3>
            <p>Request a copy of all personal data we hold about you.</p>
            <form method="POST" class="right-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="access_data">
                <button type="submit" class="btn btn-primary">Request Data Access</button>
            </form>
        </div>

        <!-- Right to Rectification -->
        <div class="right-card">
            <h3>Right to Rectification</h3>
            <p>Correct inaccurate or incomplete personal data.</p>
            <form method="POST" class="right-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="rectify_data">
                <div class="form-group">
                    <label for="field">Field to Update:</label>
                    <select name="field" id="field" required>
                        <option value="">Select field...</option>
                        <option value="full_name">Full Name</option>
                        <option value="email">Email Address</option>
                        <option value="phone_number">Phone Number</option>
                        <option value="address">Address</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new_value">New Value:</label>
                    <input type="text" name="new_value" id="new_value" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Data</button>
            </form>
        </div>

        <!-- Right to Erasure -->
        <div class="right-card">
            <h3>Right to Erasure</h3>
            <p>Request deletion of your personal data (subject to legal requirements).</p>
            <form method="POST" class="right-form" onsubmit="return confirm('Are you sure you want to request data deletion? This action may affect your academic records.');">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="delete_data">
                <button type="submit" class="btn btn-warning">Request Data Deletion</button>
            </form>
        </div>

        <!-- Right to Restrict Processing -->
        <div class="right-card">
            <h3>Right to Restrict Processing</h3>
            <p>Limit how we process your personal data.</p>
            <form method="POST" class="right-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="restrict_processing">
                <button type="submit" class="btn btn-secondary">Request Processing Restriction</button>
            </form>
        </div>
    </div>

    <div class="privacy-info">
        <h3>Privacy Information</h3>
        <p>For more information about how we handle your personal data, please read our <a href="/privacy-policy.html" target="_blank">Privacy Policy</a>.</p>
        
        <h4>Contact Information</h4>
        <ul>
            <li><strong>Data Protection Officer:</strong> dpo@ui.edu.ng</li>
            <li><strong>Privacy Inquiries:</strong> privacy@ui.edu.ng</li>
            <li><strong>Phone:</strong> +234-XXX-XXX-XXXX</li>
        </ul>
    </div>
</div>

<style>
.rights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.right-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.right-card h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.right-card p {
    color: #666;
    margin-bottom: 15px;
}

.right-form {
    margin-top: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-warning {
    background-color: #ffc107;
    color: #212529;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

.privacy-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 30px;
}

.privacy-info h3,
.privacy-info h4 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.privacy-info ul {
    list-style-type: disc;
    margin-left: 20px;
}

.privacy-info li {
    margin-bottom: 5px;
}
</style>
