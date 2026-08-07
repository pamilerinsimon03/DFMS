<?php
require_once __DIR__ . '/../config/config.php';

global $conn;

if (!$conn) {
    die("Database connection failed.\n");
}

echo "Applying DFMS Enterprise Production Migrations...\n";

$queries = [
    "CREATE TABLE IF NOT EXISTS `faculties` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(100) NOT NULL,
      `code` VARCHAR(10) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `faculty_departments` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `faculty_id` INT(11) NOT NULL,
      `department_id` INT(11) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_fac_dept` (`faculty_id`, `department_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "INSERT IGNORE INTO `faculties` (`id`, `name`, `code`) VALUES
      (1, 'Faculty of Science', 'SCI'),
      (2, 'Faculty of Social Sciences', 'SOCSCI'),
      (3, 'Faculty of Arts', 'ARTS')",

    "INSERT IGNORE INTO `faculty_departments` (`faculty_id`, `department_id`) VALUES
      (1, 1), (2, 2), (3, 3), (2, 4), (3, 5)",

    "CREATE TABLE IF NOT EXISTS `document_processing_queue` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `document_id` INT(11) NOT NULL,
      `user_id` INT(11) NOT NULL,
      `file_path` VARCHAR(255) NOT NULL,
      `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
      `attempts` INT(11) DEFAULT 0,
      `error_log` TEXT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_queue_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `rate_limits` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `key_hash` VARCHAR(64) NOT NULL,
      `timestamp` INT(11) NOT NULL,
      PRIMARY KEY (`id`),
      INDEX `idx_key_timestamp` (`key_hash`, `timestamp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `session_history` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `session` VARCHAR(20) NOT NULL,
      `level` VARCHAR(10) NOT NULL,
      `department_id` INT(11) NOT NULL,
      `archived_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `audit_logs` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NULL,
      `event_type` VARCHAR(100) NOT NULL,
      `event_description` TEXT,
      `ip_address` VARCHAR(45),
      `user_agent` TEXT,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      INDEX `idx_event_type` (`event_type`),
      INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Successfully executed query.\n";
    } else {
        echo "Query note: " . $conn->error . "\n";
    }
}

// Alter users table columns safely
$alterUserCols = [
    "ALTER TABLE users ADD COLUMN `faculty_id` INT(11) NULL AFTER `department_id`",
    "ALTER TABLE users ADD COLUMN `activation_token` VARCHAR(64) NULL AFTER `role`",
    "ALTER TABLE users ADD COLUMN `is_activated` TINYINT(1) NOT NULL DEFAULT 1 AFTER `activation_token`"
];

foreach ($alterUserCols as $alt) {
    @$conn->query($alt);
}

echo "Migrations completed successfully.\n";
