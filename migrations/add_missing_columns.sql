-- Migration: Add missing rental and exchange period fields
-- Run this in phpMyAdmin with ba_dere_exchange database selected
-- It will skip columns that already exist and only add missing ones

-- Add missing columns to books table
ALTER TABLE `fp_books`
ADD COLUMN IF NOT EXISTS `rental_price` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Price per rental period' AFTER `price`,
ADD COLUMN IF NOT EXISTS `rental_period_unit` ENUM('day','week','month') NULL DEFAULT 'day' COMMENT 'Rental pricing unit' AFTER `rental_price`,
ADD COLUMN IF NOT EXISTS `rental_min_period` INT(11) NULL DEFAULT 1 COMMENT 'Minimum rental duration' AFTER `rental_period_unit`,
ADD COLUMN IF NOT EXISTS `rental_max_period` INT(11) NULL DEFAULT 30 COMMENT 'Maximum rental duration' AFTER `rental_min_period`,
ADD COLUMN IF NOT EXISTS `exchange_duration` INT(11) NULL DEFAULT 14 COMMENT 'Exchange duration value' AFTER `rental_max_period`,
ADD COLUMN IF NOT EXISTS `exchange_duration_unit` ENUM('day','week','month') NULL DEFAULT 'day' COMMENT 'Exchange duration unit' AFTER `exchange_duration`;

-- Add missing columns to transactions table
ALTER TABLE `fp_transactions`
ADD COLUMN IF NOT EXISTS `rental_duration` INT(11) NULL DEFAULT NULL COMMENT 'Rental duration (number of periods)' AFTER `transaction_type`,
ADD COLUMN IF NOT EXISTS `rental_period_unit` ENUM('day','week','month') NULL DEFAULT NULL COMMENT 'Rental period unit' AFTER `rental_duration`;

-- Verify changes
SELECT 'Books table columns added successfully' AS status;
DESCRIBE `fp_books`;
SELECT 'Transactions table columns added successfully' AS status;
DESCRIBE `fp_transactions`;
