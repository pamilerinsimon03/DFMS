<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if (!isset($_SESSION['user_id']) || !User::isAdmin($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user = User::getById($_SESSION['user_id']);

$page_title = 'Analytics';
$active_page = 'analytics';
$css_files = ['admin.css', 'analytics.css'];
$js_files = ['admin.js'];

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/admin_sidebar.php';
require_once __DIR__ . '/../views/analytics.php';
require_once __DIR__ . '/../views/partials/footer.php';
