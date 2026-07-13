-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 13, 2026 at 01:55 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `webdrop_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id_log` bigint UNSIGNED NOT NULL,
  `id_user` bigint UNSIGNED NOT NULL,
  `id_project` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `allowed_file_types`
--

CREATE TABLE `allowed_file_types` (
  `id_type` int UNSIGNED NOT NULL,
  `extension` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('editable','asset') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_size_mb` int UNSIGNED NOT NULL DEFAULT '2',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id_project` bigint UNSIGNED NOT NULL,
  `id_user` bigint UNSIGNED NOT NULL,
  `project_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(140) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `workspace_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `public_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `last_published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_files`
--

CREATE TABLE `project_files` (
  `id_file` bigint UNSIGNED NOT NULL,
  `id_project` bigint UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `file_name` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `relative_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_extension` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint UNSIGNED NOT NULL DEFAULT '0',
  `is_folder` tinyint(1) NOT NULL DEFAULT '0',
  `is_editable` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `project_files`
--
DELIMITER $$
CREATE TRIGGER `trg_project_files_after_delete` AFTER DELETE ON `project_files` FOR EACH ROW BEGIN
    UPDATE projects
    SET status = 'draft'
    WHERE id_project = OLD.id_project;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_project_files_after_insert` AFTER INSERT ON `project_files` FOR EACH ROW BEGIN
    UPDATE projects
    SET status = 'draft'
    WHERE id_project = NEW.id_project;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_project_files_after_update` AFTER UPDATE ON `project_files` FOR EACH ROW BEGIN
    UPDATE projects
    SET status = 'draft'
    WHERE id_project = NEW.id_project;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `publish_jobs`
--

CREATE TABLE `publish_jobs` (
  `id_publish_job` bigint UNSIGNED NOT NULL,
  `id_project` bigint UNSIGNED NOT NULL,
  `id_user` bigint UNSIGNED NOT NULL,
  `status` enum('queued','running','success','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` bigint UNSIGNED NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('user','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_project_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `v_project_dashboard` (
`created_at` datetime
,`id_project` bigint unsigned
,`id_user` bigint unsigned
,`last_published_at` datetime
,`project_name` varchar(120)
,`public_url` varchar(255)
,`published_path` varchar(255)
,`slug` varchar(140)
,`status` enum('draft','published')
,`status_label` varchar(50)
,`total_files` decimal(23,0)
,`total_folders` decimal(23,0)
,`total_items` bigint
,`total_size_bytes` decimal(42,0)
,`updated_at` datetime
,`username` varchar(50)
,`workspace_path` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_publish_history`
-- (See below for the actual view)
--
CREATE TABLE `v_publish_history` (
`created_at` datetime
,`duration_seconds` bigint
,`finished_at` datetime
,`id_project` bigint unsigned
,`id_publish_job` bigint unsigned
,`id_user` bigint unsigned
,`message` text
,`project_name` varchar(120)
,`slug` varchar(140)
,`started_at` datetime
,`status` enum('queued','running','success','failed')
,`username` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_user_storage_usage`
-- (See below for the actual view)
--
CREATE TABLE `v_user_storage_usage` (
`id_user` bigint unsigned
,`total_items` bigint
,`total_projects` bigint
,`total_storage_bytes` decimal(42,0)
,`total_storage_mb` decimal(45,2)
,`username` varchar(50)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `fk_logs_project` (`id_project`),
  ADD KEY `idx_logs_user_created` (`id_user`,`created_at`),
  ADD KEY `idx_logs_action` (`action`);

--
-- Indexes for table `allowed_file_types`
--
ALTER TABLE `allowed_file_types`
  ADD PRIMARY KEY (`id_type`),
  ADD UNIQUE KEY `extension` (`extension`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id_project`),
  ADD UNIQUE KEY `uq_user_project_slug` (`id_user`,`slug`),
  ADD KEY `idx_projects_status` (`status`),
  ADD KEY `idx_projects_user_updated` (`id_user`,`updated_at`);

--
-- Indexes for table `project_files`
--
ALTER TABLE `project_files`
  ADD PRIMARY KEY (`id_file`),
  ADD UNIQUE KEY `uq_project_relative_path` (`id_project`,`relative_path`),
  ADD KEY `fk_files_parent` (`parent_id`),
  ADD KEY `idx_files_project_folder` (`id_project`,`is_folder`),
  ADD KEY `idx_files_extension` (`file_extension`);

--
-- Indexes for table `publish_jobs`
--
ALTER TABLE `publish_jobs`
  ADD PRIMARY KEY (`id_publish_job`),
  ADD KEY `fk_publish_project` (`id_project`),
  ADD KEY `fk_publish_user` (`id_user`),
  ADD KEY `idx_publish_status_created` (`status`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id_log` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `allowed_file_types`
--
ALTER TABLE `allowed_file_types`
  MODIFY `id_type` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id_project` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_files`
--
ALTER TABLE `project_files`
  MODIFY `id_file` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publish_jobs`
--
ALTER TABLE `publish_jobs`
  MODIFY `id_publish_job` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `v_project_dashboard`
--
DROP TABLE IF EXISTS `v_project_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_project_dashboard`  AS SELECT `p`.`id_project` AS `id_project`, `p`.`id_user` AS `id_user`, `u`.`username` AS `username`, `p`.`project_name` AS `project_name`, `p`.`slug` AS `slug`, `p`.`status` AS `status`, `fn_project_status_label`(`p`.`status`) AS `status_label`, `p`.`workspace_path` AS `workspace_path`, `p`.`published_path` AS `published_path`, `p`.`public_url` AS `public_url`, `p`.`last_published_at` AS `last_published_at`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, count(`f`.`id_file`) AS `total_items`, sum((case when (`f`.`is_folder` = 0) then 1 else 0 end)) AS `total_files`, sum((case when (`f`.`is_folder` = 1) then 1 else 0 end)) AS `total_folders`, coalesce(sum(`f`.`file_size`),0) AS `total_size_bytes` FROM ((`projects` `p` join `users` `u` on((`u`.`id_user` = `p`.`id_user`))) left join `project_files` `f` on((`f`.`id_project` = `p`.`id_project`))) GROUP BY `p`.`id_project`, `p`.`id_user`, `u`.`username`, `p`.`project_name`, `p`.`slug`, `p`.`status`, `p`.`workspace_path`, `p`.`published_path`, `p`.`public_url`, `p`.`last_published_at`, `p`.`created_at`, `p`.`updated_at` ;

-- --------------------------------------------------------

--
-- Structure for view `v_publish_history`
--
DROP TABLE IF EXISTS `v_publish_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_publish_history`  AS SELECT `pj`.`id_publish_job` AS `id_publish_job`, `pj`.`id_project` AS `id_project`, `pj`.`id_user` AS `id_user`, `u`.`username` AS `username`, `p`.`project_name` AS `project_name`, `p`.`slug` AS `slug`, `pj`.`status` AS `status`, `pj`.`message` AS `message`, `pj`.`started_at` AS `started_at`, `pj`.`finished_at` AS `finished_at`, timestampdiff(SECOND,`pj`.`started_at`,`pj`.`finished_at`) AS `duration_seconds`, `pj`.`created_at` AS `created_at` FROM ((`publish_jobs` `pj` join `users` `u` on((`u`.`id_user` = `pj`.`id_user`))) join `projects` `p` on((`p`.`id_project` = `pj`.`id_project`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_user_storage_usage`
--
DROP TABLE IF EXISTS `v_user_storage_usage`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_storage_usage`  AS SELECT `u`.`id_user` AS `id_user`, `u`.`username` AS `username`, count(distinct `p`.`id_project`) AS `total_projects`, count(`f`.`id_file`) AS `total_items`, coalesce(sum(`f`.`file_size`),0) AS `total_storage_bytes`, round(((coalesce(sum(`f`.`file_size`),0) / 1024) / 1024),2) AS `total_storage_mb` FROM ((`users` `u` left join `projects` `p` on((`p`.`id_user` = `u`.`id_user`))) left join `project_files` `f` on((`f`.`id_project` = `p`.`id_project`))) GROUP BY `u`.`id_user`, `u`.`username` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_logs_project` FOREIGN KEY (`id_project`) REFERENCES `projects` (`id_project`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `project_files`
--
ALTER TABLE `project_files`
  ADD CONSTRAINT `fk_files_parent` FOREIGN KEY (`parent_id`) REFERENCES `project_files` (`id_file`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_files_project` FOREIGN KEY (`id_project`) REFERENCES `projects` (`id_project`) ON DELETE CASCADE;

--
-- Constraints for table `publish_jobs`
--
ALTER TABLE `publish_jobs`
  ADD CONSTRAINT `fk_publish_project` FOREIGN KEY (`id_project`) REFERENCES `projects` (`id_project`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_publish_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
