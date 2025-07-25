
ALTER TABLE `users` MODIFY `department_id` int(11) DEFAULT NULL;

INSERT INTO `users` (`matric_number`, `password`, `full_name`, `email`, `role`, `department_id`) 
VALUES ('SUPER_ADMIN', '$2y$10$tpsg0ONSXH..0GlQ77sKNuj0M5KBsW6qbc4UhFXMxtYCDnqKBBl3y', 'Super Admin', 'super_admin@dfms.com', 'admin', NULL)
ON DUPLICATE KEY UPDATE `full_name`=`full_name`;
