-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: mysql.crosswiseconsulting.com
-- Generation Time: Jan 28, 2018 at 05:46 PM
-- Server version: 5.6.34-log
-- PHP Version: 7.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mtimporter`
--
CREATE DATABASE IF NOT EXISTS `mtimporter` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `mtimporter`;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `class` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `pages_per_import` int(2) UNSIGNED NOT NULL DEFAULT '3',
  `import_type_id` int(10) UNSIGNED NOT NULL,
  `extensions` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `import_domain` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `import_path` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `api_url` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `api_username` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `api_password` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `prod_import_path` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `import_drafts` tinyint(4) NOT NULL DEFAULT '0',
  `archived` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `import_queue`
--

CREATE TABLE `import_queue` (
  `import_queue_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `file` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `extension` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `status` enum('done','fail','processing','queue') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'queue',
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of queued Word imports.';

-- --------------------------------------------------------

--
-- Table structure for table `import_types`
--

CREATE TABLE `import_types` (
  `import_type_id` int(10) UNSIGNED NOT NULL,
  `import_type_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `import_type_class` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `import_type_file` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `import_type_extensions` varchar(100) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of installed import types.';

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `client` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `import_title` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `production` tinyint(1) NOT NULL DEFAULT '0',
  `report_data` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE `session` (
  `session_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `session_data` longtext COLLATE utf8_unicode_ci NOT NULL,
  `expires` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Session data.';

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `forgot_token` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `forgot_token_datetime` datetime NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_accessed` datetime NOT NULL,
  `expires_on` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Users table.';

-- --------------------------------------------------------

--
-- Table structure for table `user_to_clients`
--

CREATE TABLE `user_to_clients` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `client_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table to map users to clients.';

-- --------------------------------------------------------

--
-- Table structure for table `user_usage`
--

CREATE TABLE `user_usage` (
  `usage_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `type` enum('login','logout','upload','parse','process','queue') COLLATE utf8_unicode_ci NOT NULL,
  `pages` int(10) UNSIGNED NOT NULL,
  `size` int(10) UNSIGNED NOT NULL,
  `time` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table to monitor user usage.';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `import_type_id` (`import_type_id`);

--
-- Indexes for table `import_queue`
--
ALTER TABLE `import_queue`
  ADD PRIMARY KEY (`import_queue_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `import_types`
--
ALTER TABLE `import_types`
  ADD PRIMARY KEY (`import_type_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `import_title` (`import_title`);

--
-- Indexes for table `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`session_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_to_clients`
--
ALTER TABLE `user_to_clients`
  ADD PRIMARY KEY (`user_id`,`client_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `user_usage`
--
ALTER TABLE `user_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `user_id` (`user_id`,`type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_queue`
--
ALTER TABLE `import_queue`
  MODIFY `import_queue_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_types`
--
ALTER TABLE `import_types`
  MODIFY `import_type_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_usage`
--
ALTER TABLE `user_usage`
  MODIFY `usage_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_to_clients`
--
ALTER TABLE `user_to_clients`
  ADD CONSTRAINT `user_to_clients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_to_clients_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
