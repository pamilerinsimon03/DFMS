<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matric_number = $_POST['matric_number'];
    $password = $_POST['password'];

    $user = User::getByMatricNumber($matric_number);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];

        if ($user['is_admin'] == 1) {
            header("Location: /app/controllers/admin.php");
        } else {
            header("Location: /app/controllers/dashboard.php");
        }
        exit();
    } else {
        $_SESSION['error'] = "Invalid matric number or password.";
        header("Location: /public/index.php");
        exit();
    }
} else {
    header("Location: /public/index.php");
    exit();
}