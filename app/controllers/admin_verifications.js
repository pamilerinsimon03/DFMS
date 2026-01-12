function logEvent(message) {
    fetch('log_event.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message })
    });
}

// Form Handling
// Form Handling
function initForm() {
    const dateForm = document.getElementById('filter-sort-form');
    const searchInput = document.getElementById('search-input');
    const formSearch = document.getElementById('form-search');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (dateForm) {
        dateForm.addEventListener('submit', (e) => {
            // Validate timestamps
            if (startDateInput && endDateInput && startDateInput.value && endDateInput.value) {
                if (startDateInput.value > endDateInput.value) {
                    endDateInput.value = startDateInput.value;
                }
            }
            
            // Sync search value if it exists
            if (searchInput && formSearch) {
                formSearch.value = searchInput.value;
            }

            logEvent('Filter form submitted: ' + JSON.stringify({
                tab: dateForm.querySelector('input[name="tab"]')?.value || '',
                start_date: startDateInput?.value || '',
                end_date: endDateInput?.value || '',
                search: searchInput?.value || ''
            }));
            
            // We do NOT call preventDefault() or submit() here.
            // We let the event bubble up to admin.js which handles the AJAX submission.
        });
    }
}

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
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    logEvent('admin_verifications.js loaded');
    initForm();
});