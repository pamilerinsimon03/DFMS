<?php
/**
 * DFMS Dynamic Query-Level Access Scope Helper
 * 
 * Enforces two-stage authorization: Translates active session roles
 * into parameterized SQL query predicates for defense-in-depth object scoping.
 */

class AccessScope
{
    /**
     * Resolve normalized role from user record
     */
    public static function resolveRole(array $user): string
    {
        if (($user['matric_number'] ?? '') === 'SUPER_ADMIN' || ($user['role'] ?? '') === 'super_admin') {
            return 'super_admin';
        }
        
        $role = $user['role'] ?? 'student';
        $isAdmin = (int)($user['is_admin'] ?? 0);

        if ($role === 'faculty_admin' || (!empty($user['faculty_id']) && $isAdmin === 1)) {
            return 'faculty_admin';
        }

        if ($role === 'department_admin' || $role === 'admin' || $isAdmin === 1) {
            return 'department_admin';
        }

        return 'student';
    }

    /**
     * Fetch list of department IDs mapped to a faculty
     */
    public static function getFacultyDepartmentIds(int $facultyId): array
    {
        global $conn;
        if (!$conn) return [];

        $stmt = $conn->prepare("SELECT department_id FROM faculty_departments WHERE faculty_id = ?");
        if (!$stmt) return [];

        $stmt->bind_param("i", $facultyId);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int)$row['department_id'];
        }
        $stmt->close();
        return $ids;
    }

    /**
     * Build parameterized SQL filter for documents table based on user role and optional requested department filter
     * 
     * @param array $user Authenticated session user
     * @param int|null $requestedDeptId Optional department ID requested by UI/filter
     * @param string $prefix Table alias prefix, e.g. 'u.' or 'd.'
     * @return array ['sql' => string, 'types' => string, 'params' => array]
     */
    public static function buildDocumentScopeFilter(array $user, ?int $requestedDeptId = null, string $prefix = 'u.'): array
    {
        $role = self::resolveRole($user);
        $p = $prefix ? rtrim($prefix, '.') . '.' : '';

        if ($role === 'super_admin') {
            if ($requestedDeptId !== null && $requestedDeptId > 0) {
                return [
                    'sql'    => "{$p}department_id = ?",
                    'types'  => 'i',
                    'params' => [(int)$requestedDeptId]
                ];
            }
            return ['sql' => '1=1', 'types' => '', 'params' => []];
        }

        if ($role === 'faculty_admin') {
            $facultyId = (int)($user['faculty_id'] ?? 0);
            $allowedDepts = self::getFacultyDepartmentIds($facultyId);
            if (empty($allowedDepts)) {
                return ['sql' => '1=0', 'types' => '', 'params' => []];
            }

            if ($requestedDeptId !== null && $requestedDeptId > 0) {
                if (in_array((int)$requestedDeptId, $allowedDepts, true)) {
                    return [
                        'sql'    => "{$p}department_id = ?",
                        'types'  => 'i',
                        'params' => [(int)$requestedDeptId]
                    ];
                }
                // Out of faculty scope: fail closed
                return ['sql' => '1=0', 'types' => '', 'params' => []];
            }

            $placeholders = implode(',', array_fill(0, count($allowedDepts), '?'));
            return [
                'sql'    => "{$p}department_id IN ({$placeholders})",
                'types'  => str_repeat('i', count($allowedDepts)),
                'params' => $allowedDepts
            ];
        }

        if ($role === 'department_admin') {
            $deptId = (int)($user['department_id'] ?? 0);
            return [
                'sql'    => "{$p}department_id = ?",
                'types'  => 'i',
                'params' => [$deptId]
            ];
        }

        // Student scope: only their own documents
        $userId = (int)($user['id'] ?? 0);
        $docPrefix = ($prefix === 'u.') ? 'd.' : $p;
        return [
            'sql'    => "{$docPrefix}user_id = ?",
            'types'  => 'i',
            'params' => [$userId]
        ];
    }

    /**
     * Build parameterized SQL filter for users table based on user role and optional requested department filter
     * 
     * @param array $user Authenticated session user
     * @param int|null $requestedDeptId Optional department ID requested by UI/filter
     * @param string $prefix Table alias prefix, e.g. 'u.'
     * @return array ['sql' => string, 'types' => string, 'params' => array]
     */
    public static function buildUserScopeFilter(array $user, ?int $requestedDeptId = null, string $prefix = 'u.'): array
    {
        $role = self::resolveRole($user);
        $p = $prefix ? rtrim($prefix, '.') . '.' : '';

        if ($role === 'super_admin') {
            if ($requestedDeptId !== null && $requestedDeptId > 0) {
                return [
                    'sql'    => "{$p}department_id = ?",
                    'types'  => 'i',
                    'params' => [(int)$requestedDeptId]
                ];
            }
            return ['sql' => '1=1', 'types' => '', 'params' => []];
        }

        if ($role === 'faculty_admin') {
            $facultyId = (int)($user['faculty_id'] ?? 0);
            $allowedDepts = self::getFacultyDepartmentIds($facultyId);
            if (empty($allowedDepts)) {
                return ['sql' => '1=0', 'types' => '', 'params' => []];
            }

            if ($requestedDeptId !== null && $requestedDeptId > 0) {
                if (in_array((int)$requestedDeptId, $allowedDepts, true)) {
                    return [
                        'sql'    => "{$p}department_id = ?",
                        'types'  => 'i',
                        'params' => [(int)$requestedDeptId]
                    ];
                }
                return ['sql' => '1=0', 'types' => '', 'params' => []];
            }

            $placeholders = implode(',', array_fill(0, count($allowedDepts), '?'));
            return [
                'sql'    => "{$p}department_id IN ({$placeholders})",
                'types'  => str_repeat('i', count($allowedDepts)),
                'params' => $allowedDepts
            ];
        }

        if ($role === 'department_admin') {
            $deptId = (int)($user['department_id'] ?? 0);
            return [
                'sql'    => "{$p}department_id = ?",
                'types'  => 'i',
                'params' => [$deptId]
            ];
        }

        // Student scope: only their own user record
        $userId = (int)($user['id'] ?? 0);
        return [
            'sql'    => "{$p}id = ?",
            'types'  => 'i',
            'params' => [$userId]
        ];
    }

    /**
     * Generate SQL scope predicate for documents table
     */
    public static function getDocumentScope(array $user, string $prefix = ''): array
    {
        return self::buildDocumentScopeFilter($user, null, $prefix);
    }

    /**
     * Generate SQL scope predicate for users/students table
     */
    public static function getUserScope(array $user, string $prefix = ''): array
    {
        return self::buildUserScopeFilter($user, null, $prefix);
    }

    /**
     * Validate whether a specific document record falls within the user's scope
     */
    public static function isDocumentInScope(array $user, array $doc): bool
    {
        $role = self::resolveRole($user);

        if ($role === 'super_admin') {
            return true;
        }

        if ($role === 'student') {
            return (int)($doc['user_id'] ?? 0) === (int)($user['id'] ?? -1);
        }

        $docDeptId = !empty($doc['department_id'])
            ? (int)$doc['department_id']
            : null;

        if ($docDeptId === null && !empty($doc['user_id'])) {
            require_once __DIR__ . '/../models/User.php';
            $owner = User::getById((int)$doc['user_id']);
            $docDeptId = $owner ? (int)$owner['department_id'] : -1;
        }

        if ($role === 'department_admin') {
            return $docDeptId === (int)($user['department_id'] ?? -1);
        }

        if ($role === 'faculty_admin') {
            $facultyId = (int)($user['faculty_id'] ?? 0);
            $allowedDepts = self::getFacultyDepartmentIds($facultyId);
            return in_array($docDeptId, $allowedDepts, true);
        }

        return false;
    }

    /**
     * Validate whether a target user record falls within the current user's scope
     */
    public static function isUserInScope(array $user, array $targetUser): bool
    {
        $role = self::resolveRole($user);

        if ($role === 'super_admin') {
            return true;
        }

        if ($role === 'student') {
            return (int)($targetUser['id'] ?? 0) === (int)($user['id'] ?? -1);
        }

        $targetDeptId = (int)($targetUser['department_id'] ?? 0);

        if ($role === 'department_admin') {
            return $targetDeptId === (int)($user['department_id'] ?? -1);
        }

        if ($role === 'faculty_admin') {
            $facultyId = (int)($user['faculty_id'] ?? 0);
            $allowedDepts = self::getFacultyDepartmentIds($facultyId);
            return in_array($targetDeptId, $allowedDepts, true);
        }

        return false;
    }
}
