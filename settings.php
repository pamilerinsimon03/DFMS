<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT full_name, matric_number FROM users WHERE id = $user_id";
$user = $conn->query($sql)->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $sql = "SELECT password FROM users WHERE id = $user_id";
    $current_hashed_password = $conn->query($sql)->fetch_assoc()['password'];

    if (!password_verify($current_password, $current_hashed_password)) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = '$new_hashed_password' WHERE id = $user_id";
        if ($conn->query($sql)) {
            $success = "Password updated successfully.";
        } else {
            $error = "Failed to update password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - UI File Management Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { background-color: #343a40; color: white; height: 100vh; }
        .sidebar a { color: white; text-decoration: none; }
        .sidebar a:hover { background-color: #495057; }
        .error { color: red; }
        .success { color: green; }
        @media (max-width: 768px) { .sidebar { height: auto; } }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="uploads.php">My Uploads</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link active" href="settings.php">Settings</a></li>
                    <li class="nav-item mt-3"><a class="nav-link" href="logout.php">Log out</a></li>
                </ul>
            </div>
            <div class="col-md-9 col-lg-10 p-4">
                <h3>Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                        <p>Matric No: <?php echo htmlspecialchars($user['matric_number']); ?></p>
                    </div>
                </div>
                <div class="card">
                    <h5 class="card-header">Change Password</h5>
                    <div class="card-body">
                        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
                        <?php if (isset($success)) { echo "<p class='success'>$success</p>"; } ?>
                        <form action="settings.php" method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>