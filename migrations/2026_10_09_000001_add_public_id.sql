-- Migration: Add UUIDv4 public_id to users and documents tables
-- Replaces sequential integer IDs across URLs, forms, and API responses

-- 1. Add public_id to users
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `public_id` CHAR(36) NULL AFTER `id`;

-- Backfill public_id for existing users
UPDATE `users` 
SET `public_id` = LOWER(UUID())
WHERE `public_id` IS NULL OR `public_id` = '';

-- Enforce NOT NULL and UNIQUE on users.public_id
ALTER TABLE `users` 
MODIFY COLUMN `public_id` CHAR(36) NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS `idx_users_public_id` ON `users` (`public_id`);

-- 2. Add public_id to documents
ALTER TABLE `documents` 
ADD COLUMN IF NOT EXISTS `public_id` CHAR(36) NULL AFTER `id`;

-- Backfill public_id for existing documents
UPDATE `documents` 
SET `public_id` = LOWER(UUID())
WHERE `public_id` IS NULL OR `public_id` = '';

-- Enforce NOT NULL and UNIQUE on documents.public_id
ALTER TABLE `documents` 
MODIFY COLUMN `public_id` CHAR(36) NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS `idx_docs_public_id` ON `documents` (`public_id`);
