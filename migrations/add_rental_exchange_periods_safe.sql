-- Migration: Add rental and exchange period fields to books table (SAFE VERSION)
-- This version checks if columns exist before adding them
-- Run this SQL in your database (ba_dere_exchange)

-- Add rental period fields only if they don't exist
SET @dbname = DATABASE();
SET @tablename = 'fp_books';

-- Add rental_price if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @dbname
AND TABLE_NAME = @tablename
AND COLUMN_NAME = 'rental_price';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `fp_books` ADD COLUMN `rental_price` DECIMAL(10,2) NULL DEFAULT NULL COMMENT "Price per rental period" AFTER `price`',
    'SELECT "Column rental_price already exists" AS message');
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
    'ALTER TABLE `fp_books` ADD COLUMN `rental_period_unit` ENUM("day","week","month") NULL DEFAULT "day" COMMENT "Rental pricing unit" AFTER `rental_price`',
    'SELECT "Column rental_period_unit already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add rental_min_period if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @dbname
AND TABLE_NAME = @tablename
AND COLUMN_NAME = 'rental_min_period';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `fp_books` ADD COLUMN `rental_min_period` INT(11) NULL DEFAULT 1 COMMENT "Minimum rental duration" AFTER `rental_period_unit`',
    'SELECT "Column rental_min_period already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add rental_max_period if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @dbname
AND TABLE_NAME = @tablename
AND COLUMN_NAME = 'rental_max_period';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `fp_books` ADD COLUMN `rental_max_period` INT(11) NULL DEFAULT 30 COMMENT "Maximum rental duration" AFTER `rental_min_period`',
    'SELECT "Column rental_max_period already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add exchange_duration if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @dbname
AND TABLE_NAME = @tablename
AND COLUMN_NAME = 'exchange_duration';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `fp_books` ADD COLUMN `exchange_duration` INT(11) NULL DEFAULT 14 COMMENT "Exchange duration value" AFTER `rental_max_period`',
    'SELECT "Column exchange_duration already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add exchange_duration_unit if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @dbname
AND TABLE_NAME = @tablename
AND COLUMN_NAME = 'exchange_duration_unit';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `fp_books` ADD COLUMN `exchange_duration_unit` ENUM("day","week","month") NULL DEFAULT "day" COMMENT "Exchange duration unit" AFTER `exchange_duration`',
    'SELECT "Column exchange_duration_unit already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the changes
DESCRIBE `fp_books`;
