<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/models/User.php';

if (isset($_SESSION['user_id'])) {
    if (User::isAdmin($_SESSION['user_id'])) {
        header("Location: /app/controllers/admin.php");
    } else {
        header("Location: /app/controllers/dashboard.php");
    }
    exit();
}

$page_title = 'Login';
$css_files = ['style.css', 'styleguide.css', 'toast.css'];
$js_files = ['toast.js'];

require_once __DIR__ . '/../app/views/partials/header.php';
?>
<div class="log-in">
    <div class="div">
        <div class="overlap">
            <div class="overlap-group">
                <div class="rectangle"></div>
                <img class="image" src="/app/src/assets/images/ui-logo.png" alt="University of Ibadan Logo" />
                <div class="rectangle-2"></div>
                <hr class="line" />
                <div class="frame-7">
                    <h1 class="text-wrapper-5">UNIVERSITY OF IBADAN</h1>
                    <h2 class="text-wrapper-6">DIGITAL FILE MANAGEMENT PORTAL</h2>
                </div>
                <div class="overlap-2">
                    <img class="img" src="/app/src/assets/images/ui-logo-watermark.png"
                        alt="University of Ibadan Logo Watermark" />
                    <div class="frame">
                        <h3 class="text-wrapper">Login</h3>
                        <form class="frame" action="/app/controllers/login.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <div class="frame-2">
                                <div class="frame-3">
                                    <div class="frame-4">
                                        <div class="component-wrapper">
                                            <div class="component">
                                                <div class="frame-5">
                                                    <label class="input-label" for="matricNumber">Matric Number</label>
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
                                                            src="/app/src/assets/images/eye-slash.svg"
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
                                        <a href="/app/controllers/reset_password.php" class="text-wrapper-4">Reset
                                            Password</a>
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

<!-- Toast notification data -->
<?php if (isset($_SESSION['error'])): ?>
<div data-error="<?php echo htmlspecialchars($_SESSION['error']); ?>" style="display: none;"></div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<div data-success="<?php echo htmlspecialchars($_SESSION['success']); ?>" style="display: none;"></div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    this.src = type === 'password' ? '/app/src/assets/images/eye-slash.svg' : '/app/src/assets/images/eye.svg';
});
</script>
<?php
require_once __DIR__ . '/../app/views/partials/footer.php';