-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 30, 2026 at 10:13 AM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `program_register`
--

-- --------------------------------------------------------

--
-- Table structure for table `gm_libraries`
--

CREATE TABLE `gm_libraries` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `gm_library_types`
--

CREATE TABLE `gm_library_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gm_notes`
--

CREATE TABLE `gm_notes` (
  `note_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note_role` enum('creator','verifier','reject','remove','admin') NOT NULL DEFAULT 'creator',
  `note_text` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Seed data for table `gm_notes`
--


-- --------------------------------------------------------

--
-- Table structure for table `gm_participants`
--

CREATE TABLE `gm_participants` (
  `participant_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `participant_name` varchar(255) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `position_title` varchar(255) DEFAULT NULL,
  `organization_name` varchar(255) DEFAULT NULL,
  `attendance_mode` enum('physical','online') NOT NULL,
  `attendance_time` datetime DEFAULT NULL,
  `registration_source` enum('self','staff_upload') NOT NULL DEFAULT 'self',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Seed data for table `gm_participants`
--


-- --------------------------------------------------------

--
-- Table structure for table `gm_platforms`
--

CREATE TABLE `gm_platforms` (
  `id` int(11) NOT NULL,
  `platform_name` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `gm_programs`
--

CREATE TABLE `gm_programs` (
  `program_id` int(11) NOT NULL,
  `parent_library_id` int(11) DEFAULT NULL,
  `library_type_id` int(11) NOT NULL,
  `library_id` int(11) NOT NULL,
  `program_type_id` int(11) NOT NULL,
  `scale_id` int(11) NOT NULL,
  `program_mode` enum('physical','online','hybrid') NOT NULL,
  `platform_id` int(11) DEFAULT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_details` text DEFAULT NULL,
  `document_url` text DEFAULT NULL,
  `program_start` datetime NOT NULL,
  `program_end` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `officiated_by` varchar(255) DEFAULT NULL,
  `public_token` char(36) NOT NULL,
  `cover_image_url` text DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected','incomplete') NOT NULL DEFAULT 'incomplete',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `program_stage` enum('pre_program','completed','cancelled') DEFAULT 'pre_program'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Seed data for table `gm_programs`
--


-- --------------------------------------------------------

--
-- Table structure for table `gm_program_participant_stats`
--

CREATE TABLE `gm_program_participant_stats` (
  `program_id` int(11) NOT NULL,
  `total_participant_count` int(11) NOT NULL DEFAULT 0,
  `is_manual_override` tinyint(1) NOT NULL DEFAULT 0,
  `male_participant_count` int(11) NOT NULL DEFAULT 0,
  `female_participant_count` int(11) NOT NULL DEFAULT 0,
  `average_age` decimal(5,2) DEFAULT NULL,
  `physical_participant_count` int(11) NOT NULL DEFAULT 0,
  `online_participant_count` int(11) NOT NULL DEFAULT 0,
  `self_registered_participant_count` int(11) NOT NULL DEFAULT 0,
  `staff_uploaded_participant_count` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Seed data for table `gm_program_participant_stats`
--


-- --------------------------------------------------------

--
-- Table structure for table `gm_program_target_groups`
--

CREATE TABLE `gm_program_target_groups` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `target_group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Seed data for table `gm_program_target_groups`
--


-- --------------------------------------------------------

--
-- Table structure for table `gm_program_types`
--

CREATE TABLE `gm_program_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `gm_roles`
--

CREATE TABLE `gm_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `gm_scales`
--

CREATE TABLE `gm_scales` (
  `id` int(11) NOT NULL,
  `scale_name` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `gm_socmed_activities`
--

CREATE TABLE `gm_socmed_activities` (
  `activity_id` int(11) NOT NULL,
  `parent_library_id` int(11) DEFAULT NULL,
  `library_type_id` int(11) NOT NULL,
  `library_id` int(11) NOT NULL,
  `program_type_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `activity_title` varchar(255) NOT NULL,
  `activity_description` text DEFAULT NULL,
  `post_url` text DEFAULT NULL,
  `reach_estimate` int(11) DEFAULT NULL,
  `engagement_estimate` int(11) DEFAULT NULL,
  `activity_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gm_target_groups`
--

CREATE TABLE `gm_target_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `gm_users`
--

CREATE TABLE `gm_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `library_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Seed data for table `gm_users`
--

--
-- Seed data for reusable lookup tables and a sample 4-layer library tree
--

INSERT INTO `gm_library_types` (`id`, `type_name`) VALUES
(1, 'Main Library'),
(2, 'Regional Library'),
(3, 'Branch Library'),
(4, 'Unit Library');

INSERT INTO `gm_libraries` (`id`, `name`, `type_id`, `parent_id`, `address`, `status`) VALUES
(1, 'Main Library', 1, NULL, 'Replace with organization address', 'active'),
(2, 'Regional Library Example', 2, 1, 'Replace with regional address', 'active'),
(3, 'Branch Library Example', 3, 2, 'Replace with branch address', 'active'),
(4, 'Unit Library Example', 4, 3, 'Replace with unit address', 'active');

INSERT INTO `gm_platforms` (`id`, `platform_name`, `enabled`, `created_at`) VALUES
(1, 'Facebook', 1, CURRENT_TIMESTAMP),
(2, 'Instagram', 1, CURRENT_TIMESTAMP),
(3, 'YouTube', 1, CURRENT_TIMESTAMP),
(4, 'Google Meet', 1, CURRENT_TIMESTAMP),
(5, 'Zoom', 1, CURRENT_TIMESTAMP),
(6, 'Other', 1, CURRENT_TIMESTAMP);

INSERT INTO `gm_program_types` (`id`, `type_name`, `enabled`, `created_at`) VALUES
(1, 'General Program', 1, CURRENT_TIMESTAMP),
(2, 'Reading Program', 1, CURRENT_TIMESTAMP),
(3, 'Digital Literacy Program', 1, CURRENT_TIMESTAMP),
(4, 'Community Outreach', 1, CURRENT_TIMESTAMP),
(5, 'Other', 1, CURRENT_TIMESTAMP);

INSERT INTO `gm_roles` (`id`, `role_name`) VALUES
(1, 'super_admin'),
(2, 'admin'),
(3, 'user');

INSERT INTO `gm_scales` (`id`, `scale_name`, `enabled`, `created_at`) VALUES
(1, 'Large', 1, CURRENT_TIMESTAMP),
(2, 'Medium', 1, CURRENT_TIMESTAMP),
(3, 'Small / Ongoing', 1, CURRENT_TIMESTAMP);

INSERT INTO `gm_target_groups` (`id`, `group_name`, `enabled`, `created_at`) VALUES
(1, 'General Public', 1, CURRENT_TIMESTAMP),
(2, 'Children', 1, CURRENT_TIMESTAMP),
(3, 'Students', 1, CURRENT_TIMESTAMP),
(4, 'Adults', 1, CURRENT_TIMESTAMP),
(5, 'Senior Citizens', 1, CURRENT_TIMESTAMP),
(6, 'Persons with Disabilities', 1, CURRENT_TIMESTAMP),
(7, 'Professionals', 1, CURRENT_TIMESTAMP),
(8, 'Community Groups', 1, CURRENT_TIMESTAMP),
(9, 'Other', 1, CURRENT_TIMESTAMP);




--
-- Indexes for dumped tables
--

--
-- Indexes for table `gm_libraries`
--
ALTER TABLE `gm_libraries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `gm_library_types`
--
ALTER TABLE `gm_library_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gm_notes`
--
ALTER TABLE `gm_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_notes_program_created` (`program_id`,`created_at`);

--
-- Indexes for table `gm_participants`
--
ALTER TABLE `gm_participants`
  ADD PRIMARY KEY (`participant_id`),
  ADD UNIQUE KEY `unique_program_email` (`program_id`,`email`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_participants_program_mode_source` (`program_id`,`attendance_mode`,`registration_source`),
  ADD KEY `idx_participants_source` (`registration_source`);

--
-- Indexes for table `gm_platforms`
--
ALTER TABLE `gm_platforms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gm_programs`
--
ALTER TABLE `gm_programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `unique_public_token` (`public_token`),
  ADD KEY `parent_library_id` (`parent_library_id`),
  ADD KEY `library_type_id` (`library_type_id`),
  ADD KEY `library_id` (`library_id`),
  ADD KEY `program_type_id` (`program_type_id`),
  ADD KEY `scale_id` (`scale_id`),
  ADD KEY `platform_id` (`platform_id`),
  ADD KEY `idx_program_start` (`program_start`),
  ADD KEY `idx_programs_report_scope` (`library_id`,`is_deleted`,`verification_status`,`program_stage`,`program_start`),
  ADD KEY `idx_programs_parent_report_scope` (`parent_library_id`,`is_deleted`,`program_start`),
  ADD KEY `idx_programs_type_report` (`program_type_id`,`is_deleted`,`verification_status`,`program_start`);

--
-- Indexes for table `gm_program_participant_stats`
--
ALTER TABLE `gm_program_participant_stats`
  ADD PRIMARY KEY (`program_id`);

--
-- Indexes for table `gm_program_target_groups`
--
ALTER TABLE `gm_program_target_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_group_id` (`target_group_id`),
  ADD UNIQUE KEY `unique_program_target_group` (`program_id`,`target_group_id`);

--
-- Indexes for table `gm_program_types`
--
ALTER TABLE `gm_program_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gm_roles`
--
ALTER TABLE `gm_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `gm_scales`
--
ALTER TABLE `gm_scales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gm_socmed_activities`
--
ALTER TABLE `gm_socmed_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_activity_date` (`activity_date`),
  ADD KEY `idx_library` (`library_id`),
  ADD KEY `idx_platform` (`platform_id`),
  ADD KEY `gm_socmed_parent_lib_fk` (`parent_library_id`),
  ADD KEY `gm_socmed_library_type_fk` (`library_type_id`),
  ADD KEY `idx_program_type` (`program_type_id`),
  ADD KEY `idx_socmed_library_deleted_date` (`library_id`,`is_deleted`,`activity_date`);

--
-- Indexes for table `gm_target_groups`
--
ALTER TABLE `gm_target_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gm_users`
--
ALTER TABLE `gm_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `library_id` (`library_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gm_libraries`
--
ALTER TABLE `gm_libraries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gm_library_types`
--
ALTER TABLE `gm_library_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gm_notes`
--
ALTER TABLE `gm_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `gm_participants`
--
ALTER TABLE `gm_participants`
  MODIFY `participant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `gm_platforms`
--
ALTER TABLE `gm_platforms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `gm_programs`
--
ALTER TABLE `gm_programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `gm_program_target_groups`
--
ALTER TABLE `gm_program_target_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `gm_program_types`
--
ALTER TABLE `gm_program_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gm_roles`
--
ALTER TABLE `gm_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gm_scales`
--
ALTER TABLE `gm_scales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gm_socmed_activities`
--
ALTER TABLE `gm_socmed_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gm_target_groups`
--
ALTER TABLE `gm_target_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `gm_users`
--
ALTER TABLE `gm_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `gm_libraries`
--
ALTER TABLE `gm_libraries`
  ADD CONSTRAINT `gm_libraries_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `gm_library_types` (`id`),
  ADD CONSTRAINT `gm_libraries_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `gm_libraries` (`id`);

--
-- Constraints for table `gm_notes`
--
ALTER TABLE `gm_notes`
  ADD CONSTRAINT `gm_notes_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `gm_programs` (`program_id`);

--
-- Constraints for table `gm_participants`
--
ALTER TABLE `gm_participants`
  ADD CONSTRAINT `gm_participants_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `gm_programs` (`program_id`);

--
-- Constraints for table `gm_programs`
--
ALTER TABLE `gm_programs`
  ADD CONSTRAINT `gm_programs_ibfk_1` FOREIGN KEY (`parent_library_id`) REFERENCES `gm_libraries` (`id`),
  ADD CONSTRAINT `gm_programs_ibfk_2` FOREIGN KEY (`library_type_id`) REFERENCES `gm_library_types` (`id`),
  ADD CONSTRAINT `gm_programs_ibfk_3` FOREIGN KEY (`library_id`) REFERENCES `gm_libraries` (`id`),
  ADD CONSTRAINT `gm_programs_ibfk_4` FOREIGN KEY (`program_type_id`) REFERENCES `gm_program_types` (`id`),
  ADD CONSTRAINT `gm_programs_ibfk_5` FOREIGN KEY (`scale_id`) REFERENCES `gm_scales` (`id`),
  ADD CONSTRAINT `gm_programs_ibfk_6` FOREIGN KEY (`platform_id`) REFERENCES `gm_platforms` (`id`);

--
-- Constraints for table `gm_program_participant_stats`
--
ALTER TABLE `gm_program_participant_stats`
  ADD CONSTRAINT `gm_pps_program_fk` FOREIGN KEY (`program_id`) REFERENCES `gm_programs` (`program_id`) ON DELETE CASCADE;

--
-- Constraints for table `gm_program_target_groups`
--
ALTER TABLE `gm_program_target_groups`
  ADD CONSTRAINT `gm_program_target_groups_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `gm_programs` (`program_id`),
  ADD CONSTRAINT `gm_program_target_groups_ibfk_2` FOREIGN KEY (`target_group_id`) REFERENCES `gm_target_groups` (`id`);

--
-- Constraints for table `gm_socmed_activities`
--
ALTER TABLE `gm_socmed_activities`
  ADD CONSTRAINT `gm_socmed_library_fk` FOREIGN KEY (`library_id`) REFERENCES `gm_libraries` (`id`),
  ADD CONSTRAINT `gm_socmed_library_type_fk` FOREIGN KEY (`library_type_id`) REFERENCES `gm_library_types` (`id`),
  ADD CONSTRAINT `gm_socmed_parent_lib_fk` FOREIGN KEY (`parent_library_id`) REFERENCES `gm_libraries` (`id`),
  ADD CONSTRAINT `gm_socmed_platform_fk` FOREIGN KEY (`platform_id`) REFERENCES `gm_platforms` (`id`),
  ADD CONSTRAINT `gm_socmed_program_type_fk` FOREIGN KEY (`program_type_id`) REFERENCES `gm_program_types` (`id`);

--
-- Constraints for table `gm_users`
--
ALTER TABLE `gm_users`
  ADD CONSTRAINT `gm_users_ibfk_1` FOREIGN KEY (`library_id`) REFERENCES `gm_libraries` (`id`),
  ADD CONSTRAINT `gm_users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `gm_roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
