class ToastNotification {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    }

    show(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = this.getIcon(type);
        const content = `
            <div class="toast-content">
                <div class="toast-icon">${icon}</div>
                <div class="toast-message">${message}</div>
                <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        toast.innerHTML = content;
        this.container.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 100);
        
        if (duration > 0) {
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
        
        return toast;
    }

    getIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }

    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 7000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 6000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }
}

window.toast = new ToastNotification();

const initToastMessages = () => {
    const errorMessage = document.querySelector('[data-error]');
    if (errorMessage) {
        window.toast.error(errorMessage.getAttribute('data-error'));
    }
    
    const successMessage = document.querySelector('[data-success]');
    if (successMessage) {
        window.toast.success(successMessage.getAttribute('data-success'));
    }
};

window.initToastMessages = initToastMessages;
document.addEventListener('DOMContentLoaded', initToastMessages);
