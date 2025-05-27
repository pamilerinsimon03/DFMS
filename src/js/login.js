// Login page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            // Toggle the type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle the eye slash icon
            this.src = type === 'password' 
                ? 'src/assets/images/eye-slash.svg' 
                : 'src/assets/images/eye.svg';
        });
    }
    
    // Form submission handling
    const loginForm = document.querySelector('.component-3');
    
    if (loginForm) {
        loginForm.addEventListener('click', function(e) {
            e.preventDefault();
            
            const matricNumber = document.getElementById('matricNumber').value;
            const password = document.getElementById('password').value;
            
            // Basic validation
            if (!matricNumber.trim()) {
                alert('Please enter your Matric Number');
                return;
            }
            
            if (!password.trim()) {
                alert('Please enter your Password');
                return;
            }
            
            // For demonstration purposes only
            // In a real application, this would be handled by Supabase authentication
            console.log('Login attempt with:', { matricNumber, password });
            
            // Mock authentication - replace with actual Supabase auth in Phase 2
            alert('Login successful. Redirecting to dashboard...');
            
            // Redirect to dashboard
            window.location.href = 'src/pages/dashboard.html';
        });
    }
});
