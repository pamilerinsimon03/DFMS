// JavaScript for uploads page functionality

document.addEventListener('DOMContentLoaded', function () {
    // Menu item click functionality
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function () {
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
                window.location.href = 'dashboard.php';
            } else if (menuText === 'My Uploads') {
                window.location.href = 'uploads.php';
            } //else {
            // Other pages not yet implemented
            //alert(`The ${menuText} page will be implemented in a future phase.`);
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

    // Search UX parity with dashboard
    const searchContainer = document.getElementById('search-container');
    const searchIconBtn = document.getElementById('search-icon-btn');
    const searchInput = document.querySelector('.search-input');

    if (searchIconBtn && searchContainer && searchInput) {
        searchIconBtn.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                searchContainer.classList.toggle('active');
                if (searchContainer.classList.contains('active')) {
                    searchInput.focus();
                }
            }
        });

        // Submit on Enter
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchInput.form.submit();
            }
        });

        // Debounced submit while typing
        let t;
        searchInput.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => {
                searchInput.form.submit();
            }, 500);
        });
    }
    
    // Tab switching functionality
    const tabItems = document.querySelectorAll('.tab-item');
    
    tabItems.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabItems.forEach(item => item.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Here you would typically filter the table based on the selected tab
            // For now, we'll just show a notification
            const tabName = this.textContent.trim();
            console.log(`Switched to ${tabName} tab`);
            
            // Filter table rows based on selected tab
            filterTableByTab(tabName);
        });
    });
    
    // Date filter functionality
    const dateInputs = document.querySelectorAll('.date-input');
    
    dateInputs.forEach(input => {
        input.addEventListener('click', function() {
            // In a real implementation, this would open a date picker
            console.log('Date picker would open here');
        });
    });
    
    // Action button functionality
    const actionButtons = document.querySelectorAll('.td-action button');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.textContent.trim();
            const row = this.closest('.table-row');
            const documentName = row.querySelector('.td-document').textContent;
            
            if (action === 'Reupload Document') {
                // In a real implementation, this would open a file upload dialog
                console.log(`Reupload requested for ${documentName}`);
                alert(`Reupload requested for ${documentName}`);
            } else {
                console.log(`${action} clicked for ${documentName}`);
                alert(`${action} clicked for ${documentName}`);
            }
        });
    });
    
    // Function to filter table rows based on selected tab
    function filterTableByTab(tabName) {
        document.querySelectorAll('.table-row').forEach(row => {
            const statusText = row.querySelector('.td-status')
                                .textContent.trim();

            let show = false;
            switch (tabName) {
            case 'Total Uploads':
                show = true; break;
            case 'Pending Verification':
                show = (statusText === 'Pending Approval'); break;
            case 'Verified':
                show = (statusText === 'Approved'); break;
            case 'Rejected':
                show = (statusText === 'Rejected'); break;
            default:
                show = true;  // or false, depending on your UX
            }

            row.style.display = show ? '' : 'none';
        });
    }
    
    // Add calendar icon images
    const calendarIcons = document.querySelectorAll('.date-input img');
    calendarIcons.forEach(icon => {
        if (!icon.src.includes('calendar.svg')) {
            // If we're missing the actual calendar.svg file, we could create a fallback
            // But for now, we'll just log a message
            console.log('Calendar icon image is missing');
        }
    });
    
    // Initialize with the first tab (Total Uploads) selected
    const firstTab = document.querySelector('.tab-item.active');
    if (firstTab) {
        filterTableByTab(firstTab.textContent.trim());
    }

    // Notifications dropdown support (page-local)
    const notifIcons = document.querySelectorAll('.notification-icon');
    notifIcons.forEach(icon => {
        const dropdown = icon.querySelector('.notifications-dropdown');
        if (!dropdown) return;
        icon.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
        });
    });
    document.addEventListener('click', () => {
        document.querySelectorAll('.notifications-dropdown').forEach(d => d.style.display = 'none');
    });
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const res = await fetch('/app/controllers/notifications_mark_read.php', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    document.querySelectorAll('.notif-item').forEach(n => n.classList.remove('unread'));
                    document.querySelectorAll('.notification-icon .notification-dot').forEach(dot => dot.remove());
                }
            } catch (err) {
                console.error('Failed to mark notifications as read');
            }
        });
    });
});
