document.addEventListener('DOMContentLoaded', () => {
    // Action dropdown for 'more' buttons
    const moreIcons = document.querySelectorAll('.docs-table img[src$="more.svg"]');
    moreIcons.forEach(icon => {
        const cell = icon.closest('td');
        if (!cell.classList.contains('td-action')) {
            cell.classList.add('td-action');
            icon.classList.add('action-btn');
            // build dropdown
            const dropdown = document.createElement('div');
            dropdown.classList.add('action-dropdown');
            dropdown.innerHTML = `
                <div class="action-item" style="color: #01017A;"><img src="/app/src/assets/images/Bold Eye.svg" alt="Bold Eye">View</div>
                <div class="action-item" style="color: #FF3B30;"><img src="/app/src/assets/images/Trash.svg" alt="Trash">Delete</div>
            `;
            cell.appendChild(dropdown);
            // add View/Delete click handlers
            const actionItems = dropdown.querySelectorAll('.action-item');
            if (actionItems.length >= 2) {
                const [viewItem, deleteItem] = actionItems;
                viewItem.addEventListener('click', e => {
                    e.stopPropagation();
                    window.location.href = '/app/views/verification_detail.php';
                });
                deleteItem.addEventListener('click', e => {
                    e.stopPropagation();
                    alert('Delete clicked');
                    dropdown.style.display = 'none';
                });
            }
            // toggle on click
            icon.addEventListener('click', e => {
                e.stopPropagation();
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                const {
                    top,
                    left,
                    height
                } = icon.getBoundingClientRect();
                dropdown.style.top = (icon.offsetTop + icon.offsetHeight) + 'px';
                dropdown.style.left = icon.offsetLeft + 'px';
            });
        }
    });
    // close dropdown on outside click
    document.addEventListener('click', () => document.querySelectorAll('.action-dropdown').forEach(dd => dd.style.display = 'none'));
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
    
    //Make Menu Button Work
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
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const searchTerm = searchInput.value.trim();
            
            // On the main admin dashboard, redirect to the verifications page with the search query
            if (window.location.pathname.includes('admin.php')) {
                window.location.href = `/app/controllers/verifications.php?search=${encodeURIComponent(searchTerm)}`;
                return;
            }  else if (filterForm && formSearchInput) {
                formSearchInput.value = searchTerm;
                filterForm.submit();
            }
        });
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