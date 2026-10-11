<?php
/**
 * DFMS Demo Account Seeder & Credential Generator
 * 
 * Generates secure, cryptographically random initial credentials for administrative
 * and demo student accounts without hardcoded or surname-derived passwords.
 * 
 * Usage:
 *   php app/scripts/seed_demo.php
 *   php app/scripts/seed_demo.php --json
 */

if (php_sapi_name() !== 'cli') {
    die("Error: This script must be run from the command line interface.\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

global $conn;

if (!$conn || $conn->connect_error) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$isJson = in_array('--json', $argv, true);

function generateSecurePassword(int $length = 16): string {
    $bytes = random_bytes($length);
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[ord($bytes[$i]) % strlen($chars)];
    }
    // Ensure at least one uppercase, lowercase, digit, and special char
    return $password . 'Aa1!';
}

// 1. Admin Users Setup
$admins = [
    [
        'matric_number' => 'SUPER_ADMIN',
        'full_name'     => 'Super Administrator',
        'email'         => 'super_admin@dfms.ui.edu.ng',
        'role'          => 'super_admin',
        'department_id' => null,
        'faculty_id'    => null
    ],
    [
        'matric_number' => 'E0500627',
        'full_name'     => 'Professor Afolabi Samuel',
        'email'         => 'faculty_admin@dfms.ui.edu.ng',
        'role'          => 'faculty_admin',
        'department_id' => 1,
        'faculty_id'    => 1
    ],
    [
        'matric_number' => 'ADMIN_PSYC',
        'full_name'     => 'Psychology Department Admin',
        'email'         => 'psyc_admin@dfms.ui.edu.ng',
        'role'          => 'department_admin',
        'department_id' => 2,
        'faculty_id'    => 1
    ],
    [
        'matric_number' => 'ADMIN_ENG',
        'full_name'     => 'English Department Admin',
        'email'         => 'eng_admin@dfms.ui.edu.ng',
        'role'          => 'department_admin',
        'department_id' => 3,
        'faculty_id'    => 2
    ],
    [
        'matric_number' => 'ADMIN_SOC',
        'full_name'     => 'Sociology Department Admin',
        'email'         => 'soc_admin@dfms.ui.edu.ng',
        'role'          => 'department_admin',
        'department_id' => 4,
        'faculty_id'    => 1
    ],
    [
        'matric_number' => 'ADMIN_LING',
        'full_name'     => 'Linguistics Department Admin',
        'email'         => 'ling_admin@dfms.ui.edu.ng',
        'role'          => 'department_admin',
        'department_id' => 5,
        'faculty_id'    => 2
    ]
];

$adminCredentials = [];

foreach ($admins as $admin) {
    $rawPassword = generateSecurePassword(16);
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
    $publicId = User::generateUUID();

    $stmt = $conn->prepare("
        INSERT INTO users (public_id, matric_number, password, full_name, email, role, is_admin, is_activated, department_id, level, session, programme)
        VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?, 'N/A', '2023/2024', 'Administration')
        ON DUPLICATE KEY UPDATE 
            password = VALUES(password),
            role = VALUES(role),
            is_admin = 1,
            is_activated = 1,
            department_id = VALUES(department_id)
    ");
    $stmt->bind_param("ssssssi", $publicId, $admin['matric_number'], $hashedPassword, $admin['full_name'], $admin['email'], $admin['role'], $admin['department_id']);
    $stmt->execute();
    $stmt->close();

    $adminCredentials[] = [
        'matric_number' => $admin['matric_number'],
        'role'          => $admin['role'],
        'full_name'     => $admin['full_name'],
        'email'         => $admin['email'],
        'password'      => $rawPassword
    ];
}

// 2. Demo Student Users Setup (Activation Tokens)
$students = [
    ['matric' => 'U123001', 'name' => 'CS Student One',   'dept' => 1, 'email' => 'cs.student1@dfms.ui.edu.ng', 'level' => 100],
    ['matric' => 'U123002', 'name' => 'CS Student Two',   'dept' => 1, 'email' => 'cs.student2@dfms.ui.edu.ng', 'level' => 200],
    ['matric' => 'U123003', 'name' => 'Psychology One',   'dept' => 2, 'email' => 'psyc.student1@dfms.ui.edu.ng', 'level' => 300],
    ['matric' => 'U123004', 'name' => 'Psychology Two',   'dept' => 2, 'email' => 'psyc.student2@dfms.ui.edu.ng', 'level' => 400],
    ['matric' => 'U123005', 'name' => 'English One',      'dept' => 3, 'email' => 'eng.student1@dfms.ui.edu.ng', 'level' => 100],
    ['matric' => 'U123006', 'name' => 'English Two',      'dept' => 3, 'email' => 'eng.student2@dfms.ui.edu.ng', 'level' => 200],
    ['matric' => 'U123007', 'name' => 'Sociology One',    'dept' => 4, 'email' => 'soc.student1@dfms.ui.edu.ng', 'level' => 300],
    ['matric' => 'U123008', 'name' => 'Sociology Two',    'dept' => 4, 'email' => 'soc.student2@dfms.ui.edu.ng', 'level' => 400],
    ['matric' => 'U123009', 'name' => 'Linguistics One',  'dept' => 5, 'email' => 'ling.student1@dfms.ui.edu.ng', 'level' => 100],
    ['matric' => 'U123010', 'name' => 'Linguistics Two',  'dept' => 5, 'email' => 'ling.student2@dfms.ui.edu.ng', 'level' => 200],
];

$studentCredentials = [];
$baseUrl = 'http://localhost';

foreach ($students as $stu) {
    $activationToken = bin2hex(random_bytes(32));
    $publicId = User::generateUUID();
    $placeholderPassword = '*ACTIVATION_REQUIRED*';

    $stmt = $conn->prepare("
        INSERT INTO users (public_id, matric_number, password, full_name, email, level, session, programme, department_id, role, is_admin, is_activated, activation_token, activation_expires_at)
        VALUES (?, ?, ?, ?, ?, ?, '2023/2024', 'Bachelor Degree', ?, 'student', 0, 0, ?, DATE_ADD(NOW(), INTERVAL 48 HOUR))
        ON DUPLICATE KEY UPDATE
            password = VALUES(password),
            is_activated = 0,
            activation_token = VALUES(activation_token),
            activation_expires_at = VALUES(activation_expires_at)
    ");
    $stmt->bind_param("ssssssis", $publicId, $stu['matric'], $placeholderPassword, $stu['name'], $stu['email'], $stu['level'], $stu['dept'], $activationToken);
    $stmt->execute();
    $stmt->close();

    $activationUrl = "{$baseUrl}/app/controllers/activate.php?token={$activationToken}";
    $studentCredentials[] = [
        'matric_number'  => $stu['matric'],
        'full_name'      => $stu['name'],
        'email'          => $stu['email'],
        'status'         => 'Pending Activation',
        'activation_url' => $activationUrl,
        'activation_token' => $activationToken
    ];
}

$output = [
    'generated_at' => date('c'),
    'admins'       => $adminCredentials,
    'students'     => $studentCredentials
];

if ($isJson) {
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo "========================================================================================\n";
echo "           DFMS CRYPTOGRAPHIC DEMO CREDENTIAL SEEDER (W8)\n";
echo "========================================================================================\n";
echo "Administrative Accounts (Cryptographically Random Passwords Generated):\n";
printf("%-15s %-20s %-22s %s\n", "Matric Number", "Role", "Generated Password", "Full Name");
echo str_repeat("-", 88) . "\n";
foreach ($adminCredentials as $adm) {
    printf("%-15s %-20s %-22s %s\n", $adm['matric_number'], $adm['role'], $adm['password'], $adm['full_name']);
}

echo "\n" . str_repeat("=", 88) . "\n";
echo "Student Accounts (Zero Default Passwords - Single-Use Activation Links Required):\n";
printf("%-12s %-22s %s\n", "Matric", "Status", "Activation URL");
echo str_repeat("-", 88) . "\n";
foreach ($studentCredentials as $stu) {
    printf("%-12s %-22s %s\n", $stu['matric_number'], $stu['status'], $stu['activation_url']);
}
echo "========================================================================================\n";
echo "Notice: Passwords displayed above are generated once per seeding. Store securely.\n";
echo "========================================================================================\n";
exit(0);
