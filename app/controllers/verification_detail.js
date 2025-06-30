function logEvent(message) {
    fetch('log_event.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message })
    });
}

// Modal Handling
function initModal() {
    const zoomIcon = document.querySelector('.zoom-icon img');
    const modal = document.querySelector('.doc-modal');
    const modalImg = document.getElementById('modal-img');
    const closeModal = document.querySelector('.close-modal img');
    const pdfObject = document.querySelector('.doc-embed');

    if (zoomIcon && modal && modalImg && closeModal && pdfObject) {
        zoomIcon.addEventListener('click', () => {
            modalImg.src = pdfObject.data; // Use PDF URL
            modal.style.display = 'block';
            logEvent('Zoom modal opened');
        });

        closeModal.addEventListener('click', () => {
            modal.style.display = 'none';
            modalImg.src = '';
            logEvent('Zoom modal closed');
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                modalImg.src = '';
                logEvent('Zoom modal closed via overlay');
            }
        });
    }
}

// Form Handling
function initForm() {
    const dateForm = document.getElementById('date-filter-form');
    const filterButton = document.querySelector('.filter-button');
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');

    if (dateForm && filterButton) {
        filterButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (startDateInput.value && endDateInput.value) {
                if (startDateInput.value > endDateInput.value) {
                    endDateInput.value = startDateInput.value;
                }
                logEvent('Filter button clicked: ' + JSON.stringify({
                    start_date: startDateInput.value,
                    end_date: endDateInput.value
                }));
                dateForm.submit();
            }
        });
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    logEvent('verification_detail.js loaded');
    initModal();
    initForm();
});