document.addEventListener('DOMContentLoaded', () => {
    // Back button
    const backButton = document.querySelector('.back-icon');
    if (backButton) {
        backButton.addEventListener('click', () => {
        window.history.back();
        });
    }

    // Modal elements
    const modal = document.getElementById('doc-modal');
    const zoomIcon = document.querySelector('.zoom-icon');
    const closeModal = document.querySelector('.close-modal');
    const modalIframe = document.getElementById('modal-iframe');
    const docEmbed = document.querySelector('.doc-embed');
    const captionText = document.getElementById('doc-modal-caption-text');

    // Function to open the modal
    const openModal = () => {
        if (modal && modalIframe && docEmbed && captionText) {
        const docName = modal.dataset.docName;
        captionText.textContent = docName;
        modal.style.display = 'block';
        modalIframe.src = `${docEmbed.src}#toolbar=0&navpanes=0&scrollbar=0`;
        }
    };

    // Function to close the modal
    const closeModalHandler = () => {
        if (modal) {
        modal.style.display = 'none';
        }
    };

    // Event listeners
    if (zoomIcon) {
        zoomIcon.addEventListener('click', openModal);
    }

    if (closeModal) {
        closeModal.addEventListener('click', closeModalHandler);
    }

    // Close modal when clicking outside the content
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
        closeModalHandler();
        }
    });
});
