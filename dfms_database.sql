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
  `phone_number` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `state_of_origin` varchar(50) DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `profile_updated_at` timestamp NULL DEFAULT NULL,
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
  `rejection_reason` TEXT DEFAULT NULL,
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


-- Insert or update Super Admin user
INSERT INTO `users` (`matric_number`, `password`, `full_name`, `email`, `role`, `department_id`)
VALUES ('SUPER_ADMIN', '$2y$10$tpsg0ONSXH..0GlQ77sKNuj0M5KBsW6qbc4UhFXMxtYCDnqKBBl3y',
        'Super Admin', 'super_admin@dfms.com', 'admin', NULL)
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- ====================================================
-- 3. Migration Phase 2: Populate Dept Codes & User Fields
-- ====================================================

-- Set codes for existing departments
UPDATE `departments` SET `code` = 'PSYC' WHERE `name` = 'Psychology';
UPDATE `departments` SET `code` = 'MATH' WHERE `name` = 'Mathematics';


-- One-time populate department_id based on programme
UPDATE `users` u
JOIN `departments` d ON u.`programme` LIKE CONCAT('%', d.`name`, '%')
SET u.`department_id` = d.`id`;

-- ====================================================
-- 4. Migration Phase 3: Idempotent Updates & Inserts
-- ====================================================


-- Add new departments
INSERT INTO `departments` (`id`, `name`, `code`) VALUES
  (1, 'Computer Science', 'CS'),
  (2, 'Psychology', 'PSYC'),
  (3, 'English', 'ENG'),
  (4, 'Sociology', 'SOC'),
  (5, 'Linguistics', 'LING'),
  (7, 'Computer Science and Artificial Intelligence', 'CSC'),
  (8, 'Data Science', 'DATA'),
  (9, 'Software Engineering', 'SENG'),
  (10, 'Information and Communication Technology', 'ICT')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `code` = VALUES(`code`);

INSERT INTO `users` (`matric_number`, `password`, `full_name`, `email`, `role`, `department_id`) VALUES
  ('ADMIN_PSYC', '$2y$10$KuDyQHjFT2vtqdGAQVtjb.PpGQgXGgZlAhAUKCl7AzFOU/Qs9q5hy', 'Psychology Admin', 'psych_admin@dfms.com', 'admin', 2),
  ('ADMIN_ENG',  '$2y$10$KuDyQHjFT2vtqdGAQVtjb.PpGQgXGgZlAhAUKCl7AzFOU/Qs9q5hy', 'English Admin',   'eng_admin@dfms.com',  'admin', 3),
  ('ADMIN_SOC',  '$2y$10$KuDyQHjFT2vtqdGAQVtjb.PpGQgXGgZlAhAUKCl7AzFOU/Qs9q5hy', 'Sociology Admin', 'soc_admin@dfms.com',  'admin', 4),
  ('ADMIN_LING', '$2y$10$KuDyQHjFT2vtqdGAQVtjb.PpGQgXGgZlAhAUKCl7AzFOU/Qs9q5hy', 'Linguistics Admin','ling_admin@dfms.com','admin',5)
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- Create 10 student users across departments
INSERT INTO `users` (`matric_number`, `password`, `full_name`, `email`, `level`, `programme`, `program_type`, `mode`, `department_id`, `role`) VALUES
    ('U123001', '$2y$10$TDKKWY3QbOm9pfxQ2wIW9OK0B3iYUuBlmgIwuq9cm.B5h2h.EA9q2', 'CS Student One',   'cs.student1@dfms.com', 100, 'Computer Science','Undergraduate','Regular',1,'student'),
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
-- 5. Migration: Student Profile Fields
-- ====================================================

-- Update existing users with default values for required profile fields
UPDATE `users` SET 
    `phone_number` = '08123456789',
    `date_of_birth` = '1997-04-01',
    `gender` = 'Male',
    `state_of_origin` = 'Osun State',
    `marital_status` = 'Single',
    `address` = 'No 123, Unit 1, Road H, Kogberegbe Street, Ologuneu, Ibadan',
    `profile_updated_at` = `created_at`
WHERE `is_admin` = 0 AND `phone_number` IS NULL;

-- ====================================================
-- 6. Migration: Notifications Table
-- ====================================================

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX (user_id, is_read, created_at)
);

-- ====================================================
-- 7. Security Updates and NDPR Compliance
-- ====================================================

-- Create data deletion schedule table for NDPR compliance
CREATE TABLE IF NOT EXISTS data_deletion_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    scheduled_date DATE NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create audit log table for security events
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
);

-- Create consent tracking table for NDPR compliance
CREATE TABLE IF NOT EXISTS consent_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    consent_type VARCHAR(100) NOT NULL,
    consent_given BOOLEAN NOT NULL,
    consent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    consent_withdrawn_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_consent (user_id, consent_type),
    INDEX idx_consent_date (consent_date)
);

-- Add security-related columns to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS last_login_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS account_locked_until TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS data_retention_consent BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS marketing_consent BOOLEAN DEFAULT FALSE;

-- Add security-related columns to documents table
ALTER TABLE documents
ADD COLUMN IF NOT EXISTS file_hash VARCHAR(64),
ADD COLUMN IF NOT EXISTS upload_ip VARCHAR(45),
ADD COLUMN IF NOT EXISTS file_scan_status ENUM('pending', 'clean', 'infected', 'error') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS file_scan_date TIMESTAMP NULL;

-- Create indexes for better performance and security
CREATE INDEX IF NOT EXISTS idx_users_last_login ON users(last_login_at);
CREATE INDEX IF NOT EXISTS idx_users_failed_logins ON users(failed_login_attempts);
CREATE INDEX IF NOT EXISTS idx_documents_file_hash ON documents(file_hash);
CREATE INDEX IF NOT EXISTS idx_documents_scan_status ON documents(file_scan_status);

-- Create view for security monitoring
CREATE OR REPLACE VIEW security_summary AS
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_events,
    SUM(CASE WHEN event_type = 'failed_login' THEN 1 ELSE 0 END) as failed_logins,
    SUM(CASE WHEN event_type = 'rate_limit_exceeded' THEN 1 ELSE 0 END) as rate_limit_hits,
    SUM(CASE WHEN event_type = 'file_upload_validation_failed' THEN 1 ELSE 0 END) as upload_failures
FROM audit_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Create stored procedure for cleaning up old audit logs
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CleanupOldAuditLogs()
BEGIN
    -- Delete audit logs older than 1 year
    DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
    
    -- Delete completed data deletion records older than 6 months
    DELETE FROM data_deletion_schedule 
    WHERE status = 'completed' AND completed_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
    
    -- Log the cleanup
    INSERT INTO audit_logs (event_type, event_description, created_at)
    VALUES ('maintenance', 'Old audit logs and deletion records cleaned up', NOW());
END //
DELIMITER ;

-- Create stored procedure for user data export (NDPR compliance)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS ExportUserData(IN user_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Log the data export request
    INSERT INTO audit_logs (user_id, event_type, event_description, created_at)
    VALUES (user_id, 'data_export', 'User data export requested', NOW());
    
    -- Return user data
    SELECT 
        u.id,
        u.matric_number,
        u.full_name,
        u.email,
        u.phone_number,
        u.date_of_birth,
        u.gender,
        u.state_of_origin,
        u.marital_status,
        u.address,
        u.emergency_contact_name,
        u.emergency_contact_phone,
        u.level,
        u.programme,
        u.session,
        u.created_at,
        u.last_login_at
    FROM users u 
    WHERE u.id = user_id;
    
    -- Return user documents
    SELECT 
        d.id,
        d.document_name,
        d.file_path,
        d.status,
        d.created_at
    FROM documents d 
    WHERE d.user_id = user_id;
    
    -- Return consent records
    SELECT 
        c.consent_type,
        c.consent_given,
        c.consent_date,
        c.consent_withdrawn_at
    FROM consent_records c 
    WHERE c.user_id = user_id;
    
    COMMIT;
END //
DELIMITER ;

-- Insert default consent types
INSERT IGNORE INTO consent_records (user_id, consent_type, consent_given, consent_date)
SELECT 
    id,
    'data_processing',
    TRUE,
    created_at
FROM users 
WHERE data_retention_consent = TRUE;

-- Update existing users with default consent
UPDATE users 
SET data_retention_consent = TRUE 
WHERE data_retention_consent = FALSE;

-- Create trigger to log user data changes
DELIMITER //
CREATE TRIGGER IF NOT EXISTS log_user_changes
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.full_name != NEW.full_name THEN
        INSERT INTO audit_logs (user_id, event_type, event_description, created_at)
        VALUES (NEW.id, 'profile_update', CONCAT('Full name changed from "', OLD.full_name, '" to "', NEW.full_name, '"'), NOW());
    END IF;
    
    IF OLD.email != NEW.email THEN
        INSERT INTO audit_logs (user_id, event_type, event_description, created_at)
        VALUES (NEW.id, 'profile_update', CONCAT('Email changed from "', OLD.email, '" to "', NEW.email, '"'), NOW());
    END IF;
    
    IF OLD.phone_number != NEW.phone_number THEN
        INSERT INTO audit_logs (user_id, event_type, event_description, created_at)
        VALUES (NEW.id, 'profile_update', CONCAT('Phone number changed from "', OLD.phone_number, '" to "', NEW.phone_number, '"'), NOW());
    END IF;
END //
DELIMITER ;

-- Create a view for NDPR compliance reporting
CREATE OR REPLACE VIEW ndpr_compliance_summary AS
SELECT 
    'Total Users' as metric,
    COUNT(*) as count,
    'Active users in system' as description
FROM users
UNION ALL
SELECT 
    'Users with Data Consent',
    COUNT(*),
    'Users who have given data processing consent'
FROM users 
WHERE data_retention_consent = TRUE
UNION ALL
SELECT 
    'Pending Data Deletions',
    COUNT(*),
    'Users scheduled for data deletion'
FROM data_deletion_schedule 
WHERE status = 'scheduled'
UNION ALL
SELECT 
    'Security Events (30 days)',
    COUNT(*),
    'Total security events in last 30 days'
FROM audit_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
UNION ALL
SELECT 
    'Failed Logins (30 days)',
    COUNT(*),
    'Failed login attempts in last 30 days'
FROM audit_logs 
WHERE event_type = 'failed_login' 
AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- ====================================================
-- 8. Migration: File Requirements (Admin-configurable Upload List)
-- ====================================================

-- Create table for managing files students must upload per department
CREATE TABLE IF NOT EXISTS `file_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `description` text,
  `due_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_file_requirements_department` (`department_id`),
  KEY `fk_file_requirements_created_by` (`created_by`),
  CONSTRAINT `fk_file_requirements_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_requirements_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional sample data (idempotent insert)
INSERT INTO `file_requirements` (`department_id`, `document_name`, `description`, `due_date`, `created_by`)
SELECT 2, 'Psychology Research Proposal', 'Research proposal for final year project', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 1
WHERE NOT EXISTS (SELECT 1 FROM file_requirements WHERE department_id=2 AND document_name='Psychology Research Proposal');


-- Final security recommendations
-- 1. Run this script during maintenance window
-- 2. Test all procedures and triggers
-- 3. Set up regular cleanup job: CALL CleanupOldAuditLogs();
-- 4. Monitor security_summary view regularly
-- 5. Review ndpr_compliance_summary monthly


-- ====================================================
-- 9. Migration: Add Rejection Reason to Documents
-- ====================================================

-- Ensure rejection_reason column exists and is of type TEXT
-- Note: This uses a stored procedure approach for idempotency in MySQL/MariaDB
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS UpgradeDocumentsTable()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'documents'
        AND COLUMN_NAME = 'rejection_reason'
    ) THEN
        ALTER TABLE documents ADD COLUMN rejection_reason TEXT NULL AFTER status;
    ELSE
        ALTER TABLE documents MODIFY COLUMN rejection_reason TEXT NULL;
    END IF;
END //
DELIMITER ;

CALL UpgradeDocumentsTable();
DROP PROCEDURE UpgradeDocumentsTable;

-- ====================================================
-- 10. Migration Phase 4: Enterprise Production Upgrades
-- ====================================================

-- 10.1 Faculties & Faculty Department Scoping
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
INSERT INTO `faculties` (`id`, `name`, `code`) VALUES
  (1, 'Faculty of Science', 'SCI'),
  (2, 'Faculty of Social Sciences', 'SOCSCI'),
  (3, 'Faculty of Arts', 'ARTS'),
  (6, 'Faculty of Computing', 'COMP')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Map existing departments to faculties
INSERT INTO `faculty_departments` (`faculty_id`, `department_id`) VALUES
  (1, 1), -- Computer Science -> Science
  (2, 2), -- Psychology -> Social Sciences
  (3, 3), -- English -> Arts
  (2, 4), -- Sociology -> Social Sciences
  (3, 5), -- Linguistics -> Arts
  (6, 7), -- Computer Science & AI -> Computing
  (6, 8), -- Data Science -> Computing
  (6, 9), -- Software Engineering -> Computing
  (6, 10) -- ICT -> Computing
ON DUPLICATE KEY UPDATE `faculty_id` = VALUES(`faculty_id`);

-- Add faculty_id, activation_token, and is_activated to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS `faculty_id` INT(11) NULL AFTER `department_id`,
ADD COLUMN IF NOT EXISTS `activation_token` VARCHAR(64) NULL AFTER `role`,
ADD COLUMN IF NOT EXISTS `is_activated` TINYINT(1) NOT NULL DEFAULT 1 AFTER `activation_token`;

-- 10.2 Document Processing Queue (Async OCR / Quality Checks)
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

-- 10.3 Stateful Rate Limiting Fallback Layer
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `key_hash` VARCHAR(64) NOT NULL,
  `timestamp` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_key_timestamp` (`key_hash`, `timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10.4 Session History & Roll-Over Archiving
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

-- 10.5 Update admin user roles & flag
UPDATE `users` SET `is_admin` = 1, `role` = 'super_admin' WHERE `matric_number` = 'SUPER_ADMIN';
UPDATE `users` SET `is_admin` = 1, `role` = 'department_admin', `department_id` = 1 WHERE `matric_number` = 'E0500627';
UPDATE `users` SET `is_admin` = 1, `role` = 'department_admin' WHERE `matric_number` IN ('ADMIN_PSYC', 'ADMIN_ENG', 'ADMIN_SOC', 'ADMIN_LING', 'ADMIN/001');

-- 10.6 Seed Initial Documents for Department 1 (Computer Science)
INSERT INTO `documents` (`id`, `user_id`, `document_name`, `file_name`, `file_path`, `upload_date`, `status`, `department_id`, `level`, `semester`, `program_type`, `mode`)
SELECT 53, u.id, 'WASSCE Certificate', 'doc_wassce_2024.pdf', 'secure_uploads/2024_2025/CS/300L/doc_wassce_2024.pdf', '2026-08-03', 'Pending Approval', 1, 300, '1st Semester', 'Full Time', 'UTME'
FROM `users` u WHERE u.`matric_number` = 'E050238'
ON DUPLICATE KEY UPDATE `document_name` = VALUES(`document_name`);

INSERT INTO `documents` (`id`, `user_id`, `document_name`, `file_name`, `file_path`, `upload_date`, `status`, `department_id`, `level`, `semester`, `program_type`, `mode`)
SELECT 54, u.id, 'Birth Certificate', 'doc_birth_cert.pdf', 'secure_uploads/2024_2025/CS/100L/doc_birth_cert.pdf', '2026-08-03', 'Approved', 1, 100, '1st Semester', 'Full Time', 'UTME'
FROM `users` u WHERE u.`matric_number` = 'U123001'
ON DUPLICATE KEY UPDATE `document_name` = VALUES(`document_name`);

INSERT INTO `documents` (`id`, `user_id`, `document_name`, `file_name`, `file_path`, `upload_date`, `status`, `department_id`, `level`, `semester`, `program_type`, `mode`)
SELECT 55, u.id, 'JAMB Admission Letter', 'doc_jamb_adm.pdf', 'secure_uploads/2024_2025/CS/200L/doc_jamb_adm.pdf', '2026-08-03', 'Pending Approval', 1, 200, '1st Semester', 'Full Time', 'Direct Entry'
FROM `users` u WHERE u.`matric_number` = 'U123002'
ON DUPLICATE KEY UPDATE `document_name` = VALUES(`document_name`);

-- 10.7 Production Performance Composite Indexes
CREATE INDEX IF NOT EXISTS `idx_docs_dept_status_created` ON `documents` (`department_id`, `status`, `created_at` DESC);
CREATE INDEX IF NOT EXISTS `idx_docs_user_status` ON `documents` (`user_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_users_dept_level` ON `users` (`department_id`, `level`, `program_type`, `mode`);

-- ====================================================
-- 11. Schema Migrations Ledger
-- ====================================================
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` VARCHAR(255) NOT NULL,
  `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `schema_migrations` (`version`) VALUES
  ('2026_08_10_000000_enterprise_upgrades.sql'),
  ('2026_08_11_000000_performance_indexes.sql');

COMMIT;



