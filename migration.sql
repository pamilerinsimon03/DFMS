ALTER TABLE `documents`
ADD COLUMN `level` INT,
ADD COLUMN `semester` VARCHAR(20),
ADD COLUMN `program_type` VARCHAR(50),
ADD COLUMN `mode` VARCHAR(50),
ADD COLUMN `department_id` INT,
ADD FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`);

CREATE TABLE `tag_errors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `document_id` INT NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `error_message` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`)
);
