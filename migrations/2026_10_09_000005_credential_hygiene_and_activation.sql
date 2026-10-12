-- Migration: 2026_10_09_000005_credential_hygiene_and_activation.sql
-- Goal: Enforce credential hygiene, account activation workflow, and secure token storage.
-- Thesis Chapter 1 & 2 requirements:
--   - No hardcoded or surname-derived default passwords
--   - Account onboarding via single-use activation tokens with expiration
--   - Asynchronous activation dispatch
--   - Password reset via hash comparison only with single-use invalidation

-- 1. Ensure activation columns in users table
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `activation_token` VARCHAR(64) NULL AFTER `role`,
ADD COLUMN IF NOT EXISTS `activation_expires_at` TIMESTAMP NULL AFTER `activation_token`;

ALTER TABLE `users`
MODIFY COLUMN `is_activated` TINYINT(1) NOT NULL DEFAULT 0;

-- 2. Invalidate fake bcrypt placeholder hashes and force unactivated status on placeholder accounts
UPDATE `users`
SET `password` = '*ACTIVATION_REQUIRED*',
    `is_activated` = 0,
    `activation_token` = SHA2(CONCAT(UUID(), RAND()), 256),
    `activation_expires_at` = DATE_ADD(NOW(), INTERVAL 48 HOUR)
WHERE `password` LIKE '%3k.P.m/eE5p%'
   OR `password` = 'surname12345'
   OR `password` LIKE '%surname%';

-- 3. Ensure email_queue table exists for asynchronous activation emails
CREATE TABLE IF NOT EXISTS `email_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `recipient` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    `attempts` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_queue_status_attempts` (`status`, `attempts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Fix password_resets legacy zero-timestamp default and add indexes
ALTER TABLE `password_resets`
MODIFY COLUMN `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS `idx_password_resets_token` ON `password_resets` (`token`);
CREATE INDEX IF NOT EXISTS `idx_password_resets_expires` ON `password_resets` (`expires_at`);
