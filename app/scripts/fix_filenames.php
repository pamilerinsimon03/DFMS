<?php
require_once __DIR__ . '/../config/config.php';

global $conn;
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "
");
}

echo "Connected to database.
";

// Fetch all documents where file_name is NULL or empty
$result = $conn->query("SELECT id, file_path FROM documents WHERE file_name IS NULL OR file_name = ''");
if (!$result) {
    die("Error fetching documents: " . $conn->error . "
");
}

$documents = $result->fetch_all(MYSQLI_ASSOC);
$result->free();

if (empty($documents)) {
    echo "No documents with empty filenames to fix.
";
    $conn->close();
    exit;
}

echo "Found " . count($documents) . " documents to fix.
";

$updateStmt = $conn->prepare("UPDATE documents SET file_name = ? WHERE id = ?");
if (!$updateStmt) {
    die("Error preparing update statement: " . $conn->error . "
");
}

$fixedCount = 0;
foreach ($documents as $doc) {
    // Extract the basename from the file_path
    $newFilename = basename($doc['file_path']);
    
    $updateStmt->bind_param("si", $newFilename, $doc['id']);
    if ($updateStmt->execute()) {
        echo "Updated doc ID {$doc['id']} with filename: $newFilename
";
        $fixedCount++;
    } else {
        echo "Failed to update doc ID {$doc['id']}: " . $updateStmt->error . "
";
    }
}

$updateStmt->close();
$conn->close();

echo "
Fixing complete.
";
echo "Successfully updated $fixedCount records.
";