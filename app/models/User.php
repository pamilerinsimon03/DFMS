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
                d.code as department_code 
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE u.id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
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