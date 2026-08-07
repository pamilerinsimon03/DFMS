<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/utils/SecurityUtils.php';

$token = SecurityUtils::validateInput($_GET['token'] ?? $_POST['token'] ?? '', 'string');
$error = '';
$success = '';

$user = null;
if (!empty($token)) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE activation_token = ? AND is_activated = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRFToken();

    $dob_input = SecurityUtils::validateInput($_POST['dob'] ?? '', 'string');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$user) {
        $error = "Invalid or expired activation token.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $res = User::activateAccountByToken($token, $dob_input, $new_password);
        if ($res['success']) {
            $success = $res['message'];
        } else {
            $error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activation - DFMS Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #05020c; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); border-radius: 16px; padding: 40px; width: 100%; max-width: 450px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); }
        h2 { color: #fff; margin-top: 0; text-align: center; font-size: 24px; }
        .sub { color: #a0aec0; text-align: center; font-size: 14px; margin-bottom: 24px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; color: #cbd5e0; margin-bottom: 8px; }
        input { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(0,0,0,0.3); color: #fff; font-size: 14px; box-sizing: border-box; }
        button { width: 100%; padding: 14px; background: #01017A; border: none; border-radius: 8px; color: #fff; font-weight: 600; font-size: 15px; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 15px rgba(1, 1, 122, 0.4); }
        button:hover { background: #020296; }
        .alert { padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: rgba(255, 59, 48, 0.2); border: 1px solid #ff3b30; color: #ff6b6b; }
        .alert-success { background: rgba(52, 199, 89, 0.2); border: 1px solid #34c759; color: #6ebe7b; }
        a { color: #60a5fa; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Activate Your Account</h2>
        <p class="sub">Set up your credentials for the Digital File Management System</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?><br><br>
                <a href="/public/index.php" style="font-weight:600;">Go to Login</a>
            </div>
        <?php else: ?>
            <?php if (!$user && !empty($token)): ?>
                <div class="alert alert-error">Invalid or expired activation link. Contact administrator.</div>
            <?php else: ?>
                <form method="POST">
                    <?= getCSRFTokenInput() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <?php if ($user): ?>
                        <p style="font-size:13px; color:#cbd5e0;">Student: <strong><?= htmlspecialchars($user['full_name']) ?></strong> (<?= htmlspecialchars($user['matric_number']) ?>)</p>
                    <?php else: ?>
                        <div class="form-group">
                            <label>Activation Token:</label>
                            <input type="text" name="token" required placeholder="Enter token received via email/admin">
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Verify Date of Birth (YYYY-MM-DD):</label>
                        <input type="date" name="dob" required>
                    </div>

                    <div class="form-group">
                        <label>Create Password:</label>
                        <input type="password" name="new_password" minlength="8" required placeholder="Minimum 8 characters">
                    </div>

                    <div class="form-group">
                        <label>Confirm Password:</label>
                        <input type="password" name="confirm_password" minlength="8" required placeholder="Re-enter password">
                    </div>

                    <button type="submit">Activate Account</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
