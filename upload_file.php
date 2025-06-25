<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_name = $conn->real_escape_string($_POST['document_name']);
    $user_id = $_SESSION['user_id'];

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_name = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        $file_type = mime_content_type($file_tmp);

        // Validate file size (5MB) and type
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        if ($file_size > 5 * 1024 * 1024) {
            $_SESSION['upload_message'] = "File size exceeds 5MB limit.";
        } elseif (!in_array($file_type, $allowed_types)) {
            $_SESSION['upload_message'] = "Invalid file type. Only PDF, JPEG, and PNG are allowed.";
        } else {
            // Create uploads directory
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate unique filename
            $file_path = $upload_dir . time() . '_' . $file_name;

            // Move file
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Insert into database
                $sql = "INSERT INTO documents (user_id, document_name, file_path, status, created_at) VALUES (?, ?, ?, 'Pending', NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $user_id, $document_name, $file_path);
                if ($stmt->execute()) {
                    $_SESSION['upload_message'] = "File uploaded successfully.";
                } else {
                    $_SESSION['upload_message'] = "Database error.";
                }
                $stmt->close();
            } else {
                $_SESSION['upload_message'] = "Failed to upload file.";
            }
        }
    } else {
        $_SESSION['upload_message'] = "No file uploaded or upload error.";
    }

    header("Location: dashboard.php");
    exit();
}
?>