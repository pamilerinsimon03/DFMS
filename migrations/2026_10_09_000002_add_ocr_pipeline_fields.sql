-- Migration: Add OCR and Quality Check pipeline fields to documents table
-- Version: 2026_10_09_000002_add_ocr_pipeline_fields.sql

ALTER TABLE `documents`
ADD COLUMN IF NOT EXISTS `ocr_match_status` ENUM('matched', 'mismatch', 'unreadable', 'pending') NOT NULL DEFAULT 'pending' AFTER `file_scan_status`,
ADD COLUMN IF NOT EXISTS `ocr_extracted` JSON NULL AFTER `ocr_match_status`,
ADD COLUMN IF NOT EXISTS `ocr_confidence` DECIMAL(5,2) NULL AFTER `ocr_extracted`,
ADD COLUMN IF NOT EXISTS `blur_variance` DECIMAL(8,2) NULL AFTER `ocr_confidence`;

CREATE INDEX IF NOT EXISTS `idx_documents_ocr_status` ON `documents` (`ocr_match_status`);
