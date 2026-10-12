<div class="dashboard-content" style="padding: 20px; max-height: calc(100vh - 80px); box-sizing: border-box;">
    <div class="content-header" style="margin-bottom: 16px;">
        <h1 style="font-size: 24px; margin-bottom: 4px;">Data Subject Rights</h1>
        <p style="color: #666; font-size: 14px; margin: 0;">Exercise statutory rights under Nigeria Data Protection Act (NDPA) 2023 &amp; GAID 2025</p>
    </div>

    <?php if (!empty($user['processing_restricted'])): ?>
    <div class="alert alert-error" style="background: #fff3cd; border-color: #ffeeba; color: #856404; margin-bottom: 15px;">
        <div class="alert-icon">⚠️</div>
        <div class="alert-message">
            <strong>Account Processing Restricted:</strong> Processing restriction is currently active on your account under NDPA 2023 Section 37. Document uploads and profile updates are restricted pending institutional review.
        </div>
    </div>
    <?php endif; ?>

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
        <div class="right-card">
            <div>
                <h3>Right to Access &amp; Portability</h3>
                <p>Download a machine-readable JSON export of all personal data held under NDPA 2023 s.34 &amp; s.38.</p>
            </div>
            <form method="POST" class="right-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="access_data">
                <button type="submit" class="btn btn-primary btn-block">Download Personal Data</button>
            </form>
        </div>

        <div class="right-card">
            <div>
                <h3>Right to Rectification</h3>
                <p>Correct inaccurate personal profile data with cryptographic audit logging (NDPA 2023 s.36).</p>
            </div>
            <form method="POST" class="right-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="rectify_data">
                <div class="form-group">
                    <label for="field">Field to Rectify:</label>
                    <select name="field" id="field" required>
                        <option value="">Select field...</option>
                        <option value="full_name">Full Name</option>
                        <option value="email">Email Address</option>
                        <option value="phone_number">Phone Number</option>
                        <option value="address">Address</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new_value">Rectified Value:</label>
                    <input type="text" name="new_value" id="new_value" placeholder="Enter updated value" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" <?php echo !empty($user['processing_restricted']) ? 'disabled' : ''; ?>>Rectify Data</button>
            </form>
        </div>

        <div class="right-card">
            <div>
                <h3>Consent Management</h3>
                <p>Withdraw optional processing consents. Core academic processing is statutory under NDPA s.25(1)(b).</p>
            </div>
            <form method="POST" class="right-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="withdraw_consent">
                <div class="form-group">
                    <label for="consent_type">Select Processing Consent:</label>
                    <select name="consent_type" id="consent_type" required>
                        <option value="">Select consent...</option>
                        <option value="email_notifications">Email Notifications (Optional)</option>
                        <option value="portal_analytics">Portal Usage Analytics (Optional)</option>
                        <option value="core_academic_processing">Core Academic Records (Statutory)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary btn-block">Withdraw Selected Consent</button>
            </form>
        </div>

        <div class="right-card">
            <div>
                <h3>Right to Restrict Processing</h3>
                <p>Lock profile modifications and uploads during active dispute review (NDPA 2023 s.37).</p>
            </div>
            <form method="POST" class="right-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="restrict_processing">
                <button type="submit" class="btn <?php echo !empty($user['processing_restricted']) ? 'btn-warning' : 'btn-secondary'; ?> btn-block">
                    <?php echo !empty($user['processing_restricted']) ? 'Lift Restriction' : 'Restrict Processing'; ?>
                </button>
            </form>
        </div>

        <div class="right-card">
            <div>
                <h3>Right to Erasure</h3>
                <p>Request data erasure under NDPA 2023 s.36. Core academic student records are exempt under s.36(2).</p>
            </div>
            <form method="POST" class="right-form" onsubmit="return confirm('Request erasure of eligible non-core personal data? Core academic matriculation records will remain preserved.');">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="delete_data">
                <button type="submit" class="btn btn-warning btn-block">Request Erasure</button>
            </form>
        </div>
    </div>

    <div class="privacy-info">
        <div class="privacy-left">
            ℹ️ Read our <a href="/privacy-policy.html" target="_blank" style="color: #007bff; text-decoration: underline;">Privacy Policy</a> to learn more about data retention rules.
        </div>
        <div class="privacy-right">
            <span><strong>DPO:</strong> dpo@ui.edu.ng</span> &bull; <span><strong>Inquiries:</strong> privacy@ui.edu.ng</span>
        </div>
    </div>
</div>

<style>
.rights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin: 16px 0;
}

@media (max-width: 1200px) {
    .rights-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .rights-grid {
        grid-template-columns: 1fr;
    }
}

.right-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 250px;
}

.right-card h3 {
    color: #2c3e50;
    font-size: 16px;
    margin-top: 0;
    margin-bottom: 8px;
}

.right-card p {
    color: #64748b;
    font-size: 13px;
    line-height: 1.4;
    margin-bottom: 12px;
}

.right-form {
    margin-top: 10px;
}

.form-group {
    margin-bottom: 10px;
}

.form-group label {
    display: block;
    margin-bottom: 4px;
    font-weight: 500;
    font-size: 12px;
    color: #334155;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 13px;
    box-sizing: border-box;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    transition: opacity 0.2s;
}

.btn-block {
    width: 100%;
    text-align: center;
    box-sizing: border-box;
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
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 10px 16px;
    border-radius: 6px;
    margin-top: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: #475569;
}

@media (max-width: 768px) {
    .privacy-info {
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }
}
</style>
