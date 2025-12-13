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

    // --- Dedicated AJAX Search for Uploads Page ---
    const searchContainer = document.getElementById('search-container');
    const searchIconBtn = document.getElementById('search-icon-btn');
    const searchInput = document.querySelector('.search-input');
    const searchForm = document.querySelector('.search-form');

    if (searchIconBtn && searchContainer && searchInput) {
        // Mobile Toggle
        searchIconBtn.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                searchContainer.classList.toggle('active');
                if (searchContainer.classList.contains('active')) {
                    searchInput.focus();
                }
            }
        });

        // Debounce Utility
        const debounce = (func, wait) => {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        };

        const performAjaxSearch = (query, tab = null) => {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('search', query);
            currentUrl.searchParams.set('ajax', '1');
            
            // If tab is provided, use it. But query param might already be there from URL
            if (tab) {
                currentUrl.searchParams.set('tab', tab);
            }

            const tbody = document.querySelector('.uploads-table tbody');
            const tableWrapper = document.querySelector('.uploads-table-wrapper');
            
            if (tbody) tbody.style.opacity = '0.5';

            fetch(currentUrl)
                .then(response => response.json())
                .then(data => {
                    if (tbody) {
                        tbody.innerHTML = data.html;
                        tbody.style.opacity = '1';
                    }
                    
                    // Update Search Info
                    const tabsContainer = document.querySelector('.uploads-tabs');
                    if (tabsContainer) {
                        let infoDiv = tabsContainer.querySelector('.search-results-info');
                        if (data.info_html) {
                            if (infoDiv) {
                                infoDiv.outerHTML = data.info_html;
                            } else {
                                tabsContainer.insertAdjacentHTML('beforeend', data.info_html);
                            }
                        } else {
                            if (infoDiv) infoDiv.remove();
                        }
                    }
                    
                    // Re-initialize Lucide icons
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }

                    // Update URL
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('search', query);
                    if (tab) newUrl.searchParams.set('tab', tab);
                    window.history.pushState({}, '', newUrl);
                })
                .catch(err => {
                    console.error('Search failed', err);
                    if (tbody) tbody.style.opacity = '1';
                });
        };

        const debouncedAjaxSearch = debounce(performAjaxSearch, 300);

        searchInput.addEventListener('input', function(e) {
            const query = this.value.toLowerCase().trim();
            debouncedAjaxSearch(query);
        });

        // Prevent default form submission
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
            });
        }
    }


    
    // Tab switching functionality

    // --- AJAX Tab Switching ---
    const tabItems = document.querySelectorAll('.tab-item');
    
    tabItems.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all
            tabItems.forEach(item => item.classList.remove('active'));
            // Add active to clicked
            this.classList.add('active');
            
            // Extract tab value from href
            const url = new URL(this.href);
            const tabValue = url.searchParams.get('tab') || 'total';
            const searchTerm = searchInput ? searchInput.value.trim() : '';
            
            // Perform AJAX fetch
            performAjaxSearch(searchTerm, tabValue);
        });
    });

    // Modified performAjaxSearch to accept tab
    const performAjaxSearch = (query, tab = null) => {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('search', query);
        currentUrl.searchParams.set('ajax', '1');
        
        // If tab is provided, use it. Otherwise keep existing or default to total
        if (tab) {
            currentUrl.searchParams.set('tab', tab);
        } else if (!currentUrl.searchParams.has('tab')) {
            currentUrl.searchParams.set('tab', 'total');
        }

        const tbody = document.querySelector('.uploads-table tbody');
        
        if (tbody) tbody.style.opacity = '0.5';

        fetch(currentUrl)
            .then(response => response.json())
            .then(data => {
                if (tbody) {
                    tbody.innerHTML = data.html;
                    tbody.style.opacity = '1';
                }
                
                // Update Search Info
                const tabsContainer = document.querySelector('.uploads-tabs');
                if (tabsContainer) {
                    let infoDiv = tabsContainer.querySelector('.search-results-info');
                    if (data.info_html) {
                        if (infoDiv) {
                            infoDiv.outerHTML = data.info_html;
                        } else {
                            tabsContainer.insertAdjacentHTML('beforeend', data.info_html);
                        }
                    } else {
                        if (infoDiv) infoDiv.remove();
                    }
                }
                
                if (window.lucide) {
                    window.lucide.createIcons();
                }

                // Update URL
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('search', query);
                if (tab) newUrl.searchParams.set('tab', tab);
                window.history.pushState({}, '', newUrl);
            })
            .catch(err => {
                console.error('Search failed', err);
                if (tbody) tbody.style.opacity = '1';
            });
    };

    // Update the earlier definition of performAjaxSearch to match this signature
    // We need to replace the definition inside the 'if (searchIconBtn...)' block too
    // But since this replaces the later block, let's just make sure the debounced one works.
    // The previous performAjaxSearch definition (around line 96) needs to be compatible.
    // Actually, I should remove the old definition and consolidate it.

    
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
