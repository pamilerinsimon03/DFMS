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

    // Initialize form values
    window.addEventListener('load', () => {
        if (startDateInput && endDateInput && formSearch && searchInput) {
            startDateInput.value = '<?php echo htmlspecialchars($start_date); ?>';
            endDateInput.value = '<?php echo htmlspecialchars($end_date); ?>';
            formSearch.value = '<?php echo htmlspecialchars($search); ?>';
            searchInput.value = 'Search Here';
            logEvent('Form initialized');
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    logEvent('admin_verifications.js loaded');
    initForm();
});