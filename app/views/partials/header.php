<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'UI File Management Portal'; ?></title>
    <link rel="stylesheet" href="/app/src/css/global.css">
    <link rel="stylesheet" href="/app/src/css/dashboard.css">
    <?php if (isset($css_files) && is_array($css_files)): ?>
    <?php foreach ($css_files as $css): ?>
    <link rel="stylesheet" href="/app/src/css/<?php echo $css; ?>">
    <?php endforeach; ?>
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="dashboard-container">