// Consolidated DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    // Action dropdown logic is handled by event delegation at the bottom of this file

    // close dropdown on outside click
    document.addEventListener('click', () => {
        // This is also handled by the delegated listener at the bottom
    });
    
    const barChartCanvas = document.getElementById('uploads-by-month');
    const pieChartCanvas = document.getElementById('uploads-by-status');

    // Charts
    if (barChartCanvas) {
        new Chart(barChartCanvas, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true
                        }
                    }
                }
            }
        });
    }

    if (pieChartCanvas) {
        new Chart(pieChartCanvas, {
            type: 'pie',
            data: pieChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', function () {
    // --- Mobile Sidebar Toggle ---
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');
    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    const searchForm = document.querySelector('.search-box');
    const searchInput = document.getElementById('search-input');
    const filterForm = document.getElementById('filter-sort-form');
    const formSearchInput = document.getElementById('form-search');
    // Handle search input submission
    // --- Debounced AJAX Search for Admin ---
    if (searchForm && searchInput) {
        
        const debounce = (func, wait) => {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        };

        const performSearch = (paramsOverride = {}) => {
             const searchTerm = searchInput.value.trim();
             
             if (window.location.pathname.includes('admin.php') || window.location.pathname.includes('verifications.php') || window.location.pathname.includes('students.php')) {
                 const currentUrl = new URL(window.location.href);

                 if (window.location.pathname.includes('admin.php')) {
                     return; 
                 }

                 // Update Search Param
                 currentUrl.searchParams.set('search', searchTerm);
                 
                 // Apply Overrides (filter inputs)
                 Object.keys(paramsOverride).forEach(key => {
                     currentUrl.searchParams.set(key, paramsOverride[key]);
                 });

                 currentUrl.searchParams.set('ajax', '1');
                 
                 const studentsGrid = document.querySelector('.students-grid') || document.querySelector('.no-students');
                 const tbody = document.querySelector('.docs-table tbody');
                 
                 const targetContainer = studentsGrid ? (document.querySelector('.students-grid') ? document.querySelector('.students-grid').parentNode : document.querySelector('.no-students').parentNode) : tbody; // parent needed for replacement if grid missing? Actually innerHTML is safer.

                 // Show loading
                 if (tbody) tbody.style.opacity = '0.5';
                 if (studentsGrid) studentsGrid.style.opacity = '0.5';

                 fetch(currentUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (tbody) {
                            tbody.innerHTML = data.html;
                            tbody.style.opacity = '1';
                        }
                        if (studentsGrid) {
                             // Robust replacement for students grid
                             const container = document.querySelector('.students-grid');
                             const noStudents = document.querySelector('.no-students');
                             
                             if (container) {
                                 container.outerHTML = '<div class="students-grid">' + data.html + '</div>'; // data.html contains cards or no-students? 
                                 // Wait, my controller returns cards directly OR a div.no-students. 
                                 // If data.html starts with <div class="no-students">, I should replace container.
                                 // The controller returns:
                                 // - <div class="no-students">...</div> if empty.
                                 // - <a ...>...</a><a ...>...</a> strings if not empty.
                                 
                                 // So if data.html contains 'student-card', it's a grid.
                                 // If it contains 'no-students', it's a div.
                                 
                                 // Better approach:
                                 // If current is grid, replace innerHTML if data is cards.
                                 // But data might be no-students div.
                                 
                                 // Let's rely on parent container selector in views/students.php: .students-container
                                 const wrapper = document.querySelector('.students-container');
                                 // Remove existing grid or no-students
                                 const existingGrid = wrapper.querySelector('.students-grid');
                                 const existingNo = wrapper.querySelector('.no-students');
                                 if(existingGrid) existingGrid.remove();
                                 if(existingNo) existingNo.remove();
                                 
                                 // Create new element based on data content hint or just insert HTML
                                 // Data.html is string of elements.
                                 if (data.html.includes('student-card')) {
                                     const newGrid = document.createElement('div');
                                     newGrid.className = 'students-grid';
                                     newGrid.innerHTML = data.html;
                                     wrapper.appendChild(newGrid);
                                 } else {
                                     // Assuming data.html is the <div class="no-students">...
                                     wrapper.insertAdjacentHTML('beforeend', data.html);
                                 }
                             } else if (noStudents) {
                                 // Same logic if starting from empty state
                                 const wrapper = document.querySelector('.students-container');
                                  if(noStudents) noStudents.remove();
                                  
                                  if (data.html.includes('student-card')) {
                                     const newGrid = document.createElement('div');
                                     newGrid.className = 'students-grid';
                                     newGrid.innerHTML = data.html;
                                     wrapper.appendChild(newGrid);
                                 } else {
                                     wrapper.insertAdjacentHTML('beforeend', data.html);
                                 }
                             }
                        }
                        
                        // Update Search Info (X results found)
                        // For Verifications: .uploads-tabs .search-results-info
                        // For Students: .students-header .search-results-info ?
                        
                        if (window.location.pathname.includes('verifications.php')) {
                            const uploadsTabs = document.querySelector('.uploads-tabs');
                            if (uploadsTabs) {
                                let infoContainer = uploadsTabs.querySelector('.search-results-info');
                                if (data.info_html) {
                                    if (infoContainer) {
                                        infoContainer.outerHTML = data.info_html;
                                    } else {
                                        uploadsTabs.insertAdjacentHTML('beforeend', data.info_html);
                                    }
                                } else {
                                    if (infoContainer) infoContainer.remove();
                                }
                            }
                        } else if (window.location.pathname.includes('students.php')) {
                             const header = document.querySelector('.students-header');
                             if (header) {
                                 let infoContainer = header.querySelector('.search-results-info');
                                 // Wait, where to put it? Maybe after the H2?
                                 // Use same logic, find existing or append.
                                 // Better: create a consistent container or just append to header. 
                                 // My controller generates .search-results-info.
                                 if (data.info_html) {
                                     if (infoContainer) {
                                         infoContainer.outerHTML = data.info_html;
                                     } else {
                                         header.insertAdjacentHTML('beforeend', data.info_html);
                                     }
                                 } else {
                                     if (infoContainer) infoContainer.remove();
                                 }
                             }
                        }

                        // Update URL without reload
                        const newUrl = new URL(window.location.href);
                        newUrl.searchParams.set('search', searchTerm);
                        Object.keys(paramsOverride).forEach(key => {
                            newUrl.searchParams.set(key, paramsOverride[key]);
                        });
                        window.history.pushState({}, '', newUrl);
                    })
                    .catch(err => {
                        console.error('Search failed', err);
                        if(tbody) tbody.style.opacity = '1';
                        if(studentsGrid) studentsGrid.style.opacity = '1';
                    });
             }
        };

        const debouncedSearch = debounce(performSearch, 300);

        searchInput.addEventListener('input', debouncedSearch);

        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const searchTerm = searchInput.value.trim();
            
            // Redirect logic
            if (window.location.pathname.includes('verifications.php') || window.location.pathname.includes('students.php')) {
                performSearch(); 
            } else {
                 if (searchTerm) {
                     // Since we removed search from everywhere else, this executes on student/verification page or if user hacked header back.
                     // But let's keep redirection safety
                     // Default to verifications? Or based on context?
                     // If on dashboard, maybe verifications.
                     window.location.href = `/app/controllers/verifications.php?search=${encodeURIComponent(searchTerm)}`;
                 }
            }
        });

        // --- AJAX for Tabs (Verifications) ---
        const tabItems = document.querySelectorAll('.tab-item');
        tabItems.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                tabItems.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const url = new URL(this.href);
                const tabValue = url.searchParams.get('tab');
                performSearch({ tab: tabValue });
            });
        });
        
        // --- AJAX for Student Filters ---
        const filterForm = document.getElementById('filter-sort-form');
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Stop reload
                // Gather data
                const formData = new FormData(filterForm);
                const params = {};
                formData.forEach((value, key) => params[key] = value);
                // Trigger search with params
                performSearch(params);
            });
            
            // Also trigger on change for selects for smoother UX?
            const selects = filterForm.querySelectorAll('select');
            selects.forEach(select => {
                select.addEventListener('change', () => {
                   const formData = new FormData(filterForm);
                   const params = {};
                   formData.forEach((value, key) => params[key] = value);
                   performSearch(params);
                });
            });
        }
    };
    // --- Datepicker Initialization ---
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    if (startDateInput && endDateInput) {
        const endDatePicker = flatpickr(endDateInput, {
            dateFormat: "Y-m-d",
        });
        const startDatePicker = flatpickr(startDateInput, {
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                endDatePicker.set('minDate', dateStr);
            }
        });
    }
    // --- Action Menu Dropdown Logic ---
    document.body.addEventListener('click', function (e) {
        // Find all action menu contents that are currently active
        const openMenus = document.querySelectorAll('.action-menu-content.active');
        // Check if the click is on an action button
        const actionButton = e.target.closest('.action-btn');
        // If the click is on an action button
        if (actionButton) {
            e.preventDefault(); // Prevent any default link behavior
            const menuContent = actionButton.nextElementSibling;
            
            // If the clicked menu is already open, close it. Otherwise, close all other menus and open this one.
            if (menuContent.classList.contains('active')) {
                menuContent.classList.remove('active');
            } else {
                openMenus.forEach(menu => menu.classList.remove('active'));
                menuContent.classList.add('active');
            }
        } else {
            // If the click is outside an action button and its menu, close all open menus.
            if (!e.target.closest('.action-menu-content')) {
                openMenus.forEach(menu => menu.classList.remove('active'));
            }
        }
    });
});