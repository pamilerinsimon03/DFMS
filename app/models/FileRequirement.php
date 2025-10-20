<?php
require_once __DIR__ . '/../config/config.php';

class FileRequirement {
    
    /**
     * Get all file requirements for a department
     */
    public static function getByDepartment($department_id, $active_only = true) {
        global $conn;
        
        $sql = "SELECT fr.*, d.name as department_name, u.full_name as created_by_name 
                FROM file_requirements fr
                JOIN departments d ON fr.department_id = d.id
                JOIN users u ON fr.created_by = u.id
                WHERE fr.department_id = ?";
        
        if ($active_only) {
            $sql .= " AND fr.is_active = 1";
        }
        
        $sql .= " ORDER BY fr.due_date ASC, fr.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get all active file requirements for a department (for students)
     */
    public static function getActiveForDepartment($department_id) {
        global $conn;
        
        $sql = "SELECT fr.*, d.name as department_name 
                FROM file_requirements fr
                JOIN departments d ON fr.department_id = d.id
                WHERE fr.department_id = ? AND fr.is_active = 1 AND fr.due_date >= CURDATE()
                ORDER BY fr.due_date ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Create a new file requirement
     */
    public static function create($department_id, $document_name, $description, $due_date, $created_by) {
        global $conn;
        
        $sql = "INSERT INTO file_requirements (department_id, document_name, description, due_date, created_by) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssi", $department_id, $document_name, $description, $due_date, $created_by);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $conn->insert_id];
        } else {
            return ['success' => false, 'message' => $stmt->error];
        }
    }
    
    /**
     * Update a file requirement
     */
    public static function update($id, $document_name, $description, $due_date, $is_active) {
        global $conn;
        
        $sql = "UPDATE file_requirements 
                SET document_name = ?, description = ?, due_date = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $document_name, $description, $due_date, $is_active, $id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => $stmt->error];
        }
    }
    
    /**
     * Delete a file requirement
     */
    public static function delete($id) {
        global $conn;
        
        $sql = "DELETE FROM file_requirements WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => $stmt->error];
        }
    }
    
    /**
     * Get a file requirement by ID
     */
    public static function getById($id) {
        global $conn;
        
        $sql = "SELECT fr.*, d.name as department_name, u.full_name as created_by_name 
                FROM file_requirements fr
                JOIN departments d ON fr.department_id = d.id
                JOIN users u ON fr.created_by = u.id
                WHERE fr.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Toggle active status
     */
    public static function toggleActive($id) {
        global $conn;
        
        $sql = "UPDATE file_requirements 
                SET is_active = NOT is_active, updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => $stmt->error];
        }
    }
}
?>
