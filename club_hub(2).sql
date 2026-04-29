-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2026 at 04:13 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(1, '2231446042', 1, 'EB Member', 0x31);

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
  `event_availablity` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `club_id`, `event_name`, `event_duration`, `event_date`, `event_creator`, `event_availablity`) VALUES
(1, 1, 'Boshonto Utshob 2026', 7.50, '2026-02-25 08:00:00', 'NSUSS', 1),
(2, 1, 'Excelsor', 2.50, '2026-02-27 13:00:00', 'NSUSS', 1);

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
(1, 'socio camp', '{\"description\":\"socio camp\",\"longDetail\":\"yghykugujhio\",\"fields\":[{\"label\":\"Full Name\",\"isFull\":true,\"type\":\"text\"},{\"label\":\"Department\",\"isFull\":false,\"type\":\"dropdown\",\"options\":\"Option 1\\nOption 2\"},{\"label\":\"New dropdown\",\"isFull\":true,\"type\":\"dropdown\",\"options\":\"Option 1\\nOption 2\"}],\"yPos\":\"50\",\"font\":\"\'Segoe UI\', sans-serif\",\"color\":\"#ffffff\",\"hSize\":\"22\",\"regToggle\":true,\"tktToggle\":false,\"formColors\":{\"bg\":\"#ffffff\",\"title\":\"#222222\",\"label\":\"#444444\",\"fieldBg\":\"#fdfdfd\",\"fieldTxt\":\"#444444\",\"btn\":\"#ff4757\",\"formTitleText\":\"Registration Form\"},\"ticketData\":{\"qty\":\"100\",\"fields\":[{\"label\":\"Full Name\",\"isFull\":true,\"type\":\"text\"}],\"colors\":{\"bg\":\"#ffffff\",\"title\":\"#222222\",\"label\":\"#444444\",\"fieldBg\":\"#fdfdfd\",\"fieldTxt\":\"#444444\",\"btn\":\"#00a8ff\",\"formTitleText\":\"Get Your Tickets\"}},\"image_path\":\"1776955176_Screenshot 2025-12-12 221636.png\"}', '2026-04-24 14:40:00', '2026-04-23 14:39:36');

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
(29, 1, '2026-04-23', 4, 'Pending');

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
('2231446042', 'Ridwanul Hoque', 'ridwanul.hoque01@northsouth.edu', NULL, '2001-11-08', '1886342215');

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

INSERT INTO `user` (`email`, `Name`, `Password`, `portal`, `created_at`) VALUES
('ridwanul.hoque01@northsouth.edu', 'Ridwanul Hoque', '$2y$10$Fo/F443bL5.iU33WfScaQ.Yh9duXVzUllAPFUSeVfDZJYy.0rrkQW', 'student', '2026-04-24 14:06:23');

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_requests`
--

CREATE TABLE `volunteer_requests` (
  `req_ID` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `student_id` bigint(20) NOT NULL,
  `student_email` varchar(255) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `event_creator` (`event_creator`),
  ADD KEY `fk_club_event` (`club_id`);

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
  ADD PRIMARY KEY (`req_ID`);

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
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `forms_responses`
--
ALTER TABLE `forms_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `space_bookings`
--
ALTER TABLE `space_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `volunteer_requests`
--
ALTER TABLE `volunteer_requests`
  MODIFY `req_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`event_creator`) REFERENCES `clubs` (`club_name`),
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
-- Constraints for table `space_bookings`
--
ALTER TABLE `space_bookings`
  ADD CONSTRAINT `space_bookings_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`student_email`) REFERENCES `user` (`email`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
