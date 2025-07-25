<?php
require_once __DIR__ . '/../config/config.php';

class User {
    /**
     * Retrieves a user's full details, including their department code.
     *
     * @param int $id The user's ID.
     * @return array|null An associative array of the user's data or null if not found.
     */
    public static function getById($id) {
        global $conn;
        $sql = "
            SELECT 
                u.*, 
                d.name as department_name 
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $user['gender'] = 'Male';
            $user['state_of_origin'] = 'Osun State';
            $user['phone_number'] = '08123456789';
            $user['marital_status'] = 'Single';
            $user['date_of_birth'] = 'April 1st, 1997';
            $user['address'] = 'No 123, Unit 1, Road H, Kogberegbe Street, Ologuneu, Ibadan';
            $user['session_of_entry'] = '2022/2023';
        }

        return $user;
    }

    public static function getByMatricNumber($matric_number) {
        global $conn;
        $sql = "SELECT * FROM users WHERE matric_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $matric_number);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function isAdmin($id) {
        $user = self::getById($id);
        return $user && $user['is_admin'] == 1;
    }

    public static function getAllStudents($search = '', $page = 1, $per_page = 50, $department_id = null, $level = null, $mode = null, $program_type = null, $sort_by = 'full_name', $sort_order = 'asc', $start_date = null, $end_date = null) {
        global $conn;
        $offset = ($page - 1) * $per_page;
        $sql = "SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.is_admin = 0";
        
        $params = [];
        $types = '';

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
        if ($start_date && $end_date) {
            $sql .= " AND u.created_at BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $types .= 'ss';
        }
        if ($search) {
            $sql .= " AND (u.full_name LIKE ? OR u.matric_number LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= 'ss';
        }

        $sql .= " ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function countAllStudents($search = '', $department_id = null, $level = null, $mode = null, $program_type = null, $start_date = null, $end_date = null) {
        global $conn;
        $sql = "SELECT COUNT(*) as total FROM users WHERE is_admin = 0";

        $params = [];
        $types = '';

        if ($department_id) {
            $sql .= " AND department_id = ?";
            $params[] = $department_id;
            $types .= 'i';
        }
        if ($level) {
            $sql .= " AND level = ?";
            $params[] = $level;
            $types .= 'i';
        }
        if ($mode) {
            $sql .= " AND mode = ?";
            $params[] = $mode;
            $types .= 's';
        }
        if ($program_type) {
            $sql .= " AND program_type = ?";
            $params[] = $program_type;
            $types .= 's';
        }
        if ($start_date && $end_date) {
            $sql .= " AND created_at BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $types .= 'ss';
        }
        if ($search) {
            $sql .= " AND (full_name LIKE ? OR matric_number LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= 'ss';
        }
        
        $stmt = $conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }

    public static function updateProfile($id, $full_name, $level, $programme, $session, $department_id, $program_type, $mode) {
        global $conn;
        $sql = "UPDATE users SET 
                    full_name = ?, 
                    level = ?, 
                    programme = ?, 
                    session = ?,
                    department_id = ?,
                    program_type = ?,
                    `mode` = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssissi", $full_name, $level, $programme, $session, $department_id, $program_type, $mode, $id);
        return $stmt->execute();
    }

    public static function updatePassword($id, $password) {
        global $conn;
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $password, $id);
        return $stmt->execute();
    }
}