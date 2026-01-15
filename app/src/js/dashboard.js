const initDashboard = () => {
    const uploadForm = document.getElementById('upload-form');
    const fileUpload = document.getElementById('file-upload');
    const fileNameDisplay = document.getElementById('file-name-display');
    const uploadButton = document.getElementById('upload-submit-btn');
    const uploadContainer = document.getElementById('upload-container');
    const progressContainer = document.getElementById('upload-progress-container');
    const progressBar = document.getElementById('upload-progress-bar');
    const progressText = document.getElementById('upload-progress-text');

    if (uploadButton) {
        const newBtn = uploadButton.cloneNode(true);
        uploadButton.parentNode.replaceChild(newBtn, uploadButton);
        
        newBtn.addEventListener('click', () => {
             if (uploadForm.checkValidity()) {
                const formData = new FormData(uploadForm);
                const xhr = new XMLHttpRequest();

                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressText.textContent = '0%';
                newBtn.disabled = true;
                newBtn.textContent = 'Uploading...';

                xhr.open('POST', '/app/controllers/upload_file.php', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percentComplete + '%';
                        if (percentComplete === 100) {
                             progressText.textContent = 'Processing & Verifying Quality...';
                             progressBar.style.backgroundColor = '#2196F3';
                        } else {
                            progressText.textContent = percentComplete + '%';
                        }
                    }
                };

                xhr.onload = function() {
                    newBtn.disabled = false;
                    newBtn.textContent = 'Upload files';
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                window.toast.success(response.message);
                                
                                uploadForm.reset();
                                fileNameDisplay.textContent = '';
                                progressContainer.style.display = 'none';

                                const grid = document.querySelector('.files-grid');
                                const noFiles = document.querySelector('.no-files');
                                const counter = document.querySelector('.counter-number');
                                
                                if (noFiles) noFiles.remove();
                                
                                const newCard = document.createElement('div');
                                newCard.className = 'file-card';
                                
                                const docName = response.data && response.data.document_name ? response.data.document_name : 'New Document';
                                
                                newCard.innerHTML = `
                                    <div class="file-image"><img src="/app/src/assets/images/PDF Icon Pic.png" alt="File"></div>
                                    <div class="file-details">
                                        <div class="file-name">${docName}</div>
                                        <div class="file-status pending">
                                            <div class="status-dot"></div>
                                            <div class="status-text">Pending</div>
                                        </div>
                                    </div>
                                `;
                                
                                newCard.addEventListener('click', function() {
                                    alert(`File: ${newCard.querySelector('.file-name').textContent}\nStatus: Pending\n\nDetailed view will be implemented in Phase 4.`);
                                });

                                if (grid) grid.insertBefore(newCard, grid.firstChild);
                                
                                if (counter) {
                                    counter.textContent = parseInt(counter.textContent) + 1;
                                }

                            } else {
                                window.toast.error(response.message);
                                progressBar.style.backgroundColor = '#f44336';
                                progressText.textContent = 'Failed';
                            }
                        } catch (e) {
                            console.error("Invalid JSON response", xhr.responseText);
                            window.toast.error("Upload failed: Server error.");
                        }
                    } else {
                        window.toast.error('Upload failed with status ' + xhr.status);
                    }
                };

                xhr.onerror = function() {
                    newBtn.disabled = false;
                    newBtn.textContent = 'Upload files';
                    window.toast.error('An error occurred during the upload.');
                };

                xhr.send(formData);

            }
        });
    }

    if (fileUpload) {
        const newFileUpload = fileUpload.cloneNode(true);
        fileUpload.parentNode.replaceChild(newFileUpload, fileUpload);
        
        newFileUpload.addEventListener('change', () => {
            if (newFileUpload.files.length > 0) {
                fileNameDisplay.textContent = newFileUpload.files[0].name;
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    }

    if (uploadContainer) {
        const newContainer = uploadContainer.cloneNode(true);
        uploadContainer.parentNode.replaceChild(newContainer, uploadContainer);
        
        newContainer.addEventListener('dragover', (event) => {
            event.preventDefault();
            newContainer.classList.add('dragover');
        });

        newContainer.addEventListener('dragleave', () => {
            newContainer.classList.remove('dragover');
        });

        newContainer.addEventListener('drop', (event) => {
            event.preventDefault();
            newContainer.classList.remove('dragover');
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('file-upload');
                if(fileInput) fileInput.files = files;
                if(fileNameDisplay) fileNameDisplay.textContent = files[0].name;
            }
        });
    }
    
    const notifIcons = document.querySelectorAll('.notification-icon');
    notifIcons.forEach(icon => {
        if (icon.hasAttribute('data-init')) return;
        icon.setAttribute('data-init', 'true');

        const dropdown = icon.querySelector('.notifications-dropdown');
        if (!dropdown) return;

        icon.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
        });
    });

    if (!window.dashboardGlobalClickAttached) {
        document.addEventListener('click', () => {
            document.querySelectorAll('.notifications-dropdown').forEach(d => d.style.display = 'none');
             const actionMenus = document.querySelectorAll('.action-menu');
             actionMenus.forEach(menu => {
                 const content = menu.querySelector('.action-menu-content');
                 if(content) content.style.display = 'none';
             });
        });
        window.dashboardGlobalClickAttached = true;
    }

    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const res = await fetch('/app/controllers/notifications_mark_read.php', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    document.querySelectorAll('.notif-item').forEach(n => n.classList.remove('unread'));
                    document.querySelectorAll('.notification-icon .notification-dot').forEach(dot => dot.remove());
                }
            } catch (err) {
                console.error('Failed to mark notifications as read');
            }
        });
    });
    
    const fileCards = document.querySelectorAll('.file-card');
    fileCards.forEach(card => {
        if(card.hasAttribute('data-init')) return;
        card.setAttribute('data-init', 'true');
        
        card.addEventListener('click', function() {
            // Placeholder for detailed view
        });
    });

    if (!window.mobileSidebarLogicAttached) {
        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('sidebar');
            let overlay = document.getElementById('mobile-sidebar-overlay');
            
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'mobile-sidebar-overlay';
                document.body.appendChild(overlay);
            }

            const toggleBtn = e.target.closest('#mobile-menu-toggle');
            if (toggleBtn) {
                e.preventDefault();
                e.stopPropagation();
                
                if (sidebar) {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                }
                return;
            }

            if (e.target.closest('#mobile-sidebar-close')) {
                e.preventDefault();
                 if (sidebar) sidebar.classList.remove('active');
                 if (overlay) overlay.classList.remove('active');
                 return;
            }

            if (e.target.closest('.menu-item')) {
                 if (sidebar) sidebar.classList.remove('active');
                 if (overlay) overlay.classList.remove('active');
            }

            if (e.target.closest('#mobile-sidebar-overlay')) {
                if (sidebar) sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                return;
            }
        });
        window.mobileSidebarLogicAttached = true;
    }
    
    if (!document.getElementById('mobile-sidebar-overlay')) {
         const overlay = document.createElement('div');
         overlay.id = 'mobile-sidebar-overlay';
         document.body.appendChild(overlay);
    }

    if (!window.searchCloseAttached) {
        document.addEventListener('click', (e) => {
            const searchContainer = document.getElementById('search-container');
            const searchIconBtn = document.getElementById('search-icon-btn');
            
            if (searchContainer && searchContainer.classList.contains('active')) {
                if (!searchContainer.contains(e.target) && (!searchIconBtn || !searchIconBtn.contains(e.target))) {
                    searchContainer.classList.remove('active');
                }
            }
        });
        window.searchCloseAttached = true;
    }

    const searchContainer = document.getElementById('search-container');
    const searchIconBtn = document.getElementById('search-icon-btn');
    const searchInput = document.querySelector('.search-input');
    
    if (searchIconBtn && searchContainer && searchInput) {
        if (!searchIconBtn.hasAttribute('data-init')) {
            searchIconBtn.setAttribute('data-init', 'true');
            searchIconBtn.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation();
                    searchContainer.classList.toggle('active');
                    if (searchContainer.classList.contains('active')) {
                        setTimeout(() => searchInput.focus(), 100);
                    }
                }
            });
        }
    }

    if (searchInput) {
        if (!searchInput.hasAttribute('data-init')) {
             searchInput.setAttribute('data-init', 'true');
             searchInput.addEventListener('input', function(e) {
                const query = this.value.toLowerCase().trim();
                const fileCards = document.querySelectorAll('.file-card');
                const filesGrid = document.querySelector('.files-grid');
                let hasVisible = false;

                if (filesGrid) {
                    fileCards.forEach(card => {
                        const name = card.querySelector('.file-name').textContent.toLowerCase();
                        if (name.includes(query)) {
                            card.style.display = '';
                            hasVisible = true;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    let noFilesMsg = document.querySelector('.no-files-search');
                    if (!hasVisible) {
                        if (!noFilesMsg) {
                            noFilesMsg = document.createElement('div');
                            noFilesMsg.className = 'no-files no-files-search';
                            noFilesMsg.textContent = 'No matching files found.';
                            filesGrid.appendChild(noFilesMsg);
                        }
                    } else {
                        if (noFilesMsg) noFilesMsg.remove();
                    }
                }
            });
        }

        const searchForm = document.querySelector('.search-form');
        if (searchForm && !searchForm.hasAttribute('data-init')) {
            searchForm.setAttribute('data-init', 'true');
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
            });
        }
    }

    const mobileBreakpoint = 768;
    const dashboardContent = document.querySelector('.dashboard-content');
    const rightSidebar     = document.querySelector('.right-sidebar');
    
    if (rightSidebar && dashboardContent) {
        const reorderForMobile = () => {
             const docsCounter      = document.querySelector('.documents-counter');
             const uploadSection    = document.querySelector('.upload-section');
             const todoFiles        = document.querySelector('.todo-files');
             const filesSection     = document.querySelector('.files-section');
             
             if (!docsCounter || !uploadSection || !todoFiles || !filesSection) return;

             const w = window.innerWidth;
             if (w <= mobileBreakpoint) {
                 if (rightSidebar.contains(docsCounter)) {
                     dashboardContent.insertBefore(docsCounter, dashboardContent.querySelector('.user-info-card').nextSibling);
                 }
                 if (rightSidebar.contains(uploadSection)) {
                    dashboardContent.insertBefore(uploadSection, docsCounter.nextSibling);
                 }
                 if (rightSidebar.contains(todoFiles)) {
                    dashboardContent.insertBefore(todoFiles, filesSection.nextSibling);
                 }
             } else {
                 if (!rightSidebar.contains(docsCounter)) rightSidebar.prepend(docsCounter);
                 if (!rightSidebar.contains(uploadSection)) rightSidebar.insertBefore(uploadSection, docsCounter.nextSibling);
                 if (!rightSidebar.contains(todoFiles)) rightSidebar.appendChild(todoFiles);
             }
        };

        reorderForMobile();
        
        if (!window.dashboardResizeAttached) {
            window.addEventListener('resize', reorderForMobile);
            window.dashboardResizeAttached = true;
        }
    }

    const userProfiles = document.querySelectorAll('.user-profile');
    userProfiles.forEach(profile => {
        if (profile.hasAttribute('data-init')) return;
        profile.setAttribute('data-init', 'true');
        
        profile.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('.notifications-dropdown').forEach(d => d.style.display = 'none');
             
            const dropdown = this.querySelector('.profile-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        });
    });

    if (!window.profileCloseAttached) {
        document.addEventListener('click', () => {
             document.querySelectorAll('.profile-dropdown').forEach(d => d.classList.remove('active'));
        });
        window.profileCloseAttached = true;
    }
};

window.initDashboard = initDashboard;
document.addEventListener('DOMContentLoaded', initDashboard);