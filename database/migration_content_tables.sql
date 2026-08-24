-- =====================================================================
-- MIGRATION: Add Content Management tables (announcements, youtube, weather, events)
-- Run this if you already have the system installed and want to add
-- the new Content Management features.
-- =====================================================================
-- Import this file via phpMyAdmin → Import → choose this file → Go
-- =====================================================================

USE `smart_attendance`;

-- ANNOUNCEMENTS TABLE
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `type` ENUM('info','success','warning','danger','primary') NOT NULL DEFAULT 'info',
    `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_announcements_active` (`is_active`),
    KEY `idx_announcements_pinned` (`is_pinned`),
    KEY `idx_announcements_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- YOUTUBE_LINKS TABLE
CREATE TABLE IF NOT EXISTS `youtube_links` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `video_id` VARCHAR(20) NULL,
    `description` TEXT NULL,
    `category` VARCHAR(100) NULL DEFAULT 'General',
    `thumbnail` VARCHAR(500) NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_youtube_active` (`is_active`),
    KEY `idx_youtube_featured` (`is_featured`),
    KEY `idx_youtube_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WEATHER_CACHE TABLE
CREATE TABLE IF NOT EXISTS `weather_cache` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `location` VARCHAR(100) NOT NULL,
    `temperature` DECIMAL(5, 2) NULL,
    `feels_like` DECIMAL(5, 2) NULL,
    `humidity` INT NULL,
    `wind_speed` DECIMAL(5, 2) NULL,
    `description` VARCHAR(255) NULL,
    `icon` VARCHAR(20) NULL,
    `city_name` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL,
    `raw_data` LONGTEXT NULL,
    `fetched_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_weather_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EVENTS TABLE
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `event_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `start_time` TIME NULL,
    `end_time` TIME NULL,
    `location` VARCHAR(255) NULL,
    `color` VARCHAR(20) NOT NULL DEFAULT '#6366F1',
    `type` ENUM('meeting','holiday','training','event','deadline','other') NOT NULL DEFAULT 'event',
    `is_public` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_events_date` (`event_date`),
    KEY `idx_events_type` (`type`),
    KEY `idx_events_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data
INSERT INTO `announcements` (`title`, `content`, `type`, `is_pinned`, `created_by`) VALUES
('Welcome to Smart Attendance System', 'Our new AI-powered attendance system is now live! Use face recognition to check in and out automatically.', 'success', 1, 1),
('System Maintenance', 'The system will be unavailable on Sunday from 2-4 AM for routine maintenance.', 'warning', 0, 1);

INSERT INTO `events` (`title`, `description`, `event_date`, `start_time`, `location`, `color`, `type`, `created_by`) VALUES
('Monthly Team Meeting', 'All-hands meeting to discuss monthly progress', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', 'Conference Room A', '#6366F1', 'meeting', 1),
('Training: New HR Policy', 'Mandatory training on updated HR policies', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '14:00:00', 'Online (Zoom)', '#10B981', 'training', 1);
