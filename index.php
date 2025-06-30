<?php
require_once 'config.php';

$_SESSION['redirect_count'] = isset($_SESSION['redirect_count']) ? $_SESSION['redirect_count'] + 1 : 1;

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    file_put_contents('debug.txt', "Index ID: $user_id (Type: " . gettype($user_id) . ")\n", FILE_APPEND);

    // Query to check admin status
    $sql = "SELECT is_admin FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $error = "Prepare failed: " . $conn->error . " | SQL: $sql";
        file_put_contents('debug.txt', $error . "\n", FILE_APPEND);
        $_SESSION['error'] = "Database error. Please contact support.";
        $_SESSION['redirect_count'] = 0;
        header("Location: index.php");
        exit();
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        file_put_contents('debug.txt', "User admin status: " . ($user['is_admin'] ?? 'N/A') . "\n", FILE_APPEND);
        $_SESSION['redirect_count'] = 0;
        if ($user['is_admin'] == 1) {
            header("Location: admin.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        file_put_contents('debug.txt', "No user found for ID: $user_id\n", FILE_APPEND);
        $_SESSION['error'] = "User not found. Please log in again.";
        unset($_SESSION['user_id']);
        $_SESSION['redirect_count'] = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of Ibadan DFMS - Login</title>
    <link rel="stylesheet" href="src/css/global.css">
    <link rel="stylesheet" href="src/css/styleguide.css">
    <link rel="stylesheet" href="src/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
    <style>
    .message {
        text-align: center;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .success {
        color: green;
    }

    .error {
        color: red;
    }
    </style>
</head>

<body>
    <?php if (isset($_SESSION['success'])): ?>
    <p class="message success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
    <script>
    setTimeout(() => {
        window.location.href = 'dashboard.php';
    }, 2000);
    </script>
    <?php elseif (isset($_SESSION['error'])): ?>
    <p class="message error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
    <?php endif; ?>
    <div class="log-in">
        <div class="div">
            <div class="overlap">
                <div class="overlap-group">
                    <div class="rectangle"></div>
                    <img class="image" src="src/assets/images/ui-logo.png" alt="University of Ibadan Logo" />
                    <div class="rectangle-2"></div>
                    <hr class="line" />
                    <div class="frame-7">
                        <h1 class="text-wrapper-5">UNIVERSITY OF IBADAN</h1>
                        <h2 class="text-wrapper-6">DIGITAL FILE MANAGEMENT PORTAL</h2>
                    </div>
                    <div class="overlap-2">
                        <img class="img" src="src/assets/images/ui-logo-watermark.png"
                            alt="University of Ibadan Logo Watermark" />
                        <div class="frame">
                            <h3 class="text-wrapper">Login</h3>
                            <form class="frame" action="login.php" method="POST">
                                <div class="frame-2">
                                    <div class="frame-3">
                                        <div class="frame-4">
                                            <div class="component-wrapper">
                                                <div class="component">
                                                    <div class="frame-5">
                                                        <label class="input-label" for="matricNumber">Matric
                                                            Number</label>
                                                        <span class="text-wrapper-2">*</span>
                                                    </div>
                                                    <div class="frame-6">
                                                        <input type="text" placeholder="Enter your matric number"
                                                            class="input-content" id="matricNumber" name="matric_number"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="group">
                                                <div class="overlap-group-2">
                                                    <div class="component-2">
                                                        <div class="frame-5">
                                                            <label class="input-label-2" for="password">Password</label>
                                                            <span class="text-wrapper-2">*</span>
                                                        </div>
                                                        <div class="frame-6">
                                                            <input type="password" placeholder="Enter your password"
                                                                class="input-content-2" id="password" name="password"
                                                                required>
                                                            <img class="COCO-line-eye-slash" id="togglePassword"
                                                                src="src/assets/images/eye-slash.svg"
                                                                alt="Toggle password visibility" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" id="loginBtn" class="component-3">
                                            <span class="button-text">Sign In</span>
                                        </button>
                                        <p class="forgotten-password">
                                            <span class="span">Forgotten Password? </span>
                                            <a href="reset_password.php" class="text-wrapper-4">Reset Password</a>
                                        </p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.src = type === 'password' ? 'src/assets/images/eye-slash.svg' : 'src/assets/images/eye.svg';
    });
    </script>
</body>

</html>