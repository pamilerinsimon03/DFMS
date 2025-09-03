<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

// Get departments for the template
$departments = User::getAllDepartments();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_import_template.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, [
    'matric_number',
    'full_name', 
    'level',
    'session',
    'programme',
    'department_id',
    'program_type',
    'mode',
    'email'
]);

// Write example rows
fputcsv($output, [
    'U123456',
    'John Doe',
    '100L',
    '2023/2024',
    'Bachelor of Science (Computer Science)',
    '1',
    'Undergraduate',
    'Regular',
    'john.doe@student.edu'
]);

fputcsv($output, [
    'U123457',
    'Jane Smith',
    '200L',
    '2023/2024',
    'Bachelor of Arts (Psychology)',
    '2',
    'Undergraduate',
    'Regular',
    'jane.smith@student.edu'
]);

fputcsv($output, [
    'U123458',
    'Mike Johnson',
    '300L',
    '2023/2024',
    'Bachelor of Arts (English)',
    '3',
    'Undergraduate',
    'Regular',
    'mike.johnson@student.edu'
]);

// Write department reference
fputcsv($output, []); // Empty row
fputcsv($output, ['Department Reference:']);
fputcsv($output, ['department_id', 'department_name']);

foreach ($departments as $dept) {
    fputcsv($output, [$dept['id'], $dept['name']]);
}

fclose($output);
exit();
