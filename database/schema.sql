-- =====================================================================
-- SMART EMPLOYEE ATTENDANCE & WORKFORCE MANAGEMENT SYSTEM
-- MySQL Database Schema (XAMPP Compatible)
-- Version: 1.0.1 - FIXED table ordering for foreign key constraints
-- =====================================================================
-- IMPORTANT: Tables are ordered by dependency (no FK to a table
--            that hasn't been created yet).
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- CREATE DATABASE
-- =====================================================================
CREATE DATABASE IF NOT EXISTS `smart_attendance` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smart_attendance`;

-- =====================================================================
-- STEP 1: TABLES WITH NO FOREIGN KEY DEPENDENCIES
-- =====================================================================

-- ROLES
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PERMISSIONS
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permissions_slug` (`slug`),
    KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- COMPANIES
CREATE TABLE IF NOT EXISTS `companies` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `legal_name` VARCHAR(255) NULL,
    `tax_id` VARCHAR(100) NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(30) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL,
    `postal_code` VARCHAR(20) NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
    `timezone` VARCHAR(50) NOT NULL DEFAULT 'UTC',
    `logo` VARCHAR(255) NULL,
    `website` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SHIFTS
CREATE TABLE IF NOT EXISTS `shifts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(30) NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `grace_period_minutes` INT NOT NULL DEFAULT 15,
    `late_threshold_minutes` INT NOT NULL DEFAULT 15,
    `early_leave_threshold_minutes` INT NOT NULL DEFAULT 15,
    `overtime_eligible` TINYINT(1) NOT NULL DEFAULT 0,
    `overtime_rate` DECIMAL(5, 2) NOT NULL DEFAULT 1.50,
    `is_night_shift` TINYINT(1) NOT NULL DEFAULT 0,
    `is_flexible` TINYINT(1) NOT NULL DEFAULT 0,
    `working_hours_per_day` DECIMAL(4, 2) NOT NULL DEFAULT 8.00,
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_shifts_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LEAVE_TYPES
CREATE TABLE IF NOT EXISTS `leave_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(30) NOT NULL,
    `description` TEXT NULL,
    `default_days_per_year` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `is_paid` TINYINT(1) NOT NULL DEFAULT 1,
    `carry_forward` TINYINT(1) NOT NULL DEFAULT 0,
    `requires_attachment` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_leavetypes_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HOLIDAYS
CREATE TABLE IF NOT EXISTS `holidays` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `holiday_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `is_recurring` TINYINT(1) NOT NULL DEFAULT 0,
    `type` ENUM('public','religious','company','national') NOT NULL DEFAULT 'public',
    `country` VARCHAR(100) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_holidays_date` (`holiday_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SETTINGS
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `data_type` ENUM('string','integer','decimal','boolean','json','text') NOT NULL DEFAULT 'string',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `description` TEXT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`setting_key`),
    KEY `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- STEP 2: TABLES WITH FK TO STEP 1 TABLES
-- =====================================================================

-- ROLE_PERMISSIONS (FK to roles, permissions)
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_rp_permission` (`permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BRANCHES (FK to companies)
CREATE TABLE IF NOT EXISTS `branches` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(30) NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(30) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL,
    `latitude` DECIMAL(10, 7) NULL,
    `longitude` DECIMAL(10, 7) NULL,
    `geofence_radius` INT NOT NULL DEFAULT 100 COMMENT 'Radius in meters',
    `manager_id` INT UNSIGNED NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_branches_company` (`company_id`),
    CONSTRAINT `fk_branches_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WORKING_POLICIES (FK to companies)
CREATE TABLE IF NOT EXISTS `working_policies` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NULL,
    `name` VARCHAR(150) NOT NULL,
    `working_days_per_week` INT NOT NULL DEFAULT 5,
    `working_hours_per_day` DECIMAL(4, 2) NOT NULL DEFAULT 8.00,
    `late_grace_minutes` INT NOT NULL DEFAULT 15,
    `late_penalty_per_hour` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `overtime_rate` DECIMAL(5, 2) NOT NULL DEFAULT 1.50,
    `weekend_days` VARCHAR(50) NOT NULL DEFAULT 'Saturday,Sunday',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_policy_company` (`company_id`),
    CONSTRAINT `fk_policy_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- STEP 3: DEPARTMENTS (FK to branches only - no FK to employees to avoid circular dep)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `branch_id` INT UNSIGNED NULL,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(30) NULL,
    `description` TEXT NULL,
    `head_employee_id` INT UNSIGNED NULL COMMENT 'No FK to avoid circular dependency',
    `parent_id` INT UNSIGNED NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_departments_code` (`code`),
    KEY `idx_departments_branch` (`branch_id`),
    KEY `idx_departments_head` (`head_employee_id`),
    KEY `idx_departments_parent` (`parent_id`),
    CONSTRAINT `fk_departments_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- STEP 4: EMPLOYEES (FK to companies, branches, departments, shifts)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `employees` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_code` VARCHAR(50) NOT NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NULL,
    `department_id` INT UNSIGNED NULL,
    `shift_id` INT UNSIGNED NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `gender` ENUM('male','female','other') NULL,
    `date_of_birth` DATE NULL,
    `national_id` VARCHAR(50) NULL,
    `passport_number` VARCHAR(50) NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(150) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL,
    `emergency_contact_name` VARCHAR(150) NULL,
    `emergency_contact_phone` VARCHAR(30) NULL,
    `emergency_contact_relation` VARCHAR(50) NULL,
    `position` VARCHAR(150) NULL,
    `job_title` VARCHAR(150) NULL,
    `employment_status` ENUM('permanent','contract','probation','intern','terminated','resigned') NOT NULL DEFAULT 'permanent',
    `employment_type` ENUM('full_time','part_time','remote','hybrid') NOT NULL DEFAULT 'full_time',
    `salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `allowance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `date_joined` DATE NOT NULL,
    `date_left` DATE NULL,
    `photo` VARCHAR(255) NULL,
    `fingerprint_template` TEXT NULL,
    `gps_attendance_required` TINYINT(1) NOT NULL DEFAULT 0,
    `face_enrolled` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_employees_code` (`employee_code`),
    KEY `idx_employees_company` (`company_id`),
    KEY `idx_employees_branch` (`branch_id`),
    KEY `idx_employees_department` (`department_id`),
    KEY `idx_employees_shift` (`shift_id`),
    KEY `idx_employees_email` (`email`),
    KEY `idx_employees_status` (`employment_status`),
    CONSTRAINT `fk_employees_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_employees_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_employees_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- STEP 5: USERS (FK to roles, employees - NOW employees exists)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id` INT UNSIGNED NOT NULL,
    `employee_id` INT UNSIGNED NULL,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `remember_token` VARCHAR(100) NULL,
    `otp_code` VARCHAR(10) NULL,
    `otp_expires_at` DATETIME NULL,
    `two_fa_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `two_fa_secret` VARCHAR(100) NULL,
    `avatar` VARCHAR(255) NULL,
    `status` ENUM('active','inactive','suspended','pending') NOT NULL DEFAULT 'active',
    `last_login_at` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `failed_login_attempts` INT NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `password_reset_token` VARCHAR(100) NULL,
    `password_reset_expires_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role_id`),
    KEY `idx_users_employee` (`employee_id`),
    KEY `idx_users_status` (`status`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_users_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- STEP 6: TABLES DEPENDING ON EMPLOYEES & USERS
-- =====================================================================

-- EMPLOYEE_DOCUMENTS (FK to employees)
CREATE TABLE IF NOT EXISTS `employee_documents` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT UNSIGNED NOT NULL,
    `document_type` VARCHAR(100) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT NULL,
    `mime_type` VARCHAR(100) NULL,
    `uploaded_by` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_empdocs_employee` (`employee_id`),
    KEY `idx_empdocs_type` (`document_type`),
    CONSTRAINT `fk_empdocs_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EMPLOYEE_FACES (FK to employees)
CREATE TABLE IF NOT EXISTS `employee_faces` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT UNSIGNED NOT NULL,
    `descriptor` LONGTEXT NOT NULL COMMENT 'JSON-serialized 128-d face descriptor',
    `label` VARCHAR(50) NULL,
    `image_path` VARCHAR(500) NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `captured_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_faces_employee` (`employee_id`),
    KEY `idx_faces_primary` (`is_primary`),
    CONSTRAINT `fk_faces_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ATTENDANCE (FK to employees, shifts)
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT UNSIGNED NOT NULL,
    `attendance_date` DATE NOT NULL,
    `shift_id` INT UNSIGNED NULL,
    `check_in` DATETIME NULL,
    `check_out` DATETIME NULL,
    `check_in_method` ENUM('face','manual','gps','fingerprint','card') NULL,
    `check_out_method` ENUM('face','manual','gps','fingerprint','card') NULL,
    `check_in_latitude` DECIMAL(10, 7) NULL,
    `check_in_longitude` DECIMAL(10, 7) NULL,
    `check_out_latitude` DECIMAL(10, 7) NULL,
    `check_out_longitude` DECIMAL(10, 7) NULL,
    `working_hours` DECIMAL(5, 2) NULL,
    `overtime_hours` DECIMAL(5, 2) NULL,
    `late_minutes` INT NOT NULL DEFAULT 0,
    `early_leave_minutes` INT NOT NULL DEFAULT 0,
    `early_departure` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('present','late','absent','leave','half_day','holiday','weekend','remote') NOT NULL DEFAULT 'present',
    `notes` TEXT NULL,
    `verified_by_face` TINYINT(1) NOT NULL DEFAULT 0,
    `verified_by_gps` TINYINT(1) NOT NULL DEFAULT 0,
    `device_used` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_attendance_emp_date` (`employee_id`, `attendance_date`),
    KEY `idx_attendance_date` (`attendance_date`),
    KEY `idx_attendance_status` (`status`),
    KEY `idx_attendance_shift` (`shift_id`),
    CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_attendance_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ATTENDANCE_LOGS (FK to employees, attendance)
CREATE TABLE IF NOT EXISTS `attendance_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT UNSIGNED NULL,
    `attendance_id` INT UNSIGNED NULL,
    `event_type` ENUM('check_in','check_out','face_detected','face_mismatch','spoof_detected','manual') NOT NULL,
    `event_time` DATETIME NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `latitude` DECIMAL(10, 7) NULL,
    `longitude` DECIMAL(10, 7) NULL,
    `face_match_score` DECIMAL(5, 4) NULL,
    `device_info` VARCHAR(255) NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_attlogs_employee` (`employee_id`),
    KEY `idx_attlogs_attendance` (`attendance_id`),
    KEY `idx_attlogs_event` (`event_type`),
    KEY `idx_attlogs_time` (`event_time`),
    CONSTRAINT `fk_attlogs_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_attlogs_attendance` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LEAVE_REQUESTS (FK to employees, leave_types)
CREATE TABLE IF NOT EXISTS `leave_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT UNSIGNED NOT NULL,
    `leave_type_id` INT UNSIGNED NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_days` DECIMAL(5, 2) NOT NULL,
    `reason` TEXT NOT NULL,
    `attachment_path` VARCHAR(500) NULL,
    `status` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `applied_by` INT UNSIGNED NOT NULL,
    `approved_by` INT UNSIGNED NULL,
    `approved_at` DATETIME NULL,
    `approval_notes` TEXT NULL,
    `emergency_contact` VARCHAR(150) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_leavereq_employee` (`employee_id`),
    KEY `idx_leavereq_type` (`leave_type_id`),
    KEY `idx_leavereq_status` (`status`),
    KEY `idx_leavereq_dates` (`start_date`, `end_date`),
    CONSTRAINT `fk_leavereq_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_leavereq_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LEAVE_BALANCES (FK to employees, leave_types)
CREATE TABLE IF NOT EXISTS `leave_balances` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT UNSIGNED NOT NULL,
    `leave_type_id` INT UNSIGNED NOT NULL,
    `year` INT NOT NULL,
    `entitled_days` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `used_days` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `carried_forward` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `remaining_days` DECIMAL(5, 2) GENERATED ALWAYS AS (`entitled_days` + `carried_forward` - `used_days`) STORED,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_leavebalance` (`employee_id`, `leave_type_id`, `year`),
    CONSTRAINT `fk_leavebal_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_leavebal_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PAYROLL (FK to employees)
CREATE TABLE IF NOT EXISTS `payroll` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT UNSIGNED NOT NULL,
    `payroll_period` VARCHAR(20) NOT NULL COMMENT 'e.g. 2026-06',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `basic_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `allowances` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `overtime_pay` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `bonus` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `gross_pay` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `tax_deduction` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `insurance_deduction` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `penalty_deduction` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `other_deductions` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `total_deductions` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `net_pay` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `working_days` INT NOT NULL DEFAULT 0,
    `present_days` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `absent_days` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `leave_days` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `overtime_hours` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `late_count` INT NOT NULL DEFAULT 0,
    `status` ENUM('draft','processed','approved','paid','cancelled') NOT NULL DEFAULT 'draft',
    `processed_by` INT UNSIGNED NULL,
    `approved_by` INT UNSIGNED NULL,
    `paid_at` DATETIME NULL,
    `payslip_path` VARCHAR(500) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payroll_emp_period` (`employee_id`, `payroll_period`),
    KEY `idx_payroll_period` (`payroll_period`),
    KEY `idx_payroll_status` (`status`),
    CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PAYROLL_ITEMS (FK to payroll)
CREATE TABLE IF NOT EXISTS `payroll_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payroll_id` INT UNSIGNED NOT NULL,
    `type` ENUM('earning','deduction','bonus','penalty') NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payrollitems_payroll` (`payroll_id`),
    CONSTRAINT `fk_payrollitems_payroll` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTIFICATIONS (FK to users)
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `employee_id` INT UNSIGNED NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'attendance, leave, payroll, system',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `channel` ENUM('in_app','email','sms','whatsapp') NOT NULL DEFAULT 'in_app',
    `status` ENUM('pending','sent','delivered','failed','read') NOT NULL DEFAULT 'pending',
    `metadata` JSON NULL,
    `scheduled_at` DATETIME NULL,
    `sent_at` DATETIME NULL,
    `read_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user` (`user_id`),
    KEY `idx_notif_employee` (`employee_id`),
    KEY `idx_notif_type` (`type`),
    KEY `idx_notif_status` (`status`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AUDIT_LOGS (FK to users)
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `employee_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `http_method` VARCHAR(10) NULL,
    `request_url` VARCHAR(500) NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `severity` ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_user` (`user_id`),
    KEY `idx_audit_action` (`action`),
    KEY `idx_audit_module` (`module`),
    KEY `idx_audit_time` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ACTIVITY_LOGS (FK to users)
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `activity` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_user` (`user_id`),
    KEY `idx_activity_time` (`created_at`),
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SCHEMA IMPORT COMPLETE - 24 tables created
-- Now import seed.sql to load roles, permissions, demo data, and admin user
-- =====================================================================

-- =====================================================================
-- ANNOUNCEMENTS TABLE
-- =====================================================================
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

-- =====================================================================
-- YOUTUBE_LINKS TABLE
-- =====================================================================
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

-- =====================================================================
-- WEATHER_CACHE TABLE
-- =====================================================================
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

-- =====================================================================
-- EVENTS TABLE (for calendar)
-- =====================================================================
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

-- ============================================================
-- ENTERPRISE ADDITIONS v2
-- ============================================================

-- QR CODE TABLE
CREATE TABLE IF NOT EXISTS `employee_qr_codes` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id`  INT UNSIGNED NOT NULL,
    `qr_token`     VARCHAR(128) NOT NULL UNIQUE,
    `qr_secret`    VARCHAR(64)  NOT NULL,
    `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
    `scan_count`   INT          NOT NULL DEFAULT 0,
    `last_scanned` DATETIME     NULL,
    `expires_at`   DATETIME     NULL,
    `created_at`   TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_qr_employee` (`employee_id`),
    KEY `idx_qr_token`    (`qr_token`),
    CONSTRAINT `fk_qr_employee` FOREIGN KEY (`employee_id`)
        REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ATTENDANCE CORRECTIONS TABLE
CREATE TABLE IF NOT EXISTS `attendance_corrections` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id`      INT UNSIGNED NOT NULL,
    `attendance_id`    INT UNSIGNED NULL,
    `attendance_date`  DATE         NOT NULL,
    `requested_check_in`  DATETIME  NULL,
    `requested_check_out` DATETIME  NULL,
    `current_check_in`    DATETIME  NULL,
    `current_check_out`   DATETIME  NULL,
    `reason`           TEXT         NOT NULL,
    `status`           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by`      INT UNSIGNED NULL,
    `reviewed_at`      DATETIME     NULL,
    `review_notes`     TEXT         NULL,
    `created_at`       TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ac_employee`   (`employee_id`),
    KEY `idx_ac_attendance` (`attendance_id`),
    KEY `idx_ac_status`     (`status`),
    CONSTRAINT `fk_ac_employee`   FOREIGN KEY (`employee_id`)   REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ac_attendance` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MOBILE ATTENDANCE SELFIES
CREATE TABLE IF NOT EXISTS `mobile_attendance` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attendance_id`  INT UNSIGNED NOT NULL,
    `employee_id`    INT UNSIGNED NOT NULL,
    `type`           ENUM('check_in','check_out') NOT NULL,
    `selfie_path`    VARCHAR(500) NULL,
    `latitude`       DECIMAL(10,7) NULL,
    `longitude`      DECIMAL(10,7) NULL,
    `accuracy`       DECIMAL(8,2)  NULL,
    `distance_from_office` DECIMAL(10,2) NULL,
    `geofence_passed` TINYINT(1)  NOT NULL DEFAULT 0,
    `branch_id`      INT UNSIGNED NULL,
    `device_info`    VARCHAR(500) NULL,
    `created_at`     TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_mob_attendance` (`attendance_id`),
    KEY `idx_mob_employee`   (`employee_id`),
    CONSTRAINT `fk_mob_attendance` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mob_employee`   FOREIGN KEY (`employee_id`)   REFERENCES `employees`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add qr_enrolled + mobile_attendance_enabled to employees
ALTER TABLE `employees`
    ADD COLUMN IF NOT EXISTS `qr_enrolled`              TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `mobile_attendance_enabled` TINYINT(1) NOT NULL DEFAULT 1;

-- Branches geofence (ensure columns exist)
ALTER TABLE `branches`
    ADD COLUMN IF NOT EXISTS `latitude`         DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS `longitude`        DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS `geofence_radius`  INT           NOT NULL DEFAULT 200;

SELECT 'Enterprise schema v2 applied.' AS status;

-- FRAUD LOG TABLE
CREATE TABLE IF NOT EXISTS `attendance_fraud_log` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attempt_time`     DATETIME     NOT NULL,
    `ip_address`       VARCHAR(45)  NULL,
    `latitude`         DECIMAL(10,7) NULL,
    `longitude`        DECIMAL(10,7) NULL,
    `match_score`      DECIMAL(6,2) NOT NULL DEFAULT 0,
    `closest_employee` VARCHAR(255) NULL,
    `snapshot_path`    VARCHAR(500) NULL,
    `user_agent`       VARCHAR(500) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_fraud_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance snapshot column
ALTER TABLE `attendance`
    ADD COLUMN IF NOT EXISTS `snapshot_path` VARCHAR(500) NULL;

-- =====================================================================
-- ENTERPRISE SELF-REGISTRATION TABLES
-- =====================================================================

-- Registration requests (pre-approval staging area)
CREATE TABLE IF NOT EXISTS `registration_requests` (
    `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token`                   VARCHAR(64)  NOT NULL UNIQUE COMMENT 'Secure token for status page',
    -- Personal info
    `first_name`              VARCHAR(100) NOT NULL,
    `last_name`               VARCHAR(100) NOT NULL,
    `email`                   VARCHAR(150) NOT NULL,
    `phone`                   VARCHAR(30)  NULL,
    `national_id`             VARCHAR(50)  NULL,
    `passport_number`         VARCHAR(50)  NULL,
    `date_of_birth`           DATE         NULL,
    `gender`                  ENUM('male','female','other') NULL,
    `address`                 TEXT         NULL,
    -- Employment info
    `employee_code`           VARCHAR(50)  NULL COMMENT 'Proposed or auto-generated',
    `position`                VARCHAR(150) NULL,
    `department_id`           INT UNSIGNED NULL,
    `branch_id`               INT UNSIGNED NULL,
    `employment_type`         ENUM('full_time','part_time','remote','hybrid') NULL DEFAULT 'full_time',
    -- Emergency contact
    `emergency_contact_name`  VARCHAR(150) NULL,
    `emergency_contact_phone` VARCHAR(30)  NULL,
    `emergency_contact_rel`   VARCHAR(50)  NULL,
    -- Auth
    `password_hash`           VARCHAR(255) NOT NULL,
    `role_id`                 INT UNSIGNED NOT NULL DEFAULT 4 COMMENT '4 = employee role',
    -- Face enrollment (JSON array of descriptors)
    `face_descriptors`        LONGTEXT     NULL COMMENT 'JSON: [{label,descriptor,image_path}]',
    `face_enrolled_at`        DATETIME     NULL,
    `face_quality_score`      DECIMAL(5,2) NULL,
    `face_angles_captured`    VARCHAR(100) NULL COMMENT 'CSV: front,left,right,up,down',
    -- Photo/documents
    `profile_photo`           VARCHAR(500) NULL,
    -- Workflow
    `status`                  ENUM('pending','under_review','approved','rejected','changes_requested') NOT NULL DEFAULT 'pending',
    `submitted_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_by`             INT UNSIGNED NULL,
    `reviewed_at`             DATETIME     NULL,
    `rejection_reason`        TEXT         NULL,
    `change_request_notes`    TEXT         NULL,
    `resubmitted_at`          DATETIME     NULL,
    `resubmit_count`          TINYINT      NOT NULL DEFAULT 0,
    -- After approval
    `user_id`                 INT UNSIGNED NULL COMMENT 'Created after approval',
    `employee_id`             INT UNSIGNED NULL COMMENT 'Created after approval',
    -- Meta
    `ip_address`              VARCHAR(45)  NULL,
    `user_agent`              VARCHAR(500) NULL,
    `created_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reg_email`   (`email`),
    UNIQUE KEY `uq_reg_token`   (`token`),
    KEY `idx_reg_status`        (`status`),
    KEY `idx_reg_national_id`   (`national_id`),
    KEY `idx_reg_phone`         (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approval history log
CREATE TABLE IF NOT EXISTS `approval_logs` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`      INT UNSIGNED NOT NULL,
    `action`          ENUM('submitted','reviewed','approved','rejected','changes_requested','resubmitted','suspended') NOT NULL,
    `performed_by`    INT UNSIGNED NULL,
    `notes`           TEXT         NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_approval_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee status history
CREATE TABLE IF NOT EXISTS `employee_status_history` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id`  INT UNSIGNED NOT NULL,
    `from_status`  VARCHAR(50)  NULL,
    `to_status`    VARCHAR(50)  NOT NULL,
    `reason`       TEXT         NULL,
    `changed_by`   INT UNSIGNED NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_esh_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Enterprise registration tables created.' AS status;
