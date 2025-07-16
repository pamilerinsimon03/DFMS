<?php
require_once __DIR__ . '/../config/config.php';

global $conn;
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "
");
}

echo "Searching for documents with blank status...
";

// Find all documents where status is empty or NULL, and is not 'Rejected'
$sql = "UPDATE documents 
        SET status = 'Pending Quality Check' 
        WHERE (status IS NULL OR status = '') AND rejection_reason IS NULL";

$result = $conn->query($sql);

if ($result === false) {
    die("Error updating records: " . $conn->error . "
");
}

$affectedRows = $conn->affected_rows;

if ($affectedRows > 0) {
    echo "Successfully repaired " . $affectedRows . " documents.
";
    echo "You can now run the 'run_quality_checks.php' script to process them.
";
} else {
    echo "No documents with blank statuses were found to repair.
";
}

$conn->close();