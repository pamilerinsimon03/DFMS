-- Migration: Enterprise Production Upgrades (Faculties, Queue, Session History, Rate Limits)

-- 1. Faculties & Faculty Department Mapping
CREATE TABLE IF NOT EXISTS `faculties` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faculty_departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` INT(11) NOT NULL,
  `department_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fac_dept` (`faculty_id`, `department_id`),
  CONSTRAINT `fk_facdept_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_facdept_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default faculties
INSERT IGNORE INTO `faculties` (`id`, `name`, `code`) VALUES
  (1, 'Faculty of Science', 'SCI'),
  (2, 'Faculty of Social Sciences', 'SOCSCI'),
  (3, 'Faculty of Arts', 'ARTS'),
  (6, 'Faculty of Computing', 'COMP');

-- Map departments to faculties
INSERT IGNORE INTO `faculty_departments` (`faculty_id`, `department_id`) VALUES
  (1, 1),
  (2, 2),
  (3, 3),
  (2, 4),
  (3, 5);

-- 2. Add users columns
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `faculty_id` INT(11) NULL AFTER `department_id`,
ADD COLUMN IF NOT EXISTS `activation_token` VARCHAR(64) NULL AFTER `role`,
ADD COLUMN IF NOT EXISTS `is_activated` TINYINT(1) NOT NULL DEFAULT 1 AFTER `activation_token`;

-- 3. Document Processing Queue
CREATE TABLE IF NOT EXISTS `document_processing_queue` (
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
  KEY `idx_queue_status` (`status`),
  KEY `fk_queue_document` (`document_id`),
  CONSTRAINT `fk_queue_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Rate Limits Fallback Table
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `key_hash` VARCHAR(64) NOT NULL,
  `timestamp` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_key_timestamp` (`key_hash`, `timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Session History
CREATE TABLE IF NOT EXISTS `session_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `session` VARCHAR(20) NOT NULL,
  `level` VARCHAR(10) NOT NULL,
  `department_id` INT(11) NOT NULL,
  `archived_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sesshist_user` (`user_id`),
  CONSTRAINT `fk_sesshist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
