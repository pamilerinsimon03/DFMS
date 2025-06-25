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
            alert('File uploaded Successfully.');
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
    const burger = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    }

    burger.addEventListener('click', function(e) {
        e.stopPropagation();
        if (sidebar.classList.contains('open')) {
        closeSidebar();
        } else {
        openSidebar();
        }
    });

    // If you click the overlay itself, close the sidebar
    overlay.addEventListener('click', function() {
        closeSidebar();
    });

    // Grab references to everything we need:
    const mobileBreakpoint = 768;
    const dashboardContent = document.querySelector('.dashboard-content');
    const filesSection     = document.querySelector('.files-section');
    const rightSidebar     = document.querySelector('.right-sidebar');
    const docsCounter      = document.querySelector('.documents-counter');
    const uploadSection    = document.querySelector('.upload-section');
    const todoFiles        = document.querySelector('.todo-files');

    // We will keep placeholders so that we can restore the HTML exactly on desktop:
    const docsPlaceholder   = document.createElement('div');
    const uploadPlaceholder = document.createElement('div');
    const todoPlaceholder   = document.createElement('div');

    // Give them non‐intrusive class names so they don't break your CSS:
    docsPlaceholder.className   = 'placeholder-docs';
    uploadPlaceholder.className = 'placeholder-upload';
    todoPlaceholder.className   = 'placeholder-todo';

    // On initial load, insert placeholders where the counter/upload/todo originally live:
    if (rightSidebar.contains(docsCounter)) {
        docsPlaceholder.replaceWith(docsCounter);       // (in case it moved on a previous resize)
        rightSidebar.insertBefore(docsCounter, rightSidebar.firstChild);
    }
    if (rightSidebar.contains(uploadSection)) {
        uploadPlaceholder.replaceWith(uploadSection);
        rightSidebar.insertBefore(uploadSection, docsCounter.nextSibling);
    }
    if (rightSidebar.contains(todoFiles)) {
        todoPlaceholder.replaceWith(todoFiles);
        rightSidebar.appendChild(todoFiles);
    }

    function reorderForMobile() {
        const w = window.innerWidth;
        if (w <= mobileBreakpoint) {
            // ───────────────────────────────────────────────────────────────────
            // MOBILE: take docsCounter & uploadSection OUT of rightSidebar,
            // and insert them inside dashboardContent, right after user-info-card.
            // Then take todoFiles out of rightSidebar and append after .files-section.
            // ───────────────────────────────────────────────────────────────────

            // 1) If docsCounter is still inside rightSidebar, move it:
            if (rightSidebar.contains(docsCounter)) {
            docsPlaceholder.replaceWith(docsCounter);      // restore docsCounter here
            dashboardContent.insertBefore(
                docsCounter,
                dashboardContent.querySelector('.user-info-card').nextSibling
            );
            }

            // 2) Similarly for uploadSection:
            if (rightSidebar.contains(uploadSection)) {
            uploadPlaceholder.replaceWith(uploadSection);
            const afterUserCard = dashboardContent.querySelector('.user-info-card').nextSibling;
            // If docsCounter was just inserted, afterUserCard is docsCounter. So insert uploadSection after docsCounter:
            dashboardContent.insertBefore(uploadSection, afterUserCard.nextSibling);
            }

            // 3) Now move todoFiles out of rightSidebar and place it right after .files-section:
            if (rightSidebar.contains(todoFiles)) {
            todoPlaceholder.replaceWith(todoFiles);
            dashboardContent.insertBefore(
                todoFiles,
                filesSection.nextSibling
            );
            }
        } else {
            // ───────────────────────────────────────────────────────────────────
            // DESKTOP (wider than 768px): put everything BACK inside rightSidebar
            // in the original order: [docsCounter, uploadSection, todoFiles].
            // ───────────────────────────────────────────────────────────────────

            // 1) docsCounter → back into rightSidebar, in front:
            if (! rightSidebar.contains(docsCounter)) {
            dashboardContent.replaceChild(docsPlaceholder, docsCounter);
            rightSidebar.insertBefore(docsCounter, rightSidebar.firstChild);
            }

            // 2) uploadSection → second child of rightSidebar:
            if (! rightSidebar.contains(uploadSection)) {
            dashboardContent.replaceChild(uploadPlaceholder, uploadSection);
            rightSidebar.insertBefore(uploadSection, docsCounter.nextSibling);
            }

            // 3) todoFiles → last child of rightSidebar:
            if (! rightSidebar.contains(todoFiles)) {
            dashboardContent.replaceChild(todoPlaceholder, todoFiles);
            rightSidebar.appendChild(todoFiles);
            }
        }
        }

    // Run on load, then again on every resize:
    reorderForMobile();
    window.addEventListener('resize', reorderForMobile);
});
