// JavaScript for uploads page functionality

const initUploads = () => {
    // Only run if we are on the uploads page
    if (!document.querySelector('.uploads-content')) return;

    // --- Search & Tabs Logic ---
    const searchContainer = document.getElementById('search-container');
    const searchIconBtn = document.getElementById('search-icon-btn');
    const searchInput = document.querySelector('.search-input');
    const searchForm = document.querySelector('.search-form');
    
    // Mobile Search Toggle is handled by dashboard.js globally. 
    // We do not attach a duplicate listener here to avoid double-toggling.

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
        if (query !== null) currentUrl.searchParams.set('search', query);
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
                const tbody = document.querySelector('.uploads-table tbody');
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

                // Update URL (SPA friendly pushState is handled here for search/tabs)
                // Note: SPA router also handles pushState. We should ensure they play nice.
                const newUrl = new URL(window.location.href);
                if (query !== null) newUrl.searchParams.set('search', query);
                if (tab) newUrl.searchParams.set('tab', tab);
                window.history.pushState({}, '', newUrl);
            })
            .catch(err => {
                console.error('Search failed', err);
                if (tbody) tbody.style.opacity = '1';
            });
    };

    const debouncedAjaxSearch = debounce(performAjaxSearch, 300);

    if (searchInput && !searchInput.hasAttribute('data-init-up')) {
        searchInput.setAttribute('data-init-up', 'true');
        searchInput.addEventListener('input', function(e) {
            const query = this.value.toLowerCase().trim();
            debouncedAjaxSearch(query);
        });
    }

    if (searchForm && !searchForm.hasAttribute('data-init-up')) {
        searchForm.setAttribute('data-init-up', 'true');
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
        });
    }

    // --- Tab Switching ---
    // --- Tab Switching & Animation ---
    const tabItems = document.querySelectorAll('.tab-item');
    const tabsContainer = document.querySelector('.tabs-left');
    const indicator = document.querySelector('.tabs-indicator');

    const updateIndicator = (targetTab) => {
        if (!targetTab || !indicator || !tabsContainer) return;
        
        const containerRect = tabsContainer.getBoundingClientRect();
        const tabRect = targetTab.getBoundingClientRect();
        
        // Calculate relative position
        const left = tabRect.left - containerRect.left;
        const width = tabRect.width;

        indicator.style.width = `${width}px`;
        indicator.style.transform = `translateX(${left}px)`;
    };

    // Initial State Check
    const activeTab = document.querySelector('.tab-item.active');
    if (activeTab) {
        // Run immediately but also slightly delayed in case of font loading shift
        updateIndicator(activeTab);
        setTimeout(() => updateIndicator(activeTab), 100);
        
        // Also handle window resize to re-align
        window.addEventListener('resize', () => {
             const currentActive = document.querySelector('.tab-item.active');
             if(currentActive) updateIndicator(currentActive);
        });
    }

    tabItems.forEach(tab => {
        if (tab.hasAttribute('data-init-up-click')) return;
        tab.setAttribute('data-init-up-click', 'true');

        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all
            tabItems.forEach(item => item.classList.remove('active'));
            // Add active to clicked
            this.classList.add('active');
            
            // Animate Line
            updateIndicator(this);
            
            // Extract tab value from href
            const url = new URL(this.href);
            const tabValue = url.searchParams.get('tab') || 'total';
            const searchTerm = searchInput ? searchInput.value.trim() : '';
            
            // Perform AJAX fetch
            performAjaxSearch(searchTerm, tabValue);
        });
    });

    // --- Sidebar Toggle for Uploads Page ---
    // REMOVED: Managed globally by dashboard.js to prevent double-binding and conflicts.
    /*
    const burger = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');

    if (burger && sidebar && overlay) {
       // ... Logic removed ...
    }
    */
};

// Expose globally
window.initUploads = initUploads;

// Auto-run on load
document.addEventListener('DOMContentLoaded', initUploads);
