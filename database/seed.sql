-- =====================================================================
-- SEED DATA - SMART EMPLOYEE ATTENDANCE SYSTEM
-- Version 1.0.2 - FIXED: Use DELETE FROM instead of TRUNCATE (TRUNCATE
--                fails on tables referenced by FK even with checks off
--                in phpMyAdmin). Also re-disables FK checks before each
--                clear section to be extra-safe across phpMyAdmin batches.
-- Run AFTER schema.sql
-- =====================================================================
USE `smart_attendance`;

-- Disable FK checks for the entire script
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- CLEAR EXISTING DATA (use DELETE FROM, not TRUNCATE)
-- Order: child tables first, then parent tables
-- =====================================================================
DELETE FROM `activity_logs`;
DELETE FROM `audit_logs`;
DELETE FROM `notifications`;
DELETE FROM `payroll_items`;
DELETE FROM `payroll`;
DELETE FROM `leave_balances`;
DELETE FROM `leave_requests`;
DELETE FROM `attendance_logs`;
DELETE FROM `attendance`;
DELETE FROM `employee_faces`;
DELETE FROM `employee_documents`;
DELETE FROM `users`;
DELETE FROM `employees`;
DELETE FROM `departments`;
DELETE FROM `working_policies`;
DELETE FROM `branches`;
DELETE FROM `companies`;
DELETE FROM `shifts`;
DELETE FROM `holidays`;
DELETE FROM `leave_types`;
DELETE FROM `role_permissions`;
DELETE FROM `permissions`;
DELETE FROM `roles`;
DELETE FROM `settings`;

-- Reset auto-increment counters (safe to skip, but cleaner)
ALTER TABLE `activity_logs`     AUTO_INCREMENT = 1;
ALTER TABLE `audit_logs`        AUTO_INCREMENT = 1;
ALTER TABLE `notifications`     AUTO_INCREMENT = 1;
ALTER TABLE `payroll_items`     AUTO_INCREMENT = 1;
ALTER TABLE `payroll`           AUTO_INCREMENT = 1;
ALTER TABLE `leave_balances`    AUTO_INCREMENT = 1;
ALTER TABLE `leave_requests`    AUTO_INCREMENT = 1;
ALTER TABLE `attendance_logs`   AUTO_INCREMENT = 1;
ALTER TABLE `attendance`        AUTO_INCREMENT = 1;
ALTER TABLE `employee_faces`    AUTO_INCREMENT = 1;
ALTER TABLE `employee_documents` AUTO_INCREMENT = 1;
ALTER TABLE `users`             AUTO_INCREMENT = 1;
ALTER TABLE `employees`         AUTO_INCREMENT = 1;
ALTER TABLE `departments`       AUTO_INCREMENT = 1;
ALTER TABLE `working_policies`  AUTO_INCREMENT = 1;
ALTER TABLE `branches`          AUTO_INCREMENT = 1;
ALTER TABLE `companies`         AUTO_INCREMENT = 1;
ALTER TABLE `shifts`            AUTO_INCREMENT = 1;
ALTER TABLE `holidays`          AUTO_INCREMENT = 1;
ALTER TABLE `leave_types`       AUTO_INCREMENT = 1;
ALTER TABLE `role_permissions`  AUTO_INCREMENT = 1;
ALTER TABLE `permissions`       AUTO_INCREMENT = 1;
ALTER TABLE `roles`             AUTO_INCREMENT = 1;
ALTER TABLE `settings`          AUTO_INCREMENT = 1;

-- =====================================================================
-- 1. ROLES (no dependencies)
-- =====================================================================
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`) VALUES
(1, 'Super Admin', 'super_admin', 'Full system access with all permissions', 1),
(2, 'HR Manager', 'hr_manager', 'Manages employees, payroll, leave, attendance', 1),
(3, 'Department Manager', 'department_manager', 'Manages own department employees and reports', 1),
(4, 'Employee', 'employee', 'Standard employee with self-service access', 1),
(5, 'Auditor', 'auditor', 'Read-only access to audit logs and reports', 1);

-- =====================================================================
-- 2. PERMISSIONS (no dependencies)
-- =====================================================================
INSERT INTO `permissions` (`id`, `name`, `slug`, `module`, `description`) VALUES
(1,  'View Dashboard',          'view_dashboard',          'dashboard',     'Access to dashboard'),
(2,  'Manage Users',            'manage_users',            'users',         'Create/edit/delete system users'),
(3,  'Manage Roles',            'manage_roles',            'users',         'Manage roles and permissions'),
(4,  'Manage Company',          'manage_company',          'company',       'Manage company profile and branches'),
(5,  'Manage Departments',      'manage_departments',      'departments',   'Create/edit/delete departments'),
(6,  'Manage Employees',        'manage_employees',        'employees',     'Full employee management'),
(7,  'View Employees',          'view_employees',          'employees',     'View employee records'),
(8,  'Manage Attendance',       'manage_attendance',       'attendance',    'Manage attendance records'),
(9,  'View Attendance',         'view_attendance',         'attendance',    'View attendance records'),
(10, 'Mark Attendance',         'mark_attendance',         'attendance',    'Mark own attendance'),
(11, 'Manage Shifts',           'manage_shifts',           'shifts',        'Manage work shifts'),
(12, 'Manage Leaves',           'manage_leaves',           'leaves',        'Approve/reject leave requests'),
(13, 'Apply Leave',             'apply_leave',             'leaves',        'Submit own leave requests'),
(14, 'Manage Payroll',          'manage_payroll',          'payroll',       'Process and approve payroll'),
(15, 'View Payroll',            'view_payroll',            'payroll',       'View own payslips'),
(16, 'Manage Holidays',         'manage_holidays',         'holidays',      'Configure holidays'),
(17, 'Manage Face Data',        'manage_face_data',        'face',          'Enroll and verify faces'),
(18, 'Generate Reports',        'generate_reports',        'reports',       'Generate and export reports'),
(19, 'View Audit Logs',         'view_audit_logs',         'audit',         'View system audit logs'),
(20, 'Manage Settings',         'manage_settings',         'settings',      'Configure system settings'),
(21, 'Manage Notifications',    'manage_notifications',    'notifications', 'Send and manage notifications');

-- =====================================================================
-- 3. ROLE_PERMISSIONS (FK to roles + permissions - both exist now)
-- =====================================================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
-- Super Admin - All permissions
(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),
-- HR Manager
(2,1),(2,5),(2,6),(2,7),(2,8),(2,9),(2,11),(2,12),(2,13),(2,14),(2,16),(2,17),(2,18),(2,21),
-- Department Manager
(3,1),(3,7),(3,8),(3,9),(3,12),(3,13),(3,18),
-- Employee
(4,1),(4,9),(4,10),(4,13),(4,15),
-- Auditor
(5,1),(5,7),(5,9),(5,18),(5,19);

-- =====================================================================
-- 4. COMPANIES (no dependencies)
-- =====================================================================
INSERT INTO `companies` (`id`, `name`, `legal_name`, `email`, `phone`, `address`, `city`, `country`, `currency`, `timezone`, `website`, `is_active`) VALUES
(1, 'Smart Corp Ltd', 'Smart Corporation Limited', 'info@smartcorp.com', '+1-555-0100', '123 Business Avenue, Downtown', 'New York', 'United States', 'USD', 'UTC', 'https://smartcorp.com', 1);

-- =====================================================================
-- 5. BRANCHES (FK to companies - exists now)
-- =====================================================================
INSERT INTO `branches` (`id`, `company_id`, `name`, `code`, `email`, `phone`, `address`, `city`, `country`, `latitude`, `longitude`, `geofence_radius`, `is_active`) VALUES
(1, 1, 'Headquarters', 'HQ', 'hq@smartcorp.com', '+1-555-0101', '123 Business Avenue', 'New York', 'United States', 40.7127760, -74.0059740, 150, 1),
(2, 1, 'West Coast Branch', 'WCB', 'west@smartcorp.com', '+1-555-0102', '456 Tech Boulevard', 'San Francisco', 'United States', 37.7749290, -122.4194150, 200, 1);

-- =====================================================================
-- 6. SHIFTS (no dependencies)
-- =====================================================================
INSERT INTO `shifts` (`id`, `name`, `code`, `start_time`, `end_time`, `grace_period_minutes`, `late_threshold_minutes`, `early_leave_threshold_minutes`, `overtime_eligible`, `overtime_rate`, `is_night_shift`, `is_flexible`, `working_hours_per_day`, `description`, `is_active`) VALUES
(1, 'Morning Shift',   'MS', '08:00:00', '17:00:00', 15, 15, 15, 1, 1.50, 0, 0, 8.00, 'Standard day shift 8AM-5PM', 1),
(2, 'Evening Shift',   'ES', '14:00:00', '22:00:00', 15, 15, 15, 1, 1.50, 0, 0, 8.00, 'Evening shift 2PM-10PM', 1),
(3, 'Night Shift',     'NS', '22:00:00', '06:00:00', 15, 15, 15, 1, 1.75, 1, 0, 8.00, 'Night shift 10PM-6AM', 1),
(4, 'Flexible Shift',  'FS', '09:00:00', '18:00:00', 30, 30, 30, 1, 1.50, 0, 1, 8.00, 'Flexible hours, 8 hours within window', 1);

-- =====================================================================
-- 7. DEPARTMENTS (FK to branches - exists now; NO FK to employees)
-- =====================================================================
INSERT INTO `departments` (`id`, `branch_id`, `name`, `code`, `description`, `is_active`) VALUES
(1, 1, 'Human Resources', 'HR', 'Human Resources Department', 1),
(2, 1, 'Finance', 'FIN', 'Finance and Accounting Department', 1),
(3, 1, 'Information Technology', 'IT', 'IT Department', 1),
(4, 1, 'Marketing', 'MKT', 'Marketing and Communications', 1),
(5, 1, 'Procurement', 'PROC', 'Procurement and Supply Chain', 1),
(6, 1, 'Operations', 'OPS', 'Operations Department', 1),
(7, 1, 'Security', 'SEC', 'Security Department', 1),
(8, 1, 'Administration', 'ADMIN', 'Administration Department', 1);

-- =====================================================================
-- 8. LEAVE TYPES (no dependencies)
-- =====================================================================
INSERT INTO `leave_types` (`id`, `name`, `code`, `description`, `default_days_per_year`, `is_paid`, `carry_forward`, `requires_attachment`, `is_active`) VALUES
(1, 'Annual Leave',     'AL', 'Paid annual vacation leave',       21.00, 1, 1, 0, 1),
(2, 'Sick Leave',       'SL', 'Paid sick leave with medical cert', 10.00, 1, 0, 1, 1),
(3, 'Maternity Leave',  'ML', 'Maternity leave',                   84.00, 1, 0, 1, 1),
(4, 'Emergency Leave',  'EL', 'Emergency personal leave',           5.00, 1, 0, 0, 1),
(5, 'Unpaid Leave',     'UL', 'Leave without pay',                  0.00, 0, 0, 0, 1);

-- =====================================================================
-- 9. HOLIDAYS (no dependencies)
-- =====================================================================
INSERT INTO `holidays` (`id`, `name`, `description`, `holiday_date`, `type`, `country`, `is_active`) VALUES
(1, 'New Year''s Day',      'First day of the year',  '2026-01-01', 'public',   'United States', 1),
(2, 'Independence Day',     'National holiday',       '2026-07-04', 'national', 'United States', 1),
(3, 'Christmas Day',        'Religious holiday',      '2026-12-25', 'religious','United States', 1),
(4, 'Labor Day',            'Workers day',            '2026-09-07', 'national', 'United States', 1);

-- =====================================================================
-- 10. WORKING POLICIES (FK to companies - exists)
-- =====================================================================
INSERT INTO `working_policies` (`id`, `company_id`, `name`, `working_days_per_week`, `working_hours_per_day`, `late_grace_minutes`, `late_penalty_per_hour`, `overtime_rate`, `weekend_days`, `is_active`) VALUES
(1, 1, 'Standard Policy', 5, 8.00, 15, 5.00, 1.50, 'Saturday,Sunday', 1);

-- =====================================================================
-- 11. SETTINGS (no dependencies)
-- =====================================================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `data_type`, `is_public`, `description`) VALUES
('app_name',               'Smart Employee Attendance System', 'general',     'string',  1, 'Application name'),
('app_version',            '1.0.0',                           'general',     'string',  1, 'Application version'),
('default_timezone',       'UTC',                              'general',     'string',  1, 'Default application timezone'),
('language',               'en',                               'general',     'string',  1, 'Default language'),
('date_format',            'Y-m-d',                            'general',     'string',  1, 'Date format'),
('time_format',            'H:i:s',                            'general',     'string',  1, 'Time format'),
('currency_symbol',        '$',                                'general',     'string',  1, 'Currency symbol'),
('face_match_threshold',   '0.55',                             'face',        'decimal', 0, 'Face match distance threshold (lower = stricter)'),
('face_min_confidence',    '0.90',                             'face',        'decimal', 0, 'Minimum face detection confidence'),
('anti_spoof_blinks',      '2',                                'face',        'integer', 0, 'Required blinks for liveness'),
('anti_spoof_head_move',   '1',                                'face',        'boolean', 0, 'Require head movement'),
('geofence_enabled',       '1',                                'attendance',  'boolean', 0, 'Enable geofencing for GPS attendance'),
('session_timeout',        '1800',                             'security',    'integer', 0, 'Session timeout in seconds'),
('max_login_attempts',     '5',                                'security',    'integer', 0, 'Max failed login attempts before lockout'),
('lockout_duration',       '900',                              'security',    'integer', 0, 'Account lockout duration in seconds'),
('password_min_length',    '8',                                'security',    'integer', 0, 'Minimum password length'),
('two_fa_required_admins', '1',                                'security',    'boolean', 0, 'Require 2FA for admin roles'),
('smtp_host',              '',                                 'email',       'string',  0, 'SMTP server host'),
('smtp_port',              '587',                              'email',       'integer', 0, 'SMTP server port'),
('smtp_username',          '',                                 'email',       'string',  0, 'SMTP username'),
('smtp_from_email',        'noreply@smartcorp.com',            'email',       'string',  0, 'Default from email address'),
('sms_api_key',            '',                                 'sms',         'string',  0, 'SMS gateway API key'),
('whatsapp_api_token',     '',                                 'whatsapp',    'string',  0, 'WhatsApp Business API token');

-- =====================================================================
-- 12. EMPLOYEES (FK to companies, branches, departments, shifts - all exist now)
-- =====================================================================
INSERT INTO `employees` (`id`, `employee_code`, `company_id`, `branch_id`, `department_id`, `shift_id`, `first_name`, `last_name`, `gender`, `phone`, `email`, `position`, `job_title`, `employment_status`, `salary`, `allowance`, `tax_rate`, `date_joined`, `is_active`, `created_by`) VALUES
(1, 'EMP001', 1, 1, 3, 1, 'John',  'Smith',    'male',   '+1-555-1001', 'john.smith@smartcorp.com',   'Senior Developer',  'Senior Software Developer', 'permanent',  8500.00, 500.00, 15.00, '2023-03-15', 1, NULL),
(2, 'EMP002', 1, 1, 1, 1, 'Sarah', 'Johnson',  'female', '+1-555-1002', 'sarah.johnson@smartcorp.com','HR Officer',        'Human Resources Officer',    'permanent',  5500.00, 400.00, 12.00, '2023-06-01', 1, NULL),
(3, 'EMP003', 1, 1, 2, 1, 'Mike',  'Davis',    'male',   '+1-555-1003', 'mike.davis@smartcorp.com',   'Accountant',        'Senior Accountant',         'permanent',  6000.00, 450.00, 13.00, '2022-11-20', 1, NULL),
(4, 'EMP004', 1, 1, 4, 1, 'Emily', 'Wilson',   'female', '+1-555-1004', 'emily.wilson@smartcorp.com', 'Marketing Lead',    'Marketing Manager',         'permanent',  7000.00, 500.00, 14.00, '2023-01-10', 1, NULL),
(5, 'EMP005', 1, 1, 6, 3, 'David', 'Brown',    'male',   '+1-555-1005', 'david.brown@smartcorp.com',  'Ops Supervisor',    'Operations Supervisor',     'contract',   5000.00, 350.00, 11.00, '2024-02-05', 1, NULL),
(6, 'EMP006', 1, 1, 3, 1, 'Lisa',  'Taylor',   'female', '+1-555-1006', 'lisa.taylor@smartcorp.com',  'UI Designer',       'UI/UX Designer',            'permanent',  6500.00, 450.00, 13.00, '2023-08-12', 1, NULL),
(7, 'EMP007', 1, 2, 3, 2, 'Robert','Anderson', 'male',   '+1-555-1007', 'robert.anderson@smartcorp.com','DevOps Eng',      'DevOps Engineer',           'permanent',  7800.00, 550.00, 15.00, '2023-04-22', 1, NULL),
(8, 'EMP008', 1, 1, 7, 3, 'James', 'Martinez', 'male',   '+1-555-1008', 'james.martinez@smartcorp.com','Security Guard',  'Security Officer',          'permanent',  3200.00, 200.00, 8.00,  '2024-01-15', 1, NULL);

-- =====================================================================
-- 13. USERS (FK to roles + employees - both exist now)
-- Default password for ALL users: password (bcrypt hash)
-- Hash generated with: password_hash('password', PASSWORD_BCRYPT)
-- =====================================================================
INSERT INTO `users` (`id`, `role_id`, `employee_id`, `name`, `email`, `phone`, `password_hash`, `status`, `created_at`) VALUES
(1, 1, NULL, 'Super Administrator', 'ethiennemugisha35@gmail.com', '+1-555-0000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),
(2, 2, 2,    'HR Manager',          'hr@smartcorp.com',           '+1-555-0001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),
(3, 3, NULL, 'IT Manager',          'manager@smartcorp.com',       '+1-555-0002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),
(4, 5, NULL, 'System Auditor',      'auditor@smartcorp.com',       '+1-555-0003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW()),
(5, 4, 1,    'John Smith',          'john.smith@smartcorp.com',     '+1-555-1001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW());

-- Now update employees to set created_by (since users exist now)
UPDATE `employees` SET `created_by` = 1 WHERE `created_by` IS NULL;

-- =====================================================================
-- 14. ATTENDANCE (FK to employees, shifts - both exist)
-- =====================================================================
INSERT INTO `attendance` (`employee_id`, `attendance_date`, `shift_id`, `check_in`, `check_out`, `check_in_method`, `check_out_method`, `working_hours`, `late_minutes`, `status`, `verified_by_face`) VALUES
(1, CURDATE(),                                1, CONCAT(CURDATE(), ' 08:05:00'), CONCAT(CURDATE(), ' 17:10:00'), 'face', 'face', 9.08, 0, 'present', 1),
(2, CURDATE(),                                1, CONCAT(CURDATE(), ' 08:25:00'), CONCAT(CURDATE(), ' 17:00:00'), 'face', 'face', 8.58, 10, 'late', 1),
(3, CURDATE(),                                1, CONCAT(CURDATE(), ' 07:55:00'), CONCAT(CURDATE(), ' 17:30:00'), 'face', 'face', 9.58, 0, 'present', 1),
(4, CURDATE(),                                1, CONCAT(CURDATE(), ' 08:15:00'), CONCAT(CURDATE(), ' 17:05:00'), 'face', 'face', 8.83, 0, 'present', 1),
(5, CURDATE(),                                3, CONCAT(CURDATE(), ' 22:05:00'), CONCAT(CURDATE(), ' 06:02:00'), 'face', 'face', 7.95, 0, 'present', 1),
(6, CURDATE(),                                1, CONCAT(CURDATE(), ' 08:50:00'), NULL,                           'face', NULL,  0.00, 35, 'late', 1),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY),      1, CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 08:00:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:05:00'), 'face', 'face', 9.08, 0, 'present', 1),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY),      1, CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 08:10:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:15:00'), 'face', 'face', 9.08, 0, 'present', 1),
(3, DATE_SUB(CURDATE(), INTERVAL 1 DAY),      1, NULL, NULL, NULL, NULL, 0.00, 0, 'absent', 0),
(4, DATE_SUB(CURDATE(), INTERVAL 1 DAY),      1, CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 08:30:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:00:00'), 'face', 'face', 8.50, 15, 'late', 1),
(5, DATE_SUB(CURDATE(), INTERVAL 1 DAY),      3, CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 22:00:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 06:00:00'), 'face', 'face', 8.00, 0, 'present', 1),
(6, DATE_SUB(CURDATE(), INTERVAL 1 DAY),      1, CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 08:00:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY), ' 17:00:00'), 'face', 'face', 9.00, 0, 'present', 1);

-- =====================================================================
-- 15. LEAVE REQUESTS (FK to employees, leave_types - both exist)
-- =====================================================================
INSERT INTO `leave_requests` (`employee_id`, `leave_type_id`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `applied_by`, `created_at`) VALUES
(3, 1, DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 9 DAY), 3.00, 'Family vacation planned', 'pending', 1, NOW()),
(5, 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 2.00, 'Flu and fever',           'approved', 5, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(6, 4, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 1.00, 'Family emergency',     'rejected', 5, DATE_SUB(NOW(), INTERVAL 11 DAY));

-- Re-enable FK checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED IMPORT COMPLETE
-- =====================================================================
-- Default login (password: password):
--   Super Admin:    ethiennemugisha35@gmail.com
--   HR Manager:     hr@smartcorp.com
--   IT Manager:     manager@smartcorp.com
--   Auditor:        auditor@smartcorp.com
--   Employee:       john.smith@smartcorp.com
-- =====================================================================
