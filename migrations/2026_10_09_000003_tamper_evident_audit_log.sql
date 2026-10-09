-- Migration: 2026_10_09_000003_tamper_evident_audit_log.sql
-- Goal: Transform audit_logs into an append-only, tamper-evident hash-chained ledger.
-- Implements thesis requirements:
--   - prev_hash CHAR(64), entry_hash CHAR(64)
--   - role, target, route, outcome
--   - triggers to block UPDATE and DELETE
--   - audit_logs_archive table
--   - deprecation of CleanupOldAuditLogs

-- 1. Alter audit_logs to support full audit schema and hash chaining
ALTER TABLE `audit_logs`
ADD COLUMN IF NOT EXISTS `action` VARCHAR(100) NULL AFTER `user_id`,
ADD COLUMN IF NOT EXISTS `details` TEXT NULL AFTER `action`,
ADD COLUMN IF NOT EXISTS `role` VARCHAR(50) NULL AFTER `details`,
ADD COLUMN IF NOT EXISTS `target` VARCHAR(255) NULL AFTER `role`,
ADD COLUMN IF NOT EXISTS `route` VARCHAR(255) NULL AFTER `target`,
ADD COLUMN IF NOT EXISTS `outcome` VARCHAR(50) NOT NULL DEFAULT 'success' AFTER `route`,
ADD COLUMN IF NOT EXISTS `prev_hash` CHAR(64) NULL AFTER `outcome`,
ADD COLUMN IF NOT EXISTS `entry_hash` CHAR(64) NULL AFTER `prev_hash`,
ADD COLUMN IF NOT EXISTS `timestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `entry_hash`;

-- Create indexes for hash chain traversal, verification and querying
CREATE INDEX IF NOT EXISTS `idx_audit_entry_hash` ON `audit_logs` (`entry_hash`);
CREATE INDEX IF NOT EXISTS `idx_audit_prev_hash` ON `audit_logs` (`prev_hash`);
CREATE INDEX IF NOT EXISTS `idx_audit_action` ON `audit_logs` (`action`);
CREATE INDEX IF NOT EXISTS `idx_audit_route` ON `audit_logs` (`route`);
CREATE INDEX IF NOT EXISTS `idx_audit_target` ON `audit_logs` (`target`);

-- Synchronize legacy rows (if any)
UPDATE `audit_logs` SET `action` = `event_type` WHERE `action` IS NULL AND `event_type` IS NOT NULL;
UPDATE `audit_logs` SET `details` = `event_description` WHERE `details` IS NULL AND `event_description` IS NOT NULL;
UPDATE `audit_logs` SET `timestamp` = `created_at` WHERE `timestamp` IS NULL AND `created_at` IS NOT NULL;

-- 2. Create audit_logs_archive table to preserve chain continuity
CREATE TABLE IF NOT EXISTS `audit_logs_archive` (
    `id` INT NOT NULL PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NULL,
    `event_type` VARCHAR(100) NULL,
    `details` TEXT NULL,
    `event_description` TEXT NULL,
    `role` VARCHAR(50) NULL,
    `target` VARCHAR(255) NULL,
    `route` VARCHAR(255) NULL,
    `outcome` VARCHAR(50) NOT NULL DEFAULT 'success',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `prev_hash` CHAR(64) NULL,
    `entry_hash` CHAR(64) NULL,
    `timestamp` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `archived_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_archive_entry_hash` (`entry_hash`),
    INDEX `idx_archive_prev_hash` (`prev_hash`),
    INDEX `idx_archive_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Replace CleanupOldAuditLogs stored procedure to block log purging
DROP PROCEDURE IF EXISTS `CleanupOldAuditLogs`;
DELIMITER //
CREATE PROCEDURE `CleanupOldAuditLogs`()
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Security Violation: CleanupOldAuditLogs is deprecated. Deletion of audit logs violates the tamper-evident append-only policy.';
END //
DELIMITER ;

-- 4. Append-only triggers to block UPDATE and DELETE on audit_logs
DROP TRIGGER IF EXISTS `trg_audit_logs_no_update`;
DELIMITER //
CREATE TRIGGER `trg_audit_logs_no_update`
BEFORE UPDATE ON `audit_logs`
FOR EACH ROW
BEGIN
    IF @ALLOW_AUDIT_LOG_MUTATION IS NULL OR @ALLOW_AUDIT_LOG_MUTATION != 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Security Violation: DFMS audit_logs table is append-only. UPDATE operations are prohibited.';
    END IF;
END //
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_audit_logs_no_delete`;
DELIMITER //
CREATE TRIGGER `trg_audit_logs_no_delete`
BEFORE DELETE ON `audit_logs`
FOR EACH ROW
BEGIN
    IF @ALLOW_AUDIT_LOG_MUTATION IS NULL OR @ALLOW_AUDIT_LOG_MUTATION != 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Security Violation: DFMS audit_logs table is append-only. DELETE operations are prohibited.';
    END IF;
END //
DELIMITER ;
