<?php
require_once __DIR__ . '/../config/config.php';

class Document {
    public static function getById($id) {
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

    public static function getByUserId($user_id) {
        global $conn;
        $sql = "SELECT * FROM documents WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getByUserIdAndStatus($user_id, $status) {
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

    public static function countByDateRange($start_date, $end_date) {
        global $conn;
        $sql = "SELECT COUNT(id) as count FROM documents WHERE DATE(created_at) BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }

    public static function countByDateRangeAndStatus($start_date, $end_date, $status) {
        global $conn;
        $sql = "SELECT COUNT(id) as count FROM documents WHERE status = ? AND DATE(created_at) BETWEEN ? AND ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $status, $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }

    public static function getUploadsByMonth($start_date, $end_date) {
        global $conn;
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count
                FROM documents
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY month
                ORDER BY month";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $uploads = [];
        foreach ($result as $row) {
            $uploads[$row['month']] = (int)$row['count'];
        }
        return $uploads;
    }

    public static function getVerificationBreakdown($start_date, $end_date) {
        global $conn;
        $sql = "SELECT status, COUNT(id) as count
                  FROM documents
                  WHERE DATE(created_at) BETWEEN ? AND ?
                  GROUP BY status";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $breakdown = [];
        foreach ($result as $row) {
            $breakdown[$row['status']] = (int)$row['count'];
        }
        return $breakdown;
    }

    public static function getPendingDocuments($start_date, $end_date, $limit) {
        global $conn;
        $sql = "SELECT d.id, d.document_name, d.file_path, u.full_name, u.programme as department, u.level, u.session
             FROM documents d
             JOIN users u ON d.user_id = u.id
             WHERE d.status = 'Pending' AND DATE(d.created_at) BETWEEN ? AND ?
             ORDER BY d.created_at DESC
             LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $start_date, $end_date, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function updateStatus($doc_id, $action) {
        global $conn;
        $status = $action === 'verify' || $action === 'approve' ? 'Verified' : 'Rejected';
        $sql = "UPDATE documents SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $doc_id);
        return $stmt->execute();
    }

    public static function getAllDocuments($tab, $start_date, $end_date, $search, $page, $per_page) {
        global $conn;
        $offset = ($page - 1) * $per_page;
        $status_filter = '';
        if ($tab !== 'all') {
            $status_filter = "AND d.status = '" . ucfirst($conn->real_escape_string($tab)) . "'";
        }
        $search_filter = '';
        if ($search) {
            $search_term = '%' . $conn->real_escape_string($search) . '%';
            $search_filter = "AND (u.full_name LIKE '$search_term' OR u.programme LIKE '$search_term' OR d.document_name LIKE '$search_term')";
        }
        $sql = "SELECT d.id, d.document_name, d.file_path, d.status, u.full_name, u.programme as department, u.level, u.session
             FROM documents d
             JOIN users u ON d.user_id = u.id
             WHERE DATE(d.created_at) BETWEEN ? AND ? $status_filter $search_filter
             ORDER BY d.created_at DESC
             LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $start_date, $end_date, $per_page, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function countAllDocuments($tab, $start_date, $end_date, $search) {
        global $conn;
        $status_filter = '';
        if ($tab !== 'all') {
            $status_filter = "AND d.status = '" . ucfirst($conn->real_escape_string($tab)) . "'";
        }
        $search_filter = '';
        if ($search) {
            $search_term = '%' . $conn->real_escape_string($search) . '%';
            $search_filter = "AND (u.full_name LIKE '$search_term' OR u.programme LIKE '$search_term' OR d.document_name LIKE '$search_term')";
        }
        $sql = "SELECT COUNT(d.id) as total
              FROM documents d
              JOIN users u ON d.user_id = u.id
              WHERE DATE(d.created_at) BETWEEN ? AND ? $status_filter $search_filter";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    public static function upload($user_id, $document_name, $file) {
        global $conn;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'An error occurred during upload.'];
        }

        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $allowed_exts = ['pdf', 'jpeg', 'jpg', 'png'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            return ['success' => false, 'message' => 'File size exceeds 5MB limit.'];
        } elseif (!in_array($file['type'], $allowed_types) || !in_array($file_ext, $allowed_exts)) {
            return ['success' => false, 'message' => 'Invalid file type. Only PDF, JPEG, and PNG are allowed.'];
        }

        $upload_dir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $unique_file_name = time() . '_' . basename($file['name']);
        $file_path = 'uploads/' . $unique_file_name;
        $destination = $upload_dir . $unique_file_name;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $sql = "INSERT INTO documents (user_id, document_name, file_path, status, created_at) VALUES (?, ?, ?, 'Pending', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $user_id, $document_name, $file_path);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'File uploaded successfully.'];
            } else {
                return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to upload file. Please try again.'];
        }
    }
}