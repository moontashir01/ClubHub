-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2026 at 02:06 PM
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
(3, 'NSU CDC'),
(2, 'NSU YES'),
(1, 'NSUSS');

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
(1, '223341264', 1, 'EB-President', 0x31),
(2, '2234501', 1, 'Member', 0x31),
(3, '2234502', 1, 'Incharge', 0x31),
(4, '2234503', 2, 'Member', 0x31),
(5, '2234504', 2, 'Member', 0x31),
(6, '2234505', 1, 'Member', 0x31),
(7, '2234506', 2, 'Incharge', 0x31),
(8, '2234601', 1, 'Member', 0x31),
(9, '2234602', 1, 'Member', 0x31),
(10, '2234603', 2, 'Member', 0x31),
(11, '2234604', 2, 'Member', 0x31),
(12, '2234605', 1, 'Incharge', 0x31),
(13, '2234606', 1, 'Member', 0x31),
(14, '2234607', 2, 'Member', 0x31),
(15, '2234608', 2, 'Member', 0x31),
(16, '2234609', 1, 'Member', 0x31),
(17, '2234610', 1, 'Member', 0x31),
(18, '2234611', 2, 'Incharge', 0x31),
(19, '2234612', 2, 'Member', 0x31),
(20, '2234613', 1, 'Member', 0x31),
(21, '2234614', 1, 'Member', 0x31),
(22, '2234615', 2, 'Member', 0x31),
(23, '2234616', 2, 'Member', 0x31),
(24, '2234617', 1, 'Member', 0x31),
(25, '2234618', 1, 'Member', 0x31),
(26, '2234619', 2, 'Member', 0x31),
(27, '2234620', 2, 'Member', 0x31),
(28, '2234621', 1, 'Incharge', 0x31),
(29, '2234622', 1, 'Member', 0x31),
(30, '2234623', 2, 'Member', 0x31),
(31, '2234624', 2, 'Member', 0x31);

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
(2, 2, 'Excelsor', 2.50, '2026-02-27 13:00:00', 'NSU YES', 1);

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
(23, 2, '2026-03-09', 2, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(20) NOT NULL,
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
('2231334995', 'Fariha Moshin ', 'fariha@gmail.com', '', '2222-11-11', '01556342215'),
('2231446042', 'Ridwanul Hoque', 'sagor@gmail.com', '', '2001-11-05', '01886342215'),
('223341', 'Nasiruddin Patwary', 'derby.patwary@northsouth.edu', 'mirza abbas st, Dhaka-1200', '1990-01-01', '01994449087'),
('223341264', 'test', 'test@northsouth.edu', '111/1 St', '2026-03-24', '01954858434'),
('2233440', 'Chanda Abbas', 'chanda.abbas@northsouth.edu', '420 Chanda Street, Dhaka-1000', '1968-12-01', '0194438309'),
('2234501', 'Rahim Mahmud', 'rahim.mahmud@northsouth.edu', 'Banani, Dhaka-1213', '2003-04-12', '01723456781'),
('2234502', 'Tania Sultana', 'tania.sultana@northsouth.edu', 'Uttara, Dhaka-1230', '2004-07-19', '01834567892'),
('2234503', 'Fahim Chowdhury', 'fahim.chowdhury@northsouth.edu', 'Dhanmondi, Dhaka-1209', '2002-02-25', '01945678903'),
('2234504', 'Nusrat Jahan', 'nusrat.jahan@northsouth.edu', 'Mirpur, Dhaka-1216', '2004-11-03', '01656789014'),
('2234505', 'Imran Hossain', 'imran.hossain@northsouth.edu', 'Mohammadpur, Dhaka-1207', '2001-09-15', '01767890125'),
('2234506', 'Sadia Karim', 'sadia.karim@northsouth.edu', 'Bashundhara, Dhaka-1229', '2003-06-30', '01878901236'),
('2234507', 'Arif Hasan', 'arif.hasan@northsouth.edu', 'Tejgaon, Dhaka-1208', '2004-01-10', '01989012347'),
('2234601', 'Mehedi Hasan', 'mehedi.hasan@northsouth.edu', 'Mirpur, Dhaka-1216', '2004-03-11', '01711112221'),
('2234602', 'Farzana Rahman', 'farzana.rahman@northsouth.edu', 'Uttara, Dhaka-1230', '2003-05-21', '01711112222'),
('2234603', 'Tanvir Alam', 'tanvir.alam@northsouth.edu', 'Dhanmondi, Dhaka-1209', '2004-01-15', '01711112223'),
('2234604', 'Sabina Yasmin', 'sabina.yasmin@northsouth.edu', 'Mohammadpur, Dhaka-1207', '2002-09-09', '01711112224'),
('2234605', 'Rakib Ahmed', 'rakib.ahmed@northsouth.edu', 'Banani, Dhaka-1213', '2004-07-18', '01711112225'),
('2234606', 'Jannat Ara', 'jannat.ara@northsouth.edu', 'Badda, Dhaka-1212', '2003-12-01', '01711112226'),
('2234607', 'Sabbir Hossain', 'sabbir.hossain@northsouth.edu', 'Rampura, Dhaka-1219', '2002-02-14', '01711112227'),
('2234608', 'Nabila Islam', 'nabila.islam@northsouth.edu', 'Khilgaon, Dhaka-1219', '2005-06-25', '01711112228'),
('2234609', 'Shakil Khan', 'shakil.khan@northsouth.edu', 'Farmgate, Dhaka-1215', '2001-10-10', '01711112229'),
('2234610', 'Mariam Akter', 'mariam.akter@northsouth.edu', 'Malibagh, Dhaka-1217', '2003-04-04', '01711112230'),
('2234611', 'Hasib Rahman', 'hasib.rahman@northsouth.edu', 'Tejgaon, Dhaka-1208', '2004-11-19', '01711112231'),
('2234612', 'Priya Dutta', 'priya.dutta@northsouth.edu', 'Shyamoli, Dhaka-1207', '2002-08-08', '01711112232'),
('2234613', 'Rifat Karim', 'rifat.karim@northsouth.edu', 'Banasree, Dhaka-1219', '2004-02-22', '01711112233'),
('2234614', 'Nafisa Anjum', 'nafisa.anjum@northsouth.edu', 'Gulshan, Dhaka-1212', '2004-09-30', '01711112234'),
('2234615', 'Omar Faruk', 'omar.faruk@northsouth.edu', 'Kafrul, Dhaka-1206', '2002-06-06', '01711112235'),
('2234616', 'Tasnim Chowdhury', 'tasnim.chowdhury@northsouth.edu', 'Baridhara, Dhaka-1212', '2003-01-12', '01711112236'),
('2234617', 'Mahmudul Hasan', 'mahmudul.hasan@northsouth.edu', 'Mirpur DOHS, Dhaka', '2003-03-17', '01711112237'),
('2234618', 'Sadia Noor', 'sadia.noor@northsouth.edu', 'Uttara Sector 7, Dhaka', '2005-05-05', '01711112238'),
('2234619', 'Ashiqur Rahman', 'ashiqur.rahman@northsouth.edu', 'Banani DOHS, Dhaka', '2002-12-12', '01711112239'),
('2234620', 'Tanzila Haque', 'tanzila.haque@northsouth.edu', 'Badda, Dhaka', '2005-08-18', '01711112240'),
('2234621', 'Rezaul Karim', 'rezaul.karim@northsouth.edu', 'Mohakhali, Dhaka', '2001-01-01', '01711112241'),
('2234622', 'Fariha Islam', 'fariha.islam@northsouth.edu', 'Rampura, Dhaka', '2004-10-10', '01711112242'),
('2234623', 'Sajid Hasan', 'sajid.hasan@northsouth.edu', 'Shantinagar, Dhaka', '2004-06-16', '01711112243'),
('2234624', 'Lamia Rahman', 'lamia.rahman@northsouth.edu', 'Elephant Road, Dhaka', '2003-09-09', '01711112244'),
('2234625', 'Nayeem Ahmed', 'nayeem.ahmed@northsouth.edu', 'Mirpur 10, Dhaka', '2003-07-07', '01711112245');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `email` varchar(255) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `portal` varchar(255) DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`email`, `Name`, `Password`, `created_at`, `portal`) VALUES
('arif.hasan@northsouth.edu', 'Arif Hasan', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('ashiqur.rahman@northsouth.edu', 'Ashiqur Rahman', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('chanda.abbas@northsouth.edu', 'Chanda Abbas', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('derby.patwary@northsouth.edu', 'Nasiruddin Patwary', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('fahim.chowdhury@northsouth.edu', 'Fahim Chowdhury', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('fariha.islam@northsouth.edu', 'Fariha Islam', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('fariha@gmail.com', 'Fariha Moshin ', '$2y$10$0YL62X7oOujJlFcmmOk6Lem1dOLt8zVf8Dg.lXwVUrqGqgAc0xld6', '2026-03-13 12:59:57', 'student'),
('farzana.rahman@northsouth.edu', 'Farzana Rahman', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('hasib.rahman@northsouth.edu', 'Hasib Rahman', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('imran.hossain@northsouth.edu', 'Imran Hossain', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('jannat.ara@northsouth.edu', 'Jannat Ara', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('lamia.rahman@northsouth.edu', 'Lamia Rahman', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('mahmudul.hasan@northsouth.edu', 'Mahmudul Hasan', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('mariam.akter@northsouth.edu', 'Mariam Akter', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('mehedi.hasan@northsouth.edu', 'Mehedi Hasan', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('nabila.islam@northsouth.edu', 'Nabila Islam', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('nafisa.anjum@northsouth.edu', 'Nafisa Anjum', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('nayeem.ahmed@northsouth.edu', 'Nayeem Ahmed', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('nsucdc@northsouth.edu', 'NSU CDC', '$2y$10$hvht9iM/cjg3NsEMlINjkebFYLU2z2ZQZPz0Vi4fkACg7YDshdsv2', '2026-03-05 15:59:25', 'admin'),
('nusrat.jahan@northsouth.edu', 'Nusrat Jahan', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('omar.faruk@northsouth.edu', 'Omar Faruk', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('priya.dutta@northsouth.edu', 'Priya Dutta', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('rahim.mahmud@northsouth.edu', 'Rahim Mahmud', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('rakib.ahmed@northsouth.edu', 'Rakib Ahmed', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('rezaul.karim@northsouth.edu', 'Rezaul Karim', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('rifat.karim@northsouth.edu', 'Rifat Karim', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('sabbir.hossain@northsouth.edu', 'Sabbir Hossain', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('sabina.yasmin@northsouth.edu', 'Sabina Yasmin', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('sadia.karim@northsouth.edu', 'Sadia Karim', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('sadia.noor@northsouth.edu', 'Sadia Noor', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('sagor@gmail.com', 'Ridwanul Hoque', '$2y$10$ILf1u8/jN.nfs.l80sb0/Ogfn0dxnxy/Jv1E4vj11fyG0nnKk8IXi', '2026-03-06 06:05:05', 'student'),
('sajid.hasan@northsouth.edu', 'Sajid Hasan', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('shakil.khan@northsouth.edu', 'Shakil Khan', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('tania.sultana@northsouth.edu', 'Tania Sultana', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('tanvir.alam@northsouth.edu', 'Tanvir Alam', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('tanzila.haque@northsouth.edu', 'Tanzila Haque', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('tasnim.chowdhury@northsouth.edu', 'Tasnim Chowdhury', '$2y$10$8sS5D3oQ9Q8Q8Q8Q8Q8Q8O.h1v6v6v6v6v6v6v6v6v6v6v6v6v6v6', '2026-03-13 12:53:34', 'student'),
('test@northsouth.edu', 'test', '$2a$12$i4.RoVJ06n.VA1DNAUF/keOZGn7272L7H/fNBEdIA6MDO4elduET2', '2026-03-06 11:54:14', 'student'),
('testuser@example.com', 'John Doe', '$2y$10$YQgxMOAcd4Tv6P1U6tMApuTgATdja5jL1PQCyvOk/Q4D6I9IagY4q', '2026-02-27 05:38:05', 'student');

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
  ADD KEY `club_id` (`club_id`),
  ADD KEY `fk_cm_student_id` (`student_id`);

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
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `fk_student_user_email` (`student_email`);

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
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `forms_responses`
--
ALTER TABLE `forms_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `space_bookings`
--
ALTER TABLE `space_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `volunteer_requests`
--
ALTER TABLE `volunteer_requests`
  MODIFY `req_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `club_members`
--
ALTER TABLE `club_members`
  ADD CONSTRAINT `club_members_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `club_members_ibfk_2` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`),
  ADD CONSTRAINT `fk_cm_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
  ADD CONSTRAINT `fk_student_user_email` FOREIGN KEY (`student_email`) REFERENCES `user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;