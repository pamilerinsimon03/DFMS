<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    // If no user is logged in, redirect to the login page
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve the document name from the form
    $document_name = $conn->real_escape_string($_POST['document_name']);
    $user_id = $_SESSION['user_id'];

    // Check if a file was uploaded and there were no errors
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_name = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        
        // Get the file extension and MIME type from the browser
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_type = $_FILES['file']['type'];

        // Define allowed file types and extensions
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $allowed_exts = ['pdf', 'jpeg', 'jpg', 'png'];

        // Validate the file against the allowed types and size limit
        if ($file_size > 5 * 1024 * 1024) { // 5MB limit
            $_SESSION['upload_message'] = "File size exceeds 5MB limit.";
        } elseif (!in_array($file_type, $allowed_types) || !in_array($file_ext, $allowed_exts)) {
            $_SESSION['upload_message'] = "Invalid file type. Only PDF, JPEG, and PNG are allowed.";
        } else {
            // Ensure the uploads directory exists, or create it
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate a unique filename using a timestamp to prevent overwrites
            $unique_file_name = time() . '_' . basename($file_name);
            $file_path = $upload_dir . $unique_file_name;

            // Move the uploaded file to the target directory
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Insert a record into the database with the file details
                $sql = "INSERT INTO documents (user_id, document_name, file_path, status, created_at) VALUES (?, ?, ?, 'Pending', NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $user_id, $document_name, $file_path);

                if ($stmt->execute()) {
                    $_SESSION['upload_message'] = "File uploaded successfully.";
                } else {
                    // If the database insert fails, provide an error message
                    $_SESSION['upload_message'] = "Database error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                // If the file move fails, provide an error message
                $_SESSION['upload_message'] = "Failed to upload file. Please try again.";
            }
        }
    } else {
        // If no file was uploaded or there was an error, provide a message
        $_SESSION['upload_message'] = "No file was uploaded or an error occurred during upload.";
    }

    // Redirect back to the dashboard after the upload attempt
    header("Location: dashboard.php");
    exit();
}
?>