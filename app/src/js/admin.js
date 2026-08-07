const initAdmin = () => {
    // Action dropdown logic is handled by event delegation at the bottom of this file

    // close dropdown on outside click
    document.addEventListener('click', () => {
        // This is also handled by the delegated listener at the bottom
    });
    
    const barChartCanvas = document.getElementById('uploads-by-month');
    const pieChartCanvas = document.getElementById('uploads-by-status');

    if (barChartCanvas && typeof Chart !== 'undefined') {
        new Chart(barChartCanvas, {
            type: 'bar',
            data: typeof barChartData !== 'undefined' ? barChartData : {},
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

    if (pieChartCanvas && typeof Chart !== 'undefined') {
        new Chart(pieChartCanvas, {
            type: 'pie',
            data: typeof pieChartData !== 'undefined' ? pieChartData : {},
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

    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');
    if (menuToggle && sidebar && overlay) {
        // Remove old listeners if any (cloneNode trick or just rely on global delegation elsewhere)
        // Here we just attach. If re-run, might double attach?
        // Better: check if attached.
        if (!menuToggle.hasAttribute('data-init')) {
            menuToggle.setAttribute('data-init', 'true');
            menuToggle.addEventListener('click', function () {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }
    }

    const searchForm = document.querySelector('.search-box');
    const searchInput = document.getElementById('search-input');
    const filterForm = document.getElementById('filter-sort-form');
    const formSearchInput = document.getElementById('form-search');

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
             if (paramsOverride instanceof Event) {
                 paramsOverride = {};
             }

             const searchTerm = searchInput.value.trim();
             
             // Hide Add Student button when searching
             const addStudentBtn = document.querySelector('.add-student-btn');
             if (addStudentBtn) {
                 addStudentBtn.style.display = searchTerm.length > 0 ? 'none' : '';
             }
             
             if (window.location.pathname.includes('admin.php') || window.location.pathname.includes('verifications.php') || window.location.pathname.includes('students.php')) {
                 const currentUrl = new URL(window.location.href);

                 if (window.location.pathname.includes('admin.php')) {
                     return; 
                 }

                 // Update Search Param
                 currentUrl.searchParams.set('search', searchTerm);
                 
                 // Apply Overrides (filter inputs)
                 if (paramsOverride && typeof paramsOverride === 'object') {
                     Object.keys(paramsOverride).forEach(key => {
                         currentUrl.searchParams.set(key, paramsOverride[key]);
                     });
                 }

                 currentUrl.searchParams.set('ajax', '1');
                 
                 const studentsGrid = document.querySelector('.students-grid') || document.querySelector('.no-students');
                 const tbody = document.querySelector('.docs-table tbody');
                 
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
                             const wrapper = document.querySelector('.students-container');

                             if (container) {
                                  if (data.html.includes('student-card')) {
                                      container.innerHTML = data.html; // Just replace content of grid
                                      container.style.opacity = '1';
                                  } else {
                                      // Replace grid with no-students
                                      container.outerHTML = data.html;
                                  }
                             } else if (noStudents) {
                                  if (data.html.includes('student-card')) {
                                     // Replace no-students with grid
                                     noStudents.outerHTML = '<div class="students-grid">' + data.html + '</div>';
                                  } else {
                                     noStudents.outerHTML = data.html;
                                  }
                             } else if (wrapper) {
                                 // Fallback
                                 wrapper.innerHTML = data.html;
                             }
                        }
                        
                        // Update Search Info (X results found)
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

        // Remove existing listener if any (by check or clone)
        // Simplest: just add. Debounce handles rapid firing.
        searchInput.addEventListener('input', debouncedSearch);

        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const searchTerm = searchInput.value.trim();
            
            // Redirect logic
            if (window.location.pathname.includes('verifications.php') || window.location.pathname.includes('students.php')) {
                performSearch(); 
            } else {
                 if (searchTerm) {
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
        if (filterForm) {
            // Remove previous submit listeners?
            // Since we re-init on SPA nav, we should avoid duplicate listeners.
            // Using a replaced node or checking a flag.
            if (!filterForm.hasAttribute('data-ajax-init')) {
                filterForm.setAttribute('data-ajax-init', 'true');
                
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
        }
    };
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    if (startDateInput && endDateInput && typeof flatpickr !== 'undefined') {
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
    
    if (!window.adminActionLogicAtttached) {
        document.body.addEventListener('click', function (e) {
            // Find all action menu contents that are currently active
            const openMenus = document.querySelectorAll('.action-menu-content.active');
            // Check if the click is on an action button
            const actionButton = e.target.closest('.action-btn');
            // If the click is on an action button
            if (actionButton) {
                e.preventDefault(); // Prevent any default link behavior
                const menuContent = actionButton.nextElementSibling;
                
                if (menuContent) {
                    if (menuContent.classList.contains('active') || menuContent.style.display === 'block') {
                        menuContent.classList.remove('active');
                        menuContent.style.display = 'none';
                    } else {
                        openMenus.forEach(menu => {
                            menu.classList.remove('active');
                            menu.style.display = 'none';
                        });
                        menuContent.classList.add('active');
                        menuContent.style.display = 'block';
                    }
                }
            } else {
                // If the click is outside an action button and its menu, close all open menus.
                if (!e.target.closest('.action-menu-content')) {
                    document.querySelectorAll('.action-menu-content').forEach(menu => {
                        menu.classList.remove('active');
                        menu.style.display = 'none';
                    });
                }
            }

            // Datepicker calendar icon click delegation
            const calendarImg = e.target.closest('.date-input img');
            if (calendarImg) {
                const input = calendarImg.previousElementSibling;
                if (input) {
                    input.focus();
                    if (input._flatpickr) {
                        input._flatpickr.open();
                    }
                }
            }
        });
        window.adminActionLogicAtttached = true;
    }
};

window.initAdmin = initAdmin;

document.addEventListener('DOMContentLoaded', window.initAdmin);