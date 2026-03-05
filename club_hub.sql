-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 05, 2026 at 05:04 PM
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
  `club_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`club_id`, `club_name`) VALUES
(2, 'NSU YES'),
(1, 'NSUSS');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_duration` decimal(4,2) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `event_creator` varchar(255) DEFAULT NULL,
  `event_availablity` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `event_duration`, `event_date`, `event_creator`, `event_availablity`) VALUES
(1, 'Boshonto Utshob 2026', 7.50, '2026-02-25 08:00:00', 'NSUSS', 1),
(2, 'Excelsor', 2.50, '2026-02-27 13:00:00', 'NSU YES', 1);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(10) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `DOB` date DEFAULT NULL,
  `contact` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `full_name`, `student_email`, `address`, `DOB`, `contact`) VALUES
(223341, 'Nasiruddin Patwary', 'derby.patwary@northsouth.edu', 'mirza abbas st, Dhaka-1200', '1990-01-01', '01994449087'),
(2233440, 'Chanda Abbas', 'chanda.abbas@northsouth.edu', '420 Chanda Street, Dhaka-1000', '1968-12-01', '0194438309'),
(2147483647, 'Ridwanul Hoque', 'sagor@gmail.com', '', '2000-11-08', '1886342215');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `email` varchar(255) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Role` varchar(50) NOT NULL DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`email`, `Name`, `Password`, `created_at`, `Role`) VALUES
('nsucdc@northsouth.edu', 'NSU CDC', '$2y$10$hvht9iM/cjg3NsEMlINjkebFYLU2z2ZQZPz0Vi4fkACg7YDshdsv2', '2026-03-05 15:59:25', 'Executive Member'),
('sagor@gmail.com', 'Ridwanul Hoque', '$2y$10$4rbA7bPKCFHdzx.PnC2SU.IaBvhxa7uMSYubu0zWkNFI3KHyH1tca', '2026-03-04 16:46:13', 'student'),
('testuser@example.com', 'John Doe', '$2y$10$YQgxMOAcd4Tv6P1U6tMApuTgATdja5jL1PQCyvOk/Q4D6I9IagY4q', '2026-02-27 05:38:05', 'student');

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_req`
--

CREATE TABLE `volunteer_req` (
  `req_ID` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `student_id` int(10) NOT NULL,
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
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `event_creator` (`event_creator`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `volunteer_req`
--
ALTER TABLE `volunteer_req`
  ADD PRIMARY KEY (`req_ID`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `event_id` (`event_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `club_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `volunteer_req`
--
ALTER TABLE `volunteer_req`
  MODIFY `req_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`event_creator`) REFERENCES `clubs` (`club_name`);

--
-- Constraints for table `volunteer_req`
--
ALTER TABLE `volunteer_req`
  ADD CONSTRAINT `volunteer_req_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `volunteer_req_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
