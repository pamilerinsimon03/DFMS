-- Migration: 2026_10_09_000004_ndpa_compliance.sql
-- Goal: Bring database into strict compliance with Nigeria Data Protection Act (NDPA) 2023 & GAID 2025.
-- Implements:
--   - users.processing_restricted flag (NDPA s.37)
--   - consent_records schema enhancements (NDPA s.26, s.30)
--   - documents status extension ('Purged') and purge metadata (NDPA s.24(1)(f))
--   - retention_rules table for core vs non-core document schedules
--   - Reconciled ExportUserData procedure for data portability (NDPA s.34, s.38)

-- 1. Add processing_restricted flag to users
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `processing_restricted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_admin`;

CREATE INDEX IF NOT EXISTS `idx_users_processing_restricted` ON `users` (`processing_restricted`);

-- 2. Enhance consent_records table
ALTER TABLE `consent_records`
ADD COLUMN IF NOT EXISTS `policy_version` VARCHAR(20) NOT NULL DEFAULT '2025.1' AFTER `consent_given`,
ADD COLUMN IF NOT EXISTS `consent_withdrawn_at` TIMESTAMP NULL AFTER `consent_date`,
ADD COLUMN IF NOT EXISTS `ip_address` VARCHAR(45) NULL AFTER `consent_withdrawn_at`,
ADD COLUMN IF NOT EXISTS `user_agent` TEXT NULL AFTER `ip_address`;

-- 3. Extend documents status to allow 'Purged' and add purge tracking columns
ALTER TABLE `documents`
MODIFY COLUMN `status` ENUM('Pending Quality Check','Pending Approval','Approved','Rejected','Purged') NOT NULL DEFAULT 'Pending Quality Check',
MODIFY COLUMN `file_path` VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS `purged_at` TIMESTAMP NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `purge_reason` VARCHAR(255) NULL AFTER `purged_at`;

CREATE INDEX IF NOT EXISTS `idx_documents_purged_at` ON `documents` (`purged_at`);

-- 4. Create retention_rules table for core vs non-core document retention
CREATE TABLE IF NOT EXISTS `retention_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `document_type` VARCHAR(100) NOT NULL UNIQUE,
    `retention_days` INT NULL, -- NULL indicates permanent retention
    `is_core_academic` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_retention_core` (`is_core_academic`),
    INDEX `idx_retention_type` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default retention rules
INSERT INTO `retention_rules` (`document_type`, `retention_days`, `is_core_academic`) VALUES
('JAMB Admission Letter', NULL, 1),
('University Admission Letter', NULL, 1),
('O\'Level Result (WAEC/NECO/NABTEB)', NULL, 1),
('Degree Certificate', NULL, 1),
('Official Academic Transcript', NULL, 1),
('Final Year Project', NULL, 1),
('Graduation Clearance Form', NULL, 1),
('Birth Certificate', 2555, 0),
('Local Government Identification', 1825, 0),
('Medical Fitness Certificate', 1095, 0),
('Reference Letter', 1095, 0),
('Passport Photograph', 1825, 0)
ON DUPLICATE KEY UPDATE
    `retention_days` = VALUES(`retention_days`),
    `is_core_academic` = VALUES(`is_core_academic`);

-- 5. Reconcile ExportUserData procedure (NDPA s.34 & s.38 Data Portability)
DROP PROCEDURE IF EXISTS `ExportUserData`;
DELIMITER //
CREATE PROCEDURE `ExportUserData`(IN user_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Return user profile data
    SELECT 
        u.id,
        u.public_id,
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
        u.emergency_contact_relationship,
        u.level,
        u.programme,
        u.session,
        u.processing_restricted,
        u.created_at,
        u.last_login_at
    FROM users u 
    WHERE u.id = user_id;
    
    -- Return user documents (reconciled with actual schema)
    SELECT 
        d.id,
        d.public_id,
        d.document_name,
        d.file_name,
        d.upload_date,
        d.status,
        d.rejection_reason,
        d.level,
        d.semester,
        d.created_at
    FROM documents d 
    WHERE d.user_id = user_id;
    
    -- Return consent records
    SELECT 
        c.consent_type,
        c.consent_given,
        c.consent_date,
        c.consent_withdrawn_at,
        c.policy_version
    FROM consent_records c 
    WHERE c.user_id = user_id;
    
    COMMIT;
END //
DELIMITER ;
