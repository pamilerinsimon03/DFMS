const initFileManagement = () => {
    const dueDateInput = document.getElementById('due_date');
    if (dueDateInput) {
        dueDateInput.min = new Date().toISOString().split('T')[0];
    }
    
    const editDueDateInput = document.getElementById('edit_due_date');
    if (editDueDateInput) {
        editDueDateInput.min = new Date().toISOString().split('T')[0];
    }
    
    const forms = document.querySelectorAll('.file-requirement-form');
    forms.forEach(form => {
        if (form.dataset.init) return;
        form.dataset.init = 'true';
        
        form.addEventListener('submit', function(e) {
            const documentNameInput = form.querySelector('input[name="document_name"]');
            const dueDateInput = form.querySelector('input[name="due_date"]');
            
            const documentName = documentNameInput ? documentNameInput.value.trim() : '';
            const dueDate = dueDateInput ? dueDateInput.value : '';
            
            if (!documentName) {
                e.preventDefault();
                showToast('Document name is required', 'error');
                return false;
            }
            
            if (!dueDate) {
                e.preventDefault();
                showToast('Due date is required', 'error');
                return false;
            }
            
            const selectedDate = new Date(dueDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                e.preventDefault();
                showToast('Due date cannot be in the past', 'error');
                return false;
            }
        });
    });
    
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
};

function showToast(message, type = 'info') {
    if (window.toast) {
        window.toast.show(message, type);
    } else {
        alert(message);
    }
}

function editRequirement(id) {
    const requirementElement = document.querySelector(`[data-requirement-id="${id}"]`);
    if (!requirementElement) {
        showToast('Requirement not found', 'error');
        return;
    }
    
    const documentName = requirementElement.getAttribute('data-name') || requirementElement.querySelector('.requirement-header h4')?.textContent || '';
    const description = requirementElement.getAttribute('data-desc') || requirementElement.querySelector('.requirement-description')?.textContent || '';
    const dueDate = requirementElement.getAttribute('data-due') || '';
    const isActive = (requirementElement.getAttribute('data-active') === '1') || requirementElement.classList.contains('active');
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_document_name').value = documentName;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_due_date').value = dueDate;
    document.getElementById('edit_is_active').checked = isActive;
    
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (!modal) return;
    modal.style.display = 'none';
    
    document.getElementById('edit_id').value = '';
    document.getElementById('edit_document_name').value = '';
    document.getElementById('edit_description').value = '';
    document.getElementById('edit_due_date').value = '';
    document.getElementById('edit_is_active').checked = false;
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeEditModal();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});

function confirmDelete(requirementName) {
    return confirm(`Are you sure you want to delete "${requirementName}"? This action cannot be undone.`);
}

function toggleActive(requirementName, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    return confirm(`Are you sure you want to ${action} "${requirementName}"?`);
}

window.initFileManagement = initFileManagement;
document.addEventListener('DOMContentLoaded', initFileManagement);
