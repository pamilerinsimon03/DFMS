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

    if (barCanvas && typeof barChartData !== 'undefined') {
        new Chart(barCanvas, {
            type: 'bar',
            data: barChartData,
            options: {
                responsive: false,
                maintainAspectRatio: false,
                layout: { padding: 0 },
                scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 200, color: '#999999', font: { family: 'Poppins', size: 14 } },
                    grid: { drawBorder: false, color: 'rgba(128,128,128,0.3)', borderDash: [5,5], lineWidth: 1 }
                },
                x: {
                    ticks: { maxRotation: 0, autoSkip: true, color: '#999999', font: { family: 'Poppins', size: 12 } },
                    grid: { display: false }
                }
                },
                plugins: { legend:{ display:false } }
            }
        });
        logEvent('Bar chart initialized: ' + JSON.stringify(barChartData));
    }

    if (pieCanvas && typeof pieChartData !== 'undefined') {
        new Chart(pieCanvas, {
            type: 'pie',
            data: pieChartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: 0 },
                plugins: {
                legend: {
                position: 'bottom',
                labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 10, padding: 20, color:'#999999', font:{ family:'Poppins', size:12 } }
                },
                tooltip: { callbacks:{ label: ctx => `${ctx.label}: ${ctx.parsed}` } }
                }
            }
        });
        logEvent('Pie chart initialized: ' + JSON.stringify(pieChartData));
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    logEvent('admin.js loaded');
    initSidebar();
    initCharts();
});