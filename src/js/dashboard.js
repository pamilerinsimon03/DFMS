// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Menu item click functionality
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            // Skip if it's already active or it's the logout item
            if (this.classList.contains('active') || this.classList.contains('logout')) return;
            
            // Remove active class from current active item
            document.querySelector('.menu-item.active').classList.remove('active');
            document.querySelector('.menu-text.active-text').classList.remove('active-text');
            document.querySelector('.menu-bg')?.remove();
            
            // Add active class to clicked item
            this.classList.add('active');
            this.querySelector('.menu-text').classList.add('active-text');
            
            // Create background element
            const bg = document.createElement('div');
            bg.classList.add('menu-bg');
            this.prepend(bg);
            
            // Handle page navigation
            const menuText = this.querySelector('.menu-text').textContent.trim();
            console.log(`Navigating to: ${menuText}`);
            
            // Navigate to the appropriate page
            if (menuText === 'Dashboard') {
                window.location.href = 'dashboard.html';
            } else if (menuText === 'My Uploads') {
                window.location.href = 'uploads.html';
            } else {
                // Other pages not yet implemented
                alert(`The ${menuText} page will be implemented in a future phase.`);
            }
        });
    });
    
    // Logout functionality
    const logoutBtn = document.querySelector('.menu-item.logout');
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to log out?')) {
                // Redirect to login page
                window.location.href = '/';
            }
        });
    }
    
    // Upload button functionality
    const uploadBtn = document.querySelector('.upload-btn');
    
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function() {
            // In a real application, this would open a file selection dialog
            // For now, we'll just show an alert
            alert('File upload functionality will be implemented in Phase 3.');
        });
    }
    
    // Notification icon functionality
    const notificationIcon = document.querySelector('.notification-icon');
    
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            alert('You have new notifications about your uploaded documents.');
        });
    }
    
    // File card click functionality
    const fileCards = document.querySelectorAll('.file-card');
    
    fileCards.forEach(card => {
        card.addEventListener('click', function() {
            const fileName = this.querySelector('.file-name').textContent;
            const status = this.querySelector('.file-status').classList.contains('verified') ? 'Verified' : 'Pending';
            
            alert(`File: ${fileName}\nStatus: ${status}\n\nDetailed view will be implemented in Phase 4.`);
        });
    });
});
