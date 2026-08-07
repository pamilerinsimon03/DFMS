/* stylelint-disable */
// Admin Verifications page JS - Event Delegated Action Menu Toggle

document.addEventListener('click', (e) => {
    const actionBtn = e.target.closest('.action-btn');
    if (actionBtn) {
        e.preventDefault();
        e.stopPropagation();
        const actionMenu = actionBtn.closest('.action-menu');
        const menuContent = actionMenu ? actionMenu.querySelector('.action-menu-content') : null;
        
        if (menuContent) {
            // Close all other action menus
            document.querySelectorAll('.action-menu-content').forEach(content => {
                if (content !== menuContent) {
                    content.classList.remove('active');
                    content.style.display = 'none';
                }
            });
            
            const isVisible = menuContent.classList.contains('active') || menuContent.style.display === 'block';
            if (isVisible) {
                menuContent.classList.remove('active');
                menuContent.style.display = 'none';
            } else {
                menuContent.classList.add('active');
                menuContent.style.display = 'block';
            }
        }
        return;
    }

    // Close menus when clicking outside
    if (!e.target.closest('.action-menu-content')) {
        document.querySelectorAll('.action-menu-content').forEach(content => {
            content.classList.remove('active');
            content.style.display = 'none';
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Enhanced filter form functionality
    const filterForm = document.getElementById('filter-sort-form');
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('select, input[type="text"]');
        filterInputs.forEach(input => {
            if (input.name !== 'search') {
                input.addEventListener('change', () => {
                    filterForm.submit();
                });
            }
        });
    }
});
