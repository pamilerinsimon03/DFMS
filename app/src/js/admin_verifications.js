/* stylelint-disable */
// Admin Verifications page JS

document.addEventListener('DOMContentLoaded', () => {
    const actionMenus = document.querySelectorAll('.action-menu');

    actionMenus.forEach(menu => {
        const btn = menu.querySelector('.action-btn');
        const menuContent = menu.querySelector('.action-menu-content');

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            // Close other menus
            actionMenus.forEach(otherMenu => {
                if (otherMenu !== menu) {
                    otherMenu.querySelector('.action-menu-content').style.display = 'none';
                }
            });
            menuContent.style.display = menuContent.style.display === 'block' ? 'none' : 'block';
        });
    });

    document.addEventListener('click', () => {
        actionMenus.forEach(menu => {
            menu.querySelector('.action-menu-content').style.display = 'none';
        });
    });

    // Enhanced Search Functionality
    const searchInput = document.getElementById('search-input');
    const searchSuggestions = document.getElementById('search-suggestions');
    
    if (searchInput && searchSuggestions) {
        // Show suggestions on focus
        searchInput.addEventListener('focus', () => {
            searchSuggestions.style.display = 'block';
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                searchSuggestions.style.display = 'none';
            }
        });

        // Auto-submit search after typing stops (debounced)
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (searchInput.value.trim().length >= 2) {
                    searchInput.form.submit();
                }
            }, 500); // 500ms delay
        });

        // Submit on Enter key
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchInput.form.submit();
            }
        });

        // Clear search functionality
        searchInput.addEventListener('input', () => {
            if (searchInput.value === '') {
                // Auto-submit empty search to clear results
                setTimeout(() => {
                    searchInput.form.submit();
                }, 100);
            }
        });
    }

    // Enhanced filter form functionality
    const filterForm = document.getElementById('filter-sort-form');
    if (filterForm) {
        // Auto-submit when filters change
        const filterInputs = filterForm.querySelectorAll('select, input[type="text"]');
        filterInputs.forEach(input => {
            if (input.name !== 'search') { // Don't auto-submit search input
                input.addEventListener('change', () => {
                    filterForm.submit();
                });
            }
        });
    }
});

