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

