const initSettings = () => {
    const burger = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-sidebar-overlay');

    if (!burger || burger.dataset.init) return;
    burger.dataset.init = 'true';

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

    overlay.addEventListener('click', function() {
        closeSidebar();
    });

    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });

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
};

window.initSettings = initSettings;
document.addEventListener('DOMContentLoaded', initSettings);
