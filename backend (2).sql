-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2025 at 02:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `backend`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `migrate_department_to_college` ()   BEGIN
  -- students table
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS 
      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='department') = 1 THEN
    ALTER TABLE students CHANGE `department` `college` varchar(120) DEFAULT NULL;
  END IF;

  -- teachers table
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS 
      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='teachers' AND COLUMN_NAME='department') = 1 THEN
    ALTER TABLE teachers CHANGE `department` `college` varchar(120) DEFAULT NULL;
  END IF;

  -- schedules table
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS 
      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedules' AND COLUMN_NAME='department') = 1 THEN
    ALTER TABLE schedules CHANGE `department` `college` varchar(120) DEFAULT NULL;
  END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher','admin','superadmin') NOT NULL DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$DPxxdHrJaoEbfdwVCvZ6Qe1PWmsaNJViDXuDwRmcHscC70ot/KX9O', 'superadmin', '2025-11-07 01:15:51'),
(2, '111111111111', '$2y$10$Wq7ai2NiAG7QQeTqoiq0V.Mqctpfnjlo/aN5g7nlsybQKxCV2bD02', 'student', '2025-11-07 01:20:12'),
(3, 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', '2025-11-07 01:39:49'),
(7, '222222222222', '$2y$10$SL7lkEktnaSfAfN00xX/nOJPfmx8PUctzfIrM.bA5x29lI1mWHSJ.', 'teacher', '2025-11-07 02:18:31'),
(8, '333333333333', '$2y$10$98gHid86I1pgCpks.vzN8uS9Mwma893JNZN63QsYUTUWnWXIDDGj6', 'student', '2025-11-07 02:24:59'),
(9, '555555555555', '$2y$10$ij0qsLGZbPwDWfxBbwTn/u.wt/ZTdaNwBp50VGxS66euyQiJRhXGi', 'teacher', '2025-11-07 07:42:11');

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_username` varchar(64) NOT NULL,
  `action` varchar(200) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_actions`
--

INSERT INTO `admin_actions` (`id`, `admin_username`, `action`, `details`, `created_at`) VALUES
(1, 'admin', 'Superadmin admin updated teacher id=3, new_username=222222222222', 'Superadmin admin updated teacher id=3, new_username=222222222222', '2025-11-07 02:19:45');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(12) NOT NULL,
  `section` varchar(64) DEFAULT NULL,
  `status` varchar(16) NOT NULL,
  `scan_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`id`, `name`, `created_at`) VALUES
(1, 'College of Computer Studies', '2025-11-07 08:21:04'),
(2, 'College of Human Environment and Food Studies', '2025-11-07 08:21:04'),
(3, 'College of Accounting and Business Education', '2025-11-07 08:21:04'),
(4, 'College of Engineering and Architecture', '2025-11-07 08:21:04'),
(5, 'College of Medical and Biological Sciences', '2025-11-07 08:21:04'),
(6, 'College of Music', '2025-11-07 08:21:04'),
(7, 'College of Nursing', '2025-11-07 08:21:04'),
(8, 'College of Art and Humanities', '2025-11-07 08:21:04'),
(9, 'College of Teacher Education', '2025-11-07 08:21:04'),
(10, 'College of Pharmacy and Chemistry', '2025-11-07 08:21:04');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`) VALUES
(1, 'Administration', '2025-11-07 01:15:51'),
(2, 'Information Technology', '2025-11-07 01:15:51'),
(3, 'Computer Science', '2025-11-07 01:15:51');

-- --------------------------------------------------------

--
-- Table structure for table `invitation_codes`
--

CREATE TABLE `invitation_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(64) NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_by` varchar(12) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invitation_codes`
--

INSERT INTO `invitation_codes` (`id`, `code`, `is_used`, `used_by`, `created_at`, `expires_at`) VALUES
(1, 'MANUAL-TEACHER-2025-01', 0, NULL, '2025-11-07 01:39:49', '2025-12-07 09:39:49'),
(4, 'AA-2025-11', 1, '222222222222', '2025-11-07 02:17:45', '2025-12-07 03:17:45'),
(5, 'GOSURF50', 1, '555555555555', '2025-11-07 07:41:28', '2025-12-07 08:41:28');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rules`
--

CREATE TABLE `rules` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `content` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rules`
--

INSERT INTO `rules` (`id`, `name`, `content`, `updated_at`) VALUES
(1, 'grace_period', '15', '2025-11-07 01:15:51');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `section` varchar(64) NOT NULL,
  `instructor` varchar(200) DEFAULT NULL,
  `department` varchar(120) DEFAULT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `announcement` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `college_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `year_level` varchar(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `college_id`, `name`, `year_level`, `created_at`) VALUES
(1, 1, 'BSIT1A', '1st Year', '2025-11-07 08:21:04'),
(2, 1, 'BSCS1A', '1st Year', '2025-11-07 08:21:04'),
(3, 1, 'BSIT2A', '2nd Year', '2025-11-07 08:21:04'),
(4, 1, 'BSCS2A', '2nd Year', '2025-11-07 08:21:04');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(12) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` varchar(16) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `department` varchar(120) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `section` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `first_name`, `last_name`, `gender`, `email`, `department`, `year_level`, `section`, `created_at`) VALUES
(1, '111111111111', 'Student', 'Student', 'Male', 'jallawan_240000002026@uic.edu.ph', 'Computer Science', '1st Year', 'BSIT2A', '2025-11-07 01:20:11'),
(2, '333333333333', 'Student2', 'Student2', 'Male', 'shinjihirako061@gmail.com', 'Information Technology', '1st Year', 'BSIT2A', '2025-11-07 02:24:59');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` varchar(12) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` varchar(16) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `department` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `teacher_id`, `first_name`, `last_name`, `gender`, `email`, `department`, `created_at`) VALUES
(1, 'admin', 'System', 'Administrator', 'Other', 'admin@example.com', 'Administration', '2025-11-07 02:01:19'),
(3, '222222222222', 'Teacher1', 'Teacher1', 'Female', 'pancitcantonn9@gmail.com', NULL, '2025-11-07 02:18:31'),
(4, '555555555555', 'Teacher5', 'Tecaher5', 'Male', 'porschool23@gmail.com', NULL, '2025-11-07 07:42:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_accounts_username` (`username`),
  ADD KEY `idx_accounts_role` (`role`);

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_actions_admin` (`admin_username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_student_date` (`student_id`,`scan_time`),
  ADD KEY `idx_attendance_section_scan` (`section`,`scan_time`),
  ADD KEY `idx_attendance_scan_time` (`scan_time`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_colleges_name` (`name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_departments_name` (`name`);

--
-- Indexes for table `invitation_codes`
--
ALTER TABLE `invitation_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_invitation_codes_code` (`code`),
  ADD KEY `idx_invitation_codes_is_used` (`is_used`),
  ADD KEY `idx_invitation_codes_expires_at` (`expires_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_password_resets_token` (`token`),
  ADD KEY `idx_password_resets_email` (`email`),
  ADD KEY `idx_password_resets_expires_at` (`expires_at`);

--
-- Indexes for table `rules`
--
ALTER TABLE `rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rules_name` (`name`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedules_section` (`section`),
  ADD KEY `idx_schedules_day` (`day_of_week`),
  ADD KEY `idx_schedules_instructor` (`instructor`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sections_college_name` (`college_id`,`name`),
  ADD KEY `idx_sections_college` (`college_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_students_student_id` (`student_id`),
  ADD UNIQUE KEY `uq_students_email` (`email`),
  ADD KEY `idx_students_department` (`department`),
  ADD KEY `idx_students_section` (`section`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_teachers_teacher_id` (`teacher_id`),
  ADD UNIQUE KEY `uq_teachers_email` (`email`),
  ADD KEY `idx_teachers_department` (`department`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invitation_codes`
--
ALTER TABLE `invitation_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rules`
--
ALTER TABLE `rules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `fk_sections_college` FOREIGN KEY (`college_id`) REFERENCES `colleges` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
