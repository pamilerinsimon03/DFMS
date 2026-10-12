<?php
/**
 * DFMS Centralized RBAC Permission Matrix & Role Hierarchy
 * 
 * Strict fail-closed policy: Any route under /app/controllers/ not in
 * $publicAllowlist or $routePermissions is rejected immediately.
 */

return [
    // Role inheritance (higher roles inherit capabilities of lower roles)
    'hierarchy' => [
        'super_admin'      => ['faculty_admin', 'department_admin', 'student'],
        'faculty_admin'    => ['department_admin', 'student'],
        'department_admin' => ['student'],
        'student'          => []
    ],

    // Unauthenticated public routes
    'public_allowlist' => [
        'login.php',
        'reset_password.php',
        'logout.php',
        'activate.php',
        'maintenance.php'
    ],

    // Strict route permissions (minimum required role or explicit allowed roles)
    'routes' => [
        // Super Admin only
        'backup.php'                   => ['super_admin'],
        'session_rollover.php'         => ['super_admin'],
        'audit_log_viewer.php'         => ['super_admin'],

        // Administrative routes (Super, Faculty, and Department Admins)
        'admin.php'                    => ['department_admin'],
        'analytics.php'                => ['department_admin'],
        'students.php'                 => ['department_admin'],
        'student_profile.php'          => ['department_admin'],
        'add_student.php'              => ['department_admin'],
        'bulk_import.php'              => ['department_admin'],
        'verifications.php'            => ['department_admin'],
        'verification_detail.php'      => ['department_admin'],
        'download_document.php'        => ['department_admin'],
        'download_all_documents.php'   => ['department_admin'],
        'download_template.php'        => ['department_admin'],
        'get_document_details.php'     => ['department_admin'],
        'file_management.php'          => ['department_admin'],
        'settings.php'                 => ['department_admin'],
        'update_passwords.php'         => ['department_admin'],

        // Shared authenticated routes
        'dashboard.php'                => ['student', 'department_admin'],
        'data_subject_rights.php'      => ['student', 'department_admin'],
        'secure_file_viewer.php'       => ['student', 'department_admin'],
        'notifications_mark_read.php'  => ['student', 'department_admin'],

        // Student-only routes
        'profile.php'                  => ['student'],
        'uploads.php'                  => ['student'],
        'upload_file.php'              => ['student'],
        'student_settings.php'         => ['student'],
        'student_profile_update.php'   => ['student']
    ],

    // Granular Object & Action Matrix
    'capabilities' => [
        'document' => [
            'view'        => ['student' => 'own', 'department_admin' => 'department', 'faculty_admin' => 'faculty', 'super_admin' => 'all'],
            'download'    => ['student' => 'own', 'department_admin' => 'department', 'faculty_admin' => 'faculty', 'super_admin' => 'all'],
            'verify'      => ['student' => 'none', 'department_admin' => 'department', 'faculty_admin' => 'faculty', 'super_admin' => 'all'],
            'upload'      => ['student' => 'own', 'department_admin' => 'none', 'faculty_admin' => 'none', 'super_admin' => 'none'],
            'reupload'    => ['student' => 'own', 'department_admin' => 'none', 'faculty_admin' => 'none', 'super_admin' => 'none'],
            'delete'      => ['student' => 'none', 'department_admin' => 'none', 'faculty_admin' => 'none', 'super_admin' => 'all']
        ],
        'student_profile' => [
            'view'        => ['student' => 'own', 'department_admin' => 'department', 'faculty_admin' => 'faculty', 'super_admin' => 'all'],
            'edit'        => ['student' => 'own', 'department_admin' => 'department', 'faculty_admin' => 'faculty', 'super_admin' => 'all']
        ]
    ]
];
