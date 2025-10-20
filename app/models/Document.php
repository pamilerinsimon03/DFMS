<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../services/AcademicCalendarService.php';

class Document
{
    public static function getById($id)
    {
        global $conn;
        $sql = "SELECT d.*, u.full_name, u.matric_number, u.programme, u.level, u.session
                FROM documents d
                JOIN users u ON d.user_id = u.id
                WHERE d.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function getDocumentsByUserId($user_id) {
        global $conn;
        $sql = "SELECT d.id, d.document_name, d.file_path, d.status, d.level, u.session, d.semester 
                FROM documents d 
                JOIN users u ON d.user_id = u.id
                WHERE d.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getByUserId($user_id, $search = null)
    {
        global $conn;
        $sql = "SELECT * FROM documents WHERE user_id = ?";
        $params = [$user_id];
        $types = "i";

        if ($search) {
            $sql .= " AND document_name LIKE ?";
            $params[] = "%" . $search . "%";
            $types .= "s";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getByUserIdAndStatus($user_id, $status)
    {
        global $conn;
        if ($status === 'total') {
            return self::getByUserId($user_id);
        }
        $sql = "SELECT * FROM documents WHERE user_id = ? AND status = ?";
        $stmt = $conn->prepare($sql);
        $uc_status = ucfirst($status);
        $stmt->bind_param("is", $user_id, $uc_status);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function countByDateRange($start_date, $end_date, $department_id = null)
    {
        global $conn;
        $sql = "SELECT COUNT(d.id) as count FROM documents d JOIN users u ON d.user_id = u.id WHERE DATE(d.created_at) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
        $types = "ss";

        if ($department_id) {
            $sql .= " AND u.department_id = ?";
            $params[] = $department_id;
            $types .= "i";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }

    public static function countByDateRangeAndStatus($start_date, $end_date, $status, $department_id = null)
    {
        global $conn;
        $sql = "SELECT COUNT(d.id) as count FROM documents d JOIN users u ON d.user_id = u.id WHERE d.status = ? AND DATE(d.created_at) BETWEEN ? AND ?";
        $params = [$status, $start_date, $end_date];
        $types = "sss";

        if ($department_id) {
            $sql .= " AND u.department_id = ?";
            $params[] = $department_id;
            $types .= "i";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }

    public static function getUploadsByMonth($start_date, $end_date, $department_id = null)
    {
        global $conn;
        $sql = "SELECT DATE_FORMAT(d.created_at, '%Y-%m') as month, COUNT(d.id) as count
                FROM documents d
                JOIN users u ON d.user_id = u.id
                WHERE d.created_at BETWEEN ? AND ?";
        
        // Create variables for datetime strings
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        
        $params = [$start_datetime, $end_datetime];
        $types = "ss";

        if ($department_id) {
            $sql .= " AND u.department_id = ?";
            $params[] = $department_id;
            $types .= "i";
        }

        $sql .= " GROUP BY month ORDER BY month";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $uploads = [];
        foreach ($result as $row) {
            $uploads[$row['month']] = (int)$row['count'];
        }
        return $uploads;
    }


    public static function getVerificationBreakdown($start_date, $end_date, $department_id = null)
    {
        global $conn;
        $sql = "SELECT d.status, COUNT(d.id) as count
                  FROM documents d
                  JOIN users u ON d.user_id = u.id
                  WHERE d.created_at BETWEEN ? AND ?";
        
        // Create variables for datetime strings
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        
        $params = [$start_datetime, $end_datetime];
        $types = "ss";

        if ($department_id) {
            $sql .= " AND u.department_id = ?";
            $params[] = $department_id;
            $types .= "i";
        }
        
        $sql .= " GROUP BY d.status";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $breakdown = [];
        foreach ($result as $row) {
            $breakdown[$row['status']] = (int)$row['count'];
        }
        return $breakdown;
    }

    public static function getPendingDocuments($start_date, $end_date, $limit, $department_id = null)
    {
        global $conn;
        $sql = "SELECT d.id, d.document_name, d.file_path, d.created_at as upload_date, u.full_name, u.programme as department, u.level, u.session
             FROM documents d
             JOIN users u ON d.user_id = u.id
             WHERE d.status = 'Pending Approval' AND d.created_at BETWEEN ? AND ?";

        if ($department_id) {
            $sql .= " AND u.department_id = ?";
        }

        $sql .= " ORDER BY d.created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);

        // Create variables for datetime strings
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';

        if ($department_id) {
            $stmt->bind_param("ssii", $start_datetime, $end_datetime, $department_id, $limit);
        } else {
            $stmt->bind_param("ssi", $start_datetime, $end_datetime, $limit);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public static function updateStatus($doc_id, $action)
    {
        global $conn;
        $status = $action === 'verify' || $action === 'approve' ? 'Approved' : 'Rejected';
        $sql = "UPDATE documents SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $doc_id);
        $ok = $stmt->execute();

        if ($ok) {
            // Fetch doc to know owner and name
            $doc = self::getById($doc_id);
            if ($doc && isset($doc['user_id'])) {
                $title = $status === 'Approved' ? 'Document approved' : 'Document rejected';
                $message = $status === 'Approved'
                    ? "Your document '" . $doc['document_name'] . "' has been approved."
                    : "Your document '" . $doc['document_name'] . "' was rejected. Please review and reupload.";
                Notification::create((int)$doc['user_id'], $title, $message);
            }
        }

        return $ok;
    }

    /**
     * Generate smart search terms for fuzzy matching
     */
    private static function generateSearchTerms($search) {
        $search = trim($search);
        if (empty($search)) {
            return [];
        }
        
        $terms = [];
        
        // Original search term
        $terms[] = '%' . $search . '%';
        
        // Case variations
        $terms[] = '%' . strtolower($search) . '%';
        $terms[] = '%' . strtoupper($search) . '%';
        $terms[] = '%' . ucfirst(strtolower($search)) . '%';
        
        // Handle common typos and variations
        $variations = [
            'approv' => 'approved',
            'pend' => 'pending',
            'reject' => 'rejected',
            'doc' => 'document',
            'cert' => 'certificate',
            'trans' => 'transcript',
            'result' => 'results',
            'id' => 'identity',
            'pass' => 'passport',
            'birth' => 'birth',
            'marriage' => 'marital',
            'state' => 'state',
            'phone' => 'phone',
            'email' => 'email',
            'addr' => 'address'
        ];
        
        foreach ($variations as $short => $full) {
            if (stripos($search, $short) !== false) {
                $terms[] = '%' . $full . '%';
            }
        }
        
        // Handle matric number patterns (common variations)
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $search)) {
            // Format: 20/21/1234
            $terms[] = '%' . $search . '%';
        } elseif (preg_match('/^\d{10}$/', $search)) {
            // Format: 2021123456
            $terms[] = '%' . $search . '%';
        }
        
        // Remove duplicates and return
        return array_unique($terms);
    }

    /**
     * Get search suggestions based on existing data
     */
    public static function getSearchSuggestions($query, $limit = 10) {
        global $conn;
        
        if (empty($query) || strlen($query) < 2) {
            return [];
        }
        
        $search_term = '%' . $query . '%';
        
        $sql = "SELECT DISTINCT 
                    u.full_name as suggestion,
                    'name' as type
                FROM users u
                JOIN documents d ON u.id = d.user_id
                WHERE u.full_name LIKE ?
                UNION
                SELECT DISTINCT 
                    u.matric_number as suggestion,
                    'matric' as type
                FROM users u
                JOIN documents d ON u.id = d.user_id
                WHERE u.matric_number LIKE ?
                UNION
                SELECT DISTINCT 
                    d.document_name as suggestion,
                    'document' as type
                FROM documents d
                WHERE d.document_name LIKE ?
                UNION
                SELECT DISTINCT 
                    d.status as suggestion,
                    'status' as type
                FROM documents d
                WHERE d.status LIKE ?
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $search_term, $search_term, $search_term, $search_term, $limit);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getAllDocuments($status_filter, $start_date, $end_date, $search, $page, $per_page, $department_id, $level, $mode, $program_type, $sort_by, $sort_order)
    {
        global $conn;
        $offset = ($page - 1) * $per_page;
        $params = [];
        $types = '';

        $sql = "SELECT d.id, d.document_name, d.file_path, d.status, d.created_at as upload_date, u.full_name, u.programme as department, u.level, u.session, u.matric_number, u.mode, u.program_type
                FROM documents d
                JOIN users u ON d.user_id = u.id
                WHERE DATE(d.created_at) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= 'ss';

        if ($status_filter) {
            $sql .= " AND d.status = ?";
            $params[] = $status_filter;
            $types .= 's';
        }

        if ($search) {
            // Enhanced smart search with multiple patterns
            $search_terms = self::generateSearchTerms($search);
            $search_conditions = [];
            
            foreach ($search_terms as $term) {
                $search_conditions[] = "(
                    u.full_name LIKE ? OR 
                    u.matric_number LIKE ? OR 
                    d.document_name LIKE ? OR 
                    u.programme LIKE ? OR
                    d.status LIKE ? OR
                    u.level LIKE ? OR
                    u.session LIKE ? OR
                    u.mode LIKE ? OR
                    u.program_type LIKE ?
                )";
                // Add parameters for each search term
                for ($i = 0; $i < 9; $i++) {
                    $params[] = $term;
                    $types .= 's';
                }
            }
            
            if (!empty($search_conditions)) {
                $sql .= " AND (" . implode(" OR ", $search_conditions) . ")";
            }
        }

        if ($department_id) {
            $sql .= " AND u.department_id = ?";
            $params[] = $department_id;
            $types .= 'i';
        }
        if ($level) {
            $sql .= " AND u.level = ?";
            $params[] = $level;
            $types .= 'i';
        }
        if ($mode) {
            $sql .= " AND u.mode = ?";
            $params[] = $mode;
            $types .= 's';
        }
        if ($program_type) {
            $sql .= " AND u.program_type = ?";
            $params[] = $program_type;
            $types .= 's';
        }

        $valid_sort_columns = ['upload_date' => 'd.created_at', 'full_name' => 'u.full_name', 'department' => 'department', 'level' => 'u.level'];
        $order_by = $valid_sort_columns[$sort_by] ?? 'd.created_at';
        $sort_direction = strtolower($sort_order) === 'asc' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY $order_by $sort_direction";

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function countAllDocuments($status_filter, $start_date, $end_date, $search, $department_id, $level, $mode, $program_type)
    {
        global $conn;
        $params = [];
        $types = '';

        // Create variables for datetime strings
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';

        $sql = "SELECT COUNT(d.id) as total
                FROM documents d
                JOIN users u ON d.user_id = u.id
                WHERE d.created_at BETWEEN ? AND ?";
        $params[] = $start_datetime;
        $params[] = $end_datetime;
        $types .= 'ss';

        if ($status_filter) {
            $sql .= " AND d.status = ?";
            $params[] = $status_filter;
            $types .= 's';
        }

        if ($search) {
            // Enhanced smart search with multiple patterns
            $search_terms = self::generateSearchTerms($search);
            $search_conditions = [];
            
            foreach ($search_terms as $term) {
                $search_conditions[] = "(
                    u.full_name LIKE ? OR 
                    u.matric_number LIKE ? OR 
                    d.document_name LIKE ? OR 
                    u.programme LIKE ? OR
                    d.status LIKE ? OR
                    u.level LIKE ? OR
                    u.session LIKE ? OR
                    u.mode LIKE ? OR
                    u.program_type LIKE ?
                )";
                // Add parameters for each search term
                for ($i = 0; $i < 9; $i++) {
                    $params[] = $term;
                    $types .= 's';
                }
            }
            
            if (!empty($search_conditions)) {
                $sql .= " AND (" . implode(" OR ", $search_conditions) . ")";
            }
        }

        if ($department_id) {
            $sql .= " AND u.department_id = ?";
            $params[] = $department_id;
            $types .= 'i';
        }
        if ($level) {
            $sql .= " AND u.level = ?";
            $params[] = $level;
            $types .= 'i';
        }
        if ($mode) {
            $sql .= " AND u.mode = ?";
            $params[] = $mode;
            $types .= 's';
        }
        if ($program_type) {
            $sql .= " AND u.program_type = ?";
            $params[] = $program_type;
            $types .= 's';
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }
    public static function upload($user_id, $document_name, $generated_filename, $file, $classification_data)
    {
        global $conn;
        
        // Include security utilities
        require_once __DIR__ . '/../utils/SecurityUtils.php';

        // Validate file upload using security utilities
        $validationErrors = SecurityUtils::validateFileUpload($file);
        if (!empty($validationErrors)) {
            SecurityUtils::logSecurityEvent('file_upload_validation_failed', [
                'user_id' => $user_id,
                'errors' => $validationErrors,
                'filename' => $file['name']
            ]);
            return ['success' => false, 'message' => 'File validation failed: ' . implode(', ', $validationErrors)];
        }

        // Create secure upload directory outside webroot
        $upload_dir = __DIR__ . '/../../secure_uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate secure filename
        $secure_filename = SecurityUtils::generateSecureFilename($file['name'], $user_id);
        $file_path = 'secure_uploads/' . $secure_filename;
        $destination = $upload_dir . $secure_filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            SecurityUtils::logSecurityEvent('file_upload_failed', [
                'user_id' => $user_id,
                'filename' => $file['name'],
                'error' => 'Failed to move uploaded file'
            ]);
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
        }
        
        // Log successful file upload
        SecurityUtils::logSecurityEvent('file_upload_success', [
            'user_id' => $user_id,
            'original_filename' => $file['name'],
            'secure_filename' => $secure_filename,
            'file_size' => $file['size']
        ]);

        $sql = "
            INSERT INTO documents
              (user_id,
               document_name,
               file_path,
               status,
               level,
               semester,
               program_type,
               mode,
               department_id,
               created_at)
            VALUES
              (?, ?, ?, 'Pending Approval', ?, ?, ?, ?, ?, NOW())
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            unlink($destination);
            return ['success' => false, 'message' => 'DB prepare failed: ' . $conn->error];
        }
        $stmt->bind_param(
            'ississsi',
            $user_id,
            $document_name,
            $file_path,
            $classification_data['level'],
            $classification_data['semester'],
            $classification['program_type'],
            $classification_data['mode'],
            $classification_data['department_id']
        );

        if ($stmt->execute()) {
            // Create notification for user
            $docTitle = $document_name;
            Notification::create(
                $user_id,
                'Upload successful',
                "Your document '" . $docTitle . "' was uploaded and is awaiting verification."
            );
            return ['success' => true, 'message' => 'File uploaded successfully.'];
        } else {
            unlink($destination);
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
    }

    public static function reupload($doc_id, $new_file, $generated_filename)
    {
        global $conn;
        
        // Include security utilities
        require_once __DIR__ . '/../utils/SecurityUtils.php';

        // 1. Get document details first
        $doc = self::getById($doc_id);
        if (!$doc) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

        // 2. Validate file upload using security utilities
        $validationErrors = SecurityUtils::validateFileUpload($new_file);
        if (!empty($validationErrors)) {
            SecurityUtils::logSecurityEvent('file_reupload_validation_failed', [
                'user_id' => $doc['user_id'],
                'doc_id' => $doc_id,
                'errors' => $validationErrors,
                'filename' => $new_file['name']
            ]);
            return ['success' => false, 'message' => 'File validation failed: ' . implode(', ', $validationErrors)];
        }

        // 2. Move the new file to secure location
        $upload_dir = __DIR__ . '/../../secure_uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate secure filename for reupload
        $secure_filename = SecurityUtils::generateSecureFilename($new_file['name'], $doc['user_id']);
        $new_file_path = 'secure_uploads/' . $secure_filename;
        $destination = $upload_dir . $secure_filename;
        
        if (!move_uploaded_file($new_file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
        }

        // 3. Update the database
        $sql = "UPDATE documents SET file_path = ?, status = 'Pending Approval', created_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            unlink($destination); // Clean up uploaded file
            return ['success' => false, 'message' => 'DB prepare failed: ' . $conn->error];
        }
        $stmt->bind_param('si', $new_file_path, $doc_id);

        if ($stmt->execute()) {
            // Log successful file reupload
            SecurityUtils::logSecurityEvent('file_reupload_success', [
                'user_id' => $doc['user_id'],
                'doc_id' => $doc_id,
                'original_filename' => $new_file['name'],
                'secure_filename' => $secure_filename,
                'file_size' => $new_file['size']
            ]);
            
            // Notify user of reupload awaiting verification
            $doc = self::getById($doc_id);
            Notification::create(
                (int)$doc['user_id'],
                'Reupload received',
                "Your document '" . $doc['document_name'] . "' was reuploaded and is awaiting verification."
            );
            return ['success' => true, 'message' => 'File re-uploaded successfully.'];
        } else {
            unlink($destination); // Clean up uploaded file
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
    }
}