-- Create database (if needed)
CREATE DATABASE IF NOT EXISTS `job_portal`;
USE `job_portal`;

-- Set foreign key checks off temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist (to avoid conflicts)
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `saved_jobs`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- Create users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','employer','applicant') DEFAULT 'applicant',
  `status` enum('active','inactive') DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert users (with your records)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `profile_image`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@jobportal.com', '$2y$10$/yhkOAHwu1dQMx/LmnuOfeJR3W8P9z74wCBb23p5yxAwGrRcSfS/i', 'System Administrator', 'admin', 'active', NULL, NULL, NULL, '2026-03-24 09:29:20', '2026-03-24 10:31:11'),
(2, 'techcorp', 'hr@techcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'TechCorp Solutions', 'employer', 'active', NULL, '+1234567890', NULL, '2026-03-24 09:29:20', '2026-03-24 09:29:20'),
(3, 'john_doe', 'john@example.com', '$2y$10$/yhkOAHwu1dQMx/LmnuOfeJR3W8P9z74wCBb23p5yxAwGrRcSfS/i', 'John Doe', 'applicant', 'active', NULL, NULL, NULL, '2026-03-24 09:29:20', '2026-03-24 10:31:28'),
(4, 'Mugisha', 'ishimwejean15@gmail.com', '$2y$10$VU1qH0zEzJkzTj8j/8lZi.CLZr2re5P3/syxKX/2pjKm1M4Z4ufxW', 'ISHIMWE JEAN DE DIEU', 'employer', 'active', NULL, '0794152807', 'bugesera mukaje', '2026-03-24 13:15:05', '2026-03-24 16:30:01');

-- Create categories table
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert categories (with your records)
INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`) VALUES
(1, 'Technology', 'IT, Software Development, and Technology related jobs', 'active', '2026-03-24 09:29:20'),
(2, 'Healthcare', 'Medical, Nursing, and Healthcare positions', 'active', '2026-03-24 09:29:20'),
(3, 'Finance', 'Accounting, Banking, and Financial Services', 'active', '2026-03-24 09:29:20'),
(4, 'Marketing', 'Digital Marketing, Branding, and Communications', 'active', '2026-03-24 09:29:20'),
(5, 'Sales', 'Sales Representative, Business Development', 'active', '2026-03-24 09:29:20'),
(6, 'Education', 'Teaching, Training, and Education roles', 'active', '2026-03-24 09:29:20'),
(7, 'Administrative', 'Office Management, Administrative Support', 'active', '2026-03-24 09:29:20'),
(8, 'Customer Service', 'Support and Service roles', 'active', '2026-03-24 09:29:20');

-- Create jobs table
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employer_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `salary` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `job_type` enum('full-time','part-time','contract','internship') DEFAULT 'full-time',
  `experience_level` enum('entry','mid','senior','lead') DEFAULT 'entry',
  `status` enum('open','closed','pending') DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert jobs (with your records)
INSERT INTO `jobs` (`id`, `employer_id`, `category_id`, `title`, `description`, `requirements`, `salary`, `location`, `job_type`, `experience_level`, `status`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'Senior PHP Developer', 'We are looking for an experienced PHP developer to join our team.', '5+ years PHP experience, MySQL, JavaScript, Laravel framework', 'Rwf 80,000 - Rwf 100,000', 'Huye butare', 'full-time', 'entry', 'closed', '2026-03-24', NULL, '2026-03-24 09:29:21', '2026-03-24 13:53:46'),
(2, 2, 1, 'Frontend Developer', 'Join our frontend team to build amazing user interfaces.', '3+ years React, HTML/CSS, JavaScript, responsive design', 'Rwf 70,000 - Rwf 90,000', 'Remote', 'full-time', 'entry', 'open', '2026-03-24', NULL, '2026-03-24 09:29:21', '2026-03-24 13:53:46'),
(3, 2, 4, 'Digital Marketing Manager', 'Lead our digital marketing efforts and campaigns.', '5+ years digital marketing, SEO, social media, analytics', 'Rwf 75,000 - Rwf 95,000', 'Los Angeles, CA', 'full-time', 'entry', 'open', '2026-03-24', NULL, '2026-03-24 09:29:21', '2026-03-24 14:13:46'),
(4, 2, 3, 'cashier', 'NI umukozi uzajya ukora ibijyanye na transaction management', 'A2 IN ACCOUNTING ,A0 on related field', 'Rwf 80,000 - Rwf 100,000', 'Huye DISTRICT', 'full-time', 'entry', 'open', '2026-03-24', '2026-03-25', '2026-03-24 13:49:06', '2026-03-24 14:13:36'),
(5, 4, 8, 'customer care', 'nu ukwakira abantu', 'A0 IN LANGUAGES', 'Rwf 80,000 - Rwf 100,000', 'Huye DISTRICT', 'full-time', 'mid', 'pending', NULL, NULL, '2026-03-24 16:33:30', '2026-03-24 16:33:30'),
(6, 4, 2, 'NURSE', 'CARING PATIENT FULL TIME', 'A0,A1,A2 IN NURSING', 'Rwf 80,000 - Rwf 100,000', 'NGOMA', 'full-time', 'mid', 'open', '2026-03-25', '2026-03-26', '2026-03-25 05:51:33', '2026-03-25 05:53:36');

-- Create applications table
CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `resume_path` varchar(255) NOT NULL,
  `status` enum('pending','reviewed','shortlisted','rejected','accepted') DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_application` (`job_id`,`applicant_id`),
  KEY `applicant_id` (`applicant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create saved_jobs table
CREATE TABLE `saved_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_saved` (`user_id`,`job_id`),
  KEY `job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add foreign key constraints
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`applicant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `saved_jobs`
  ADD CONSTRAINT `saved_jobs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_jobs_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

-- Turn foreign key checks back on
SET FOREIGN_KEY_CHECKS = 1;