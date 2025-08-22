document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.tab-link');
    const downloadBtn = document.getElementById('download-all');
    const studentId = document.querySelector('.student-id').getAttribute('data-student-id');
    const docRows = document.querySelectorAll('.doc-row');

    // helper to update download button text & data-status
    function updateDownloadBtn(activeTab) {
        let labelMap = {
        all:                'Download All Documents',
        Approved:           'Download All Verified Documents',
        'Pending Approval': 'Download All Pending Documents',
        Rejected:           'Download All Rejected Documents'
        };
        downloadBtn.textContent = labelMap[activeTab] || labelMap.all;
        downloadBtn.setAttribute('data-status', activeTab);
    }

    updateDownloadBtn('all');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Deactivate all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Activate clicked tab
            tab.classList.add('active');

            const selectedStatus = tab.getAttribute('data-tab');
            updateDownloadBtn(selectedStatus);

            // Filter documents
            docRows.forEach(row => {
                const docStatus = row.getAttribute('data-status');
                if (selectedStatus === 'all' || docStatus === selectedStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // on click, navigate to our new download‐all endpoint
    downloadBtn.addEventListener('click', () => {
        const status = downloadBtn.getAttribute('data-status');
        // URL encode status
        const urlStatus = encodeURIComponent(status);
        window.location.href = `/app/controllers/download_all_documents.php`
                            + `?user_id=${studentId}&status=${urlStatus}`;
    });

    const toggles = document.querySelectorAll('.dropdown-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('click', function (e) {
        e.stopPropagation(); // don’t let the global click‐handler immediately close it
        const wrapper = this.closest('.dropdown-wrapper');
        // close any others
        document.querySelectorAll('.dropdown-wrapper.open')
                .forEach(w => w !== wrapper && w.classList.remove('open'));
        // toggle this one
        wrapper.classList.toggle('open');
        });
    });

    // click anywhere else closes open menus
    document.addEventListener('click', function () {
        document.querySelectorAll('.dropdown-wrapper.open')
                .forEach(w => w.classList.remove('open'));
    });
});
