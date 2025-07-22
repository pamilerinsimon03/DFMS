<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
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
                WHERE DATE(d.created_at) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
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
                  WHERE DATE(d.created_at) BETWEEN ? AND ?";
        $params = [$start_date, $end_date];
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
             WHERE d.status = 'Pending Approval' AND DATE(d.created_at) BETWEEN ? AND ?";

        if ($department_id) {
            $sql .= " AND u.department_id = ?";
        }

        $sql .= " ORDER BY d.created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);

        if ($department_id) {
            $stmt->bind_param("ssii", $start_date, $end_date, $department_id, $limit);
        } else {
            $stmt->bind_param("ssi", $start_date, $end_date, $limit);
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
        return $stmt->execute();
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
            $search_term = '%' . $search . '%';
            $sql .= " AND (u.full_name LIKE ? OR u.programme LIKE ? OR d.document_name LIKE ? OR u.matric_number LIKE ?)";
            for ($i = 0; $i < 4; $i++) {
                $params[] = $search_term;
                $types .= 's';
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

        $sql = "SELECT COUNT(d.id) as total
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
            $search_term = '%' . $search . '%';
            $sql .= " AND (u.full_name LIKE ? OR u.programme LIKE ? OR d.document_name LIKE ? OR u.matric_number LIKE ?)";
            for ($i = 0; $i < 4; $i++) {
                $params[] = $search_term;
                $types .= 's';
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

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'An error occurred during file upload.'];
        }

        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $allowed_exts  = ['pdf', 'jpeg', 'jpg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File size exceeds 5 MB limit.'];
        }
        if (!in_array($file['type'], $allowed_types) || !in_array($ext, $allowed_exts)) {
            return ['success' => false, 'message' => 'Invalid file type. Only PDF, JPEG, and PNG allowed.'];
        }

        $upload_dir  = __DIR__ . '/../../public/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_path   = 'uploads/' . $generated_filename;
        $destination = $upload_dir . $generated_filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
        }

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
            return ['success' => true, 'message' => 'File uploaded successfully.'];
        } else {
            unlink($destination);
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
    }
}