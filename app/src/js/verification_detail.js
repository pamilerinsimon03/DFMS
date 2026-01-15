const initVerificationDetail = () => {
    const backButton = document.querySelector('.back-icon');
    if (backButton && !backButton.dataset.init) {
        backButton.dataset.init = 'true';
        backButton.addEventListener('click', () => {
            window.history.back();
        });
    }

    const modal = document.getElementById('doc-modal');
    const zoomIcon = document.querySelector('.zoom-icon');
    const closeModal = document.querySelector('.close-modal');
    const modalIframe = document.getElementById('modal-iframe');
    const docEmbed = document.querySelector('.doc-embed');
    const captionText = document.getElementById('doc-modal-caption-text');

    const openModal = () => {
        if (modal && modalIframe && docEmbed && captionText) {
            const docName = modal.dataset.docName;
            captionText.textContent = docName;
            modal.style.display = 'block';
            modalIframe.src = `${docEmbed.src}#toolbar=0&navpanes=0&scrollbar=0`;
        }
    };

    const closeModalHandler = () => {
        if (modal) {
            modal.style.display = 'none';
        }
    };

    if (zoomIcon && !zoomIcon.dataset.init) {
        zoomIcon.dataset.init = 'true';
        zoomIcon.addEventListener('click', openModal);
    }

    if (closeModal && !closeModal.dataset.init) {
        closeModal.dataset.init = 'true';
        closeModal.addEventListener('click', closeModalHandler);
    }

    if (modal && !modal.dataset.initGlobal) {
        modal.dataset.initGlobal = 'true';
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModalHandler();
            }
            const rejectionModal = document.getElementById('rejection-modal');
            if (event.target === rejectionModal) {
                rejectionModal.style.display = 'none';
            }
        });
    }

    const rejectTriggerBtn = document.getElementById('reject-trigger-btn');
    const rejectionModal = document.getElementById('rejection-modal');
    const cancelRejectionBtn = document.getElementById('cancel-rejection');
    const confirmRejectionBtn = document.getElementById('confirm-rejection');
    const rejectionTextarea = document.getElementById('rejection-textarea');
    const hiddenRejectionReason = document.getElementById('hidden-rejection-reason');
    const rejectForm = document.getElementById('reject-form');

    if (rejectTriggerBtn && rejectionModal && !rejectTriggerBtn.dataset.init) {
        rejectTriggerBtn.dataset.init = 'true';
        rejectTriggerBtn.addEventListener('click', () => {
            rejectionModal.style.display = 'flex';
            rejectionTextarea.value = '';
            rejectionTextarea.focus();
        });

        cancelRejectionBtn.addEventListener('click', () => {
            rejectionModal.style.display = 'none';
        });

        confirmRejectionBtn.addEventListener('click', () => {
            const reason = rejectionTextarea.value.trim();
            if (!reason) {
                alert('Please provide a reason for rejection.');
                return;
            }
            hiddenRejectionReason.value = reason;
            rejectForm.submit();
        });
    }
};

window.initVerificationDetail = initVerificationDetail;
document.addEventListener('DOMContentLoaded', initVerificationDetail);
