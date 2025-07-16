-- Update the status enum to include the new quality check step and a more descriptive final state.
ALTER TABLE `documents` 
MODIFY COLUMN `status` ENUM('Pending Review', 'Verified', 'Rejected') NOT NULL DEFAULT 'Pending Review';

-- Add a column to store the reason for a rejection.
ALTER TABLE `documents`
ADD COLUMN `rejection_reason` VARCHAR(255) DEFAULT NULL AFTER `status`;
