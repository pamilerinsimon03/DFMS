<div class="update-passwords-container">
    <div class="page-header">
        <h1>Update Student Passwords</h1>
        <p>Update all existing student passwords to the new format: <strong>surname12345</strong></p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="warning-box">
        <div class="warning-icon">⚠️</div>
        <div class="warning-content">
            <h3>Important Notice</h3>
            <p>This action will update the passwords for <strong>ALL existing students</strong> in the system.</p>
            <ul>
                <li>New password format: <strong>surname12345</strong></li>
                <li>Example: For "John Doe" → password becomes "doe12345"</li>
                <li>This action cannot be undone</li>
                <li>Students will need to be notified of their new passwords</li>
            </ul>
        </div>
    </div>

    <div class="action-container">
        <form method="POST" class="update-form" onsubmit="return confirm('Are you sure you want to update all student passwords? This action cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update_passwords">
            <button type="submit" class="btn btn-warning">
                <span class="btn-icon">🔄</span>
                Update All Student Passwords
            </button>
        </form>
    </div>

    <div class="info-sections">
        <div class="info-card">
            <h3>Password Generation Rules</h3>
            <ul>
                <li>Extracts the last name (surname) from the student's full name</li>
                <li>Converts to lowercase</li>
                <li>Removes any special characters or spaces</li>
                <li>Appends "12345" to the surname</li>
                <li>If no surname is found, uses "student12345" as fallback</li>
            </ul>
        </div>

        <div class="info-card">
            <h3>Examples</h3>
            <div class="example-list">
                <div class="example-item">
                    <strong>John Doe</strong> → <code>doe12345</code>
                </div>
                <div class="example-item">
                    <strong>Mary Jane Smith</strong> → <code>smith12345</code>
                </div>
                <div class="example-item">
                    <strong>O'Connor, Patrick</strong> → <code>oconnor12345</code>
                </div>
                <div class="example-item">
                    <strong>Single Name</strong> → <code>student12345</code>
                </div>
            </div>
        </div>

        <div class="info-card">
            <h3>After Update</h3>
            <ul>
                <li>All existing students will have new passwords</li>
                <li>Students should be notified to change their passwords after first login</li>
                <li>New students added will automatically use this format</li>
                <li>Admin passwords remain unchanged</li>
            </ul>
        </div>
    </div>
</div>
