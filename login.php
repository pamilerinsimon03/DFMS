<?php
require_once 'config.php';

// Prevent redirect loop
if (isset($_SESSION['redirect_count']) && $_SESSION['redirect_count'] > 5) {
    file_put_contents('debug.txt', "Redirect loop detected: User ID {$_SESSION['user_id']}\n", FILE_APPEND);
    die("Error: Too many redirects. Please contact support.");
}
$_SESSION['redirect_count'] = isset($_SESSION['redirect_count']) ? $_SESSION['redirect_count'] + 1 : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matric_number = $conn->real_escape_string($_POST['matric_number']);
    $password = $_POST['password'];

    // Debug: Log login attempt
    file_put_contents('debug.txt', "Login attempt: Matric Number: $matric_number\n", FILE_APPEND);

    $sql = "SELECT id, full_name, password, is_admin FROM users WHERE matric_number = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $error = "Prepare failed: " . $conn->error . " | SQL: $sql";
        file_put_contents('debug.txt', $error . "\n", FILE_APPEND);
        $_SESSION['error'] = "Database error: " . $conn->error;
        $_SESSION['redirect_count'] = 0;
        header("Location: index.php");
        exit();
    }
    $stmt->bind_param("s", $matric_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['success'] = "Login successful. Redirecting...";

        // Debug: Log successful login and admin status
        file_put_contents('debug.txt', "Login successful: User ID: {$user['id']}, is_admin: {$user['is_admin']}\n", FILE_APPEND);

        $_SESSION['redirect_count'] = 0;
        if ($user['is_admin'] == 1) {
            header("Location: admin.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        // Debug: Log failed login
        file_put_contents('debug.txt', "Login failed: Invalid matric number or password for $matric_number\n", FILE_APPEND);
        $_SESSION['error'] = "Invalid matric number or password.";
        $_SESSION['redirect_count'] = 0;
        header("Location: index.php");
        exit();
    }
} else {
    // Debug: Log invalid request method
    file_put_contents('debug.txt', "Invalid request method: {$_SERVER['REQUEST_METHOD']}\n", FILE_APPEND);
    $_SESSION['error'] = "Invalid request.";
    $_SESSION['redirect_count'] = 0;
    header("Location: index.php");
    exit();
}
?>