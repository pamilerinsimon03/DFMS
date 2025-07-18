-- Phase 3 Migration: Idempotent Script
-- This script is safe to run multiple times. It checks for the existence of columns and indexes before creating them.

DELIMITER $$

-- Procedure to add columns to the 'users' table if they don't exist
CREATE PROCEDURE AddColumnsToUsers()
BEGIN
    -- Add 'email' column if it does not exist
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'
    ) THEN
        ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) NULL;
    END IF;

    -- Add 'role' column if it does not exist
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
    ) THEN
        ALTER TABLE `users` ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'student';
    END IF;
END$$
DELIMITER ;

-- Procedure to add columns and indexes to the 'documents' table if they don't exist
DELIMITER $$
CREATE PROCEDURE AddColumnsToDocuments()
BEGIN
    -- Add 'department_id' column if it does not exist
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documents' AND COLUMN_NAME = 'department_id'
    ) THEN
        ALTER TABLE `documents` ADD COLUMN `department_id` INT(11) NULL;
    END IF;

    -- Add index for 'department_id' if it does not exist
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documents' AND INDEX_NAME = 'idx_department_id'
    ) THEN
        ALTER TABLE `documents` ADD INDEX `idx_department_id` (`department_id`);
    END IF;
END$$
DELIMITER ;

-- Execute the procedures to modify table structures
CALL AddColumnsToUsers();
CALL AddColumnsToDocuments();

-- Drop the procedures as they are no longer needed
DROP PROCEDURE AddColumnsToUsers;
DROP PROCEDURE AddColumnsToDocuments;

-- Add new departments
INSERT INTO `departments` (`id`, `name`, `code`) VALUES
(3, 'English', 'ENG'),
(4, 'Sociology', 'SOC'),
(5, 'Linguistics', 'LING')
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Create department-specific admin accounts
INSERT INTO `users` (`matric_number`, `password`, `full_name`, `email`, `role`, `department_id`) VALUES
('ADMIN_PSYC', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Psychology Admin', 'psych_admin@dfms.com', 'admin', 2),
('ADMIN_ENG', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'English Admin', 'eng_admin@dfms.com', 'admin', 3),
('ADMIN_SOC', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Sociology Admin', 'soc_admin@dfms.com', 'admin', 4),
('ADMIN_LING', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Linguistics Admin', 'ling_admin@dfms.com', 'admin', 5)
ON DUPLICATE KEY UPDATE `full_name`=`full_name`;

-- Create 10 student users across the 5 departments
INSERT INTO `users` (`matric_number`, `password`, `full_name`, `email`, `level`, `programme`, `program_type`, `mode`, `department_id`, `role`) VALUES
('U123001', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'CS Student One', 'cs.student1@dfms.com', 100, 'Computer Science', 'Undergraduate', 'Regular', 1, 'student'),
('U123002', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'CS Student Two', 'cs.student2@dfms.com', 200, 'Computer Science', 'Undergraduate', 'Regular', 1, 'student'),
('U123003', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Psychology Student One', 'psych.student1@dfms.com', 300, 'Psychology', 'Undergraduate', 'Regular', 2, 'student'),
('U123004', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Psychology Student Two', 'psych.student2@dfms.com', 400, 'Psychology', 'Postgraduate', 'Distance Learning', 2, 'student'),
('U123005', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'English Student One', 'eng.student1@dfms.com', 100, 'English', 'Undergraduate', 'Regular', 3, 'student'),
('U123006', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'English Student Two', 'eng.student2@dfms.com', 200, 'English', 'Undergraduate', 'Regular', 3, 'student'),
('U123007', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Sociology Student One', 'soc.student1@dfms.com', 300, 'Sociology', 'Undergraduate', 'Regular', 4, 'student'),
('U123008', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Sociology Student Two', 'soc.student2@dfms.com', 400, 'Sociology', 'Postgraduate', 'Distance Learning', 4, 'student'),
('U123009', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Linguistics Student One', 'ling.student1@dfms.com', 100, 'Linguistics', 'Undergraduate', 'Regular', 5, 'student'),
('U123010', '$2y$10$3k.P.m/eE5p.dO5.y3o7A.8Z4nCqJqHCpUa.4c3s6b8v/r.W/yS9G', 'Linguistics Student Two', 'ling.student2@dfms.com', 200, 'Linguistics', 'Undergraduate', 'Regular', 5, 'student')
ON DUPLICATE KEY UPDATE `full_name`=`full_name`;

-- Populate the new department_id column in the documents table
UPDATE documents d
JOIN users u ON d.user_id = u.id
SET d.department_id = u.department_id
WHERE d.department_id IS NULL;
