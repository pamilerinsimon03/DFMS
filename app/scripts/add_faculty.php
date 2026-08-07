<?php
/**
 * DFMS CLI Helper: Add Standalone Faculty (Or Optionally Map Depts & Create Admin)
 * 
 * Examples:
 *   1. Just create a Parent Faculty:
 *      php app/scripts/add_faculty.php "Faculty of Law" "LAW"
 * 
 *   2. Create Faculty & Create Admin:
 *      php app/scripts/add_faculty.php "Faculty of Law" "LAW" "" "ADMIN_LAW" "password123" "Law Dean" "law@ui.edu.ng"
 * 
 *   3. Create Faculty, Map Existing Depts & Create Admin:
 *      php app/scripts/add_faculty.php "Faculty of Technology" "TECH" "1,6" "ADMIN_TECH" "pass123" "Tech Officer" "tech@ui.edu.ng"
 */

require_once __DIR__ . '/../config/config.php';

global $conn;

if (!$conn) {
    die("Database connection failed.\n");
}

if ($argc < 3) {
    echo "=========================================================================\n";
    echo "DFMS Faculty Creation Helper\n";
    echo "=========================================================================\n";
    echo "Usage:\n";
    echo "  1. Add Parent Faculty Only:\n";
    echo "     php app/scripts/add_faculty.php <Name> <Code>\n\n";
    echo "  2. Add Faculty with Optional Departments and Admin:\n";
    echo "     php app/scripts/add_faculty.php <Name> <Code> [DeptIDs] [AdminMatric] [AdminPassword] [AdminName] [AdminEmail]\n\n";
    echo "Examples:\n";
    echo "  php app/scripts/add_faculty.php \"Faculty of Law\" \"LAW\"\n";
    echo "  php app/scripts/add_faculty.php \"Faculty of Pharmacy\" \"PHARM\" \"\" \"ADMIN_PHARM\" \"Pass123!\" \"Pharm Dean\" \"pharm@ui.edu.ng\"\n";
    echo "=========================================================================\n";
    exit(1);
}

$facultyName = $argv[1];
$facultyCode = strtoupper($argv[2]);

$rawDeptIds = $argv[3] ?? '';
$adminMatric = isset($argv[4]) ? strtoupper($argv[4]) : null;
$adminPass = $argv[5] ?? null;
$adminName = $argv[6] ?? null;
$adminEmail = $argv[7] ?? null;

// 1. Create Parent Faculty First
$stmt = $conn->prepare("INSERT INTO faculties (name, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
$stmt->bind_param("ss", $facultyName, $facultyCode);
if (!$stmt->execute()) {
    die("Failed to create faculty: " . $stmt->error . "\n");
}

// Get Faculty ID
$facRes = $conn->query("SELECT id FROM faculties WHERE code = '" . $conn->real_escape_string($facultyCode) . "'");
$faculty = $facRes ? $facRes->fetch_assoc() : null;

if (!$faculty) {
    die("Faculty lookup failed.\n");
}

$facultyId = (int)$faculty['id'];
echo "SUCCESS: Parent Faculty created/updated: '{$facultyName}' (ID: {$facultyId}, Code: {$facultyCode})\n";

// 2. Map Departments to Faculty (Optional)
if (!empty($rawDeptIds)) {
    $departmentIds = array_map('intval', explode(',', $rawDeptIds));
    $mapStmt = $conn->prepare("INSERT IGNORE INTO faculty_departments (faculty_id, department_id) VALUES (?, ?)");
    foreach ($departmentIds as $deptId) {
        if ($deptId > 0) {
            $mapStmt->bind_param("ii", $facultyId, $deptId);
            $mapStmt->execute();
            echo "   - Mapped Department ID {$deptId} to Faculty ID {$facultyId}\n";
        }
    }
}

// 3. Create Faculty Admin User (Optional)
if ($adminMatric && $adminPass && $adminName) {
    $passHash = password_hash($adminPass, PASSWORD_DEFAULT);
    $adminStmt = $conn->prepare("INSERT INTO users (matric_number, password, full_name, email, role, is_admin, faculty_id) VALUES (?, ?, ?, ?, 'faculty_admin', 1, ?) ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), role='faculty_admin', is_admin=1, faculty_id=VALUES(faculty_id)");
    $adminStmt->bind_param("ssssi", $adminMatric, $passHash, $adminName, $adminEmail, $facultyId);

    if ($adminStmt->execute()) {
        echo "   - Faculty Admin Created: {$adminName} ({$adminMatric})\n";
    } else {
        echo "   - Error creating faculty admin: " . $adminStmt->error . "\n";
    }
}

echo "Done.\n";
