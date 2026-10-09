<?php
require_once __DIR__ . '/../config/config.php';

class User {
    public static function generateUUID(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function resolveId($idOrPublicId): ?int {
        if (empty($idOrPublicId)) {
            return null;
        }
        if (is_numeric($idOrPublicId)) {
            return (int)$idOrPublicId;
        }
        global $conn;
        if (!$conn) return null;
        $stmt = $conn->prepare("SELECT id FROM users WHERE public_id = ?");
        if (!$stmt) return null;
        $stmt->bind_param("s", $idOrPublicId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? (int)$row['id'] : null;
    }

    public static function findByPublicId(string $publicId): ?array {
        global $conn;
        if (!$conn) return null;
        $sql = "
            SELECT 
                u.*, 
                d.name as department_name,
                d.code as department_code,
                f.name as faculty_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN faculties f ON u.faculty_id = f.id
            WHERE u.public_id = ?
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("s", $publicId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    public static function getByIdScoped($idOrPublicId, $actor = null): ?array {
        $id = self::resolveId($idOrPublicId);
        if (!$id) {
            return null;
        }
        $targetUser = self::getById($id);
        if (!$targetUser) {
            return null;
        }
        if ($actor === null && isset($_SESSION['user_id'])) {
            $actor = self::getById((int)$_SESSION['user_id']);
        }
        if ($actor !== null) {
            require_once __DIR__ . '/../utils/AccessScope.php';
            if (!AccessScope::isUserInScope($actor, $targetUser)) {
                return null;
            }
        }
        return $targetUser;
    }

    public static function getById($id) {
        global $conn;
        if (!$conn) return null;
        $numericId = self::resolveId($id);
        if (!$numericId) return null;
        $sql = "
            SELECT 
                u.*, 
                d.name as department_name,
                d.code as department_code,
                f.name as faculty_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN faculties f ON u.faculty_id = f.id
            WHERE u.id = ?
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("i", $numericId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
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
        
        $public_id = self::generateUUID();
        $activation_token = bin2hex(random_bytes(32));
        $is_activated = 0;
        $placeholder_password = '*ACTIVATION_REQUIRED*';
        
        $sql = "INSERT INTO users (public_id, matric_number, password, full_name, level, session, programme, program_type, mode, department_id, email, role, is_admin, activation_token, is_activated) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 0, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database prepare failed: ' . $conn->error];
        }
        
        $program_type = $student_data['program_type'] ?? 'Full Time';
        $mode = $student_data['mode'] ?? 'UTME';
        $session = $student_data['session'] ?? '2025/2026';
        $level = $student_data['level'] ?? 100;
        
        $stmt->bind_param("sssssssssissi", 
            $public_id,
            $student_data['matric_number'],
            $placeholder_password,
            $student_data['full_name'],
            $level,
            $session,
            $student_data['programme'],
            $program_type,
            $mode,
            $student_data['department_id'],
            $student_data['email'],
            $activation_token,
            $is_activated
        );
        
        if ($stmt->execute()) {
            return [
                'success' => true, 
                'message' => 'Student account created. Activation token generated.',
                'activation_token' => $activation_token,
                'public_id' => $public_id,
                'password' => $placeholder_password
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
            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 0, ?, 0)";
            $types .= "sssssssssiss";
            $params[] = self::generateUUID();
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

        $sql = "INSERT INTO users (public_id, matric_number, password, full_name, level, session, programme, program_type, mode, department_id, email, role, is_admin, activation_token, is_activated) VALUES " . implode(', ', $values);
        
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
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $clientUa = $_SERVER['HTTP_USER_AGENT'] ?? 'Web/Activation';
            self::recordConsent((int)$user['id'], 'core_academic_processing', true, $clientIp, $clientUa, '2025.1');
            self::recordConsent((int)$user['id'], 'email_notifications', true, $clientIp, $clientUa, '2025.1');
            self::recordConsent((int)$user['id'], 'portal_analytics', true, $clientIp, $clientUa, '2025.1');

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

    public static function getAllStudents($search = '', $page = 1, $per_page = 50, $department_id = null, $level = null, $mode = null, $program_type = null, $sort_by = 'full_name', $sort_order = 'asc', $start_date = null, $end_date = null, $actor = null) {
        global $conn;
        if (!$conn) return [];
        $offset = ($page - 1) * $per_page;
        $sql = "SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.is_admin = 0";
        
        $params = [];
        $types = '';

        if ($actor === null && isset($_SESSION['user_id'])) {
            $actor = self::getById((int)$_SESSION['user_id']);
        }

        require_once __DIR__ . '/../utils/AccessScope.php';

        if ($actor !== null) {
            $scope = AccessScope::buildUserScopeFilter($actor, $department_id ? (int)$department_id : null, 'u.');
            $sql .= " AND " . $scope['sql'];
            $types .= $scope['types'];
            foreach ($scope['params'] as $p) {
                $params[] = $p;
            }
        } elseif ($department_id) {
            $sql .= " AND u.department_id = ?";
            $params[] = (int)$department_id;
            $types .= 'i';
        }

        if ($level) {
            $sql .= " AND u.level = ?";
            $params[] = (int)$level;
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

        // Whitelist sort fields against SQL injection
        $allowed_sort_by = ['full_name', 'matric_number', 'level', 'session', 'programme', 'created_at'];
        $clean_sort_by = in_array($sort_by, $allowed_sort_by, true) ? $sort_by : 'full_name';
        $clean_sort_order = strtolower($sort_order) === 'desc' ? 'DESC' : 'ASC';

        $sql .= " ORDER BY u.{$clean_sort_by} {$clean_sort_order} LIMIT ? OFFSET ?";
        $params[] = (int)$per_page;
        $params[] = (int)$offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function countAllStudents($search = '', $department_id = null, $level = null, $mode = null, $program_type = null, $start_date = null, $end_date = null, $actor = null) {
        global $conn;
        if (!$conn) return 0;
        $sql = "SELECT COUNT(*) as total FROM users u WHERE u.is_admin = 0";

        $params = [];
        $types = '';

        if ($actor === null && isset($_SESSION['user_id'])) {
            $actor = self::getById((int)$_SESSION['user_id']);
        }

        require_once __DIR__ . '/../utils/AccessScope.php';

        if ($actor !== null) {
            $scope = AccessScope::buildUserScopeFilter($actor, $department_id ? (int)$department_id : null, 'u.');
            $sql .= " AND " . $scope['sql'];
            $types .= $scope['types'];
            foreach ($scope['params'] as $p) {
                $params[] = $p;
            }
        } elseif ($department_id) {
            $sql .= " AND u.department_id = ?";
            $params[] = (int)$department_id;
            $types .= 'i';
        }

        if ($level) {
            $sql .= " AND u.level = ?";
            $params[] = (int)$level;
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
        
        $stmt = $conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? (int)$result['total'] : 0;
    }

    public static function updateProfile($id, $full_name, $level, $programme, $session, $department_id, $program_type, $mode) {
        global $conn;
        if (self::isProcessingRestricted((int)$id)) {
            return false;
        }

        $oldUser = self::getById((int)$id);

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
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            require_once __DIR__ . '/../services/AuditService.php';
            $diff = [];
            if ($oldUser) {
                foreach ([
                    'full_name' => $full_name,
                    'level' => $level,
                    'programme' => $programme,
                    'session' => $session,
                    'department_id' => $department_id,
                    'program_type' => $program_type,
                    'mode' => $mode
                ] as $k => $v) {
                    if (isset($oldUser[$k]) && (string)$oldUser[$k] !== (string)$v) {
                        $diff[$k] = ['old' => $oldUser[$k], 'new' => $v];
                    }
                }
            }

            AuditService::log(
                'data_rectification',
                [
                    'target_user_id'   => (int)$id,
                    'action'           => 'update_profile',
                    'diff'             => $diff,
                    'statutory_clause' => 'NDPA 2023 s.36'
                ],
                $_SESSION['role'] ?? 'admin',
                'user:' . $id,
                $_SERVER['REQUEST_URI'] ?? '/app/controllers/profile.php',
                'success'
            );
        }

        return $success;
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
        if (self::isProcessingRestricted((int)$id)) {
            return false;
        }

        $oldUser = self::getById((int)$id);
        
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
        
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            require_once __DIR__ . '/../services/AuditService.php';
            $diff = [];
            if ($oldUser) {
                foreach ($data as $k => $v) {
                    if (array_key_exists($k, $oldUser) && (string)$oldUser[$k] !== (string)$v) {
                        $diff[$k] = ['old' => $oldUser[$k], 'new' => $v];
                    }
                }
            }

            AuditService::log(
                'data_rectification',
                [
                    'target_user_id'   => (int)$id,
                    'action'           => 'update_student_profile',
                    'diff'             => $diff,
                    'statutory_clause' => 'NDPA 2023 s.36'
                ],
                $_SESSION['role'] ?? 'student',
                'user:' . $id,
                $_SERVER['REQUEST_URI'] ?? '/app/controllers/profile.php',
                'success'
            );
        }

        return $success;
    }

    public static function updateSingleField($id, $field, $value) {
        global $conn;
        if (self::isProcessingRestricted((int)$id)) {
            return false;
        }

        if (!in_array($field, ['full_name', 'email', 'phone_number', 'address'], true)) {
            return false;
        }

        $oldUser = self::getById((int)$id);
        $oldVal = $oldUser[$field] ?? null;

        $sql = "UPDATE users SET `$field` = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("si", $value, $id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            require_once __DIR__ . '/../services/AuditService.php';
            AuditService::log(
                'data_rectification',
                [
                    'target_user_id'   => (int)$id,
                    'field'            => $field,
                    'old_value'        => $oldVal,
                    'new_value'        => $value,
                    'statutory_clause' => 'NDPA 2023 s.36'
                ],
                $_SESSION['role'] ?? 'student',
                'user:' . $id,
                $_SERVER['REQUEST_URI'] ?? '/app/controllers/data_subject_rights.php',
                'success'
            );
        }

        return $success;
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

    public static function recordConsent(int $userId, string $consentType, bool $given, ?string $ip = null, ?string $ua = null, string $policyVersion = '2025.1'): bool
    {
        global $conn;
        if (!$conn) return false;

        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $ua = $ua ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'System');
        $givenInt = $given ? 1 : 0;

        $stmt = $conn->prepare("
            INSERT INTO consent_records (user_id, consent_type, consent_given, policy_version, ip_address, user_agent, consent_date)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmt) return false;

        $stmt->bind_param("isisss", $userId, $consentType, $givenInt, $policyVersion, $ip, $ua);
        $res = $stmt->execute();
        $stmt->close();

        require_once __DIR__ . '/../services/AuditService.php';
        AuditService::log(
            'consent_recorded',
            [
                'user_id'        => $userId,
                'consent_type'   => $consentType,
                'consent_given'  => $given,
                'policy_version' => $policyVersion,
                'ip'             => $ip
            ],
            'student',
            'user:' . $userId,
            $_SERVER['REQUEST_URI'] ?? '/app/controllers/activate.php',
            'success'
        );

        return $res;
    }

    public static function withdrawConsent(int $userId, string $consentType): array
    {
        global $conn;
        if (!$conn) return ['success' => false, 'message' => 'Database connection unavailable'];

        // Core academic record processing cannot be withdrawn (NDPA s.25(1)(b))
        if ($consentType === 'core_academic_processing') {
            return [
                'success' => false,
                'message' => 'Core academic record processing cannot be withdrawn. It is maintained pursuant to statutory legal obligations under NDPA 2023 Section 25(1)(b).'
            ];
        }

        $stmt = $conn->prepare("
            UPDATE consent_records 
            SET consent_given = 0, consent_withdrawn_at = NOW() 
            WHERE user_id = ? AND consent_type = ? AND consent_given = 1
        ");
        if (!$stmt) {
            return ['success' => false, 'message' => 'DB error: ' . $conn->error];
        }

        $stmt->bind_param("is", $userId, $consentType);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        require_once __DIR__ . '/../services/AuditService.php';
        AuditService::log(
            'consent_withdrawn',
            [
                'user_id'      => $userId,
                'consent_type' => $consentType,
                'withdrawn_at' => date('Y-m-d H:i:s')
            ],
            'student',
            'user:' . $userId,
            $_SERVER['REQUEST_URI'] ?? '/app/controllers/data_subject_rights.php',
            'success'
        );

        return [
            'success' => true,
            'message' => "Consent for '{$consentType}' successfully withdrawn."
        ];
    }

    public static function getUserConsents(int $userId): array
    {
        global $conn;
        if (!$conn) return [];

        $stmt = $conn->prepare("SELECT * FROM consent_records WHERE user_id = ? ORDER BY id DESC");
        if (!$stmt) return [];

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    public static function isProcessingRestricted(int $userId): bool
    {
        $user = self::getById($userId);
        return !empty($user['processing_restricted']);
    }

    public static function setProcessingRestriction(int $userId, bool $restricted, string $reason = '', ?int $actorId = null): array
    {
        global $conn;
        if (!$conn) return ['success' => false, 'message' => 'Database connection unavailable'];

        $user = self::getById($userId);
        if (!$user) return ['success' => false, 'message' => 'User not found'];

        $oldVal = !empty($user['processing_restricted']) ? 1 : 0;
        $newVal = $restricted ? 1 : 0;

        $stmt = $conn->prepare("UPDATE users SET processing_restricted = ? WHERE id = ?");
        if (!$stmt) return ['success' => false, 'message' => 'DB error: ' . $conn->error];

        $stmt->bind_param("ii", $newVal, $userId);
        $stmt->execute();
        $stmt->close();

        require_once __DIR__ . '/../services/AuditService.php';
        AuditService::log(
            'processing_restriction_toggled',
            [
                'target_user_id'   => $userId,
                'restricted'       => $restricted,
                'previous_state'   => $oldVal,
                'reason'           => $reason,
                'changed_by'       => $actorId ?? ($_SESSION['user_id'] ?? $userId),
                'statutory_clause' => 'NDPA 2023 s.37'
            ],
            $_SESSION['role'] ?? 'admin',
            'user:' . $userId,
            $_SERVER['REQUEST_URI'] ?? '/app/controllers/data_subject_rights.php',
            'success'
        );

        return [
            'success' => true,
            'message' => $restricted ? 'Processing restriction applied successfully.' : 'Processing restriction lifted.'
        ];
    }
}
