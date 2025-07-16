-- Add a 'code' column to the departments table for easier filename generation
ALTER TABLE `departments`
ADD COLUMN `code` VARCHAR(10) AFTER `name`;

-- Populate the code for existing departments
UPDATE `departments` SET `code` = 'CS' WHERE `name` = 'Computer Science';
UPDATE `departments` SET `code` = 'PSYC' WHERE `name` = 'Psychology';
UPDATE `departments` SET `code` = 'MATH' WHERE `name` = 'Mathematics';

-- Add program_type, mode, and semester to users table
ALTER TABLE `users`
ADD COLUMN `program_type` VARCHAR(50) NOT NULL DEFAULT 'Undergraduate' AFTER `programme`,
ADD COLUMN `mode` VARCHAR(50) NOT NULL DEFAULT 'Regular' AFTER `program_type`;

-- Add department_id to users table and set it up as a foreign key
ALTER TABLE `users`
ADD COLUMN `department_id` INT(11) AFTER `mode`;

ALTER TABLE `users`
ADD CONSTRAINT `fk_users_department`
FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`);

-- Manually set the department_id for existing users based on their 'programme' string
-- This is a one-time operation
UPDATE `users` u
JOIN `departments` d ON u.programme LIKE CONCAT('%', d.name, '%')
SET u.department_id = d.id;
