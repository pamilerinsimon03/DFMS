// Dashboard functionality
const initDashboard = () => {
    // Only run if we are on the dashboard page or similar
    // We can check for a key element to decide if we should run logic
    // But since this script is included, we might want to run most of it.
    
    // --- File Upload Functionality ---
    const uploadForm = document.getElementById('upload-form');
    const fileUpload = document.getElementById('file-upload');
    const fileNameDisplay = document.getElementById('file-name-display');
    const uploadButton = document.getElementById('upload-submit-btn');
    const uploadContainer = document.getElementById('upload-container');
    const progressContainer = document.getElementById('upload-progress-container');
    const progressBar = document.getElementById('upload-progress-bar');
    const progressText = document.getElementById('upload-progress-text');

    // Trigger form submission when the upload button is clicked
    if (uploadButton) {
        // Remove existing listeners to avoid duplicates on re-init
        const newBtn = uploadButton.cloneNode(true);
        uploadButton.parentNode.replaceChild(newBtn, uploadButton);
        
        newBtn.addEventListener('click', () => {
             if (uploadForm.checkValidity()) {
                const formData = new FormData(uploadForm);
                const xhr = new XMLHttpRequest();

                // Show progress UI directly inside the upload box
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressText.textContent = '0%';
                newBtn.disabled = true;
                newBtn.textContent = 'Uploading...';

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
                    newBtn.disabled = false;
                    newBtn.textContent = 'Upload files';
                    
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
                    newBtn.disabled = false;
                    newBtn.textContent = 'Upload files';
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

    // Drag and drop functionality
    if (uploadContainer) {
        // Clone to remove old listeners
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
                // We need to update the file input, but file inputs are read-only for security on some browsers
                // but setting files property works in modern browsers
                const fileInput = document.getElementById('file-upload');
                if(fileInput) fileInput.files = files;
                if(fileNameDisplay) fileNameDisplay.textContent = files[0].name;
            }
        });
    }
    
    // Notification icon functionality
    const notifIcons = document.querySelectorAll('.notification-icon');
    notifIcons.forEach(icon => {
        // We can't easily clone these without losing inner structure references or bindings 
        // if they were complex, but here it's simple.
        // Better to just ensure we don't double bind. 
        // Simplest way for re-init safe code is to check a data attribute.
        if (icon.hasAttribute('data-init')) return;
        icon.setAttribute('data-init', 'true');

        const dropdown = icon.querySelector('.notifications-dropdown');
        if (!dropdown) return;

        icon.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
        });
    });

    // Global click to close notifications - check if listener already exists? 
    // It's hard to check global listeners. 
    // For SPA, we might accumulate these. 
    // Ideally we'd remove them on cleanup, but we don't have cleanup yet.
    // For now, let's name the function so we can remove it?
    // Or just check if we attached it.
    if (!window.dashboardGlobalClickAttached) {
        document.addEventListener('click', () => {
            document.querySelectorAll('.notifications-dropdown').forEach(d => d.style.display = 'none');
             // Also close action menus
             const actionMenus = document.querySelectorAll('.action-menu'); // Re-query as they might be new
             actionMenus.forEach(menu => {
                 const content = menu.querySelector('.action-menu-content');
                 if(content) content.style.display = 'none';
             });
        });
        window.dashboardGlobalClickAttached = true;
    }

    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        // Cloning to remove listeners
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
    
    // File card click functionality
    const fileCards = document.querySelectorAll('.file-card');
    fileCards.forEach(card => {
        if(card.hasAttribute('data-init')) return;
        card.setAttribute('data-init', 'true');
        
        card.addEventListener('click', function() {
            const fileName = this.querySelector('.file-name').textContent;
            const status = this.querySelector('.file-status').classList.contains('verified') ? 'Verified' : 'Pending';
            
            // Temporary alert
            // alert(`File: ${fileName}\nStatus: ${status}\n\nDetailed view will be implemented in Phase 4.`);
        });
    });

    // --- Mobile Sidebar Helper Logic (Event Delegation) ---
    // Ensure we only attach the global listener ONCE
    if (!window.mobileSidebarLogicAttached) {
        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('sidebar');
            let overlay = document.getElementById('mobile-sidebar-overlay');
            
            // Auto-create overlay if missing (lazy init)
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'mobile-sidebar-overlay';
                document.body.appendChild(overlay);
            }

            // 1. Toggle Button Click
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

            // 3. Close Button Click
            if (e.target.closest('#mobile-sidebar-close')) {
                e.preventDefault();
                 if (sidebar) sidebar.classList.remove('active');
                 if (overlay) overlay.classList.remove('active');
                 return;
            }

            // 4. Menu Item Click (Navigation)
            if (e.target.closest('.menu-item')) {
                // Allow default navigation (no preventDefault), just close sidebar
                 if (sidebar) sidebar.classList.remove('active');
                 if (overlay) overlay.classList.remove('active');
                 // Do not return, let the event propagate to links
            }

            // 2. Overlay Click
            if (e.target.closest('#mobile-sidebar-overlay')) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                return;
            }
        });
        window.mobileSidebarLogicAttached = true;
    }
    
    // Ensure overlay exists on init just in case
    if (!document.getElementById('mobile-sidebar-overlay')) {
         const overlay = document.createElement('div');
         overlay.id = 'mobile-sidebar-overlay';
         document.body.appendChild(overlay);
    }

    // click outside search
    if (!window.searchCloseAttached) {
        document.addEventListener('click', (e) => {
            const searchContainer = document.getElementById('search-container');
            const searchIconBtn = document.getElementById('search-icon-btn');
            
            // If click is outside search container AND outside the toggle button
            if (searchContainer && searchContainer.classList.contains('active')) {
                if (!searchContainer.contains(e.target) && (!searchIconBtn || !searchIconBtn.contains(e.target))) {
                    searchContainer.classList.remove('active');
                }
            }
        });
        window.searchCloseAttached = true;
    }

    // --- Search Functionality (Client-Side for Dashboard) ---
    const searchContainer = document.getElementById('search-container');
    const searchIconBtn = document.getElementById('search-icon-btn');
    const searchInput = document.querySelector('.search-input');
    
    if (searchIconBtn && searchContainer && searchInput) {
        // Search container is in header (global). Check init.
        if (!searchIconBtn.hasAttribute('data-init')) {
            searchIconBtn.setAttribute('data-init', 'true');
            searchIconBtn.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    e.stopPropagation(); // Stop bubbling
                    searchContainer.classList.toggle('active');
                    if (searchContainer.classList.contains('active')) {
                        setTimeout(() => searchInput.focus(), 100); // Delay focus
                    }
                }
            });
        }
    }

    if (searchInput) {
        // Search input is in header (global).
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
        }

        const searchForm = document.querySelector('.search-form');
        if (searchForm && !searchForm.hasAttribute('data-init')) {
            searchForm.setAttribute('data-init', 'true');
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
            });
        }
    }

    // Mobile Reorder Logic
    // This logic manipulates DOM elements that ARE re-rendered (dashboard content)
    // So we should run it every time initDashboard is called.
    const mobileBreakpoint = 768;
    const dashboardContent = document.querySelector('.dashboard-content');
    const rightSidebar     = document.querySelector('.right-sidebar');
    
    if (rightSidebar && dashboardContent) {
        // Definition of reorder function
        const reorderForMobile = () => {
             const docsCounter      = document.querySelector('.documents-counter');
             const uploadSection    = document.querySelector('.upload-section');
             const todoFiles        = document.querySelector('.todo-files');
             const filesSection     = document.querySelector('.files-section');
             
             if (!docsCounter || !uploadSection || !todoFiles || !filesSection) return;

             // Logic to move elements...
             // Since this is complex and depends on current DOM state which wipes on navigation,
             // We can just run it.
             const w = window.innerWidth;
             if (w <= mobileBreakpoint) {
                 // Move to main content
                 if (rightSidebar.contains(docsCounter)) {
                     dashboardContent.insertBefore(docsCounter, dashboardContent.querySelector('.user-info-card').nextSibling);
                 }
                 if (rightSidebar.contains(uploadSection)) {
                    // insert after docsCounter
                    dashboardContent.insertBefore(uploadSection, docsCounter.nextSibling);
                 }
                 if (rightSidebar.contains(todoFiles)) {
                    dashboardContent.insertBefore(todoFiles, filesSection.nextSibling);
                 }
             } else {
                 // Return to sidebar
                 // This requires placeholders or knowing order. 
                 // For now, simpler check:
                 if (!rightSidebar.contains(docsCounter)) rightSidebar.prepend(docsCounter);
                 if (!rightSidebar.contains(uploadSection)) rightSidebar.insertBefore(uploadSection, docsCounter.nextSibling);
                 if (!rightSidebar.contains(todoFiles)) rightSidebar.appendChild(todoFiles);
             }
        };

        // Run immediately
        reorderForMobile();
        
        // Add resize listener only once
        if (!window.dashboardResizeAttached) {
            window.addEventListener('resize', reorderForMobile);
            window.dashboardResizeAttached = true;
        }
    }

    // --- Profile Dropdown Logic (Global) ---
    const userProfiles = document.querySelectorAll('.user-profile');
    userProfiles.forEach(profile => {
        if (profile.hasAttribute('data-init')) return;
        profile.setAttribute('data-init', 'true');
        
        profile.addEventListener('click', function(e) {
            e.stopPropagation();
            // Close other dropdowns
            document.querySelectorAll('.notifications-dropdown').forEach(d => d.style.display = 'none');
             
            // Toggle this one
            const dropdown = this.querySelector('.profile-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        });
    });

    // Close profile dropdown on global click
    if (!window.profileCloseAttached) {
        document.addEventListener('click', () => {
             document.querySelectorAll('.profile-dropdown').forEach(d => d.classList.remove('active'));
        });
        window.profileCloseAttached = true;
    }
};

// Expose globally
window.initDashboard = initDashboard;

// Auto-run on load
document.addEventListener('DOMContentLoaded', initDashboard);