<?php
/**
 * DFMS CLI Helper: Add Department & Link Directly to a Faculty
 * Usage example:
 *   php app/scripts/add_department.php "Software Engineering" "SE" 1
 */

require_once __DIR__ . '/../config/config.php';

global $conn;

if (!$conn) {
    die("Database connection failed.\n");
}

if ($argc < 4) {
    echo "=========================================================================\n";
    echo "DFMS Department Creation Helper\n";
    echo "=========================================================================\n";
    echo "Usage: php app/scripts/add_department.php <DepartmentName> <DepartmentCode> <FacultyID>\n\n";
    echo "Example:\n";
    echo "  php app/scripts/add_department.php \"Software Engineering\" \"SE\" 1\n";
    echo "=========================================================================\n";
    exit(1);
}

$deptName = $argv[1];
$deptCode = strtoupper($argv[2]);
$facultyId = (int)$argv[3];

// 1. Check Faculty Exists
$facCheck = $conn->prepare("SELECT id, name FROM faculties WHERE id = ?");
$facCheck->bind_param("i", $facultyId);
$facCheck->execute();
$fac = $facCheck->get_result()->fetch_assoc();

if (!$fac) {
    die("Error: Faculty ID {$facultyId} does not exist in the database.\n");
}

// 2. Insert into departments table
$stmt = $conn->prepare("INSERT INTO departments (name, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE code = VALUES(code)");
$stmt->bind_param("ss", $deptName, $deptCode);

if (!$stmt->execute()) {
    die("Failed to create department: " . $stmt->error . "\n");
}

// Get Department ID
$deptRes = $conn->query("SELECT id FROM departments WHERE name = '" . $conn->real_escape_string($deptName) . "'");
$dept = $deptRes ? $deptRes->fetch_assoc() : null;

if (!$dept) {
    die("Error looking up newly created department.\n");
}

$deptId = (int)$dept['id'];

// 3. Link Department to Faculty in faculty_departments table
$linkStmt = $conn->prepare("INSERT IGNORE INTO faculty_departments (faculty_id, department_id) VALUES (?, ?)");
$linkStmt->bind_param("ii", $facultyId, $deptId);

if ($linkStmt->execute()) {
    echo "Department Created & Linked Successfully!\n";
    echo "   - Department ID:   {$deptId}\n";
    echo "   - Department Name: {$deptName}\n";
    echo "   - Department Code: {$deptCode}\n";
    echo "   - Under Faculty:   {$fac['name']} (Faculty ID: {$facultyId})\n";
} else {
    echo "Failed to link department to faculty: " . $linkStmt->error . "\n";
}

echo "Done.\n";
