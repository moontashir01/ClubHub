-- --------------------------------------------------------
-- Fix student_id datatype in volunteer_requests
-- --------------------------------------------------------
ALTER TABLE `volunteer_requests` 
MODIFY `student_id` varchar(20) NOT NULL;

-- --------------------------------------------------------
-- Add missing club_id column to volunteer_requests
-- --------------------------------------------------------
ALTER TABLE `volunteer_requests`
ADD COLUMN `club_id` int(11) DEFAULT NULL AFTER `req_ID`;

-- --------------------------------------------------------
-- Add missing security columns to events
-- --------------------------------------------------------
ALTER TABLE `events`
ADD COLUMN `security_clearance` varchar(50) DEFAULT 'Pending' AFTER `event_availablity`,
ADD COLUMN `admin_clearance` varchar(50) DEFAULT 'Pending' AFTER `security_clearance`,
ADD COLUMN `security_message` text DEFAULT NULL AFTER `admin_clearance`;

-- --------------------------------------------------------
-- Create volunteer_request_club table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `volunteer_request_club` (
  `event_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `requested_count` int(11) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Open',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`event_id`, `club_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`club_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create club_role_definitions table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `club_role_definitions` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `club_id` int(11) NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `unique_club_role` (`club_id`, `role_name`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`club_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create rooms table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rooms` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  PRIMARY KEY (`room_id`),
  UNIQUE KEY `room_number` (`room_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert some default rooms
INSERT IGNORE INTO `rooms` (`room_number`, `capacity`) VALUES
('AUDI801', 200), ('LIB502', 50), ('NAC301', 40);

-- --------------------------------------------------------
-- Create room_bookings table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `room_bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `club_name` varchar(255) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  PRIMARY KEY (`booking_id`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`room_id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_name`) REFERENCES `clubs`(`club_name`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Add missing foreign keys to club_members
-- --------------------------------------------------------
ALTER TABLE `club_members`
ADD CONSTRAINT `fk_cm_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_cm_club` FOREIGN KEY (`club_id`) REFERENCES `clubs`(`club_id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Add missing foreign keys to volunteer_requests
-- --------------------------------------------------------
ALTER TABLE `volunteer_requests`
ADD CONSTRAINT `fk_vr_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_vr_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`event_id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_vr_club` FOREIGN KEY (`club_id`) REFERENCES `clubs`(`club_id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Admin-created event support for volunteer flow
-- (moved from club_hub_final.sql)
-- --------------------------------------------------------

-- 1) Drop event_creator FK so admin events can use 'Admin' (or NULL) as creator
SET @fk_exists := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'events'
    AND CONSTRAINT_NAME = 'events_ibfk_1'
);
SET @drop_fk_sql := IF(@fk_exists > 0,
  'ALTER TABLE `events` DROP FOREIGN KEY `events_ibfk_1`',
  'SELECT \"events_ibfk_1 not found; skipping\"'
);
PREPARE stmt_drop_fk FROM @drop_fk_sql;
EXECUTE stmt_drop_fk;
DEALLOCATE PREPARE stmt_drop_fk;

-- 2) Seed one admin event (safe/idempotent)
INSERT INTO `events`
(`club_id`, `event_name`, `event_duration`, `event_date`, `event_creator`, `event_availablity`, `security_clearance`, `admin_clearance`, `security_message`)
SELECT
  NULL, 'Admin Orientation Support', 3.00, '2026-05-05 10:00:00', 'Admin', 1, 'Approved', 'Approved', NULL
WHERE NOT EXISTS (
  SELECT 1
  FROM `events`
  WHERE `event_name` = 'Admin Orientation Support'
    AND `event_date` = '2026-05-05 10:00:00'
);
