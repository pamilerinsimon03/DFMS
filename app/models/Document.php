<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../services/AcademicCalendarService.php';
require_once __DIR__ . '/../services/MailService.php';

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
        self::processPendingQueue();
        global $conn;
        $sql = "SELECT d.id, d.document_name, d.file_path, d.status, d.level, u.session, d.semester, d.rejection_reason 
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
    public static function updateStatus($doc_id, $action, $rejection_reason = null)
    {
        global $conn;
        $status = $action === 'verify' || $action === 'approve' ? 'Approved' : 'Rejected';
        
        $sql = "UPDATE documents SET status = ?, rejection_reason = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $reason = ($status === 'Rejected') ? $rejection_reason : null;
        $stmt->bind_param("ssi", $status, $reason, $doc_id);
        $ok = $stmt->execute();

        if ($ok) {
            $doc = self::getById($doc_id);
            if ($doc && isset($doc['user_id'])) {
                $title = $status === 'Approved' ? 'Document approved' : 'Document rejected';
                $message = $status === 'Approved'
                    ? "Your document '" . $doc['document_name'] . "' has been approved."
                    : "Your document '" . $doc['document_name'] . "' was rejected." . ($reason ? " Reason: " . $reason : " Please review and reupload.");
                Notification::create((int)$doc['user_id'], $title, $message);

                $user = User::getById($doc['user_id']);
                if ($user && !empty($user['email'])) {
                    $emailSubject = "Document Status: " . $status;
                    $emailMessage = "<p>Dear " . htmlspecialchars($user['full_name']) . ",</p>";
                    
                    if ($status === 'Approved') {
                        $emailMessage .= "<p>Your document '<strong>" . htmlspecialchars($doc['document_name']) . "</strong>' has been <strong>APPROVED</strong>.</p>";
                        $emailMessage .= "<p>No further action is required for this document.</p>";
                    } else {
                        $emailMessage .= "<p>Your document '<strong>" . htmlspecialchars($doc['document_name']) . "</strong>' has been <strong>REJECTED</strong>.</p>";
                        if ($reason) {
                            $emailMessage .= "<p><strong>Reason for rejection:</strong> " . htmlspecialchars($reason) . "</p>";
                        }
                        $emailMessage .= "<p>Please review the feedback and re-upload the document via your dashboard.</p>";
                    }
                    
                    MailService::send($user['email'], $emailSubject, $emailMessage);
                }
            }
        }

        return $ok;
    }


    private static function generateSearchTerms($search) {
        $search = trim($search);
        if (empty($search)) {
            return [];
        }
        
        $terms = [];
        
        $terms[] = '%' . $search . '%';
        
        $terms[] = '%' . strtolower($search) . '%';
        $terms[] = '%' . strtoupper($search) . '%';
        $terms[] = '%' . ucfirst(strtolower($search)) . '%';
        
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
        
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $search)) {
            $terms[] = '%' . $search . '%';
        } elseif (preg_match('/^\d{10}$/', $search)) {
            $terms[] = '%' . $search . '%';
        }
        
        return array_unique($terms);
    }


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

    public static function processPendingQueue() {
        global $conn;
        if (!$conn) return;
        try {
            $res = $conn->query("SELECT id FROM document_processing_queue WHERE status = 'pending' LIMIT 1");
            if ($res && $res->num_rows > 0) {
                // Auto process pending items inline via queue worker CLI runner
                $cmd = "php " . escapeshellarg(__DIR__ . '/../scripts/queue_worker.php') . " --once";
                if (str_contains(strtoupper(PHP_OS), 'WIN')) {
                    pclose(popen("start /B " . $cmd, "r"));
                } else {
                    exec($cmd . " > /dev/null 2>&1 &");
                }
            }
        } catch (Throwable $e) {}
    }

    public static function getAllDocuments($status_filter, $start_date, $end_date, $search, $page, $per_page, $department_id, $level, $mode, $program_type, $sort_by, $sort_order)
    {
        self::processPendingQueue();
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
        
        require_once __DIR__ . '/../utils/SecurityUtils.php';
        require_once __DIR__ . '/../utils/StoragePathResolver.php';

        $validationErrors = SecurityUtils::validateFileUpload($file);
        if (!empty($validationErrors)) {
            SecurityUtils::logSecurityEvent('file_upload_validation_failed', [
                'user_id' => $user_id,
                'errors' => $validationErrors,
                'filename' => $file['name']
            ]);
            return ['success' => false, 'message' => 'File validation failed: ' . implode(', ', $validationErrors)];
        }

        $user = User::getById($user_id);
        $deptCode = $user['department_code'] ?? 'CS';

        $paths = StoragePathResolver::resolveUploadDirectory(
            $classification_data['semester'] ?? '2024_2025',
            $deptCode,
            $classification_data['level']
        );
        
        $secure_filename = SecurityUtils::generateSecureFilename($file['name'], $user_id);
        $file_path = $paths['relative'] . $secure_filename;
        $destination = $paths['absolute'] . $secure_filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            SecurityUtils::logSecurityEvent('file_upload_failed', [
                'user_id' => $user_id,
                'filename' => $file['name'],
                'error' => 'Failed to move uploaded file'
            ]);
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
        }
        
        $file_hash = hash_file('sha256', $destination);

        $conn->begin_transaction();

        try {
            $sql = "
                INSERT INTO documents
                  (user_id, document_name, file_path, file_hash, status, level, semester, program_type, mode, department_id, created_at)
                VALUES
                  (?, ?, ?, ?, 'Pending Quality Check', ?, ?, ?, ?, ?, NOW())
            ";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("DB prepare failed: " . $conn->error);
            }

            $stmt->bind_param(
                'isssisssi',
                $user_id,
                $document_name,
                $file_path,
                $file_hash,
                $classification_data['level'],
                $classification_data['semester'],
                $classification_data['program_type'],
                $classification_data['mode'],
                $classification_data['department_id']
            );

            $stmt->execute();
            $doc_id = $conn->insert_id;

            // Push to background processing queue
            $queueStmt = $conn->prepare("INSERT INTO document_processing_queue (document_id, user_id, file_path, status) VALUES (?, ?, ?, 'pending')");
            if ($queueStmt) {
                $queueStmt->bind_param("iis", $doc_id, $user_id, $file_path);
                $queueStmt->execute();
            }

            // Create notification record
            Notification::create(
                $user_id,
                'Upload received',
                "Your document '" . $document_name . "' was uploaded and is queued for quality verification."
            );

            $conn->commit();

            SecurityUtils::logSecurityEvent('file_upload_success', [
                'user_id' => $user_id,
                'original_filename' => $file['name'],
                'secure_filename' => $secure_filename,
                'file_size' => $file['size'],
                'file_hash' => $file_hash
            ]);

            return ['success' => true, 'message' => 'File uploaded successfully.', 'id' => $doc_id];

        } catch (Exception $e) {
            $conn->rollback();
            if (file_exists($destination)) {
                @unlink($destination);
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public static function reupload($doc_id, $new_file, $generated_filename)
    {
        global $conn;
        
        require_once __DIR__ . '/../utils/SecurityUtils.php';
        require_once __DIR__ . '/../utils/StoragePathResolver.php';

        $doc = self::getById($doc_id);
        if (!$doc) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

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

        $user = User::getById($doc['user_id']);
        $deptCode = $user['department_code'] ?? 'CS';

        $paths = StoragePathResolver::resolveUploadDirectory(
            $doc['semester'] ?? '2024_2025',
            $deptCode,
            $doc['level']
        );
        
        $secure_filename = SecurityUtils::generateSecureFilename($new_file['name'], $doc['user_id']);
        $new_file_path = $paths['relative'] . $secure_filename;
        $destination = $paths['absolute'] . $secure_filename;
        
        if (!move_uploaded_file($new_file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
        }

        $conn->begin_transaction();

        try {
            $sql = "UPDATE documents SET file_path = ?, status = 'Pending Quality Check', created_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("DB prepare failed: " . $conn->error);
            }
            $stmt->bind_param('si', $new_file_path, $doc_id);
            $stmt->execute();

            // Push to background processing queue
            $queueStmt = $conn->prepare("INSERT INTO document_processing_queue (document_id, user_id, file_path, status) VALUES (?, ?, ?, 'pending')");
            if ($queueStmt) {
                $queueStmt->bind_param("iis", $doc_id, $doc['user_id'], $new_file_path);
                $queueStmt->execute();
            }

            Notification::create(
                (int)$doc['user_id'],
                'Reupload received',
                "Your document '" . $doc['document_name'] . "' was reuploaded and is queued for quality verification."
            );

            $conn->commit();

            SecurityUtils::logSecurityEvent('file_reupload_success', [
                'user_id' => $doc['user_id'],
                'doc_id' => $doc_id,
                'original_filename' => $new_file['name'],
                'secure_filename' => $secure_filename,
                'file_size' => $new_file['size']
            ]);

            return ['success' => true, 'message' => 'File re-uploaded successfully.'];

        } catch (Exception $e) {
            $conn->rollback();
            if (file_exists($destination)) {
                @unlink($destination);
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}