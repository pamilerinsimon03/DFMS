/**
 * File Management JavaScript
 * Handles interactions for the file management page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize date picker for due date
    const dueDateInput = document.getElementById('due_date');
    if (dueDateInput) {
        // Set minimum date to today
        dueDateInput.min = new Date().toISOString().split('T')[0];
    }
    
    // Initialize edit form date picker
    const editDueDateInput = document.getElementById('edit_due_date');
    if (editDueDateInput) {
        editDueDateInput.min = new Date().toISOString().split('T')[0];
    }
    
    // Form validation
    const forms = document.querySelectorAll('.file-requirement-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const documentName = form.querySelector('input[name="document_name"]').value.trim();
            const dueDate = form.querySelector('input[name="due_date"]').value;
            
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
            
            // Check if due date is in the past
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
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Toast notification function (if not already defined)
function showToast(message, type = 'info') {
    if (window.toast) {
        window.toast.show(message, type);
    } else {
        // Fallback to alert if toast is not available
        alert(message);
    }
}

// Edit requirement function
function editRequirement(id) {
    // Get requirement data from the page
    const requirementElement = document.querySelector(`[data-requirement-id="${id}"]`);
    if (!requirementElement) {
        showToast('Requirement not found', 'error');
        return;
    }
    
    const documentName = requirementElement.getAttribute('data-name') || requirementElement.querySelector('.requirement-header h4')?.textContent || '';
    const description = requirementElement.getAttribute('data-desc') || requirementElement.querySelector('.requirement-description')?.textContent || '';
    const dueDate = requirementElement.getAttribute('data-due') || '';
    const isActive = (requirementElement.getAttribute('data-active') === '1') || requirementElement.classList.contains('active');
    
    // Populate edit form
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_document_name').value = documentName;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_due_date').value = dueDate;
    document.getElementById('edit_is_active').checked = isActive;
    
    // Show modal
    document.getElementById('editModal').style.display = 'block';
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    
    // Clear form
    document.getElementById('edit_id').value = '';
    document.getElementById('edit_document_name').value = '';
    document.getElementById('edit_description').value = '';
    document.getElementById('edit_due_date').value = '';
    document.getElementById('edit_is_active').checked = false;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeEditModal();
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Close modal with Escape key
    if (e.key === 'Escape') {
        closeEditModal();
    }
});

// Confirm delete function
function confirmDelete(requirementName) {
    return confirm(`Are you sure you want to delete "${requirementName}"? This action cannot be undone.`);
}

// Toggle active status with confirmation
function toggleActive(requirementName, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    return confirm(`Are you sure you want to ${action} "${requirementName}"?`);
}
