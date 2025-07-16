-- Update the status enum to be more descriptive and support a multi-level approval process
ALTER TABLE `documents` 
MODIFY COLUMN `status` ENUM('Pending Quality Check', 'Pending Approval', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending Quality Check';

-- Migrate old statuses to the new, clearer statuses
UPDATE documents SET status = 'Pending Approval' WHERE status = 'Pending';
UPDATE documents SET status = 'Approved' WHERE status = 'Verified';
UPDATE documents SET status = 'Pending Quality Check' WHERE status = 'Pending Review';