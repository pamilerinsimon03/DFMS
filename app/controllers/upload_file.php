<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Document.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_name = $_POST['document_name'];
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file'];

    $result = Document::upload($user_id, $document_name, $file);

    $_SESSION['upload_message'] = $result['message'];

    header("Location: /app/controllers/dashboard.php");
    exit();
}