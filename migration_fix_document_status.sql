-- Step 1: Modify the ENUM definition to include all necessary new and old values
-- This ensures the subsequent UPDATE statement doesn't fail.
ALTER TABLE `documents`
MODIFY COLUMN `status` ENUM('Pending Review', 'Verified', 'Rejected', 'Pending Quality Check', 'Pending Approval', 'Approved', 'Pending') NOT NULL;

-- Step 2: Update old status values to the new, consolidated ones
UPDATE `documents` SET `status` = 'Pending Approval' WHERE `status` = 'Pending Review';
UPDATE `documents` SET `status` = 'Pending Approval' WHERE `status` = 'Pending';
UPDATE `documents` SET `status` = 'Approved' WHERE `status` = 'Verified';

-- Step 3: Modify the ENUM definition a final time to remove the old, unused values
-- and set the correct default.
ALTER TABLE `documents`
MODIFY COLUMN `status` ENUM('Pending Quality Check', 'Pending Approval', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending Quality Check';
