// login.js
document.addEventListener('DOMContentLoaded', function() {
  // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.src = type === 'password'
            ? 'src/assets/images/eye-slash.svg'
            : 'src/assets/images/eye.svg';
        });
    }

    // Handle login click
    const loginBtn = document.getElementById('loginBtn');
    loginBtn.addEventListener('click', function(e) {
        e.preventDefault();

            const matricNumber = document.getElementById('matricNumber').value.trim();
            const password = document.getElementById('password').value.trim();

        // Basic validation
        if (!matricNumber) {
        alert('Please enter your Staff ID');
        return;
        }
        if (!password) {
        alert('Please enter your Password');
        return;
        }

        // Dummy admin credentials
        const ADMIN_ID       = 'ADMIN001';
        const ADMIN_PASSWORD = 'password123';

        if (matricNumber === ADMIN_ID && password === ADMIN_PASSWORD) {
        // Admin login
        alert('Admin login successful. Redirecting to admin dashboard...');
        window.location.href = 'src/pages/admin.html'; 
        } else {
        // Student (or incorrect) login
        // In a real app you'd verify against a database or auth provider
        alert('Login successful. Redirecting to student dashboard...');
        window.location.href = 'src/pages/dashboard.html';
        }
    });
});
