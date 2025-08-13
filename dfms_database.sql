-- Merged DFMS SQL Script
-- Main structure from database_structure.sql with all migrations applied

-- ============================================
-- 1. Initial Dump: Structure and Seed Data
-- ============================================

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
-- Host: localhost    Database: dfms_local
-- ------------------------------------------------------
-- Server version       10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Table structure for table `departments`
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matric_number` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `level` varchar(10) NOT NULL,
  `session` varchar(10) NOT NULL,
  `programme` varchar(100) NOT NULL,
  `program_type` varchar(50) NOT NULL DEFAULT 'Undergraduate',
  `mode` varchar(50) NOT NULL DEFAULT 'Regular',
  `department_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0,
  `email` varchar(255) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'student',
  PRIMARY KEY (`id`),
  UNIQUE KEY `matric_number` (`matric_number`),
  KEY `fk_users_department` (`department_id`),
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Seed users
INSERT INTO `users` (`id`, `matric_number`, `password`, `full_name`, `level`, `session`, `programme`, `created_at`, `is_admin`) VALUES
(1, 'E0500627', '$2y$10$tpsg0ONSXH..0GlQ77sKNuj0M5KBsW6qbc4UhFXMxtYCDnqKBBl3y', 'Professor Afolabi Samuel', '500L', '2023/2024', 'Bachelor Of Science (Computer Science)', '2025-05-28 18:53:05', 1),
(2, 'E050238', '$2y$10$3ofYsNgQf8ryeI21JyGZGufUgUQ3CVHiQAJRYh3pYqq2mQIxK4sBK', 'Demo User', '300L', '2023/2024', 'Bachelor Of Science (Computer Science)', '2025-05-28 22:41:39', 0);

-- Table structure for table `documents`
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` date NOT NULL,
  `status` enum('Pending Quality Check','Pending Approval','Approved','Rejected') NOT NULL DEFAULT 'Pending Quality Check',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `department_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `program_type` varchar(50) DEFAULT NULL,
  `mode` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_department_id` (`department_id`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `password_resets`
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `tag_errors`
DROP TABLE IF EXISTS `tag_errors`;
CREATE TABLE `tag_errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `error_message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `tag_errors_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- ====================================================
-- 2. Migration: Add Super Admin
-- ====================================================

-- Ensure department_id allows NULL
ALTER TABLE `users` MODIFY `department_id` int(11) DEFAULT NULL;

-- Insert or update Super Admin user
INSERT INTO `users` (`matric_number`, `password`, `full_name`, `email`, `role`, `department_id`)
VALUES ('SUPER_ADMIN', '$2y$10$tpsg0ONSXH..0GlQ77sKNuj0M5KBsW6qbc4UhFXMxtYCDnqKBBl3y',
        'Super Admin', 'super_admin@dfms.com', 'admin', NULL)
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- ====================================================
-- 3. Migration Phase 2: Populate Dept Codes & User Fields
-- ====================================================

-- Add 'code' to departments if not exists
ALTER TABLE `departments` ADD COLUMN IF NOT EXISTS `code` VARCHAR(10) AFTER `name`;

-- Set codes for existing departments
UPDATE `departments` SET `code` = 'CS'   WHERE `name` = 'Computer Science';
UPDATE `departments` SET `code` = 'PSYC' WHERE `name` = 'Psychology';
UPDATE `departments` SET `code` = 'MATH' WHERE `name` = 'Mathematics';

-- Add program_type, mode to users
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `program_type` VARCHAR(50) NOT NULL DEFAULT 'Undergraduate' AFTER `programme`,
  ADD COLUMN IF NOT EXISTS `mode` VARCHAR(50) NOT NULL DEFAULT 'Regular' AFTER `program_type`;

-- Ensure department_id column exists and FK set
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `department_id` INT(11) AFTER `mode`;
ALTER TABLE `users` ADD CONSTRAINT IF NOT EXISTS `fk_users_department`
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`);

-- One-time populate department_id based on programme
UPDATE `users` u
JOIN `departments` d ON u.`programme` LIKE CONCAT('%', d.`name`, '%')
SET u.`department_id` = d.`id`;

-- ====================================================
-- 4. Migration Phase 3: Idempotent Updates & Inserts
-- ====================================================

DELIMITER $$
CREATE PROCEDURE AddColumnsToUsers()
BEGIN
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email') THEN
        ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role') THEN
        ALTER TABLE `users` ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'student';
    END IF;
END$$

CREATE PROCEDURE AddColumnsToDocuments()
BEGIN
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documents' AND COLUMN_NAME = 'department_id') THEN
        ALTER TABLE `documents` ADD COLUMN `department_id` INT(11) NULL;
    END IF;
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documents' AND INDEX_NAME = 'idx_department_id') THEN
        ALTER TABLE `documents` ADD INDEX `idx_department_id` (`department_id`);
    END IF;
END$$
DELIMITER ;

-- Execute procedures
CALL AddColumnsToUsers();
CALL AddColumnsToDocuments();

-- Drop procedures
DROP PROCEDURE IF EXISTS AddColumnsToUsers;
DROP PROCEDURE IF EXISTS AddColumnsToDocuments;

-- Add new departments and admin users for each
INSERT INTO `departments` (`id`, `name`, `code`) VALUES
  (1, 'Computer Science', 'CS'),
  (2, 'Psychology', 'PSYC'),
  (3, 'English', 'ENG'),
  (4, 'Sociology', 'SOC'),
  (5, 'Linguistics', 'LING')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `users` (`matric_number`, `password`, `full_name`, `email`, `role`, `department_id`) VALUES
  ('ADMIN_PSYC', '$2y$10$3k.P.m/eE5p.dO5.y3o7A...', 'Psychology Admin', 'psych_admin@dfms.com', 'admin', 2),
  ('ADMIN_ENG',  '$2y$10$3k.P.m/eE5p.dO5.y3o7A...', 'English Admin',   'eng_admin@dfms.com',  'admin', 3),
  ('ADMIN_SOC',  '$2y$10$3k.P.m/eE5p.dO5.y3o7A...', 'Sociology Admin', 'soc_admin@dfms.com',  'admin', 4),
  ('ADMIN_LING', '$2y$10$3k.P.m/eE5p.dO5.y3o7A...', 'Linguistics Admin','ling_admin@dfms.com','admin',5)
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- Create 10 student users across departments
INSERT INTO `users` (`matric_number`, `password`, `full_name`, `email`, `level`, `programme`, `program_type`, `mode`, `department_id`, `role`) VALUES
    ('U123001', '$2y$10$3k.P.m/eE5p.dO5.y3o7A...', 'CS Student One',   'cs.student1@dfms.com', 100, 'Computer Science','Undergraduate','Regular',1,'student'),
    ('U123002', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'CS Student Two', 'cs.student2@dfms.com', 200, 'Computer Science', 'Undergraduate', 'Regular', 1, 'student'),
    ('U123003', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Psychology Student One', 'psych.student1@dfms.com', 300, 'Psychology', 'Undergraduate', 'Regular', 2, 'student'),
    ('U123004', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Psychology Student Two', 'psych.student2@dfms.com', 400, 'Psychology', 'Postgraduate', 'Distance Learning', 2, 'student'),
    ('U123005', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'English Student One', 'eng.student1@dfms.com', 100, 'English', 'Undergraduate', 'Regular', 3, 'student'),
    ('U123006', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'English Student Two', 'eng.student2@dfms.com', 200, 'English', 'Undergraduate', 'Regular', 3, 'student'),
    ('U123007', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Sociology Student One', 'soc.student1@dfms.com', 300, 'Sociology', 'Undergraduate', 'Regular', 4, 'student'),
    ('U123008', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Sociology Student Two', 'soc.student2@dfms.com', 400, 'Sociology', 'Postgraduate', 'Distance Learning', 4, 'student'),
    ('U123009', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Linguistics Student One', 'ling.student1@dfms.com', 100, 'Linguistics', 'Undergraduate', 'Regular', 5, 'student'),
    ('U123010', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Linguistics Student Two', 'ling.student2@dfms.com', 200, 'Linguistics', 'Undergraduate', 'Regular', 5, 'student')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- Populate documents.department_id from users
UPDATE `documents` d
JOIN `users` u ON d.`user_id` = u.`id`
SET d.`department_id` = u.`department_id`
WHERE d.`department_id` IS NULL;

-- ====================================================
-- 5. Optional: Consolidated Migrations
--    (Already covered above; skip to avoid duplication)
-- ====================================================

COMMIT;
