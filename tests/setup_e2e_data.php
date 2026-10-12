<?php
// setup_e2e_data.php
// Connect to test DBs
$dbs = ['dfms_test', 'dfms_local'];

foreach ($dbs as $db) {
    echo "Seeding $db...\n";
    $conn = new mysqli('localhost', 'root', '', $db);
    if ($conn->connect_error) {
        echo "Skipping $db: " . $conn->connect_error . "\n";
        continue;
    }

    // Create E2E User
    $matric = 'E2E/2026/001';
    $pass = 'password123';
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $name = 'E2E Student';

    // Clean up first
    $conn->query("DELETE FROM users WHERE matric_number = '$matric'");

    // Insert
    $sql = "INSERT INTO users (matric_number, password, full_name, level, session, programme, program_type, mode, department_id, email, role, is_admin) 
            VALUES ('$matric', '$hash', '$name', '100', '2025/2026', 'Computer Science', 'Full Time', 'UTME', 1, 'e2e@test.com', 'student', 0)";

    if ($conn->query($sql)) {
        echo "E2E User created: $matric / $pass\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }

    // Create E2E Admin
    $adminMatric = 'ADMIN/001';
    $adminName = 'E2E Admin';
    $conn->query("DELETE FROM users WHERE matric_number = '$adminMatric'");
    $sqlAdmin = "INSERT INTO users (matric_number, password, full_name, level, session, programme, program_type, mode, department_id, email, role, is_admin) 
            VALUES ('$adminMatric', '$hash', '$adminName', 'Staff', '2025/2026', 'Admin Dept', 'Staff', 'Regular', 1, 'admin@test.com', 'admin', 1)";
            
    if ($conn->query($sqlAdmin)) {
        echo "E2E Admin created: $adminMatric / $pass\n";
    } else {
        echo "Error Admin: " . $conn->error . "\n";
    }
    
    // Create File Requirement for Department 1
    $reqName = 'E2E Upload Project';
    $conn->query("DELETE FROM file_requirements WHERE document_name = '$reqName'");
    $sqlReq = "INSERT INTO file_requirements (department_id, document_name, description, due_date, created_by, is_active)
               VALUES (1, '$reqName', 'Required for graduation', '2030-01-01', 1, 1)";
    if ($conn->query($sqlReq)) {
        echo "E2E Requirement created in $db\n";
    } else {
        echo "Error Requirement: " . $conn->error . "\n";
    }
    
    $conn->close();
}