-- ical-availability-sync: Database schema
-- MySQL 5.7+ / MariaDB 10.2+

CREATE TABLE IF NOT EXISTS `booked_periods` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` INT UNSIGNED NOT NULL,
    `source` VARCHAR(50) NOT NULL COMMENT 'booking, airbnb, direct, other',
    `date_start` DATE NOT NULL,
    `date_end` DATE NOT NULL,
    `summary` VARCHAR(255) DEFAULT 'Booked',
    `external_uid` VARCHAR(255) DEFAULT NULL COMMENT 'UID from iCal event',
    `synced_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_property_dates` (`property_id`, `date_start`, `date_end`),
    INDEX `idx_property_source` (`property_id`, `source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
