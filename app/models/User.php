<?php
require_once __DIR__ . '/../config/config.php';

class User {
    public static function getById($id) {
        global $conn;
        $sql = "
            SELECT 
                u.*, 
                d.name as department_name,
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

    public static function generatePassword($full_name) {
        $name_parts = explode(' ', trim($full_name));
        $surname = end($name_parts);
        $surname = strtolower(preg_replace('/[^a-zA-Z]/', '', $surname));
        if (empty($surname)) {
            $surname = 'student';
        }
        return $surname . '12345';
    }

    public static function createStudent($student_data) {
        global $conn;
        
        $existing_user = self::getByMatricNumber($student_data['matric_number']);
        if ($existing_user) {
            return ['success' => false, 'message' => 'Matric number already exists.'];
        }
        
        $activation_token = bin2hex(random_bytes(32));
        $is_activated = 0;
        $placeholder_password = '*ACTIVATION_REQUIRED*';
        
        $sql = "INSERT INTO users (matric_number, password, full_name, level, session, programme, program_type, mode, department_id, email, role, is_admin, activation_token, is_activated) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 0, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database prepare failed: ' . $conn->error];
        }
        
        $stmt->bind_param("ssssssssissi", 
            $student_data['matric_number'],
            $placeholder_password,
            $student_data['full_name'],
            $student_data['level'],
            $student_data['session'],
            $student_data['programme'],
            $student_data['program_type'],
            $student_data['mode'],
            $student_data['department_id'],
            $student_data['email'],
            $activation_token,
            $is_activated
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true, 
                'message' => 'Student account created. Activation token generated.',
                'activation_token' => $activation_token
            ];
        } else {
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
    }

    public static function bulkCreateStudents(array $students) {
        global $conn;
        if (empty($students)) return ['success' => true, 'count' => 0];

        $values = [];
        $params = [];
        $types = '';

        foreach ($students as $s) {
            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 0, ?, 0)";
            $types .= "ssssssssiss";
            $params[] = $s['matric_number'];
            $params[] = '*ACTIVATION_REQUIRED*';
            $params[] = $s['full_name'];
            $params[] = $s['level'];
            $params[] = $s['session'];
            $params[] = $s['programme'];
            $params[] = $s['program_type'] ?? 'Undergraduate';
            $params[] = $s['mode'] ?? 'Regular';
            $params[] = $s['department_id'];
            $params[] = $s['email'] ?? '';
            $params[] = bin2hex(random_bytes(16));
        }

        $sql = "INSERT INTO users (matric_number, password, full_name, level, session, programme, program_type, mode, department_id, email, role, is_admin, activation_token, is_activated) VALUES " . implode(', ', $values);
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Bulk prepare failed: ' . $conn->error];
        }
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return ['success' => true, 'count' => count($students)];
        } else {
            return ['success' => false, 'message' => 'Bulk insert failed: ' . $stmt->error];
        }
    }

    public static function activateAccountByToken($token, $dobInput, $newPassword) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM users WHERE activation_token = ? AND is_activated = 0");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired activation token.'];
        }

        if (!empty($user['date_of_birth']) && $user['date_of_birth'] !== $dobInput) {
            return ['success' => false, 'message' => 'Date of Birth does not match portal records.'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE users SET password = ?, is_activated = 1, activation_token = NULL WHERE id = ?");
        $update->bind_param("si", $hashed, $user['id']);

        if ($update->execute()) {
            return ['success' => true, 'message' => 'Account activated successfully. You can now login.'];
        } else {
            return ['success' => false, 'message' => 'Failed to activate account: ' . $conn->error];
        }
    }

    public static function getAllDepartments() {
        global $conn;
        $sql = "SELECT * FROM departments ORDER BY name";
        $result = $conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function getFacultyDepartments($faculty_id) {
        global $conn;
        $stmt = $conn->prepare("SELECT d.* FROM departments d JOIN faculty_departments fd ON d.id = fd.department_id WHERE fd.faculty_id = ?");
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function updateAllStudentPasswords() {
        global $conn;
        
        $sql = "SELECT id, full_name FROM users WHERE is_admin = 0";
        $result = $conn->query($sql);
        $students = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        $updated_count = 0;
        $errors = [];
        
        foreach ($students as $student) {
            $new_password = self::generatePassword($student['full_name']);
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $hashed_password, $student['id']);
            
            if ($stmt->execute()) {
                $updated_count++;
            } else {
                $errors[] = "Failed to update student ID {$student['id']}: " . $stmt->error;
            }
        }
        
        if (empty($errors)) {
            return [
                'success' => true, 
                'message' => "Successfully updated passwords for {$updated_count} students."
            ];
        } else {
            return [
                'success' => false, 
                'message' => "Updated {$updated_count} students but encountered errors: " . implode(', ', $errors)
            ];
        }
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
            $start_datetime = $start_date . ' 00:00:00';
            $end_datetime = $end_date . ' 23:59:59';
            $params[] = $start_datetime;
            $params[] = $end_datetime;
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
            $start_datetime = $start_date . ' 00:00:00';
            $end_datetime = $end_date . ' 23:59:59';
            $params[] = $start_datetime;
            $params[] = $end_datetime;
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

    public static function updateStudentProfile($id, $data) {
        global $conn;
        
        $sql = "UPDATE users SET 
                    full_name = ?,
                    email = ?,
                    phone_number = ?,
                    date_of_birth = ?,
                    gender = ?,
                    state_of_origin = ?,
                    marital_status = ?,
                    address = ?,
                    emergency_contact_name = ?,
                    emergency_contact_phone = ?,
                    emergency_contact_relationship = ?,
                    profile_updated_at = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ssssssssssssi", 
            $data['full_name'],
            $data['email'],
            $data['phone_number'],
            $data['date_of_birth'],
            $data['gender'],
            $data['state_of_origin'],
            $data['marital_status'],
            $data['address'],
            $data['emergency_contact_name'],
            $data['emergency_contact_phone'],
            $data['emergency_contact_relationship'],
            $data['profile_updated_at'],
            $id
        );
        
        return $stmt->execute();
    }

    public static function updateSingleField($id, $field, $value) {
        global $conn;
        if (!in_array($field, ['full_name', 'email', 'phone_number', 'address'])) {
            return false;
        }
        $sql = "UPDATE users SET `$field` = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("si", $value, $id);
        return $stmt->execute();
    }

    public static function getAdmins() {
        global $conn;
        $sql = "SELECT email FROM users WHERE is_admin = 1 AND email IS NOT NULL AND email != ''";
        $result = $conn->query($sql);
        $emails = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row['email'];
            }
        }
        return $emails;
    }

    public static function getProfilePicture($user_id) {
        $target_dir = __DIR__ . '/../src/assets/uploads/profile_pictures/';
        $web_path = '/app/src/assets/uploads/profile_pictures/';
        
        $extensions = ['jpg', 'jpeg', 'png'];
        foreach ($extensions as $ext) {
            if (file_exists($target_dir . "user_{$user_id}.{$ext}")) {
                return $web_path . "user_{$user_id}.{$ext}?v=" . time();
            }
        }
        
        return '/app/src/assets/images/Small Avatar.svg';
    }

    public static function incrementFailedLoginAttempts($matric_number) {
        global $conn;
        
        $user = self::getByMatricNumber($matric_number);
        if (!$user) return false;
        
        $new_attempts = $user['failed_login_attempts'] + 1;
        
        if ($new_attempts >= 5) {
            $sql = "UPDATE users SET failed_login_attempts = ?, account_locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $new_attempts, $user['id']);
        } else {
            $sql = "UPDATE users SET failed_login_attempts = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $new_attempts, $user['id']);
        }
        
        return $stmt->execute();
    }

    public static function resetFailedLoginAttempts($id) {
        global $conn;
        $sql = "UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL, last_login_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
