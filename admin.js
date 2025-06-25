function logEvent(message) {
    fetch('log_event.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message })
    });
}

// Sidebar Toggle
function initSidebar() {
    const toggleBtn = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');

    if (toggleBtn && sidebar && overlay) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            logEvent('Sidebar toggled');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            logEvent('Sidebar closed via overlay');
        });
    }
}

// Chart Initialization for admin.php
function initCharts() {
    const barCanvas = document.getElementById('uploads-by-month');
    const pieCanvas = document.getElementById('uploads-by-status');

    if (barCanvas) {
        const barData = {
            labels: ['May 2025', 'Jun 2025'],
            datasets: [{
                label: 'Documents Uploaded',
                data: [3, 1],
                backgroundColor: ['#4e73df', '#1cc88a'],
                borderColor: ['#4e73df', '#1cc88a'],
                borderWidth: 1
            }]
        };
        new Chart(barCanvas, {
            type: 'bar',
            data: barData,
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
        logEvent('Bar chart initialized: ' + JSON.stringify(barData));
    }

    if (pieCanvas) {
        const pieData = {
            labels: ['Pending', 'Verified', 'Failed'],
            datasets: [{
                data: [4, 0, 0],
                backgroundColor: ['#f6c23e', '#1cc88a', '#e74a3b']
            }]
        };
        new Chart(pieCanvas, {
            type: 'pie',
            data: pieData,
            options: { responsive: true }
        });
        logEvent('Pie chart initialized: ' + JSON.stringify(pieData));
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    logEvent('admin.js loaded');
    initSidebar();
    initCharts();
});