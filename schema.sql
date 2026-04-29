CREATE TABLE `users` (
  `email` varchar(255) PRIMARY KEY,
  `Name` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Role` varchar(50) NOT NULL DEFAULT 'student'
);


CREATE TABLE `clubs` (
  `club_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `club_name` VARCHAR(255) UNIQUE NOT NULL,
  `club_email` VARCHAR(255) UNIQUE NOT NULL,
  FOREIGN KEY (`club_email`) REFERENCES `users`(`email`)
);


CREATE TABLE `students` (
  `student_id` VARCHAR(10) PRIMARY KEY,
  `full_name` VARCHAR(255) NOT NULL,
  `student_email` VARCHAR(255) UNIQUE NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `DOB` DATE DEFAULT NULL,
  `contact` VARCHAR(15) DEFAULT NULL,
  FOREIGN KEY (`student_email`) REFERENCES `users`(`email`)
);



CREATE TABLE `events` (
  `event_id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `event_name` VARCHAR(255) NOT NULL,
  `event_duration` DECIMAL(4,2) DEFAULT NULL,
  `event_date` DATETIME DEFAULT NULL,
  `club_id` INT(11) DEFAULT NULL, 
  `event_availability` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`club_id`) REFERENCES `clubs` (`club_id`)
);


CREATE TABLE `volunteer_requests` (
  `req_ID` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `student_id` VARCHAR(10) NOT NULL,
  `event_id` INT(11) NOT NULL,
  `request_status` VARCHAR(50) DEFAULT 'Pending',
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`)
);


CREATE TABLE `club_members`(
  `member_id` INT PRIMARY KEY AUTO_INCREMENT,
  `student_id` VARCHAR(10),
  `club_id` INT(11),
  `Role` VARCHAR(255),
  `active` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`club_id`)
);