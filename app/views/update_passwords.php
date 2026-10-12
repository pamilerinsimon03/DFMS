<div class="update-passwords-container">
    <div class="page-header">
        <h1>Resend Student Activation Emails</h1>
        <p>Send account activation links to students who have not yet activated their DFMS accounts.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="warning-box">
        <div class="warning-icon">📧</div>
        <div class="warning-content">
            <h3>Student Activation Dispatch</h3>
            <p>This action will resend account activation emails to all unactivated student accounts.</p>
            <ul>
                <li>Includes unique activation tokens valid for account setup</li>
                <li>Allows students to choose their own secure passwords</li>
                <li>Does not reset passwords for students who have already activated their accounts</li>
            </ul>
        </div>
    </div>

    <div class="action-container">
        <form method="POST" class="update-form" onsubmit="return confirm('Are you sure you want to resend activation links to all unactivated students?');">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update_passwords">
            <button type="submit" class="btn btn-warning">
                <span class="btn-icon">📩</span>
                Resend Pending Activation Emails
            </button>
        </form>
    </div>

    <div class="info-sections">
        <div class="info-card">
            <h3>Activation Flow Details</h3>
            <ul>
                <li>Generates a secure 64-character activation token if one is missing</li>
                <li>Sends a personalized activation link to the student's registered email</li>
                <li>Directs student to <code>/app/controllers/activate.php</code> to complete password setup</li>
            </ul>
        </div>

        <div class="info-card">
            <h3>Security Guidelines</h3>
            <ul>
                <li>Ensures zero default passwords exist in production</li>
                <li>Tokens remain active until the student sets their initial password</li>
                <li>Administrators can re-trigger activation links whenever a student requests help</li>
            </ul>
        </div>
    </div>
</div>
