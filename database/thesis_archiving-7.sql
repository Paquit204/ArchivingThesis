-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2026 at 08:05 AM
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
-- Database: `thesis_archiving`
--

-- --------------------------------------------------------

--
-- Table structure for table `archive_table`
--

CREATE TABLE `archive_table` (
  `archive_id` int(11) NOT NULL,
  `thesis_id` int(11) NOT NULL,
  `archived_by` int(11) NOT NULL,
  `archive_date` datetime NOT NULL,
  `retention_period` int(11) DEFAULT 5,
  `archive_notes` text DEFAULT NULL,
  `access_level` varchar(20) DEFAULT 'public',
  `views_count` int(11) DEFAULT 0,
  `downloads_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(255) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`audit_id`, `user_id`, `action_type`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
(1, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', NULL, '2026-03-25 08:55:03'),
(2, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', NULL, '2026-03-25 08:55:04'),
(166, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 13:34:53'),
(167, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 13:50:10'),
(168, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 14:22:17'),
(169, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 14:22:36'),
(170, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 14:22:59'),
(171, 8, 'Viewed Audit Logs', 'audit_logs', 0, 'Admin viewed audit logs page', '127.0.0.1', '2026-04-21 14:23:02'),
(172, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 14:23:04'),
(173, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 14:28:33'),
(174, 8, 'Viewed Audit Logs', 'audit_logs', 0, 'Admin viewed audit logs page', '127.0.0.1', '2026-04-21 14:28:42'),
(175, 8, 'Viewed Audit Logs', 'audit_logs', 0, 'Admin viewed audit logs page', '127.0.0.1', '2026-04-21 14:32:35'),
(176, 8, 'Viewed Audit Logs', 'audit_logs', 0, 'Admin viewed audit logs page', '127.0.0.1', '2026-04-21 14:39:06'),
(177, 8, 'Viewed Audit Logs', 'audit_logs', 0, 'Admin viewed audit logs page', '127.0.0.1', '2026-04-21 14:39:07'),
(178, 8, 'Viewed Audit Logs', 'audit_logs', 0, 'Admin viewed audit logs page', '127.0.0.1', '2026-04-21 14:40:16'),
(179, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 14:53:50'),
(180, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 15:58:20'),
(181, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 19:19:42'),
(182, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 19:20:17'),
(183, 8, 'Edited User', 'user_table', 19, 'Edited user: Mark Paquit', '127.0.0.1', '2026-04-21 19:20:46'),
(184, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-21 19:21:21'),
(185, 8, 'Edited User', 'user_table', 19, 'Edited user: Mark Paquit', '127.0.0.1', '2026-04-21 19:21:35'),
(186, 8, 'Edited User', 'user_table', 19, 'Edited user: Mark Paquit', '127.0.0.1', '2026-04-21 19:21:41'),
(187, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 09:02:59'),
(188, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 09:05:12'),
(189, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 09:06:19'),
(190, 8, 'Edited User', 'user_table', 13, 'Edited user: Jorvin Pengoc', '127.0.0.1', '2026-04-22 09:06:31'),
(191, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 11:58:54'),
(192, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 11:58:59'),
(193, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 12:00:29'),
(194, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 12:02:08'),
(195, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 12:15:17'),
(196, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 12:15:40'),
(197, 8, 'Google Drive single backup', 'backup', 0, 'Admin backed up file to Google Drive', '127.0.0.1', '2026-04-22 12:15:47'),
(198, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 12:17:12'),
(199, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 12:17:43'),
(200, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 12:17:57'),
(201, 8, 'Google Drive single backup', 'backup', 0, 'Admin backed up file to Google Drive', '127.0.0.1', '2026-04-22 12:18:01'),
(202, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 12:19:05'),
(203, 8, 'Local single backup', 'backup', 0, 'Admin backed up file locally', '127.0.0.1', '2026-04-22 12:19:25'),
(204, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 12:24:57'),
(205, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 13:10:29'),
(206, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 13:18:53'),
(207, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 13:18:54'),
(208, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 13:19:06'),
(209, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 13:29:14'),
(210, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:29:16'),
(211, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:28'),
(212, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:30'),
(213, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:33'),
(214, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:35'),
(215, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:37'),
(216, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:40'),
(217, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:42'),
(218, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:45'),
(219, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:47'),
(220, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:49'),
(221, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 13:30:52'),
(222, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 13:31:03'),
(223, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 23:46:30'),
(224, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 23:48:00'),
(225, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 23:56:11'),
(226, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-22 23:56:20'),
(227, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-22 23:56:22'),
(228, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-23 00:01:33'),
(229, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 00:58:56'),
(230, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 00:59:01'),
(231, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 01:06:20'),
(232, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 01:06:32'),
(233, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 01:10:37'),
(234, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 08:33:17'),
(235, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 08:42:56'),
(236, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 08:43:52'),
(237, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-23 08:43:55'),
(238, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-23 08:44:20'),
(239, 8, 'Google Drive single backup', 'backup', 0, 'Admin backed up file to Google Drive', '127.0.0.1', '2026-04-23 08:44:29'),
(240, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 08:47:48'),
(241, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-23 08:47:50'),
(242, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-23 08:48:00'),
(243, 8, 'Single restore', 'backup', 0, 'Admin restored file', '127.0.0.1', '2026-04-23 08:48:26'),
(244, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 09:09:14'),
(245, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 09:19:14'),
(246, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 09:41:14'),
(247, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 10:07:57'),
(248, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 10:17:42'),
(249, 8, 'Admin accessed dashboard', 'user_table', 8, 'Admin Ivon Candilanza accessed the admin dashboard', '127.0.0.1', '2026-04-23 10:21:19'),
(250, 8, 'Added User', 'user_table', 23, 'Added new user: MR. BSBA', '127.0.0.1', '2026-04-23 10:41:02'),
(251, 8, 'Edited User', 'user_table', 14, 'Edited user: Tyrone James Dela Victoria', '127.0.0.1', '2026-04-23 10:55:20'),
(252, 8, 'Added User', 'user_table', 24, 'Added new user: MR. BSIT', '127.0.0.1', '2026-04-23 16:14:10'),
(253, 8, 'Edited User', 'user_table', 24, 'Edited user: MR. BSIT', '127.0.0.1', '2026-04-23 16:14:28'),
(254, 8, 'Edited User', 'user_table', 24, 'Edited user: MR. BSIT', '127.0.0.1', '2026-04-23 16:21:49'),
(255, 8, 'Edited User', 'user_table', 10, 'Edited user: Jorvin Pengoc', '127.0.0.1', '2026-04-23 16:22:09'),
(256, 8, 'Edited User', 'user_table', 8, 'Edited user: Ivon Candilanza', '127.0.0.1', '2026-04-23 16:22:28'),
(257, 8, 'Edited User', 'user_table', 7, 'Edited user: Tyrone James', '127.0.0.1', '2026-04-23 16:22:45'),
(258, 8, 'Edited User', 'user_table', 10, 'Edited user: MR. BSCRIM', '127.0.0.1', '2026-04-23 16:23:38'),
(259, 8, 'Edited User', 'user_table', 7, 'Edited user: MR> BSHTM', '127.0.0.1', '2026-04-23 16:24:00'),
(260, 8, 'Added User', 'user_table', 25, 'Added new user: MS. BSED', '127.0.0.1', '2026-04-23 16:26:48'),
(261, 8, 'Added User', 'user_table', 26, 'Added new user: MR. BSBA', '127.0.0.1', '2026-04-23 16:27:58'),
(262, 8, 'Edited User', 'user_table', 11, 'Edited user: Joyce Geocallo', '127.0.0.1', '2026-04-23 16:28:31'),
(263, 8, 'Edited User', 'user_table', 12, 'Edited user: Joyce Camille', '127.0.0.1', '2026-04-23 16:29:52'),
(264, 8, 'Edited User', 'user_table', 13, 'Edited user: Jorvin Pengoc', '127.0.0.1', '2026-04-23 16:30:04'),
(265, 8, 'Edited User', 'user_table', 16, 'Edited user: Ivon Candilanza', '127.0.0.1', '2026-04-23 16:30:16'),
(266, 8, 'Edited User', 'user_table', 9, 'Edited user: Mylene Sellar', '127.0.0.1', '2026-04-23 16:32:09'),
(267, 8, 'Edited User', 'user_table', 24, 'Edited user: MR. BSIT', '127.0.0.1', '2026-04-23 16:36:23'),
(268, 8, 'Edited User', 'user_table', 7, 'Edited user: MR. BSHTM', '127.0.0.1', '2026-04-23 16:37:59'),
(269, 8, 'Edited User', 'user_table', 25, 'Edited user: MS. BSED', '127.0.0.1', '2026-04-23 16:58:17'),
(270, 8, 'Edited User', 'user_table', 27, 'Edited user: April Raganas', '127.0.0.1', '2026-04-23 23:54:52'),
(271, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-23 23:55:26'),
(272, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-23 23:55:31'),
(273, 8, 'Admin accessed backup page', 'backup', 0, 'Admin opened backup management', '127.0.0.1', '2026-04-23 23:55:34'),
(274, 8, 'Edited User', 'user_table', 17, 'Edited user: Mylene Villareal', '127.0.0.1', '2026-04-24 00:12:55'),
(275, 8, 'Edited User', 'user_table', 17, 'Edited user: Mylene Villareal', '127.0.0.1', '2026-04-24 00:13:38'),
(276, 8, 'Edited User', 'user_table', 17, 'Edited user: Mylene Villareal', '127.0.0.1', '2026-04-24 00:15:54'),
(277, 8, 'Edited User', 'user_table', 8, 'Edited user: Ivon Candilanza', '127.0.0.1', '2026-04-24 00:40:42'),
(278, 8, 'Edited User', 'user_table', 8, 'Edited user: Ivon Candilanza', '127.0.0.1', '2026-04-24 00:40:48'),
(279, 8, 'Edited User', 'user_table', 8, 'Edited user: Ivon Candilanza', '127.0.0.1', '2026-04-24 00:40:54'),
(280, 8, 'Added User', 'user_table', 28, 'Added new user: MS. BSCRIM', '127.0.0.1', '2026-04-24 01:16:47'),
(281, 8, 'Added User', 'user_table', 29, 'Added new user: MS. BSHTM', '127.0.0.1', '2026-04-24 01:17:35'),
(282, 8, 'Edited User', 'user_table', 1, 'Edited user: mylenee raganas', '127.0.0.1', '2026-04-24 01:17:48'),
(283, 8, 'Edited User', 'user_table', 5, 'Edited user: catalina sellar', '127.0.0.1', '2026-04-24 03:08:56'),
(284, 8, 'Edited User', 'user_table', 11, 'Edited user: Joyce Geocallo', '127.0.0.1', '2026-04-24 03:09:06'),
(285, 8, 'Edited User', 'user_table', 23, 'Edited user: MR. BSBA', '127.0.0.1', '2026-04-24 03:09:14'),
(286, 8, 'Edited User', 'user_table', 28, 'Edited user: MS. BSCRIM', '127.0.0.1', '2026-04-24 03:09:24'),
(287, 8, 'Edited User', 'user_table', 29, 'Edited user: MS. BSHTM', '127.0.0.1', '2026-04-24 03:09:40'),
(288, 8, 'Edited User', 'user_table', 29, 'Edited user: MS. BSHTM', '127.0.0.1', '2026-04-24 03:10:17'),
(289, 8, 'Edited User', 'user_table', 29, 'Edited user: MS. BSHTM', '127.0.0.1', '2026-04-24 03:10:30'),
(290, 8, 'Edited User', 'user_table', 5, 'Edited user: catalina sellar', '127.0.0.1', '2026-04-24 03:11:42'),
(291, 8, 'Edited User', 'user_table', 5, 'Edited user: catalina sellar', '127.0.0.1', '2026-04-24 03:12:47'),
(292, 8, 'Edited User', 'user_table', 7, 'Edited user: MR. BSHTM', '127.0.0.1', '2026-04-24 03:43:25'),
(293, 8, 'Edited User', 'user_table', 10, 'Edited user: MR. BSCRIM', '127.0.0.1', '2026-04-24 03:43:47');

-- --------------------------------------------------------

--
-- Table structure for table `certificates_table`
--

CREATE TABLE `certificates_table` (
  `certificate_id` int(11) NOT NULL,
  `thesis_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `certificate_file` varchar(255) NOT NULL,
  `generated_date` datetime NOT NULL,
  `downloaded_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates_table`
--

INSERT INTO `certificates_table` (`certificate_id`, `thesis_id`, `student_id`, `certificate_file`, `generated_date`, `downloaded_count`) VALUES
(1, 6, 2, 'certificate_6_1773283503.html', '2026-03-12 10:45:03', 0),
(2, 7, 2, 'certificate_7_1773322049.html', '2026-03-12 21:27:29', 0);

-- --------------------------------------------------------

--
-- Table structure for table `department_coordinator`
--

CREATE TABLE `department_coordinator` (
  `coordinator_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `position` varchar(255) NOT NULL,
  `assigned_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_table`
--

CREATE TABLE `department_table` (
  `department_id` int(11) NOT NULL,
  `department_code` varchar(100) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_table`
--

INSERT INTO `department_table` (`department_id`, `department_code`, `department_name`, `created_at`) VALUES
(1, 'BSIT', 'BS Information Technology', '2026-04-20 02:21:23'),
(2, 'BSCRIM', 'BS Criminology', '2026-04-20 02:21:23'),
(3, 'BSHTM', 'BS Hospitality Management', '2026-04-20 02:21:23'),
(4, 'BSED', 'BS Education', '2026-04-20 02:21:23'),
(5, 'BSBA', 'BS Business Administration', '2026-04-20 02:21:23');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_table`
--

CREATE TABLE `faculty_table` (
  `faculty_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `specialization` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_table`
--

CREATE TABLE `feedback_table` (
  `feedback_id` int(11) NOT NULL,
  `thesis_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `comments` text NOT NULL,
  `feedback_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_table`
--

INSERT INTO `feedback_table` (`feedback_id`, `thesis_id`, `faculty_id`, `comments`, `feedback_date`) VALUES
(5, 4, 5, 'sample', '2026-03-04 09:59:44'),
(6, 5, 5, 'wrong', '2026-03-04 10:52:26'),
(7, 5, 5, 'wrong', '2026-03-04 10:52:32'),
(8, 6, 5, 'okay', '2026-03-11 13:04:13'),
(9, 7, 5, 'try', '2026-03-12 13:27:29'),
(10, 40, 5, 'okay', '2026-04-23 07:30:20'),
(11, 41, 5, 'all goods', '2026-04-23 08:39:39'),
(12, 45, 23, 'hsdbf', '2026-04-23 11:03:26'),
(13, 20, 5, 'hmmm', '2026-04-24 00:17:49');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('project_manager','co_author') DEFAULT 'co_author',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `thesis_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `type` varchar(50) DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `thesis_id`, `message`, `is_read`, `type`, `link`, `created_at`) VALUES
(153, 20, NULL, '📢 April Raganas invited you to collaborate as co-author!', 0, 'coauthor_invitation', NULL, '2026-04-23 23:52:28'),
(154, 2, 20, '📢 New thesis forwarded for review: \"The Influence of Social Media Usage on Academic Performance Among Senior High School Students\" from student Ivon Candilanza. Faculty: catalina sellar has approved this thesis.', 0, 'faculty_forward', '../coordinator/reviewThesis.php?id=20', '2026-04-24 00:17:49'),
(155, 9, 20, '📢 New thesis forwarded for review: \"The Influence of Social Media Usage on Academic Performance Among Senior High School Students\" from student Ivon Candilanza. Faculty: catalina sellar has approved this thesis.', 1, 'faculty_forward', '../coordinator/reviewThesis.php?id=20', '2026-04-24 00:17:49'),
(156, 16, 20, '✅ Your thesis \"The Influence of Social Media Usage on Academic Performance Among Senior High School Students\" has been approved by faculty catalina sellar and forwarded to the coordinator for final review.', 0, 'thesis_approved', NULL, '2026-04-24 00:17:49'),
(157, 7, 20, '📋 Thesis ready for Dean approval: \"The Influence of Social Media Usage on Academic Performance Among Senior High School Students\" from student Ivon Candilanza. Forwarded by Coordinator: Mylene Sellar', 1, 'dean_forward', NULL, '2026-04-24 00:25:44'),
(158, 10, 20, '📋 Thesis ready for Dean approval: \"The Influence of Social Media Usage on Academic Performance Among Senior High School Students\" from student Ivon Candilanza. Forwarded by Coordinator: Mylene Sellar', 0, 'dean_forward', NULL, '2026-04-24 00:25:44'),
(159, 24, 20, '📋 Thesis ready for Dean approval: \"The Influence of Social Media Usage on Academic Performance Among Senior High School Students\" from student Ivon Candilanza. Forwarded by Coordinator: Mylene Sellar', 1, 'dean_forward', NULL, '2026-04-24 00:25:44'),
(160, 25, 20, '📋 Thesis ready for Dean approval: \"The Influence of Social Media Usage on Academic Performance Among Senior High School Students\" from student Ivon Candilanza. Forwarded by Coordinator: Mylene Sellar', 0, 'dean_forward', NULL, '2026-04-24 00:25:44'),
(161, 26, 20, '📋 Thesis ready for Dean approval: \"The Influence of Social Media Usage on Academic Performance Among Senior High School Students\" from student Ivon Candilanza. Forwarded by Coordinator: Mylene Sellar', 0, 'dean_forward', NULL, '2026-04-24 00:25:44'),
(162, 16, 20, '📢 Your thesis \"The Influence of Social Media Usage on Academic Performance Among Senior High School Students\" has been forwarded to the Dean for final approval by Coordinator Mylene Sellar', 0, 'student_notif', NULL, '2026-04-24 00:25:44'),
(163, 5, 46, '📢 New thesis submission from Ivon Candilanza: \"Enhancing Customer Satisfaction Through Service Qu...\"', 1, 'thesis_submission', '../faculty/reviewThesis.php?id=46', '2026-04-24 00:43:18'),
(164, 11, 46, '📢 New thesis submission from Ivon Candilanza: \"Enhancing Customer Satisfaction Through Service Qu...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=46', '2026-04-24 00:43:18'),
(165, 23, 46, '📢 New thesis submission from Ivon Candilanza: \"Enhancing Customer Satisfaction Through Service Qu...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=46', '2026-04-24 00:43:18'),
(166, 5, 58, '📢 New thesis submission from Mylene Raganas: \"Eco-Tourism Practices and Tourist Satisfaction in ...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=58', '2026-04-24 02:54:46'),
(167, 11, 58, '📢 New thesis submission from Mylene Raganas: \"Eco-Tourism Practices and Tourist Satisfaction in ...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=58', '2026-04-24 02:54:46'),
(168, 23, 58, '📢 New thesis submission from Mylene Raganas: \"Eco-Tourism Practices and Tourist Satisfaction in ...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=58', '2026-04-24 02:54:46'),
(169, 28, 58, '📢 New thesis submission from Mylene Raganas: \"Eco-Tourism Practices and Tourist Satisfaction in ...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=58', '2026-04-24 02:54:46'),
(170, 29, 58, '📢 New thesis submission from Mylene Raganas: \"Eco-Tourism Practices and Tourist Satisfaction in ...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=58', '2026-04-24 02:54:46'),
(171, 5, 59, '📢 New thesis submission from Mylene Raganas: \"Eco-Tourism Practices and Tourist Satisfaction in ...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=59', '2026-04-24 02:57:47'),
(172, 11, 59, '📢 New thesis submission from Mylene Raganas: \"Eco-Tourism Practices and Tourist Satisfaction in ...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=59', '2026-04-24 02:57:47'),
(173, 23, 59, '📢 New thesis submission from Mylene Raganas: \"Eco-Tourism Practices and Tourist Satisfaction in ...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=59', '2026-04-24 02:57:47'),
(174, 28, 59, '📢 New thesis submission from Mylene Raganas: \"Eco-Tourism Practices and Tourist Satisfaction in ...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=59', '2026-04-24 02:57:47'),
(175, 29, 59, '📢 New thesis submission from Mylene Raganas: \"Eco-Tourism Practices and Tourist Satisfaction in ...\"', 0, 'thesis_submission', '../faculty/reviewThesis.php?id=59', '2026-04-24 02:57:47'),
(176, 5, 62, '📢 New thesis submission from Ivon Candilanza: \"CUSTOMER PERCEPTION OF SERVICE QUALITY IN FAST-FOO...\"', 1, 'thesis_submission', '../faculty/reviewThesis.php?id=62', '2026-04-24 03:31:48'),
(177, 3, 62, '📢 Ivon Candilanza invited you to collaborate on thesis: \"CUSTOMER PERCEPTION OF SERVICE QUALITY IN FAST-FOOD RESTAURANTS\"', 0, 'thesis_invitation', NULL, '2026-04-24 03:31:48'),
(178, 2, 62, '📢 New thesis forwarded for review: \"CUSTOMER PERCEPTION OF SERVICE QUALITY IN FAST-FOOD RESTAURANTS\" from student Ivon Candilanza. Faculty: catalina sellar has approved this thesis.', 0, 'faculty_forward', '../coordinator/reviewThesis.php?id=62', '2026-04-24 03:36:10'),
(179, 9, 62, '📢 New thesis forwarded for review: \"CUSTOMER PERCEPTION OF SERVICE QUALITY IN FAST-FOOD RESTAURANTS\" from student Ivon Candilanza. Faculty: catalina sellar has approved this thesis.', 1, 'faculty_forward', '../coordinator/reviewThesis.php?id=62', '2026-04-24 03:36:10'),
(180, 16, 62, '✅ Your thesis \"CUSTOMER PERCEPTION OF SERVICE QUALITY IN FAST-FOOD RESTAURANTS\" has been approved by faculty catalina sellar and forwarded to the coordinator for final review.', 0, 'thesis_approved', NULL, '2026-04-24 03:36:10');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `otp`, `expires_at`, `is_used`, `created_at`) VALUES
(19, 'geocallocamillejoyce72@gmail.com', '629409', '2026-04-23 18:22:16', 1, '2026-04-23 16:07:16'),
(21, 'myleneraganas@gmail.com', '126006', '2026-04-24 02:29:17', 1, '2026-04-24 00:14:17'),
(22, 'mylenesellar13@gmail.com', '108418', '2026-04-24 05:29:52', 0, '2026-04-24 03:14:52');

-- --------------------------------------------------------

--
-- Table structure for table `pending_invitations`
--

CREATE TABLE `pending_invitations` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `invited_by_name` varchar(255) DEFAULT NULL,
  `thesis_title` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_table`
--

CREATE TABLE `role_table` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `thesis_collaborators`
--

CREATE TABLE `thesis_collaborators` (
  `collaborator_id` int(11) NOT NULL,
  `thesis_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT 'co-author',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thesis_collaborators`
--

INSERT INTO `thesis_collaborators` (`collaborator_id`, `thesis_id`, `user_id`, `role`, `joined_at`) VALUES
(2, 24, 13, 'owner', '2026-04-22 13:35:20'),
(11, 33, 13, 'owner', '2026-04-22 22:21:04'),
(15, 37, 13, 'owner', '2026-04-22 22:57:38'),
(16, 38, 13, 'owner', '2026-04-22 23:08:09'),
(17, 39, 16, 'owner', '2026-04-23 05:47:01'),
(18, 40, 16, 'owner', '2026-04-23 05:54:55'),
(19, 41, 8, 'owner', '2026-04-23 08:36:27'),
(20, 42, 16, 'owner', '2026-04-23 09:00:36'),
(21, 43, 16, 'owner', '2026-04-23 09:07:05'),
(22, 44, 16, 'owner', '2026-04-23 09:07:22'),
(23, 45, 14, 'owner', '2026-04-23 11:01:08'),
(24, 46, 16, 'owner', '2026-04-24 00:43:18'),
(25, 58, 4, 'owner', '2026-04-24 02:54:46'),
(26, 59, 4, 'owner', '2026-04-24 02:57:47'),
(27, 61, 16, 'owner', '2026-04-24 03:06:51'),
(28, 62, 16, 'owner', '2026-04-24 03:31:48');

-- --------------------------------------------------------

--
-- Table structure for table `thesis_groups`
--

CREATE TABLE `thesis_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `project_manager_id` int(11) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `thesis_invitations`
--

CREATE TABLE `thesis_invitations` (
  `invitation_id` int(11) NOT NULL,
  `thesis_id` int(11) NOT NULL,
  `invited_user_id` int(11) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `is_read` enum('pending','accepted','declined') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thesis_invitations`
--

INSERT INTO `thesis_invitations` (`invitation_id`, `thesis_id`, `invited_user_id`, `invited_by`, `is_read`, `created_at`, `updated_at`) VALUES
(4, 40, 3, 16, 'pending', '2026-04-23 05:55:01', '2026-04-23 05:55:01'),
(5, 41, 3, 8, 'pending', '2026-04-23 08:36:38', '2026-04-23 08:36:38'),
(6, 43, 14, 16, 'pending', '2026-04-23 09:07:16', '2026-04-23 09:07:16'),
(7, 44, 14, 16, 'pending', '2026-04-23 09:07:33', '2026-04-23 09:07:33'),
(8, 45, 20, 14, 'pending', '2026-04-23 11:01:23', '2026-04-23 11:01:23'),
(9, 62, 3, 16, 'pending', '2026-04-24 03:31:48', '2026-04-24 03:31:48');

-- --------------------------------------------------------

--
-- Table structure for table `thesis_table`
--

CREATE TABLE `thesis_table` (
  `thesis_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `abstract` text NOT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `year` varchar(4) DEFAULT NULL,
  `adviser` varchar(255) NOT NULL,
  `is_read` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `date_submitted` datetime NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_date` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `retention_period` int(11) DEFAULT NULL COMMENT 'in years',
  `archive_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thesis_table`
--

INSERT INTO `thesis_table` (`thesis_id`, `student_id`, `title`, `abstract`, `keywords`, `department_id`, `year`, `adviser`, `is_read`, `file_path`, `date_submitted`, `is_archived`, `archived_date`, `archived_by`, `retention_period`, `archive_notes`) VALUES
(4, 0, 'trydfasdfasdfd dfregtrt hghrtyh  grtg', 'YJERYTJRTHNGHTYIHGTNHNGJNHHIOTE[TH JITJHGERJG RJGIRUGKJ\'Q[PURGT JHIRGH IRHTUWRTN RKTHGREGNGIOSDGJITW]0RT45TJI84TJVJIREOUT5TIJQERQIERHERVJ IJRIEURGJIURGJRGRWY', NULL, NULL, NULL, 'MR. BREGILDO', 'approved', 'uploads/manuscripts/1772351838_69a3f15e2d232_trydfasdfasdfd_dfregtrt_hghrtyh__grtg.pdf', '2026-03-01 08:57:18', 0, NULL, NULL, NULL, NULL),
(5, 0, 'trydfasdfasdfd dfregtrt hghrtyh  grtg', 'YJERYTJRTHNGHTYIHGTNHNGJNHHIOTE[TH JITJHGERJG RJGIRUGKJ\'Q[PURGT JHIRGH IRHTUWRTN RKTHGREGNGIOSDGJITW]0RT45TJI84TJVJIREOUT5TIJQERQIERHERVJ IJRIEURGJIURGJRGRWY', NULL, NULL, NULL, 'MR. BREGILDO', 'rejected', 'uploads/manuscripts/1772351971_69a3f1e343482_trydfasdfasdfd_dfregtrt_hghrtyh__grtg.pdf', '2026-03-01 08:59:31', 0, NULL, NULL, NULL, NULL),
(6, 0, 'archiving', 'bvhvbsufvbc v;sfva;odfvjfdbvjb;djvb;scvnb;bnmdfbjsfbhb bhfgldygf hggyugf hpiydgfdf fagfpf bvbadufh osndjdv oksyy kfjvsdogf fkgdafjg jhgfoushg jhgouseg njgoiad irhhg\'ifhg goufgjnj fngjfhs jngjsnfgjnfg', NULL, NULL, NULL, 'camille joyce geocallo', 'approved', 'uploads/manuscripts/1773064034_69aecf629c828_archiving.pdf', '2026-03-09 14:47:14', 0, NULL, NULL, NULL, NULL),
(7, 0, 'enrollment', 'enrollment is the process where students officially register in a school or educational institution for a specific academic term. during this process, students submit required documents, select or confirm their courses, and pay necessary fees. the school records the student’s information in the system to confirm their admission and allow them to attend classes. enrollment helps the institution organize student records, manage class schedules, and ensure that students are properly registered for their chosen program.', NULL, NULL, NULL, 'MR. BREGILDO', 'approved', 'uploads/manuscripts/1773321994_69b2bf0ae329f_enrollment.pdf', '2026-03-12 14:26:34', 0, NULL, NULL, NULL, NULL),
(8, 0, 'web-based thesis archiving system', 'this study aims to develop and evaluate a web-based thesis archiving system designed to improve the storage, organization, and accessibility of academic research within a private college. many institutions still rely on manual or paper-based archiving of theses, which makes searching, retrieving, and managing research documents difficult and time-consuming. the proposed system provides a centralized digital platform where students can submit their thesis, while administrators and faculty members can review, approve, and archive these documents efficiently.\r\n\r\nthe system includes features such as user management, thesis submission, approval workflow, digital archiving, and report generation. it also allows authorized users to easily search and retrieve archived theses, improving accessibility for academic reference and research purposes. the development of the system follows a systematic process that includes requirements analysis, system design, development, and testing.', 'web-based system, thesis archiving, digital repository, academic document management, information retrieval, research management system', NULL, '2026', 'MR. BREGILDO', 'pending', 'uploads/manuscripts/1773401374_69b3f51e282ec_web_based_thesis_archiving_system.pdf', '2026-03-13 12:29:34', 0, NULL, NULL, NULL, NULL),
(9, 0, 'digital research repository and management system for academic institutions', 'this study focuses on the development of a digital research repository and management system designed to improve the storage, organization, and accessibility of academic research within an educational institution. many schools still rely on traditional or manual methods of managing student research outputs, which often leads to difficulties in searching, retrieving, and preserving important documents. the proposed system provides a centralized digital platform where research papers, theses, and other academic documents can be securely uploaded, stored, and accessed by authorized users such as administrators, faculty members, and students. the system includes features such as document categorization, search functionality, secure user authentication, and administrative management tools. through the implementation of this system, the institution can enhance research accessibility, ensure better document preservation, and support knowledge sharing among students and faculty members.', 'digital repository, research management system, academic documents, information system, research database', NULL, '2026', 'MR. BREGILDO', 'pending', 'uploads/manuscripts/1773411824_69b41df0692c6_digital_research_repository_and_management_system_.pdf', '2026-03-13 15:23:44', 0, NULL, NULL, NULL, NULL),
(10, 0, 'student research management and digital storage system', 'this study focuses on the development of a digital research repository and management system designed to improve the storage, organization, and accessibility of academic research within an educational institution. many schools still rely on traditional or manual methods of managing student research outputs, which often leads to difficulties in searching, retrieving, and preserving important documents. the proposed system provides a centralized digital platform where research papers, theses, and other academic documents can be securely uploaded, stored, and accessed by authorized users such as administrators, faculty members, and students. the system includes features such as document categorization, search functionality, secure user authentication, and administrative management tools. through the implementation of this system, the institution can enhance research accessibility, ensure better document preservation, and support knowledge sharing among students and faculty members.', 'digital repository, research management system, academic documents, information system, research database', NULL, '2025', 'Ms.Camille Joyce Geocallo', 'approved', 'uploads/manuscripts/1773412463_69b4206f36811_student_research_management_and_digital_storage_sy.pdf', '2026-03-13 15:34:23', 0, NULL, NULL, NULL, NULL),
(12, 4, 'Smart Academic Document Management and Retrieval System', 'This study aims to design and develop a smart academic document management and retrieval system that will improve the storage, organization, and accessibility of academic documents such as theses, research papers, and institutional records. the system provides a centralized digital repository where users can securely upload, manage, and retrieve files using advanced search features and metadata indexing. it also includes user access control to ensure data security and proper authorization among administrators, faculty, and students. the proposed system addresses common issues such as document loss, slow manual retrieval, and lack of organization in traditional filing systems. by implementing a digital solution, the system enhances efficiency, accuracy, and convenience in handling academic documents. furthermore, it supports long-term preservation and easy sharing of knowledge within the institution. the study demonstrates how technology can improve academic workflows and contribute to better information management practices.', 'document management, digital archiving, academic system, information retrieval, metadata indexing, file management, data security', NULL, '2027', 'MR. BREGILDO', 'pending', 'uploads/manuscripts/1775819879_69d8dc6780425_Smart_Academic_Document_Management_and_Retrieval_S.pdf', '2026-04-10 19:17:59', 0, NULL, NULL, NULL, NULL),
(13, 4, 'ai-powered academic document archiving and intelligent retrieval system', 'this study focuses on the development of an ai-powered academic document archiving and intelligent retrieval system designed to enhance the management of academic files such as theses, dissertations, and research papers. the system utilizes artificial intelligence techniques, including natural language processing and metadata-based indexing, to enable faster and more accurate document searching and classification. it provides a centralized, web-based platform where users can upload, organize, and retrieve documents efficiently. the system also integrates role-based access control to ensure data privacy and security among administrators, faculty members, and students. unlike traditional manual filing systems, the proposed solution minimizes document loss, reduces retrieval time, and improves overall productivity. additionally, the intelligent search feature allows users to find relevant documents using keywords, phrases, or related topics. the implementation of this system promotes digital transformation in academic institutions and supports effective knowledge sharing and long-term data preservation.', 'artificial intelligence, document archiving, intelligent retrieval, natural language processing, academic system, metadata indexing, data security, web-based system', NULL, '2026', 'MR. BREGILDO', 'archived', 'uploads/manuscripts/1775822062_69d8e4ee72314_ai_powered_academic_document_archiving_and_intelli.pdf', '2026-04-10 19:54:22', 0, '2026-04-20 13:38:02', 0, NULL, NULL),
(14, 4, 'cloud-based academic repository system with intelligent search and recommendation engine', 'this study aims to develop a cloud-based academic repository system integrated with an intelligent search and recommendation engine to improve the storage and accessibility of academic documents such as theses, dissertations, and research papers. the system allows users to upload and manage documents in a centralized cloud environment, ensuring scalability, data security, and remote accessibility. it incorporates advanced search functionality using keyword matching and metadata filtering to provide fast and accurate results. additionally, a recommendation engine suggests related documents based on user queries and document content, enhancing research efficiency and knowledge discovery. the system also implements role-based access control to regulate user permissions and protect sensitive data. by replacing traditional manual archiving methods, the proposed system reduces document loss, improves retrieval speed, and supports collaborative academic work. this innovation contributes to the digital transformation of academic institutions and promotes efficient information management.', 'cloud computing, academic repository, recommendation system, information retrieval, metadata, digital archiving, data security, web-based system', NULL, '2025', 'Ms.Camille Joyce Geocallo', 'archived', 'uploads/manuscripts/1775826387_69d8f5d300da1_cloud_based_academic_repository_system_with_intell.pdf', '2026-04-10 21:06:27', 0, '2026-04-10 23:13:24', NULL, NULL, NULL),
(15, 4, 'Development of a Mobile-Based Campus Navigation System Using QR Code Technology', 'Navigating large educational campuses can be challenging for new students and visitors. This study aimed to develop a mobile-based campus navigation system that utilizes QR code technology to provide real-time directions and location-based information. The system allows users to scan QR codes placed in strategic locations to access maps, building details, and route guidance. The development followed the Agile methodology, incorporating user feedback throughout the design process. Testing results showed that the system significantly improved navigation efficiency and user satisfaction compared to traditional signage. The study concludes that integrating QR code technology in campus navigation is a cost-effective and user-friendly solution.', 'Mobile application, QR code, campus navigation, user experience, Agile development', NULL, '2026', 'Mylene Sellar', 'pending', 'uploads/manuscripts/1775911133_69da40dd69730_Development_of_a_Mobile_Based_Campus_Navigation_Sy.pdf', '2026-04-11 20:38:53', 0, NULL, NULL, NULL, NULL),
(16, 14, 'The Impact of Digital Marketing Strategies on Small Business Growth in the Philippines', 'This study examines the impact of digital marketing strategies on the growth of small businesses in the Philippines. With the rapid expansion of internet usage and social media platforms, small enterprises are increasingly adopting digital tools to enhance their market reach and competitiveness. The research focuses on commonly used strategies such as social media marketing, search engine optimization (SEO), email marketing, and online advertising. Using a quantitative research design, data were collected from small business owners through structured surveys to assess the effectiveness of these strategies in terms of customer engagement, sales growth, and brand visibility.\r\n\r\nThe findings reveal that digital marketing significantly contributes to business growth, particularly through social media platforms, which provide cost-effective and targeted marketing opportunities. Moreover, businesses that consistently implement digital strategies demonstrate higher customer retention and improved financial performance. However, challenges such as limited technical knowledge, budget constraints, and rapidly changing digital trends were also identified.\r\n\r\nThe study concludes that digital marketing is a crucial factor in the sustainability and expansion of small businesses in the Philippines. It recommends that business owners invest in digital skills development and adopt strategic marketing plans to maximize the benefits of digital platforms.', 'Digital Marketing, Small Business Growth, Social Media Marketing, Philippines, Online Advertising, Customer Engagement, SEO, E-commerce', 3, '2026', 'Ms.Camille Joyce Geocallo', 'approved', 'uploads/manuscripts/1776653947_69e5967b8d795_The_Impact_of_Digital_Marketing_Strategies_on_Smal.pdf', '2026-04-20 10:59:07', 0, NULL, NULL, NULL, NULL),
(17, 14, 'Design and Implementation of an AI-Powered Chatbot for Student Services', 'This study focuses on the design and implementation of an AI-powered chatbot for student services aimed at improving accessibility, efficiency, and responsiveness in handling student inquiries. Educational institutions often face challenges in providing timely support due to the high volume of requests related to admissions, enrollment, schedules, and general academic information. To address this issue, the research proposes the development of a chatbot system capable of delivering instant, accurate, and user-friendly responses.\r\n\r\nThe study adopts a development-based research approach, utilizing natural language processing (NLP) techniques and machine learning algorithms to enable the chatbot to understand and respond to user queries effectively. The system is designed to be accessible through web and mobile platforms, ensuring convenience for students. Testing and evaluation were conducted using usability metrics such as response accuracy, user satisfaction, and system efficiency.\r\n\r\nResults indicate that the AI-powered chatbot significantly reduces response time and improves user satisfaction compared to traditional student service methods. The chatbot demonstrated a high level of accuracy in answering frequently asked questions and provided consistent support without human intervention. However, limitations were observed in handling complex or ambiguous queries, highlighting the need for continuous training and system improvement.\r\n\r\nThe study concludes that AI-powered chatbots can serve as a valuable tool in enhancing student services, streamlining communication, and supporting digital transformation in educational institutions.', 'AI Chatbot, Student Services, Natural Language Processing, Machine Learning, Automation, User Satisfaction, Educational Technology, System Development', 1, '2026', 'MR. BREGILDO', 'pending', 'uploads/manuscripts/1776654743_69e5999722085_Design_and_Implementation_of_an_AI_Powered_Chatbot.pdf', '2026-04-20 11:12:23', 0, NULL, NULL, NULL, NULL),
(20, 16, 'The Influence of Social Media Usage on Academic Performance Among Senior High School Students', 'This study explores the influence of social media usage on the academic performance of senior high school students. With the widespread use of platforms such as Facebook, Instagram, and TikTok, students spend a significant amount of time ონლაინ, which may affect their study habits and overall academic outcomes. The research aims to determine whether there is a significant relationship between the frequency and purpose of social media use and students’ academic performance.\r\n\r\nA quantitative research design was employed, using structured questionnaires distributed to senior high school students. The data collected included information on time spent on social media, types of platforms used, and students’ grade point averages (GPA). Statistical analysis was conducted to identify correlations between social media usage patterns and academic achievement.\r\n\r\nThe findings indicate that excessive use of social media for non-academic purposes is associated with lower academic performance, while moderate and academically-oriented use can have positive effects, such as improved collaboration and access to learning resources. The study also reveals that time management plays a crucial role in balancing social media use and academic responsibilities.\r\n\r\nThe study concludes that while social media can be a valuable educational tool, improper and excessive use may hinder academic success. It is recommended that students practice responsible usage, and that educators and parents guide students in developing effective time management and digital literacy skills.', 'Social Media, Academic Performance, Senior High School Students, Time Management, Digital Literacy, Online Behavior, Education Technology', 1, '2026', 'MR. BREGILDO', 'archived', 'uploads/manuscripts/1776668989_69e5d13dd1c1b_The_Influence_of_Social_Media_Usage_on_Academic_Pe.pdf', '2026-04-20 15:09:49', 0, '2026-04-20 13:06:29', 0, NULL, NULL),
(21, 16, 'Web-Based Thesis Archiving System', 'A Web-Based Thesis Archiving System is designed to provide an efficient and centralized platform for storing, managing, and retrieving academic research documents. Traditional methods of thesis storage, such as physical libraries and manual filing systems, often lead to difficulties in access, risk of document loss, and time-consuming retrieval processes. This study aims to develop a web-based solution that digitizes thesis documents and enables users to easily upload, search, and access research materials through an online interface.\r\n\r\nThe system utilizes database management technologies to securely store documents and incorporates search and indexing functionalities to improve information retrieval. User authentication features are implemented to ensure that only authorized individuals can upload, modify, or access files. Additionally, the system supports categorization and filtering to enhance usability and organization of academic records.\r\n\r\nThe proposed system improves accessibility, reduces physical storage limitations, and enhances the overall efficiency of thesis management in educational institutions. It serves as a reliable digital repository that promotes knowledge sharing and preserves academic work for future reference.', 'Web-based system, digital repository, thesis archiving, document management, information retrieval, database management, user authentication, and academic records management.', 1, '2026', 'MR. BREGILDO', 'pending', 'uploads/manuscripts/1776779810_69e782229d55b_Web_Based_Thesis_Archiving_System.pdf', '2026-04-21 21:56:50', 0, NULL, NULL, NULL, NULL),
(22, 16, 'Web-Based Thesis Archiving System', 'A Web-Based Thesis Archiving System is designed to provide an efficient and centralized platform for storing, managing, and retrieving academic research documents. Traditional methods of thesis storage, such as physical libraries and manual filing systems, often lead to difficulties in access, risk of document loss, and time-consuming retrieval processes. This study aims to develop a web-based solution that digitizes thesis documents and enables users to easily upload, search, and access research materials through an online interface.\r\n\r\nThe system utilizes database management technologies to securely store documents and incorporates search and indexing functionalities to improve information retrieval. User authentication features are implemented to ensure that only authorized individuals can upload, modify, or access files. Additionally, the system supports categorization and filtering to enhance usability and organization of academic records.\r\n\r\nThe proposed system improves accessibility, reduces physical storage limitations, and enhances the overall efficiency of thesis management in educational institutions. It serves as a reliable digital repository that promotes knowledge sharing and preserves academic work for future reference.', 'Web-based system, digital repository, thesis archiving, document management, information retrieval, database management, user authentication, and academic records management.', 1, '2026', 'MR. BREGILDO', 'pending', 'uploads/manuscripts/1776779981_69e782cd019e9_Web_Based_Thesis_Archiving_System.pdf', '2026-04-21 21:59:41', 0, NULL, NULL, NULL, NULL),
(24, 13, 'The Impact of Cybercrime on Consumer Trust in Online Businesses', 'The rapid growth of online businesses has been accompanied by an increase in cybercrime, posing significant risks to consumers and organizations. This study examines the impact of cybercrime on consumer trust in online businesses. It explores how incidents such as data breaches, identity theft, and online fraud influence consumers’ willingness to engage in e-commerce transactions. Using a quantitative research approach, data is collected through surveys from online shoppers. The findings are expected to reveal that higher awareness of cybercrime negatively affects consumer trust. The study highlights the importance of implementing strong cybersecurity measures to maintain customer confidence and sustain business growth.', 'Cybercrime, consumer trust, online business, data security, e-commerce, digital fraud', 2, '2026', 'Ms.Camille Joyce Geocallo', 'pending', 'uploads/manuscripts/1776864920_The_Impact_of_Cybercrime_on_Consumer_Trust_in_Onli.pdf', '2026-04-22 21:35:20', 0, NULL, NULL, NULL, NULL),
(33, 13, 'The Effect of Shoplifting on Retail Business Profitability', 'Shoplifting remains a significant issue affecting retail businesses, leading to inventory losses and reduced profitability. This study examines the effect of shoplifting on the financial performance of retail stores. It analyzes the extent of losses due to theft and evaluates the effectiveness of prevention strategies such as surveillance systems and security personnel. A quantitative research method is employed, with data gathered from retail store managers. The findings are expected to indicate that shoplifting has a negative impact on profitability, while effective prevention measures can reduce losses. The study offers recommendations to improve retail security and operational efficiency.', 'Shoplifting, retail loss, business profitability, theft prevention, inventory shrinkage, retail management', 2, '2025', 'Ms.Camille Joyce Geocallo', '', 'uploads/manuscripts/1776896464_The_Effect_of_Shoplifting_on_Retail_Business_Profi.pdf', '2026-04-23 06:21:04', 0, NULL, NULL, NULL, NULL),
(37, 13, 'Employee Theft and Its Impact on Organizational Performance', 'Employee theft is a form of workplace crime that can significantly affect organizational performance. This study explores the relationship between employee theft and business outcomes, including productivity and profitability. It examines contributing factors such as lack of supervision, low job satisfaction, and weak internal controls. Data is collected through surveys among employees and managers in selected organizations. The study is expected to show that employee theft negatively impacts organizational performance. The findings emphasize the importance of strong internal controls and employee engagement strategies to minimize workplace crime.', 'Employee theft, workplace crime, organizational performance, internal control, employee behavior, business management', 2, '2025', 'Ms.Camille Joyce Geocallo', '', 'uploads/manuscripts/1776898658_Employee_Theft_and_Its_Impact_on_Organizational_Pe.pdf', '2026-04-23 06:57:38', 0, NULL, NULL, NULL, NULL),
(38, 13, 'Employee Theft and Its Impact on Organizational Performance', 'Employee theft is a form of workplace crime that can significantly affect organizational performance. This study explores the relationship between employee theft and business outcomes, including productivity and profitability. It examines contributing factors such as lack of supervision, low job satisfaction, and weak internal controls. Data is collected through surveys among employees and managers in selected organizations. The study is expected to show that employee theft negatively impacts organizational performance. The findings emphasize the importance of strong internal controls and employee engagement strategies to minimize workplace crime.', 'Employee theft, workplace crime, organizational performance, internal control, employee behavior, business management', 2, '2024', 'Ms.Camille Joyce Geocallo', '', 'uploads/manuscripts/1776899289_Employee_Theft_and_Its_Impact_on_Organizational_Pe.pdf', '2026-04-23 07:08:09', 1, '2026-04-23 10:23:18', 6, 5, '0'),
(39, 16, 'Smart Barangay Information and Incident Reporting System Using Mobile and Web Technologies', 'This study aims to develop a Smart Barangay Information and Incident Reporting System that integrates mobile and web technologies to improve communication between residents and local government units. The system allows residents to report incidents in real time using a mobile application, while barangay officials can monitor, manage, and respond through a web-based dashboard. It includes features such as user authentication, incident categorization, GPS location tracking, and automated notifications. The system enhances transparency, efficiency, and responsiveness in handling community concerns. Results show improved reporting speed and better data organization compared to traditional manual processes.', 'Barangay System, Incident Reporting, Mobile Application, Web-Based System, Real-Time Notification, Information Management', 1, '2026', 'MR. BREGILDO', '', 'uploads/manuscripts/1776923220_Smart_Barangay_Information_and_Incident_Reporting_.pdf', '2026-04-23 13:47:00', 1, '0000-00-00 00:00:00', 6, 5, '0'),
(40, 16, 'Smart Barangay Information and Incident Reporting System Using Mobile and Web Technologies', 'This study aims to develop a Smart Barangay Information and Incident Reporting System that integrates mobile and web technologies to improve communication between residents and local government units. The system allows residents to report incidents in real time using a mobile application, while barangay officials can monitor, manage, and respond through a web-based dashboard. It includes features such as user authentication, incident categorization, GPS location tracking, and automated notifications. The system enhances transparency, efficiency, and responsiveness in handling community concerns. Results show improved reporting speed and better data organization compared to traditional manual processes.', 'Barangay System, Incident Reporting, Mobile Application, Web-Based System, Real-Time Notification, Information Management', 1, '2026', 'MR. BREGILDO', 'approved', 'uploads/manuscripts/1776923695_Smart_Barangay_Information_and_Incident_Reporting_.pdf', '2026-04-23 13:54:55', 1, '2026-04-23 10:23:01', 6, 5, '0'),
(41, 8, 'Smart Inventory Management System for Small Businesses Using Sales Forecasting', 'Small businesses often struggle with inventory management due to inaccurate demand estimation and manual tracking systems. This capstone project aims to develop a Smart Inventory Management System that utilizes sales forecasting to improve stock control and decision-making. The system analyzes historical sales data to predict future demand and automatically suggests reorder levels. It features real-time inventory tracking, low-stock alerts, and reporting tools. The implementation of the system is expected to reduce overstocking and stockouts, improve operational efficiency, and support better financial planning for small business owners.', 'Smart,Inventory,System', 1, '2025', 'Ms.Camille Joyce Geocallo', 'approved', 'uploads/manuscripts/1776933387_Smart_Inventory_Management_System_for_Small_Busine.pdf', '2026-04-23 16:36:27', 1, '0000-00-00 00:00:00', 6, 10, '0'),
(42, 16, '“Sales and Inventory Management System with Automated Financial Reporting for Small Businesses”', 'Small businesses often face challenges in managing their daily sales, tracking inventory, and preparing accurate financial reports due to reliance on manual processes or fragmented tools. This capstone project aims to develop a Sales and Inventory Management System with Automated Financial Reporting to streamline business operations and improve decision-making.\r\n\r\nThe system is designed to record sales transactions, monitor inventory levels in real time, and automatically update stock quantities. It includes features such as low-stock alerts, product categorization, and sales tracking. In addition, the system generates essential financial reports, including daily sales summaries, profit calculations, and expense tracking, providing business owners with accurate and timely financial information.', 'Sales, Inventory', 2, '2026', 'MR. BREGILDO', '', 'uploads/manuscripts/1776934836____Sales_and_Inventory_Management_System_with_Auto.pdf', '2026-04-23 17:00:36', 0, NULL, NULL, NULL, NULL),
(43, 16, 'CYBERCRIME', 'Impact and Consequences\r\nThe impact of cybercrime is profound, affecting not only financial losses but also reputational damage and breaches of privacy. In 2020, the FBI reported losses exceeding $4 billion due to cybercrime in the United States alone. Critical sectors, such as healthcare, have been particularly vulnerable, especially during the COVID-19 pandemic when ransomware attacks surged. \r\nWikipedia\r\n+1\r\nPrevention and Response\r\nPreventing cybercrime requires a multi-faceted approach, including:\r\nEducation and Awareness: Training individuals and organizations on cybersecurity best practices.\r\nRobust Security Measures: Implementing firewalls, antivirus software, and regular system updates to protect against threats.\r\nInternational Cooperation: Countries are encouraged to collaborate through treaties and organizations to combat cybercrime effectively. The Budapest Convention is a key intern', 'Cybercrime, Cybersecurity, security awareness', 1, '2026', 'Sir. John Brigildo', '', 'uploads/manuscripts/1776935225_CYBERCRIME.pdf', '2026-04-23 17:07:05', 0, NULL, NULL, NULL, NULL),
(44, 16, 'CYBERCRIME', 'Impact and Consequences\r\nThe impact of cybercrime is profound, affecting not only financial losses but also reputational damage and breaches of privacy. In 2020, the FBI reported losses exceeding $4 billion due to cybercrime in the United States alone. Critical sectors, such as healthcare, have been particularly vulnerable, especially during the COVID-19 pandemic when ransomware attacks surged. \r\nWikipedia\r\n+1\r\nPrevention and Response\r\nPreventing cybercrime requires a multi-faceted approach, including:\r\nEducation and Awareness: Training individuals and organizations on cybersecurity best practices.\r\nRobust Security Measures: Implementing firewalls, antivirus software, and regular system updates to protect against threats.\r\nInternational Cooperation: Countries are encouraged to collaborate through treaties and organizations to combat cybercrime effectively. The Budapest Convention is a key intern', 'Cybercrime, Cybersecurity, security awareness', 1, '2026', 'Sir. John Brigildo', '', 'uploads/manuscripts/1776935242_CYBERCRIME.pdf', '2026-04-23 17:07:22', 0, NULL, NULL, NULL, NULL),
(45, 14, 'The Impact of Financial Management Practices on the Profitability of Small and Medium Enterprises (SMEs)', 'Financial management plays a vital role in the success and sustainability of small and medium enterprises (SMEs). This study aims to examine the impact of financial management practices on the profitability of SMEs. Specifically, it focuses on key practices such as budgeting, cash flow management, and financial planning, and how these influence business performance.\r\n\r\nA quantitative research design is employed, with data collected through structured questionnaires distributed to selected SME owners and managers. The data is analyzed using statistical methods to determine the relationship between financial management practices and profitability. It is expected that effective financial management practices positively influence the financial performance of SMEs by improving resource allocation, reducing unnecessary expenses, and enhancing decision-making.\r\n\r\nThe findings of this study may provide valuable insights for business owners in strengthening their financial strategies. It also contributes to the understanding of how proper financial management can lead to improved profitability and long-term business growth.', 'Financial management, profitability, SMEs, budgeting, cash flow management, financial performance, business efficiency', 5, '2026', 'MR.BSBA', '', 'uploads/manuscripts/1776942068_The_Impact_of_Financial_Management_Practices_on_th.pdf', '2026-04-23 19:01:08', 0, NULL, NULL, NULL, NULL),
(46, 16, 'Enhancing Customer Satisfaction Through Service Quality in Boutique Hotels: A Study in Cebu City', 'In the competitive hospitality industry, customer satisfaction plays a crucial role in business sustainability and growth. This study aims to evaluate the relationship between service quality and customer satisfaction in boutique hotels in Cebu City. Specifically, it examines five dimensions of service quality: tangibility, reliability, responsiveness, assurance, and empathy.\r\n\r\nA quantitative research design was utilized, employing survey questionnaires distributed to hotel guests. Data were analyzed using statistical tools to determine the level of service quality and its impact on overall guest satisfaction.\r\n\r\nThe findings reveal that responsiveness and empathy significantly influence customer satisfaction, while tangibility has a moderate effect. The study concludes that improving personalized service and staff training can enhance guest experiences.\r\n\r\nThis research provides valuable insights for hotel managers and stakeholders in developing strategies to improve service delivery and maintain competitive advantage in the local tourism industry.', 'Service Quality Customer Satisfaction Boutique Hotels Hospitality Management Tourism Industry Cebu City', 3, '2025', 'Ms.Camille Joyce Geocallo', '', 'uploads/manuscripts/1776991398_Enhancing_Customer_Satisfaction_Through_Service_Qu.pdf', '2026-04-24 08:43:18', 0, NULL, NULL, NULL, NULL),
(47, 16, 'The Impact of Social Media Marketing on Travel Decisions of Millennials', 'This study examines how social media platforms influence the travel decisions of millennials. With the growing use of digital platforms such as Instagram and TikTok, tourism promotion has shifted toward visual and user-generated content. The research uses a quantitative approach through survey questionnaires distributed to millennial travelers. Findings reveal that social media significantly affects destination awareness, interest, and final decision-making. The study highlights that authenticity of content and influencer credibility play a key role in shaping travel preferences.', 'Social Media Marketing, Travel Decisions, Millennials, Tourism Promotion, Digital Marketing', 3, '2025', 'Ms.Camille Joyce Geocallo', '', 'uploads/manuscripts/1776996788_69ead1b41b2a8_The_Impact_of_Social_Media_Marketing_on_Travel_Dec.pdf', '2026-04-24 10:13:08', 0, NULL, NULL, NULL, NULL),
(48, 16, 'The Impact of Social Media Marketing on Travel Decisions of Millennials', 'This study examines how social media platforms influence the travel decisions of millennials. With the growing use of digital platforms such as Instagram and TikTok, tourism promotion has shifted toward visual and user-generated content. The research uses a quantitative approach through survey questionnaires distributed to millennial travelers. Findings reveal that social media significantly affects destination awareness, interest, and final decision-making. The study highlights that authenticity of content and influencer credibility play a key role in shaping travel preferences.', 'Social Media Marketing, Travel Decisions, Millennials, Tourism Promotion, Digital Marketing', 3, '2025', 'Ms.Camille Joyce Geocallo', '', 'uploads/manuscripts/1776996936_69ead24849153_The_Impact_of_Social_Media_Marketing_on_Travel_Dec.pdf', '2026-04-24 10:15:36', 0, NULL, NULL, NULL, NULL),
(49, 16, 'The Impact of Social Media Marketing on Travel Decisions of Millennials', 'This study examines how social media platforms influence the travel decisions of millennials. With the growing use of digital platforms such as Instagram and TikTok, tourism promotion has shifted toward visual and user-generated content. The research uses a quantitative approach through survey questionnaires distributed to millennial travelers. Findings reveal that social media significantly affects destination awareness, interest, and final decision-making. The study highlights that authenticity of content and influencer credibility play a key role in shaping travel preferences.', 'Social Media Marketing, Travel Decisions, Millennials, Tourism Promotion, Digital Marketing', 3, '2025', 'Ms.Camille Joyce Geocallo', '', 'uploads/manuscripts/1776997050_69ead2ba1db5a_The_Impact_of_Social_Media_Marketing_on_Travel_Dec.pdf', '2026-04-24 10:17:30', 0, NULL, NULL, NULL, NULL),
(50, 16, 'The Impact of Social Media Marketing on Travel Decisions of Millennials', 'This study examines how social media platforms influence the travel decisions of millennials. With the growing use of digital platforms such as Instagram and TikTok, tourism promotion has shifted toward visual and user-generated content. The research uses a quantitative approach through survey questionnaires distributed to millennial travelers. Findings reveal that social media significantly affects destination awareness, interest, and final decision-making. The study highlights that authenticity of content and influencer credibility play a key role in shaping travel preferences.', 'Social Media Marketing, Travel Decisions, Millennials, Tourism Promotion, Digital Marketing', 3, '2025', 'Ms.Camille Joyce Geocallo', '', 'uploads/manuscripts/1776997171_69ead33310b9d_The_Impact_of_Social_Media_Marketing_on_Travel_Dec.pdf', '2026-04-24 10:19:31', 0, NULL, NULL, NULL, NULL),
(51, 16, 'The Impact of Social Media Marketing on Travel Decisions of Millennials', 'This study examines how social media platforms influence the travel decisions of millennials. With the growing use of digital platforms such as Instagram and TikTok, tourism promotion has shifted toward visual and user-generated content. The research uses a quantitative approach through survey questionnaires distributed to millennial travelers. Findings reveal that social media significantly affects destination awareness, interest, and final decision-making. The study highlights that authenticity of content and influencer credibility play a key role in shaping travel preferences.', 'Social Media Marketing, Travel Decisions, Millennials, Tourism Promotion, Digital Marketing', 3, '2025', 'Ms.Camille Joyce Geocallo', 'unread', 'uploads/manuscripts/1776997373_69ead3fd0e8c1_The_Impact_of_Social_Media_Marketing_on_Travel_Dec.pdf', '2026-04-24 10:22:53', 0, NULL, NULL, NULL, NULL),
(52, 16, 'The Impact of Social Media Marketing on Travel Decisions of Millennials', 'This study examines how social media platforms influence the travel decisions of millennials. With the growing use of digital platforms such as Instagram and TikTok, tourism promotion has shifted toward visual and user-generated content. The research uses a quantitative approach through survey questionnaires distributed to millennial travelers. Findings reveal that social media significantly affects destination awareness, interest, and final decision-making. The study highlights that authenticity of content and influencer credibility play a key role in shaping travel preferences.', 'Social Media Marketing, Travel Decisions, Millennials, Tourism Promotion, Digital Marketing', 3, '2025', 'Ms.Camille Joyce Geocallo', 'unread', 'uploads/manuscripts/1776997599_69ead4df54c80_The_Impact_of_Social_Media_Marketing_on_Travel_Dec.pdf', '2026-04-24 10:26:39', 0, NULL, NULL, NULL, NULL),
(53, 16, 'The Impact of Social Media Marketing on Travel Decisions of Millennials', 'This study examines how social media platforms influence the travel decisions of millennials. With the growing use of digital platforms such as Instagram and TikTok, tourism promotion has shifted toward visual and user-generated content. The research uses a quantitative approach through survey questionnaires distributed to millennial travelers. Findings reveal that social media significantly affects destination awareness, interest, and final decision-making. The study highlights that authenticity of content and influencer credibility play a key role in shaping travel preferences.', 'Social Media Marketing, Travel Decisions, Millennials, Tourism Promotion, Digital Marketing', 3, '2025', 'Ms.Camille Joyce Geocallo', 'unread', 'uploads/manuscripts/1776997604_69ead4e4ec0e2_The_Impact_of_Social_Media_Marketing_on_Travel_Dec.pdf', '2026-04-24 10:26:44', 0, NULL, NULL, NULL, NULL),
(54, 4, 'Customer Satisfaction and Service Quality in Local Restaurants in Cebu City', 'This study evaluates the relationship between service quality and customer satisfaction in selected local restaurants in Cebu City. Using the SERVQUAL model, the research measures five dimensions: tangibility, reliability, responsiveness, assurance, and empathy. Data were collected through customer surveys and analyzed using descriptive and inferential statistics. Results show that responsiveness and assurance have the strongest impact on customer satisfaction. The study recommends continuous staff training and service improvement strategies to enhance dining experiences.', 'Customer Satisfaction, Service Quality, Restaurants, SERVQUAL, Cebu City', 3, '2025', 'Ms.Camille Joyce Geocallo', 'unread', 'uploads/manuscripts/1776997853_69ead5dd19f29_Customer_Satisfaction_and_Service_Quality_in_Local.pdf', '2026-04-24 10:30:53', 0, NULL, NULL, NULL, NULL),
(55, 4, 'The Effect of Online Reviews on Hotel Booking Decisions in TripAdvisor”', 'This research investigates how online reviews influence hotel booking decisions among travelers. With the popularity of platforms like TripAdvisor, customer feedback has become a major factor in decision-making. The study uses a quantitative design with survey data collected from frequent travelers. Results reveal that ratings, detailed reviews, and reviewer credibility strongly affect booking choices. Negative reviews were found to have a greater impact than positive ones. The study emphasizes the importance of reputation management in the hospitality industry.', 'Online Reviews, Hotel Booking, TripAdvisor, Consumer Behavior, Hospitality Industry', 3, '2025', 'Ms.Camille Joyce Geocallo', 'unread', 'uploads/manuscripts/1776998194_69ead7323ae31_The_Effect_of_Online_Reviews_on_Hotel_Booking_Deci.pdf', '2026-04-24 10:36:34', 0, NULL, NULL, NULL, NULL),
(56, 4, 'The Effect of Online Reviews on Hotel Booking Decisions in TripAdvisor”', 'This research investigates how online reviews influence hotel booking decisions among travelers. With the popularity of platforms like TripAdvisor, customer feedback has become a major factor in decision-making. The study uses a quantitative design with survey data collected from frequent travelers. Results reveal that ratings, detailed reviews, and reviewer credibility strongly affect booking choices. Negative reviews were found to have a greater impact than positive ones. The study emphasizes the importance of reputation management in the hospitality industry.', 'Online Reviews, Hotel Booking, TripAdvisor, Consumer Behavior, Hospitality Industry', 3, '2025', 'Ms.Camille Joyce Geocallo', 'unread', 'uploads/manuscripts/1776998504_69ead86803a4f_The_Effect_of_Online_Reviews_on_Hotel_Booking_Deci.pdf', '2026-04-24 10:41:44', 0, NULL, NULL, NULL, NULL),
(57, 4, 'The Effect of Online Reviews on Hotel Booking Decisions in TripAdvisor”', 'This research investigates how online reviews influence hotel booking decisions among travelers. With the popularity of platforms like TripAdvisor, customer feedback has become a major factor in decision-making. The study uses a quantitative design with survey data collected from frequent travelers. Results reveal that ratings, detailed reviews, and reviewer credibility strongly affect booking choices. Negative reviews were found to have a greater impact than positive ones. The study emphasizes the importance of reputation management in the hospitality industry.', 'Online Reviews, Hotel Booking, TripAdvisor, Consumer Behavior, Hospitality Industry', 3, '2025', 'Ms.Camille Joyce Geocallo', 'unread', 'uploads/manuscripts/1776998737_69ead951a3fbf_The_Effect_of_Online_Reviews_on_Hotel_Booking_Deci.pdf', '2026-04-24 10:45:37', 0, NULL, NULL, NULL, NULL),
(58, 4, 'Eco-Tourism Practices and Tourist Satisfaction in Coastal Destinations', 'This study assesses the relationship between eco-tourism practices and tourist satisfaction in coastal destinations. It focuses on environmentally sustainable initiatives such as waste management, conservation efforts, and eco-friendly accommodations. Data were gathered through surveys of tourists visiting coastal areas. Findings show that eco-tourism practices positively influence tourist satisfaction and encourage repeat visits. The study concludes that sustainability is a key factor in enhancing tourist experience and destination competitiveness.', 'Eco-Tourism, Tourist Satisfaction, Sustainability, Coastal Tourism, Environmental Practices', 3, '2025', 'MR. BREGILDO', '', 'uploads/manuscripts/1776999286_Eco_Tourism_Practices_and_Tourist_Satisfaction_in_.pdf', '2026-04-24 10:54:46', 0, NULL, NULL, NULL, NULL),
(59, 4, 'Eco-Tourism Practices and Tourist Satisfaction in Coastal Destinations', 'This study assesses the relationship between eco-tourism practices and tourist satisfaction in coastal destinations. It focuses on environmentally sustainable initiatives such as waste management, conservation efforts, and eco-friendly accommodations. Data were gathered through surveys of tourists visiting coastal areas. Findings show that eco-tourism practices positively influence tourist satisfaction and encourage repeat visits. The study concludes that sustainability is a key factor in enhancing tourist experience and destination competitiveness.', 'Eco-Tourism, Tourist Satisfaction, Sustainability, Coastal Tourism, Environmental Practices', 3, '2025', 'MR. BREGILDO', '', 'uploads/manuscripts/1776999467_Eco_Tourism_Practices_and_Tourist_Satisfaction_in_.pdf', '2026-04-24 10:57:47', 0, NULL, NULL, NULL, NULL),
(60, 16, 'Airbnb vs. Hotels: A Comparative Study of Customer Satisfaction on Airbnb', 'This study compares customer satisfaction between traditional hotels and accommodations booked through Airbnb. Using survey data from travelers, the research evaluates factors such as price, comfort, convenience, and service quality. Results indicate that Airbnb is preferred for affordability and local experience, while hotels are favored for consistency and service reliability. The study provides insights for both sectors to improve competitiveness in the evolving hospitality market', 'Airbnb, Hotels, Customer Satisfaction, Accommodation, Hospitality Industry', 1, '2026', 'MR. BREGILDO', 'unread', 'uploads/manuscripts/1776999777_69eadd61267a9_Airbnb_vs__Hotels__A_Comparative_Study_of_Customer.pdf', '2026-04-24 11:02:57', 0, NULL, NULL, NULL, NULL),
(61, 16, 'Airbnb vs. Hotels: A Comparative Study of Customer Satisfaction on Airbnb', 'This study compares customer satisfaction between traditional hotels and accommodations booked through Airbnb. Using survey data from travelers, the research evaluates factors such as price, comfort, convenience, and service quality. Results indicate that Airbnb is preferred for affordability and local experience, while hotels are favored for consistency and service reliability. The study provides insights for both sectors to improve competitiveness in the evolving hospitality market', 'Airbnb, Hotels, Customer Satisfaction, Accommodation, Hospitality Industry', 1, '2026', 'MR. BREGILDO', '', 'uploads/manuscripts/1777000011_69eade4b450a2_Airbnb_vs__Hotels__A_Comparative_Study_of_Customer.pdf', '2026-04-24 11:06:51', 0, NULL, NULL, NULL, NULL);
INSERT INTO `thesis_table` (`thesis_id`, `student_id`, `title`, `abstract`, `keywords`, `department_id`, `year`, `adviser`, `is_read`, `file_path`, `date_submitted`, `is_archived`, `archived_date`, `archived_by`, `retention_period`, `archive_notes`) VALUES
(62, 16, 'CUSTOMER PERCEPTION OF SERVICE QUALITY IN FAST-FOOD RESTAURANTS', 'This study evaluates customer perception of service quality in fast-food restaurants. It focuses on aspects such as speed of service, staff behavior, cleanliness, and food quality. A quantitative approach was utilized, gathering data through customer surveys.\r\n\r\nResults show that speed and cleanliness are the most influential factors in customer satisfaction. Friendly staff behavior also contributes significantly to positive dining experiences. The study recommends continuous staff training and strict hygiene practices.', 'Service Quality, Customer Perception, Fast-Food, Customer Satisfaction, Hospitality', 3, '2026', 'MR. BREGILDO', '', 'uploads/manuscripts/1777001508_CUSTOMER_PERCEPTION_OF_SERVICE_QUALITY_IN_FAST_FOO.pdf', '2026-04-24 11:31:48', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reset_method` enum('email','sms') DEFAULT 'email',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `reset_method`, `updated_at`) VALUES
(1, 4, 'sms', '2026-04-02 10:11:42'),
(2, 13, 'sms', '2026-04-22 09:30:33');

-- --------------------------------------------------------

--
-- Table structure for table `user_table`
--

CREATE TABLE `user_table` (
  `user_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `birth_date` varchar(100) NOT NULL,
  `address` varchar(100) NOT NULL,
  `contact_number` int(11) NOT NULL,
  `status` varchar(100) NOT NULL,
  `profile_picture` varchar(100) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_table`
--

INSERT INTO `user_table` (`user_id`, `student_id`, `role_id`, `first_name`, `last_name`, `email`, `username`, `password`, `department_id`, `birth_date`, `address`, `contact_number`, `status`, `profile_picture`, `date_created`, `updated_at`) VALUES
(1, NULL, 1, 'mylenee', 'raganas', 'raganas12@gmail.com', 'mylene13', '$2y$10$dr718ZjLNvJ8PV0Q1I2tNOlNyPdXyYKseoSrU2RkatjUQOSeGQ68q', NULL, '2005-05-13', 'naga', 958473215, 'Active', 'default.png', '2026-02-17 11:49:33', '2026-02-17 12:13:25'),
(2, NULL, 6, 'Mylene', 'Raganas', 'raganas@gmail.com', 'raganas', '$2y$10$opyEbvyBTppUMYsadb0uXOH0j.i4bkBUNgfNerWK7cJBesTtqyfhq', NULL, '2005-05-13', 'naga city', 985478548, 'Inactive', 'default.png', '2026-02-17 12:09:12', '2026-02-17 12:09:12'),
(3, NULL, 5, 'Camille Joyce', 'Geocallo', 'mylenesellar13@gmail.com', 'raganass', '$2y$10$yk76jFqEkaGzlcnXT2e2auvT399gu3cNaJ0sS74XIIMAc9ml7rXlW', NULL, '2005-05-12', 'San Fernando', 2147483647, 'Active', 'user_3_1771733497.jpg', '2026-02-17 12:57:57', '2026-02-22 12:11:37'),
(4, NULL, 2, 'Mylene', 'Raganas', 'mylene321@gmail.com', 'mylene321', '$2y$10$o2MZ49EX1sSVhpQPsOS/IeyjZ2F6JzgW2eDyqlvXJaBjwUE94/lqm', NULL, '2005-05-13', 'Naga City', 2147483647, 'Active', 'user_4_1772109048.png', '2026-02-20 18:22:45', '2026-02-26 20:30:48'),
(5, NULL, 3, 'catalina', 'sellar', 'catalina30@gmail.com', 'faculty', '$2y$10$3/Z6Z6IEVoVIYXGtzYaDKOeuLoC2JZU7vkxVmlNdMjDLJskdPOVWO', 3, '2026-03-10', 'naga', 2147483647, 'Active', 'default.png', '2026-02-25 23:50:31', '2026-04-02 19:30:35'),
(6, NULL, 5, 'Joyce', 'Camille', 'camille@gmail.com', 'librarian', '$2y$10$k/onlfwCFaMLPGucLuhslePVx71LaCG9yKIsDVG5cROUdqxoc/bDS', NULL, '1998-03-25', 'Bairan City of Naga', 2147483647, 'Active', 'default.png', '2026-03-24 17:18:35', '2026-03-24 17:18:35'),
(7, NULL, 4, 'MR.', 'BSHTM', 'james@gmail.com', 'dean', '$2y$10$A3IrZNu0QbPzMtNd9alfAu/KvIyKdEsSPxeYcdEX038fflnMmXfXa', 3, '1995-10-19', 'Langtad, City of Naga', 2147483647, 'Active', 'default.png', '2026-03-25 14:17:48', '2026-03-25 14:17:48'),
(8, NULL, 1, 'Ivon', 'Candilanza', 'ivon@gmail.com', 'admin', '$2y$10$dJy1alj9W27vtFrt8Jo.C.f92sS5K4cH9Hav5zJiMI8c89RgxeNfC', NULL, '2009-06-23', 'San Fernando', 2147483647, 'Active', 'default.png', '2026-03-25 15:02:44', '2026-03-25 15:02:44'),
(9, NULL, 6, 'Mylene', 'Sellar', 'mylene@gmail.com', 'coordinator', '$2y$10$WKbauz7tLp45yshNBBDl../fUbbVU9SpFNlOawi1fen2zO5jcVFG2', NULL, '2000-10-19', 'Bairan City of Naga', 548796254, 'Active', 'default.png', '2026-03-25 17:33:26', '2026-03-25 17:33:26'),
(10, NULL, 4, 'MR.', 'BSCRIM', 'pengoc@gmail.com', 'dean1', '$2y$10$1pJjJb1.S2XJ7x3O/Ft3IOqsz2h4wqRSsy7Nogc2ay2v.9G4iILwe', 2, '1998-10-16', 'Minglanilla', 2147483647, 'Active', 'default.png', '2026-03-26 20:43:59', '2026-03-26 20:43:59'),
(11, NULL, 3, 'Joyce', 'Geocallo', 'geocallocamillejoyce72@gmail.com', 'joycey', '$2y$10$5JawGWcqmAsDQ4uBhxDa2OIUCY9mEZTIALzY/0l9Lb4grvhTm8Aq2', 5, '', '', 0, 'Active', '', '2026-04-01 16:58:30', '2026-04-01 16:58:30'),
(12, NULL, 2, 'Joyce', 'Camille', 'hohayhaha@gmail.com', 'student', '$2y$10$u.5z5zbTw8IGIxm.sI7Hv.z.z2xLNkNhcJXvYL7w8VmgJSaXViwLC', NULL, '', 'Langtad City of Naga', 2147483647, 'Active', '', '2026-04-15 21:49:04', '2026-04-15 21:49:04'),
(13, NULL, 2, 'Jorvin', 'Pengoc', 'pengocjorvin@gmail.com', 'jorvin', '$2y$10$XZf7x1nE2MH1OdVZVNK2xemmIGyVPJAg4e/KDhDbxMlJWIMtpaLMu', NULL, '2003-07-09', 'Minglanilla', 2147483647, 'Active', '', '2026-04-20 10:27:26', '2026-04-20 10:27:26'),
(14, NULL, 2, 'Tyrone James', 'Dela Victoria', 'tyronedelavictoria2@gmail.com', 'tyronejames', '$2y$10$if0JMIMc85syxK05Avra0.9HZif6bD1Qg1mW2T7hu/D/.iHdK9sma', NULL, '2004-10-21', 'Sangat, San Fernando', 658421578, 'Active', '', '2026-04-20 10:35:23', '2026-04-20 10:35:23'),
(16, NULL, 2, 'Ivon', 'Candilanza', 'ivon11ki@gmail.com', 'ivon', '$2y$10$g4Sk8mjDtDQNk/qb/5X0ju5O/ZoSeqiOih2QgllgI6LyEzLZEGZ/.', NULL, '2004-10-25', 'San Fernando', 965847854, 'Active', '', '2026-04-20 10:42:24', '2026-04-20 10:42:24'),
(17, NULL, 2, 'Mylene', 'Villareal', 'raganasmylene@gmail.com', 'raganas13', '$2y$10$bthW3DXZuREeXoT4TWA6uubiL0AX7iezVf3LlfECqsf2rD8bFdor6', NULL, '2005-05-13', 'Langtad, City of Naga', 965830378, 'Active', '', '2026-04-20 10:48:01', '2026-04-20 10:48:01'),
(18, NULL, 2, 'April', 'Villareal', 'aprilsellar@gmail.com', 'april', '$2y$10$zAAhviUvJDMK3GyM0fyMJeWMIie6CrDkNRJI2IL2HpQNBdCO7ZR4u', NULL, '2007-04-26', 'Langtad', 965004039, '', '', '2026-04-20 10:53:48', '2026-04-20 10:53:48'),
(19, NULL, 2, 'Mark', 'Paquit', 'paquitmarkkivengie@gmail.com', 'mark', '$2y$10$s8ukD0ZfgPODALkcLZuEB.ZpkaDXhPIPW8RukhJhvLBRIv9syUwTa', NULL, '2004-07-21', 'Naga City', 2147483647, 'Active', '', '2026-04-22 03:19:19', '2026-04-22 03:19:19'),
(20, NULL, 2, 'Mylene', 'Sellar', 'myleneraganas@gmail.com', 'mylenesellarr', '$2y$10$Nv6eTPaqpUheNHuWp6UjWOpMiLnxQ..DMpWw3OlBPe8xhUCflCZIS', NULL, '2005-05-13', 'Langtad Cityt of Naga', 2147483647, '', '', '2026-04-23 14:51:46', '2026-04-23 14:51:46'),
(21, NULL, 2, 'Mark Kiven', 'Paquit', 'sayleleonora7@gmail.com', 'student321', '$2y$10$9wp4BP9ZADfTxfEKW7qXteIAAOciekFu51UGg2MeFZ.HHJduKFOmO', NULL, '2005-01-10', 'Langtad City og Naga', 2147483647, '', '', '2026-04-23 15:03:53', '2026-04-23 15:03:53'),
(22, NULL, 2, 'Camille', 'Geocallo', 'manjez483@gmail.com', 'student12345', '$2y$10$nlh8G/Mi89HBMucgghpsDOXohqALbhhEOy78BWQ9ww/5/Ex8UGJCK', NULL, '2006-04-06', 'Upper Lucnay South Poblacion', 2147483647, '', '', '2026-04-23 15:10:04', '2026-04-23 15:10:04'),
(23, NULL, 3, 'MR.', 'BSBA', 'ledesmaevelyn625@gmail.com', 'adviser1234', '$2y$10$fQbB5nJKtGAPzjoQl5.rUOcouljsKP3Jnc9oxoWnhKB2kWzVqIItS', 2, '', '', 0, 'Active', '', '2026-04-23 18:41:02', '2026-04-23 18:41:02'),
(24, NULL, 4, 'MR.', 'BSIT', 'myleneragas@gmail.com', 'itdean', '$2y$10$9A6EtLm82gzwoZmlBwWnN.EF1bBBQOmF5e9CHmcGtcCjcwbGt7oCG', 1, '', '', 0, 'Active', '', '2026-04-24 00:14:10', '2026-04-24 00:14:10'),
(25, NULL, 4, 'MS.', 'BSED', 'educ@gmail.com', 'educ321', '$2y$10$P0ijYAGhp0hjY6yx/jli.OdbqjupJXdee6Ad7oNwsfNU.2hxySqK6', 4, '', '', 0, 'Active', '', '2026-04-24 00:26:48', '2026-04-24 00:26:48'),
(26, NULL, 4, 'MR.', 'BSBA', 'bsba@gmail.com', 'bsba12345', '$2y$10$EEEw7g8RlAK/NUE47sF41ugWACASNeTcYhqLC/32d86nANU0T4OxG', 5, '', '', 0, 'Active', '', '2026-04-24 00:27:58', '2026-04-24 00:27:58'),
(27, NULL, 2, 'April', 'Raganas', 'aprilsellar26@gmail.com', 'april123', '$2y$10$zIq97jl09ZORq0pRsQmgFebmREuC50ZGevzsTrv8I3MxA8ZBs.Epi', NULL, '', 'Langtad', 2147483647, 'Active', '', '2026-04-24 07:52:28', '2026-04-24 07:52:28'),
(28, NULL, 3, 'MS.', 'BSCRIM', 'bscrim@gmail.com', 'bscrim', '$2y$10$YqmKqb/6VGEbWppyN6UWfOZK4A2zbxj3r0zZUyiT8cckEHNEfaWO.', 4, '', '', 0, 'Active', '', '2026-04-24 09:16:47', '2026-04-24 09:16:47'),
(29, NULL, 3, 'MS.', 'BSHTM', 'bshtm@gmail.com', 'bshtm', '$2y$10$H18fZbK5A6KneNZUlBBmm.lWq19vz88Hv5XMLtTR8rUWbjyQDM/kq', 1, '', '', 0, 'Active', '', '2026-04-24 09:17:35', '2026-04-24 09:17:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `archive_table`
--
ALTER TABLE `archive_table`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `thesis_id` (`thesis_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `certificates_table`
--
ALTER TABLE `certificates_table`
  ADD PRIMARY KEY (`certificate_id`),
  ADD KEY `fk_certificates_thesis` (`thesis_id`),
  ADD KEY `fk_certificates_student` (`student_id`);

--
-- Indexes for table `department_coordinator`
--
ALTER TABLE `department_coordinator`
  ADD PRIMARY KEY (`coordinator_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `department_table`
--
ALTER TABLE `department_table`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `faculty_table`
--
ALTER TABLE `faculty_table`
  ADD PRIMARY KEY (`faculty_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback_table`
--
ALTER TABLE `feedback_table`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `thesis_id` (`thesis_id`),
  ADD KEY `feedback_table_ibfk_2` (`faculty_id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`group_id`,`user_id`),
  ADD KEY `idx_group` (`group_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `thesis_id` (`thesis_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pending_invitations`
--
ALTER TABLE `pending_invitations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `role_table`
--
ALTER TABLE `role_table`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `thesis_collaborators`
--
ALTER TABLE `thesis_collaborators`
  ADD PRIMARY KEY (`collaborator_id`),
  ADD UNIQUE KEY `unique_collaborator` (`thesis_id`,`user_id`),
  ADD KEY `thesis_id` (`thesis_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `thesis_groups`
--
ALTER TABLE `thesis_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_manager` (`project_manager_id`);

--
-- Indexes for table `thesis_invitations`
--
ALTER TABLE `thesis_invitations`
  ADD PRIMARY KEY (`invitation_id`),
  ADD KEY `thesis_id` (`thesis_id`),
  ADD KEY `invited_user_id` (`invited_user_id`),
  ADD KEY `status` (`is_read`),
  ADD KEY `invited_by` (`invited_by`);

--
-- Indexes for table `thesis_table`
--
ALTER TABLE `thesis_table`
  ADD PRIMARY KEY (`thesis_id`),
  ADD KEY `fk_thesis_department` (`department_id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user_table`
--
ALTER TABLE `user_table`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `department_id` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `archive_table`
--
ALTER TABLE `archive_table`
  MODIFY `archive_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=294;

--
-- AUTO_INCREMENT for table `certificates_table`
--
ALTER TABLE `certificates_table`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `department_coordinator`
--
ALTER TABLE `department_coordinator`
  MODIFY `coordinator_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_table`
--
ALTER TABLE `department_table`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `faculty_table`
--
ALTER TABLE `faculty_table`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_table`
--
ALTER TABLE `feedback_table`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `pending_invitations`
--
ALTER TABLE `pending_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role_table`
--
ALTER TABLE `role_table`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `thesis_collaborators`
--
ALTER TABLE `thesis_collaborators`
  MODIFY `collaborator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `thesis_groups`
--
ALTER TABLE `thesis_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `thesis_invitations`
--
ALTER TABLE `thesis_invitations`
  MODIFY `invitation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `thesis_table`
--
ALTER TABLE `thesis_table`
  MODIFY `thesis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_table`
--
ALTER TABLE `user_table`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `archive_table`
--
ALTER TABLE `archive_table`
  ADD CONSTRAINT `archive_table_ibfk_1` FOREIGN KEY (`thesis_id`) REFERENCES `thesis_table` (`thesis_id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`);

--
-- Constraints for table `certificates_table`
--
ALTER TABLE `certificates_table`
  ADD CONSTRAINT `fk_certificates_student` FOREIGN KEY (`student_id`) REFERENCES `user_table` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_certificates_thesis` FOREIGN KEY (`thesis_id`) REFERENCES `thesis_table` (`thesis_id`) ON DELETE CASCADE;

--
-- Constraints for table `department_coordinator`
--
ALTER TABLE `department_coordinator`
  ADD CONSTRAINT `department_coordinator_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`),
  ADD CONSTRAINT `department_coordinator_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `department_table` (`department_id`);

--
-- Constraints for table `faculty_table`
--
ALTER TABLE `faculty_table`
  ADD CONSTRAINT `faculty_table_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`);

--
-- Constraints for table `feedback_table`
--
ALTER TABLE `feedback_table`
  ADD CONSTRAINT `feedback_table_ibfk_1` FOREIGN KEY (`thesis_id`) REFERENCES `thesis_table` (`thesis_id`),
  ADD CONSTRAINT `feedback_table_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `user_table` (`user_id`);

--
-- Constraints for table `thesis_collaborators`
--
ALTER TABLE `thesis_collaborators`
  ADD CONSTRAINT `thesis_collaborators_ibfk_1` FOREIGN KEY (`thesis_id`) REFERENCES `thesis_table` (`thesis_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `thesis_collaborators_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `thesis_invitations`
--
ALTER TABLE `thesis_invitations`
  ADD CONSTRAINT `thesis_invitations_ibfk_1` FOREIGN KEY (`thesis_id`) REFERENCES `thesis_table` (`thesis_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `thesis_invitations_ibfk_2` FOREIGN KEY (`invited_user_id`) REFERENCES `user_table` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `thesis_invitations_ibfk_3` FOREIGN KEY (`invited_by`) REFERENCES `user_table` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `thesis_table`
--
ALTER TABLE `thesis_table`
  ADD CONSTRAINT `fk_thesis_department` FOREIGN KEY (`department_id`) REFERENCES `department_table` (`department_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_table`
--
ALTER TABLE `user_table`
  ADD CONSTRAINT `user_table_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `user_table` (`user_id`),
  ADD CONSTRAINT `user_table_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `department_table` (`department_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
