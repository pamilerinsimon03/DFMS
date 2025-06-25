<?php
require_once 'config.php';

$mode = isset($_GET['token']) ? 'reset' : 'request';
$error = '';
$success = '';

if ($mode === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $matric_number = $conn->real_escape_string($_POST['matric_number']);
    $sql = "SELECT id FROM users WHERE matric_number = '$matric_number'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user_id = $result->fetch_assoc()['id'];
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES ($user_id, '$token', '$expires_at')";
        if ($conn->query($sql)) {
            $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=$token";
            // Simulate email sending (configure actual email sending with PHPMailer below)
            $success = "Password reset link sent to your email: <a href='$reset_link'>$reset_link</a>";
        } else {
            $error = "Failed to generate reset link.";
        }
    } else {
        $error = "Invalid matric number.";
    }
} elseif ($mode === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $conn->real_escape_string($_GET['token']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $sql = "SELECT user_id FROM password_resets WHERE token = '$token' AND expires_at > NOW()";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user_id = $result->fetch_assoc()['user_id'];
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = '$new_hashed_password' WHERE id = $user_id";
            if ($conn->query($sql)) {
                $sql = "DELETE FROM password_resets WHERE token = '$token'";
                $conn->query($sql);
                $success = "Password reset successfully. <a href='index.php'>Login</a>";
            } else {
                $error = "Failed to reset password.";
            }
        }
    } else {
        $error = "Invalid or expired reset token.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UI File Management Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .reset-container { max-width: 400px; margin: 100px auto; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <h2 class="text-center mb-4">UNIVERSITY OF IBADAN</h2>
            <h4 class="text-center mb-4">DIGITAL FILE MANAGEMENT PORTAL</h4>
            <div class="card p-4">
                <h3 class="text-center mb-4"><?php echo $mode === 'request' ? 'Reset Password' : 'Set New Password'; ?></h3>
                <?php if ($error) { echo "<p class='error text-center'>$error</p>"; } ?>
                <?php if ($success) { echo "<p class='success text-center'>$success</p>"; } ?>
                <?php if ($mode === 'request') { ?>
                    <form action="reset_password.php" method="POST">
                        <div class="mb-3">
                            <label for="matric_number" class="form-label">Matric Number *</label>
                            <input type="text" class="form-control" id="matric_number" name="matric_number" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Request Reset Link</button>
                        <div class="text-center mt-3">
                            <a href="index.php">Back to Login</a>
                        </div>
                    </form>
                <?php } else { ?>
                    <form action="reset_password.php?token=<?php echo htmlspecialchars($_GET['token']); ?>" method="POST">
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
                            <a href="index.php">Back to Login</a>
                        </div>
                    </form>
                <?php } ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>