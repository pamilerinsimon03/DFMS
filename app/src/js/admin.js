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