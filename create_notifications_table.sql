-- Notifications table for ClubHub real-time notification system
-- Run this SQL in phpMyAdmin or MariaDB CLI

CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
  `recipient_type` ENUM('admin','club') NOT NULL DEFAULT 'admin',
  `recipient_id` VARCHAR(50) NOT NULL COMMENT 'admin role string OR club_id',
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL COMMENT 'Optional link to relevant page',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_recipient` (`recipient_type`, `recipient_id`, `is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
