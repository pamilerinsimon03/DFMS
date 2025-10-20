<div class="admin-content">
    <div class="section-header">
        <div class="section-header-left">
            <h1 class="section-title">File Management</h1>
            <p class="section-subtitle">Manage files students need to upload for your department</p>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Toast notification data -->
    <?php if ($message): ?>
    <div data-<?php echo $message_type; ?>="<?php echo htmlspecialchars($message); ?>" style="display: none;"></div>
    <?php endif; ?>

    <div class="file-management-container">
        <!-- Add New File Requirement -->
        <div class="card">
            <div class="card-header">
                <h3>Add New File Requirement</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="file-requirement-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="document_name">Document Name *</label>
                            <input type="text" id="document_name" name="document_name" required 
                                   placeholder="e.g., Academic Transcript">
                        </div>
                        <div class="form-group">
                            <label for="due_date">Due Date *</label>
                            <input type="date" id="due_date" name="due_date" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" 
                                  placeholder="Optional description or instructions for students"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <img src="/app/src/assets/images/icon-plus.svg" alt="Add" class="icon-sm">
                        Add File Requirement
                    </button>
                </form>
            </div>
        </div>

        <!-- File Requirements List -->
        <div class="card">
            <div class="card-header">
                <h3>Current File Requirements</h3>
                <span class="badge"><?php echo count($file_requirements); ?> items</span>
            </div>
            <div class="card-body">
                <?php if (empty($file_requirements)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><img src="/app/src/assets/images/icon-empty-folder.svg" alt="Empty"></div>
                    <h4>No file requirements yet</h4>
                    <p>Add your first file requirement using the form above.</p>
                </div>
                <?php else: ?>
                <div class="file-requirements-list">
                    <?php foreach ($file_requirements as $requirement): ?>
                    <div class="file-requirement-item <?php echo $requirement['is_active'] ? 'active' : 'inactive'; ?>" data-requirement-id="<?php echo $requirement['id']; ?>" data-name="<?php echo htmlspecialchars($requirement['document_name']); ?>" data-desc="<?php echo htmlspecialchars($requirement['description'] ?? ''); ?>" data-due="<?php echo htmlspecialchars($requirement['due_date']); ?>" data-active="<?php echo (int)$requirement['is_active']; ?>">
                        <div class="requirement-info">
                            <div class="requirement-header">
                                <h4><?php echo htmlspecialchars($requirement['document_name']); ?></h4>
                                <div class="requirement-status">
                                    <?php if ($requirement['is_active']): ?>
                                        <span class="status-badge active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($requirement['description']): ?>
                            <p class="requirement-description">
                                <?php echo htmlspecialchars($requirement['description']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="requirement-meta">
                                <span class="meta-item">
                                    <strong>Due Date:</strong> 
                                    <?php echo date('M j, Y', strtotime($requirement['due_date'])); ?>
                                </span>
                                <span class="meta-item">
                                    <strong>Created:</strong> 
                                    <?php echo date('M j, Y', strtotime($requirement['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="requirement-actions">
                            <button class="btn btn-sm btn-secondary" onclick="editRequirement(<?php echo $requirement['id']; ?>)">
                                <img src="/app/src/assets/images/icon-edit.svg" alt="Edit" class="icon-sm">
                                Edit
                            </button>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Are you sure you want to toggle this requirement?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="id" value="<?php echo $requirement['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <img src="/app/src/assets/images/<?php echo $requirement['is_active'] ? 'icon-pause.svg' : 'icon-play.svg'; ?>" alt="Toggle" class="icon-sm">
                                    <?php echo $requirement['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Are you sure you want to delete this requirement? This action cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $requirement['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <img src="/app/src/assets/images/icon-trash.svg" alt="Delete" class="icon-sm">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit File Requirement</h3>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" class="file-requirement-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_document_name">Document Name *</label>
                        <input type="text" id="edit_document_name" name="document_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_due_date">Due Date *</label>
                        <input type="date" id="edit_due_date" name="due_date" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <span class="checkmark"></span>
                        Active (visible to students)
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Requirement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit requirement function
function editRequirement(id) {
    // Get requirement data (you might want to fetch this via AJAX)
    // For now, we'll populate from the existing data
    const requirement = <?php echo json_encode($file_requirements); ?>.find(r => r.id == id);
    
    if (requirement) {
        document.getElementById('edit_id').value = requirement.id;
        document.getElementById('edit_document_name').value = requirement.document_name;
        document.getElementById('edit_due_date').value = requirement.due_date;
        document.getElementById('edit_description').value = requirement.description || '';
        document.getElementById('edit_is_active').checked = requirement.is_active == 1;
        
        document.getElementById('editModal').style.display = 'block';
    }
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>
