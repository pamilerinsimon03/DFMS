<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Activate Account'); ?> - UI DFMS</title>
    <link rel="stylesheet" href="/app/src/css/admin.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 1.5rem;
        }
        .activate-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 480px;
            padding: 2.5rem;
        }
        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-header img {
            width: 64px;
            height: auto;
            margin-bottom: 1rem;
        }
        .brand-header h2 {
            margin: 0 0 0.5rem 0;
            color: #1e293b;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .brand-header p {
            margin: 0;
            color: #64748b;
            font-size: 0.925rem;
        }
        .user-badge {
            background: #f1f5f9;
            border-left: 4px solid #4f46e5;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        .user-badge p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: #334155;
        }
        .user-badge strong {
            color: #0f172a;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #334155;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        .btn-activate {
            width: 100%;
            padding: 0.875rem;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-activate:hover {
            background: #4338ca;
        }
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .login-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #4f46e5;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="activate-card">
    <div class="brand-header">
        <img src="/app/assets/images/ui_logo.png" alt="UI Logo" onerror="this.style.display='none'">
        <h2>University of Ibadan</h2>
        <p>Digital File Management System — Account Activation</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($user_record && (int)$user_record['is_activated'] === 0): ?>
        <div class="user-badge">
            <p><strong>Student Name:</strong> <?php echo htmlspecialchars($user_record['full_name']); ?></p>
            <p><strong>Matric Number:</strong> <?php echo htmlspecialchars($user_record['matric_number']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_record['email']); ?></p>
        </div>

        <form method="POST" action="/app/controllers/activate.php?token=<?php echo urlencode($token); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <label for="new_password">Set New Password *</label>
                <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="Minimum 8 characters">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-enter new password">
            </div>

            <button type="submit" class="btn-activate">Activate Account & Set Password</button>
        </form>
    <?php else: ?>
        <a href="/public/index.php" class="login-link">Return to Login Page</a>
    <?php endif; ?>
</div>

</body>
</html>
