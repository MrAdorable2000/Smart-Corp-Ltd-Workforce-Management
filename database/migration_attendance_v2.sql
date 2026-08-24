-- ============================================================
-- MIGRATION: Attendance System v2 - Complete Schema Updates
-- Run this against your existing smart_attendance database
-- ============================================================

USE `smart_attendance`;

-- 1. Add missing columns to attendance table if not present
ALTER TABLE `attendance`
    ADD COLUMN IF NOT EXISTS `device_used`       VARCHAR(255)  NULL AFTER `verified_by_gps`,
    ADD COLUMN IF NOT EXISTS `ip_address`        VARCHAR(45)   NULL AFTER `device_used`,
    ADD COLUMN IF NOT EXISTS `early_departure`   TINYINT(1)    NOT NULL DEFAULT 0 AFTER `early_leave_minutes`;

-- 2. Make sure attendance_logs has ip_address (already in schema but just in case)
ALTER TABLE `attendance_logs`
    MODIFY COLUMN `face_match_score` DECIMAL(6,4) NULL;

-- 3. Ensure employee_faces table exists (for face descriptors)
CREATE TABLE IF NOT EXISTS `employee_faces` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id`  INT UNSIGNED NOT NULL,
    `descriptor`   LONGTEXT NOT NULL COMMENT 'JSON array of 128 floats',
    `label`        VARCHAR(50) NOT NULL DEFAULT 'front',
    `image_path`   VARCHAR(255) NULL,
    `is_primary`   TINYINT(1) NOT NULL DEFAULT 0,
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ef_employee` (`employee_id`),
    KEY `idx_ef_active`   (`is_active`),
    CONSTRAINT `fk_ef_employee` FOREIGN KEY (`employee_id`)
        REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Ensure face_enrolled column on employees
ALTER TABLE `employees`
    ADD COLUMN IF NOT EXISTS `face_enrolled`          TINYINT(1)  NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `gps_attendance_required` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `branch_id`              INT UNSIGNED NULL;

-- 5. Ensure notifications table has employee_id
ALTER TABLE `notifications`
    ADD COLUMN IF NOT EXISTS `employee_id` INT UNSIGNED NULL AFTER `user_id`;

-- 6. Add kiosk settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `data_type`, `description`) VALUES
('kiosk_enabled',          '1',        'attendance', 'boolean', 'Enable public kiosk attendance mode'),
('kiosk_geofence_enabled', '0',        'attendance', 'boolean', 'Restrict kiosk to geofence area'),
('attendance_start_time',  '08:00:00', 'attendance', 'string',  'Default shift start time'),
('attendance_end_time',    '17:00:00', 'attendance', 'string',  'Default shift end time'),
('late_grace_minutes',     '15',       'attendance', 'integer', 'Grace period in minutes before marking late'),
('half_day_hours',         '4',        'attendance', 'decimal', 'Hours below which counted as half day'),
('face_match_threshold',   '0.55',     'attendance', 'decimal', 'Face recognition match threshold (lower = stricter)');

SELECT 'Migration v2 complete.' AS status;
