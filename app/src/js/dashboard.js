// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Menu item click functionality
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function () {
            // Skip if it's already active or it's the logout item
            if (this.classList.contains('active') || this.classList.contains('logout')) return;
            
            // Remove active class from current active item
            document.querySelector('.menu-item.active').classList.remove('active');
            document.querySelector('.menu-text.active-text').classList.remove('active-text');
            document.querySelector('.menu-bg')?.remove();
            
            // Add active class to clicked item
            this.classList.add('active');
            this.querySelector('.menu-text').classList.add('active-text');
            
            // Create background element
            const bg = document.createElement('div');
            bg.classList.add('menu-bg');
            this.prepend(bg);
            
            // Handle page navigation
            const menuText = this.querySelector('.menu-text').textContent.trim();
            console.log(`Navigating to: ${menuText}`);
            
            // Navigate to the appropriate page
            if (menuText === 'Dashboard') {
                window.location.href = 'dashboard.php';
            } else if (menuText === 'My Uploads') {
                window.location.href = 'uploads.php';
            } //else {
            // Other pages not yet implemented
            //alert(`The ${menuText} page will be implemented in a future phase.`);
        });
    });

    // File Upload Functionality
    const uploadForm = document.getElementById('upload-form');
    const fileUpload = document.getElementById('file-upload');
    const fileNameDisplay = document.getElementById('file-name-display');
    const uploadButton = document.getElementById('upload-submit-btn'); // ID changed in HTML
    const uploadContainer = document.getElementById('upload-container');
    const progressContainer = document.getElementById('upload-progress-container');
    const progressBar = document.getElementById('upload-progress-bar');
    const progressText = document.getElementById('upload-progress-text');

    // Trigger form submission when the upload button is clicked
    if (uploadButton) {
        uploadButton.addEventListener('click', () => {
             if (uploadForm.checkValidity()) {
                const formData = new FormData(uploadForm);
                const xhr = new XMLHttpRequest();

                // Show progress UI directly inside the upload box
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressText.textContent = '0%';
                uploadButton.disabled = true;
                uploadButton.textContent = 'Uploading...';

                xhr.open('POST', '/app/controllers/upload_file.php', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                // Progress event
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percentComplete + '%';
                        if (percentComplete === 100) {
                             progressText.textContent = 'Processing & Verifying Quality...';
                             progressBar.style.backgroundColor = '#2196F3'; // Blue for processing
                        } else {
                            progressText.textContent = percentComplete + '%';
                        }
                    }
                };

                // Completion event
                xhr.onload = function() {
                    uploadButton.disabled = false;
                    uploadButton.textContent = 'Upload files';
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                window.toast.success(response.message);
                                
                                // UI Update (No Reload)
                                uploadForm.reset();
                                fileNameDisplay.textContent = '';
                                progressContainer.style.display = 'none';

                                // Find grid and counter
                                const grid = document.querySelector('.files-grid');
                                const noFiles = document.querySelector('.no-files');
                                const counter = document.querySelector('.counter-number');
                                
                                if (noFiles) noFiles.remove();
                                
                                // Create new card
                                const newCard = document.createElement('div');
                                newCard.className = 'file-card';
                                
                                // Get document name from response
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
                                
                                // Add click listener to new card
                                newCard.addEventListener('click', function() {
                                    alert(`File: ${newCard.querySelector('.file-name').textContent}\nStatus: Pending\n\nDetailed view will be implemented in Phase 4.`);
                                });

                                // Prepend
                                grid.insertBefore(newCard, grid.firstChild);
                                
                                // Update counter
                                if (counter) {
                                    counter.textContent = parseInt(counter.textContent) + 1;
                                }

                            } else {
                                window.toast.error(response.message);
                                progressBar.style.backgroundColor = '#f44336'; // Red
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
                    uploadButton.disabled = false;
                    uploadButton.textContent = 'Upload files';
                    window.toast.error('An error occurred during the upload.');
                };

                xhr.send(formData);

            } else {
                // Validate manually to show browser tooltips or custom alert
                if (!uploadForm.document_name.value) {
                     window.toast.warning('Please select a document type.');
                } else if (!uploadForm.file.value) {
                     window.toast.warning('Please select a file.');
                }
            }
        });
    }

    // Update the file name when a file is selected
    if (fileUpload) {
        fileUpload.addEventListener('change', () => {
            if (fileUpload.files.length > 0) {
                fileNameDisplay.textContent = fileUpload.files[0].name;
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    }

    // Drag and drop functionality
    if (uploadContainer) {
        uploadContainer.addEventListener('dragover', (event) => {
            event.preventDefault();
            uploadContainer.classList.add('dragover');
        });

        uploadContainer.addEventListener('dragleave', () => {
            uploadContainer.classList.remove('dragover');
        });

        uploadContainer.addEventListener('drop', (event) => {
            event.preventDefault();
            uploadContainer.classList.remove('dragover');
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                fileUpload.files = files;
                fileNameDisplay.textContent = files[0].name;
            }
        });
    }
    
    // Notification icon functionality
    // Notifications dropdown
    // Support multiple pages and markup variations
    const notifIcons = document.querySelectorAll('.notification-icon');
    notifIcons.forEach(icon => {
        const dropdown = icon.querySelector('.notifications-dropdown');
        if (!dropdown) return;

        icon.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.notifications-dropdown').forEach(d => d.style.display = 'none');
    });

    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
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
    
    // File card click functionality
    const fileCards = document.querySelectorAll('.file-card');
    
    fileCards.forEach(card => {
        card.addEventListener('click', function() {
            const fileName = this.querySelector('.file-name').textContent;
            const status = this.querySelector('.file-status').classList.contains('verified') ? 'Verified' : 'Pending';
            
            alert(`File: ${fileName}
Status: ${status}

Detailed view will be implemented in Phase 4.`);
        });
    });
    const burger = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    }

    burger.addEventListener('click', function(e) {
        e.stopPropagation();
        if (sidebar.classList.contains('open')) {
        closeSidebar();
        } else {
        openSidebar();
        }
    });

    // If you click the overlay itself, close the sidebar
    overlay.addEventListener('click', function() {
        closeSidebar();
    });

    // Grab references to everything we need:
    const mobileBreakpoint = 768;
    const dashboardContent = document.querySelector('.dashboard-content');
    const filesSection     = document.querySelector('.files-section');
    const rightSidebar     = document.querySelector('.right-sidebar');
    const docsCounter      = document.querySelector('.documents-counter');
    const uploadSection    = document.querySelector('.upload-section');
    const todoFiles        = document.querySelector('.todo-files');

    // We will keep placeholders so that we can restore the HTML exactly on desktop:
    const docsPlaceholder   = document.createElement('div');
    const uploadPlaceholder = document.createElement('div');
    const todoPlaceholder   = document.createElement('div');

    // Give them non‐intrusive class names so they don't break your CSS:
    docsPlaceholder.className   = 'placeholder-docs';
    uploadPlaceholder.className = 'placeholder-upload';
    todoPlaceholder.className   = 'placeholder-todo';

    // Check if we are on the dashboard page and have the sidebar elements
    if (rightSidebar && dashboardContent) {
        // On initial load, insert placeholders where the counter/upload/todo originally live:
        if (rightSidebar.contains(docsCounter)) {
            docsPlaceholder.replaceWith(docsCounter);
            rightSidebar.insertBefore(docsCounter, rightSidebar.firstChild);
        }
        if (rightSidebar.contains(uploadSection)) {
            uploadPlaceholder.replaceWith(uploadSection);
            rightSidebar.insertBefore(uploadSection, docsCounter.nextSibling);
        }
        if (rightSidebar.contains(todoFiles)) {
            todoPlaceholder.replaceWith(todoFiles);
            rightSidebar.appendChild(todoFiles);
        }
    }

    function reorderForMobile() {
        // Safety check: ensure required elements exist
        if (!rightSidebar || !dashboardContent) return;
        
        const w = window.innerWidth;
        if (w <= mobileBreakpoint) {
            // ... (rest of logic unchanged, just ensured checks pass)
            
            // 1) If docsCounter is still inside rightSidebar, move it:
            if (rightSidebar.contains(docsCounter)) {
            docsPlaceholder.replaceWith(docsCounter);      // restore docsCounter here
            dashboardContent.insertBefore(
                docsCounter,
                dashboardContent.querySelector('.user-info-card').nextSibling
            );
            }

            // 2) Similarly for uploadSection:
            if (rightSidebar.contains(uploadSection)) {
            uploadPlaceholder.replaceWith(uploadSection);
            const afterUserCard = dashboardContent.querySelector('.user-info-card').nextSibling;
            // If docsCounter was just inserted, afterUserCard is docsCounter. So insert uploadSection after docsCounter:
            dashboardContent.insertBefore(uploadSection, afterUserCard.nextSibling);
            }

            // 3) Now move todoFiles out of rightSidebar and place it right after .files-section:
            if (rightSidebar.contains(todoFiles)) {
            todoPlaceholder.replaceWith(todoFiles);
            dashboardContent.insertBefore(
                todoFiles,
                filesSection.nextSibling
            );
            }
        } else {
            // DESKTOP logic
             // 1) docsCounter → back into rightSidebar, in front:
            if (! rightSidebar.contains(docsCounter)) {
            dashboardContent.replaceChild(docsPlaceholder, docsCounter);
            rightSidebar.insertBefore(docsCounter, rightSidebar.firstChild);
            }

            // 2) uploadSection → second child of rightSidebar:
            if (! rightSidebar.contains(uploadSection)) {
            dashboardContent.replaceChild(uploadPlaceholder, uploadSection);
            rightSidebar.insertBefore(uploadSection, docsCounter.nextSibling);
            }

            // 3) todoFiles → last child of rightSidebar:
            if (! rightSidebar.contains(todoFiles)) {
            dashboardContent.replaceChild(todoPlaceholder, todoFiles);
            rightSidebar.appendChild(todoFiles);
            }
        }
    }

    const fileUploadInput = document.getElementById('file-upload');

    fileUploadInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            fileNameDisplay.textContent = this.files[0].name;
        } else {
            fileNameDisplay.textContent = '';
        }
    });


    // Run on load, then again on every resize:
    reorderForMobile();
    window.addEventListener('resize', reorderForMobile);

    const searchContainer = document.getElementById('search-container');
    const searchIconBtn = document.getElementById('search-icon-btn');
    const searchInput = document.querySelector('.search-input');

    searchIconBtn.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            e.preventDefault();
            searchContainer.classList.toggle('active');
            if (searchContainer.classList.contains('active')) {
                searchInput.focus();
            }
        }
    });

    // --- Search Functionality (Client-Side for Dashboard) ---
    if (searchInput) {
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

                // Handle "No files" message
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

        // Prevent form submission
        const searchForm = document.querySelector('.search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
            });
        }
    }

});

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