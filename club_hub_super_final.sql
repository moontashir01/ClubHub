-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2026 at 12:16 AM
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
-- Database: `club_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `club_id` int(11) NOT NULL,
  `club_name` varchar(255) NOT NULL,
  `club_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`club_id`, `club_name`, `club_data`) VALUES
(1, 'NSUSS', '{\"info\":\"sagorvbdfgdf\",\"events\":\"dafe  df 4ef dferw dcvdsgsddga\\r\\ndfergf fega<div>dfdf<\\/div><div>&nbsp;df<\\/div><div><br><\\/div>\",\"panel\":[{\"name\":\"sagor\",\"role\":\"President\",\"image\":\"images\\/panel\\/69dcfdec247be_panel_1776090604.png\"}],\"gallery\":[\"images\\/gallery\\/69dcfdec24c03_gallery_1776090604.jpeg\",\"images\\/gallery\\/69dd00dda4d8f_gallery_1776091357.jpeg\"]}'),
(2, 'NSU YES', NULL),
(3, 'NSU CDC', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `club_members`
--

CREATE TABLE `club_members` (
  `member_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `club_id` int(11) DEFAULT NULL,
  `Role` varchar(255) DEFAULT NULL,
  `active` binary(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club_members`
--

INSERT INTO `club_members` (`member_id`, `student_id`, `club_id`, `Role`, `active`) VALUES
(1, '2231446042', 1, 'EB-Treasurer', 0x31),
(2, '2233440642', 1, 'EB-President', 0x31);

-- --------------------------------------------------------

--
-- Table structure for table `club_role_definitions`
--

CREATE TABLE `club_role_definitions` (
  `role_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club_role_definitions`
--

INSERT INTO `club_role_definitions` (`role_id`, `club_id`, `role_name`, `is_active`) VALUES
(1, 1, 'Incharge', 1);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `club_id` int(11) DEFAULT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_duration` decimal(4,2) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `event_creator` varchar(255) DEFAULT NULL,
  `event_availablity` tinyint(1) DEFAULT NULL,
  `security_clearance` varchar(50) DEFAULT 'Pending',
  `admin_clearance` varchar(50) DEFAULT 'Pending',
  `security_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `club_id`, `event_name`, `event_duration`, `event_date`, `event_creator`, `event_availablity`, `security_clearance`, `admin_clearance`, `security_message`) VALUES
(1, 1, 'Boshonto Utshob 2026', 7.50, '2026-02-25 08:00:00', 'NSUSS', 0, 'Rejected', 'Pending', 'For Some Reason :))'),
(2, 1, 'Excelsor', 2.50, '2026-02-27 13:00:00', 'NSUSS', 1, 'Approved', 'Pending', NULL),
(3, NULL, 'Admin Orientation Support', 3.00, '2026-05-05 10:00:00', 'Admin', 1, 'Approved', 'Approved', NULL),
(4, NULL, 'Admission Test 2026', NULL, '2026-05-01 08:00:00', 'Admin', 1, 'Approved', 'Approved', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_configs`
--

CREATE TABLE `event_configs` (
  `event_id` int(11) NOT NULL,
  `config_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `config_data` longtext DEFAULT NULL,
  `slider_endtime` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_configs`
--

INSERT INTO `event_configs` (`event_id`, `config_name`, `config_data`, `slider_endtime`, `created_at`) VALUES
(1, 'Boshonto 2026', '{\"description\":\"Have fun gays\",\"longDetail\":\"Yeeeeee\",\"fields\":[{\"label\":\"Full Name\",\"isFull\":true,\"type\":\"text\"},{\"label\":\"Department\",\"isFull\":false,\"type\":\"dropdown\",\"options\":\"Option 1\\nOption 2\"}],\"yPos\":\"50\",\"font\":\"\'Times New Roman\', serif\",\"color\":\"#ffd500\",\"hSize\":\"22\",\"regToggle\":true,\"tktToggle\":false,\"formColors\":{\"bg\":\"#ffffff\",\"title\":\"#222222\",\"label\":\"#444444\",\"fieldBg\":\"#fdfdfd\",\"fieldTxt\":\"#444444\",\"btn\":\"#ff4757\",\"formTitleText\":\"Registration Form\"},\"ticketData\":{\"qty\":\"100\",\"fields\":[{\"label\":\"Full Name\",\"isFull\":true,\"type\":\"text\"}],\"colors\":{\"bg\":\"#ffffff\",\"title\":\"#222222\",\"label\":\"#444444\",\"fieldBg\":\"#fdfdfd\",\"fieldTxt\":\"#444444\",\"btn\":\"#00a8ff\",\"formTitleText\":\"Get Your Tickets\"}},\"image_path\":\"1777043412_debate-1.png\"}', '2026-05-01 03:00:00', '2026-04-24 15:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `forms_responses`
--

CREATE TABLE `forms_responses` (
  `response_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`response_data`)),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forms_responses`
--

INSERT INTO `forms_responses` (`response_id`, `event_id`, `user_email`, `response_data`, `submitted_at`) VALUES
(20, 1, 'moontashir.azim@northsouth.edu', '{\"Full Name\":\"Moontashir Azim\",\"Department\":\"Option 1\",\"_form_type\":\"register\"}', '2026-04-24 15:51:51');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `recipient_type` enum('admin','club') NOT NULL DEFAULT 'admin',
  `recipient_id` varchar(50) NOT NULL COMMENT 'admin role string OR club_id',
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL COMMENT 'Optional link to relevant page',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `recipient_type`, `recipient_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 'club', '1', '✅ Security Clearance Approved for \"Excelsor\".', 'eventlogs.php', 1, '2026-04-24 18:07:15'),
(2, 'club', '1', '❌ Security Clearance Rejected for \"Boshonto Utshob 2026\". Reason: For Some Reason :))', 'eventlogs.php', 1, '2026-04-24 18:08:48'),
(3, 'club', '1', '✅ Security Clearance Approved for \"Excelsor\".', 'eventlogs.php', 1, '2026-04-24 18:28:04'),
(4, 'club', '1', '🙋 Admin has requested 10 volunteer(s) for \"Admission Test 2026\".', 'sendVolunteer.php', 1, '2026-04-24 22:09:54'),
(5, 'admin', 'all', '🚀 NSUSS has sent 2 volunteer(s) for \"Admission Test 2026\".', 'reqVolunteer.php?event_id=4', 0, '2026-04-24 22:11:33');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `capacity`) VALUES
(1, 'AUDI801', 200),
(2, 'LIB502', 50),
(3, 'NAC301', 40);

-- --------------------------------------------------------

--
-- Table structure for table `room_bookings`
--

CREATE TABLE `room_bookings` (
  `booking_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `club_name` varchar(255) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `space_bookings`
--

CREATE TABLE `space_bookings` (
  `booking_id` int(11) NOT NULL,
  `club_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `slot` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `space_bookings`
--

INSERT INTO `space_bookings` (`booking_id`, `club_id`, `booking_date`, `slot`, `status`) VALUES
(17, 2, '2026-03-09', 1, 'Confirmed'),
(23, 2, '2026-03-09', 2, 'Pending'),
(25, 1, '2026-03-27', 2, 'Confirmed'),
(26, 2, '2026-03-27', 1, 'Pending'),
(28, 1, '2026-04-19', 1, 'Pending'),
(29, 1, '2026-04-23', 4, 'Pending'),
(34, 2, '2026-04-24', 2, 'Pending'),
(35, 1, '2026-04-24', 1, 'Pending'),
(36, 1, '2026-04-25', 2, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `full_name`, `student_email`, `address`, `DOB`, `contact`) VALUES
('2231446042', 'Ridwanul Hoque', 'ridwanul.hoque01@northsouth.edu', NULL, '2001-11-08', '1886342215'),
('2233440642', 'Moontashir Azim', 'moontashir.azim@northsouth.edu', NULL, '2002-03-20', '01225422154');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `email` varchar(255) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `portal` varchar(255) DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`email`, `Name`, `Password`, `portal`, `created_at`, `otp_code`, `otp_expiry`, `is_verified`) VALUES
('moontashir.azim@northsouth.edu', 'Moontashir Azim', '$2y$10$ibUSAJOluRp2MtICO8a3..Qu0CLMVI1nV4wcDSVC9AwYCrlB4jXQ2', 'student', '2026-04-24 14:21:54', NULL, NULL, 1),
('ridwanul.hoque01@northsouth.edu', 'Ridwanul Hoque', '$2y$10$sxM9Jc1bDnQVtIvSlfsvFu2dgS3MJRWYV7411DrmgrNokrdYtHAJK', 'student', '2026-04-24 14:06:23', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_requests`
--

CREATE TABLE `volunteer_requests` (
  `req_ID` int(11) NOT NULL,
  `club_id` int(11) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `student_email` varchar(255) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `volunteer_requests`
--

INSERT INTO `volunteer_requests` (`req_ID`, `club_id`, `full_name`, `student_id`, `student_email`, `event_id`) VALUES
(1, 1, 'Moontashir Azim', '2233440642', 'moontashir.azim@northsouth.edu', 4),
(2, 1, 'Ridwanul Hoque', '2231446042', 'ridwanul.hoque01@northsouth.edu', 4);

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_request_club`
--

CREATE TABLE `volunteer_request_club` (
  `event_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `requested_count` int(11) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Open',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `volunteer_request_club`
--

INSERT INTO `volunteer_request_club` (`event_id`, `club_id`, `requested_count`, `note`, `deadline`, `status`, `updated_at`) VALUES
(4, 1, 10, 'Wear Formal', '2026-04-30', 'In Progress', '2026-04-24 22:15:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`club_id`),
  ADD UNIQUE KEY `club_name` (`club_name`);

--
-- Indexes for table `club_members`
--
ALTER TABLE `club_members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `club_id` (`club_id`);

--
-- Indexes for table `club_role_definitions`
--
ALTER TABLE `club_role_definitions`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `unique_club_role` (`club_id`,`role_name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `event_configs`
--
ALTER TABLE `event_configs`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `forms_responses`
--
ALTER TABLE `forms_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `fk_event_id` (`event_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_recipient` (`recipient_type`,`recipient_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `room_bookings`
--
ALTER TABLE `room_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `club_name` (`club_name`);

--
-- Indexes for table `space_bookings`
--
ALTER TABLE `space_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `club_id` (`club_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `student_email` (`student_email`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `volunteer_requests`
--
ALTER TABLE `volunteer_requests`
  ADD PRIMARY KEY (`req_ID`),
  ADD KEY `fk_vr_student` (`student_id`),
  ADD KEY `fk_vr_event` (`event_id`),
  ADD KEY `fk_vr_club` (`club_id`);

--
-- Indexes for table `volunteer_request_club`
--
ALTER TABLE `volunteer_request_club`
  ADD PRIMARY KEY (`event_id`,`club_id`),
  ADD KEY `club_id` (`club_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `club_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `club_members`
--
ALTER TABLE `club_members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `club_role_definitions`
--
ALTER TABLE `club_role_definitions`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `forms_responses`
--
ALTER TABLE `forms_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `room_bookings`
--
ALTER TABLE `room_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `space_bookings`
--
ALTER TABLE `space_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `volunteer_requests`
--
ALTER TABLE `volunteer_requests`
  MODIFY `req_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `club_members`
--
ALTER TABLE `club_members`
  ADD CONSTRAINT `fk_cm_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cm_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `club_role_definitions`
--
ALTER TABLE `club_role_definitions`
  ADD CONSTRAINT `club_role_definitions_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_club_event` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `event_configs`
--
ALTER TABLE `event_configs`
  ADD CONSTRAINT `fk_event_config` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `forms_responses`
--
ALTER TABLE `forms_responses`
  ADD CONSTRAINT `fk_event_id` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `room_bookings`
--
ALTER TABLE `room_bookings`
  ADD CONSTRAINT `room_bookings_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_bookings_ibfk_2` FOREIGN KEY (`club_name`) REFERENCES `clubs` (`club_name`) ON DELETE CASCADE;

--
-- Constraints for table `space_bookings`
--
ALTER TABLE `space_bookings`
  ADD CONSTRAINT `space_bookings_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`student_email`) REFERENCES `user` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `volunteer_requests`
--
ALTER TABLE `volunteer_requests`
  ADD CONSTRAINT `fk_vr_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vr_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `volunteer_request_club`
--
ALTER TABLE `volunteer_request_club`
  ADD CONSTRAINT `volunteer_request_club_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `volunteer_request_club_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
