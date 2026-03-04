-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2026 at 02:23 PM
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
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(255) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(4, 4, 5, 'sample', '2026-03-04 09:59:05'),
(5, 4, 5, 'sample', '2026-03-04 09:59:44'),
(6, 5, 5, 'wrong', '2026-03-04 10:52:26'),
(7, 5, 5, 'wrong', '2026-03-04 10:52:32');

-- --------------------------------------------------------

--
-- Table structure for table `notification_table`
--

CREATE TABLE `notification_table` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `thesis_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_table`
--

INSERT INTO `notification_table` (`notification_id`, `user_id`, `thesis_id`, `message`, `status`, `created_at`) VALUES
(5, 2, 4, 'Your thesis \'trydfasdfasdfd dfregtrt hghrtyh  grtg\' has been approved by faculty.', 'unread', '2026-03-04 09:59:05'),
(6, 2, 4, 'New feedback on your thesis \'trydfasdfasdfd dfregtrt hghrtyh  grtg\'', 'unread', '2026-03-04 09:59:44'),
(7, 2, 5, 'New feedback on your thesis \'trydfasdfasdfd dfregtrt hghrtyh  grtg\'', 'unread', '2026-03-04 10:52:26'),
(8, 2, 5, 'Your thesis \'trydfasdfasdfd dfregtrt hghrtyh  grtg\' has been rejected by faculty.', 'unread', '2026-03-04 10:52:26'),
(9, 2, 5, 'New feedback on your thesis \'trydfasdfasdfd dfregtrt hghrtyh  grtg\'', 'unread', '2026-03-04 10:52:32'),
(10, 2, 5, 'Your thesis \'trydfasdfasdfd dfregtrt hghrtyh  grtg\' has been rejected by faculty.', 'unread', '2026-03-04 10:52:32');

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
-- Table structure for table `student_table`
--

CREATE TABLE `student_table` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_number` varchar(100) NOT NULL,
  `course` varchar(200) NOT NULL,
  `year_level` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_table`
--

INSERT INTO `student_table` (`student_id`, `user_id`, `student_number`, `course`, `year_level`) VALUES
(1, 5, '', '', ''),
(2, 4, '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `thesis_table`
--

CREATE TABLE `thesis_table` (
  `thesis_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `abstract` text NOT NULL,
  `adviser` varchar(255) NOT NULL,
  `status` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `date_submitted` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thesis_table`
--

INSERT INTO `thesis_table` (`thesis_id`, `student_id`, `title`, `abstract`, `adviser`, `status`, `file_path`, `date_submitted`) VALUES
(1, 1, 'asdAFADSFSDFGSDF', 'ADGSDFGSGDFHDHGDTHgbfgbcvcvhgd gfgssssssssssh saaaaaaaa', 'sfgsgftrsgsdfg', 'pending', 'uploads/manuscripts/1772167349_69a120b54de8e_asdAFADSFSDFGSDF.pdf', '2026-02-27 05:42:29'),
(2, 1, 'Test Title', 'Test Abstract', 'Test Adviser', 'rejected', 'test.pdf', '2026-03-01 15:47:27'),
(3, 1, 'Test Title', 'Test Abstract', 'Test Adviser', 'pending', 'test.pdf', '2026-03-01 15:48:13'),
(4, 2, 'trydfasdfasdfd dfregtrt hghrtyh  grtg', 'YJERYTJRTHNGHTYIHGTNHNGJNHHIOTE[TH JITJHGERJG RJGIRUGKJ\'Q[PURGT JHIRGH IRHTUWRTN RKTHGREGNGIOSDGJITW]0RT45TJI84TJVJIREOUT5TIJQERQIERHERVJ IJRIEURGJIURGJRGRWY', 'MR. BREGILDO', 'approved', 'uploads/manuscripts/1772351838_69a3f15e2d232_trydfasdfasdfd_dfregtrt_hghrtyh__grtg.pdf', '2026-03-01 08:57:18'),
(5, 2, 'trydfasdfasdfd dfregtrt hghrtyh  grtg', 'YJERYTJRTHNGHTYIHGTNHNGJNHHIOTE[TH JITJHGERJG RJGIRUGKJ\'Q[PURGT JHIRGH IRHTUWRTN RKTHGREGNGIOSDGJITW]0RT45TJI84TJVJIREOUT5TIJQERQIERHERVJ IJRIEURGJIURGJRGRWY', 'MR. BREGILDO', 'rejected', 'uploads/manuscripts/1772351971_69a3f1e343482_trydfasdfasdfd_dfregtrt_hghrtyh__grtg.pdf', '2026-03-01 08:59:31');

-- --------------------------------------------------------

--
-- Table structure for table `user_table`
--

CREATE TABLE `user_table` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
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

INSERT INTO `user_table` (`user_id`, `role_id`, `first_name`, `last_name`, `email`, `username`, `password`, `department`, `birth_date`, `address`, `contact_number`, `status`, `profile_picture`, `date_created`, `updated_at`) VALUES
(1, 1, 'mylenee', 'raganas', 'raganas12@gmail.com', 'mylene13', '$2y$10$dr718ZjLNvJ8PV0Q1I2tNOlNyPdXyYKseoSrU2RkatjUQOSeGQ68q', 'BSIT', '2005-05-13', 'naga', 958473215, '1', 'default.png', '2026-02-17 11:49:33', '2026-02-17 12:13:25'),
(2, 2, 'Mylene', 'Raganas', 'raganas@gmail.com', 'raganas', '$2y$10$opyEbvyBTppUMYsadb0uXOH0j.i4bkBUNgfNerWK7cJBesTtqyfhq', 'BSIT', '2005-05-13', 'naga city', 985478548, '1', 'default.png', '2026-02-17 12:09:12', '2026-02-17 12:09:12'),
(3, 2, 'Camille Joyce', 'Geocallo', 'mylenesellar13@gmail.com', 'raganass', '$2y$10$yk76jFqEkaGzlcnXT2e2auvT399gu3cNaJ0sS74XIIMAc9ml7rXlW', 'HTM', '2005-05-12', 'San Fernando', 2147483647, 'Active', 'user_3_1771733497.jpg', '2026-02-17 12:57:57', '2026-02-22 12:11:37'),
(4, 2, 'Mylene', 'Raganas', 'mylene321@gmail.com', 'mylene321', '$2y$10$92e7gZ/niRmZGhLFM0EiSOd.TuhhgpTaMkCr6QTdT2FIFnfT3Y30u', 'BSIT', '2005-05-13', 'Naga City', 2147483647, 'Active', 'user_4_1772109048.png', '2026-02-20 18:22:45', '2026-02-26 20:30:48'),
(5, 3, 'catalina', 'sellar', 'catalina30@gmail.com', 'faculty', '$2y$10$UjQwrV7qr1WsC16yYWWU/.GITEN09bwRqJmq6QFSH/MPXYTJu84Ai', 'BSIT', '1979-11-30', 'naga', 2147483647, 'Active', 'default.png', '2026-02-25 23:50:31', '2026-02-25 23:50:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `notification_table`
--
ALTER TABLE `notification_table`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `thesis_id` (`thesis_id`);

--
-- Indexes for table `role_table`
--
ALTER TABLE `role_table`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `student_table`
--
ALTER TABLE `student_table`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `thesis_table`
--
ALTER TABLE `thesis_table`
  ADD PRIMARY KEY (`thesis_id`),
  ADD KEY `thesis_table_ibfk_1` (`student_id`);

--
-- Indexes for table `user_table`
--
ALTER TABLE `user_table`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_coordinator`
--
ALTER TABLE `department_coordinator`
  MODIFY `coordinator_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_table`
--
ALTER TABLE `department_table`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculty_table`
--
ALTER TABLE `faculty_table`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_table`
--
ALTER TABLE `feedback_table`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notification_table`
--
ALTER TABLE `notification_table`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `role_table`
--
ALTER TABLE `role_table`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_table`
--
ALTER TABLE `student_table`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `thesis_table`
--
ALTER TABLE `thesis_table`
  MODIFY `thesis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_table`
--
ALTER TABLE `user_table`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`);

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
-- Constraints for table `notification_table`
--
ALTER TABLE `notification_table`
  ADD CONSTRAINT `notification_table_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`),
  ADD CONSTRAINT `notification_table_ibfk_2` FOREIGN KEY (`thesis_id`) REFERENCES `thesis_table` (`thesis_id`);

--
-- Constraints for table `student_table`
--
ALTER TABLE `student_table`
  ADD CONSTRAINT `student_table_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `thesis_table`
--
ALTER TABLE `thesis_table`
  ADD CONSTRAINT `thesis_table_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student_table` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_table`
--
ALTER TABLE `user_table`
  ADD CONSTRAINT `user_table_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `user_table` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
