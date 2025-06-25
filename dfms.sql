-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 23, 2025 at 10:37 AM
-- Server version: 5.7.44-log
-- PHP Version: 8.1.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `otunbaaw_dfms`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'Computer Science'),
(2, 'Psychology');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` date NOT NULL,
  `status` enum('Pending','Verified','Failed') DEFAULT 'Pending',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `user_id`, `document_name`, `file_name`, `file_path`, `upload_date`, `status`, `due_date`, `created_at`) VALUES
(2, 1, 'Admission Letter', '', 'uploads/1748465958_AFOLABI SAMUEL CV.pdf', '0000-00-00', 'Verified', '2024-10-11', '2025-05-28 20:59:18'),
(3, 1, 'Course Registration Form', '', 'uploads/1748466322_Oladunjoye Esther_CHAPTER_(1-5).pdf', '0000-00-00', 'Verified', '2025-09-04', '2025-05-28 21:05:22'),
(4, 1, 'Faculty Registration Receipt', '', 'Uploads/1748469013_TIME TABLE.pdf', '0000-00-00', 'Failed', NULL, '2025-05-28 21:50:13'),
(7, 1, 'NACOSS Receipt', '', 'Uploads/1750017225_400L.pdf', '0000-00-00', 'Verified', NULL, '2025-06-15 19:53:45'),
(8, 2, 'Faculty Registration Receipt', '', 'Uploads/1750615197_200 Accommodation.pdf', '0000-00-00', 'Pending', NULL, '2025-06-22 17:59:57'),
(9, 2, 'Admission Letter', '', 'Uploads/1750618852_Afolabi Ezekiel Oluwasegun 220595.pdf', '0000-00-00', 'Pending', NULL, '2025-06-22 19:00:53'),
(10, 2, 'FFG', '', 'Uploads/1750624397_GRAPHICBUNDLE-675-GB-PLAN-si8jx2 (1).pdf', '0000-00-00', 'Verified', NULL, '2025-06-22 20:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `matric_number` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `level` varchar(10) NOT NULL,
  `session` varchar(10) NOT NULL,
  `programme` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_admin` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `matric_number`, `password`, `full_name`, `level`, `session`, `programme`, `created_at`, `is_admin`) VALUES
(1, 'E0500627', '$2y$10$tpsg0ONSXH..0GlQ77sKNuj0M5KBsW6qbc4UhFXMxtYCDnqKBBl3y', 'Professor Afolabi Samuel', '500L', '2023/2024', 'Bachelor Of Science (Computer Science)', '2025-05-28 18:53:05', 1),
(2, 'E050238', '$2y$10$3ofYsNgQf8ryeI21JyGZGufUgUQ3CVHiQAJRYh3pYqq2mQIxK4sBK', 'Demo User', '300L', '2023/2024', 'Bachelor Of Science (Computer Science)', '2025-05-28 22:41:39', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matric_number` (`matric_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
