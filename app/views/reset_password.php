<div class="reset-container">
    <h2 class="text-center mb-4">UNIVERSITY OF IBADAN</h2>
    <h4 class="text-center mb-4">DIGITAL FILE MANAGEMENT PORTAL</h4>
    <div class="card p-4">
        <h3 class="text-center mb-4"><?php echo $mode === 'request' ? 'Reset Password' : 'Set New Password'; ?></h3>
        <?php if ($error): ?>
        <p class="error text-center"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
        <p class="success text-center"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if ($mode === 'request'): ?>
        <form action="/app/controllers/reset_password.php" method="POST">
            <div class="mb-3">
                <label for="matric_number" class="form-label">Matric Number *</label>
                <input type="text" class="form-control" id="matric_number" name="matric_number" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Request Reset Link</button>
            <div class="text-center mt-3">
                <a href="/public/index.php">Back to Login</a>
            </div>
        </form>
        <?php else: ?>
        <form action="/app/controllers/reset_password.php?token=<?php echo htmlspecialchars($_GET['token']); ?>"
            method="POST">
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password *</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
            <div class="text-center mt-3">
                <a href="/public/index.php">Back to Login</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>