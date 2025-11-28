-- Migration: Add rental duration fields to transactions table (SAFE VERSION)
-- This version checks if columns exist before adding them
-- Run this SQL in your database (ba_dere_exchange)

SET @dbname = DATABASE();
SET @tablename = 'fp_transactions';

-- Add rental_duration if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @dbname
AND TABLE_NAME = @tablename
AND COLUMN_NAME = 'rental_duration';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `fp_transactions` ADD COLUMN `rental_duration` INT(11) NULL DEFAULT NULL COMMENT "Rental duration (number of periods)" AFTER `transaction_type`',
    'SELECT "Column rental_duration already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add rental_period_unit if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @dbname
AND TABLE_NAME = @tablename
AND COLUMN_NAME = 'rental_period_unit';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `fp_transactions` ADD COLUMN `rental_period_unit` ENUM("day","week","month") NULL DEFAULT NULL COMMENT "Rental period unit" AFTER `rental_duration`',
    'SELECT "Column rental_period_unit already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the changes
DESCRIBE `fp_transactions`;
