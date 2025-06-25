<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT full_name, matric_number, level, session, programme FROM users WHERE id = $user_id";
$user = $conn->query($sql)->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $level = $conn->real_escape_string($_POST['level']);
    $programme = $conn->real_escape_string($_POST['programme']);
    $session = $conn->real_escape_string($_POST['session']);

    $sql = "UPDATE users SET full_name = '$full_name', level = '$level', programme = '$programme', session = '$session' WHERE id = $user_id";
    if ($conn->query($sql)) {
        $success = "Profile updated successfully.";
        $_SESSION['full_name'] = $full_name;
        $user = $conn->query("SELECT full_name, matric_number, level, session, programme FROM users WHERE id = $user_id")->fetch_assoc();
    } else {
        $error = "Failed to update profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - UI File Management Portal</title>
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
                    <li class="nav-item"><a class="nav-link active" href="profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
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
                    <h5 class="card-header">Edit Profile</h5>
                    <div class="card-body">
                        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
                        <?php if (isset($success)) { echo "<p class='success'>$success</p>"; } ?>
                        <form action="profile.php" method="POST">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="matric_number" class="form-label">Matric Number</label>
                                <input type="text" class="form-control" id="matric_number" value="<?php echo htmlspecialchars($user['matric_number']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="level" class="form-label">Level</label>
                                <input type="text" class="form-control" id="level" name="level" value="<?php echo htmlspecialchars($user['level']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="session" class="form-label">Session</label>
                                <input type="text" class="form-control" id="session" name="session" value="<?php echo htmlspecialchars($user['session']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="programme" class="form-label">Programme</label>
                                <input type="text" class="form-control" id="programme" name="programme" value="<?php echo htmlspecialchars($user['programme']); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>