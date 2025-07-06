function logEvent(message) {
    fetch('log_event.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message })
    });
}

// Form Handling
function initForm() {
    const dateForm = document.getElementById('date-filter-form');
    const searchInput = document.getElementById('search-input');
    const formSearch = document.getElementById('form-search');
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const filterButton = document.querySelector('.filter-button');

    if (dateForm && filterButton) {
        filterButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (startDateInput.value && endDateInput.value) {
                if (startDateInput.value > endDateInput.value) {
                    endDateInput.value = startDateInput.value;
                }
                formSearch.value = searchInput.value;
                logEvent('Filter button clicked: ' + JSON.stringify({
                    tab: dateForm.querySelector('input[name="tab"]').value,
                    start_date: startDateInput.value,
                    end_date: endDateInput.value,
                    search: searchInput.value
                }));
                dateForm.submit();
            }
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