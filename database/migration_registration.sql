-- Run this against your existing smart_attendance database
USE `smart_attendance`;

CREATE TABLE IF NOT EXISTS `registration_requests` (
    `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token`                   VARCHAR(64)  NOT NULL,
    `first_name`              VARCHAR(100) NOT NULL,
    `last_name`               VARCHAR(100) NOT NULL,
    `email`                   VARCHAR(150) NOT NULL,
    `phone`                   VARCHAR(30)  NULL,
    `national_id`             VARCHAR(50)  NULL,
    `passport_number`         VARCHAR(50)  NULL,
    `date_of_birth`           DATE         NULL,
    `gender`                  ENUM('male','female','other') NULL,
    `address`                 TEXT         NULL,
    `employee_code`           VARCHAR(50)  NULL,
    `position`                VARCHAR(150) NULL,
    `department_id`           INT UNSIGNED NULL,
    `branch_id`               INT UNSIGNED NULL,
    `employment_type`         VARCHAR(20)  NULL DEFAULT 'full_time',
    `emergency_contact_name`  VARCHAR(150) NULL,
    `emergency_contact_phone` VARCHAR(30)  NULL,
    `emergency_contact_rel`   VARCHAR(50)  NULL,
    `password_hash`           VARCHAR(255) NOT NULL,
    `role_id`                 INT UNSIGNED NOT NULL DEFAULT 4,
    `face_descriptors`        LONGTEXT     NULL,
    `face_enrolled_at`        DATETIME     NULL,
    `face_quality_score`      DECIMAL(5,2) NULL,
    `face_angles_captured`    VARCHAR(100) NULL,
    `profile_photo`           VARCHAR(500) NULL,
    `status`                  VARCHAR(30)  NOT NULL DEFAULT 'pending',
    `submitted_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_by`             INT UNSIGNED NULL,
    `reviewed_at`             DATETIME     NULL,
    `rejection_reason`        TEXT         NULL,
    `change_request_notes`    TEXT         NULL,
    `resubmitted_at`          DATETIME     NULL,
    `resubmit_count`          TINYINT      NOT NULL DEFAULT 0,
    `user_id`                 INT UNSIGNED NULL,
    `employee_id`             INT UNSIGNED NULL,
    `ip_address`              VARCHAR(45)  NULL,
    `user_agent`              VARCHAR(500) NULL,
    `created_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reg_email`  (`email`),
    UNIQUE KEY `uq_reg_token`  (`token`),
    KEY `idx_reg_status`       (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `approval_logs` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`   INT UNSIGNED NOT NULL,
    `action`       VARCHAR(30)  NOT NULL,
    `performed_by` INT UNSIGNED NULL,
    `notes`        TEXT         NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_approval_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_status_history` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT UNSIGNED NOT NULL,
    `from_status` VARCHAR(50)  NULL,
    `to_status`   VARCHAR(50)  NOT NULL,
    `reason`      TEXT         NULL,
    `changed_by`  INT UNSIGNED NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_esh_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure employees has registration_request_id column
ALTER TABLE `employees`
    ADD COLUMN IF NOT EXISTS `registration_request_id` INT UNSIGNED NULL AFTER `created_by`,
    ADD COLUMN IF NOT EXISTS `qr_enrolled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `face_enrolled`,
    ADD COLUMN IF NOT EXISTS `mobile_attendance_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `qr_enrolled`;

SELECT 'Migration complete.' AS status;
