-- Migration: Add rental and exchange period fields to books table
-- Run this SQL in your database (ba_dere_exchange)

-- Add rental period fields
ALTER TABLE `books`
ADD COLUMN `rental_price` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Price per rental period' AFTER `price`,
ADD COLUMN `rental_period_unit` ENUM('day','week','month') NULL DEFAULT 'day' COMMENT 'Rental pricing unit' AFTER `rental_price`,
ADD COLUMN `rental_min_period` INT(11) NULL DEFAULT 1 COMMENT 'Minimum rental duration' AFTER `rental_period_unit`,
ADD COLUMN `rental_max_period` INT(11) NULL DEFAULT 30 COMMENT 'Maximum rental duration' AFTER `rental_min_period`;

-- Add exchange period fields
ALTER TABLE `books`
ADD COLUMN `exchange_duration` INT(11) NULL DEFAULT 14 COMMENT 'Exchange duration value' AFTER `rental_max_period`,
ADD COLUMN `exchange_duration_unit` ENUM('day','week','month') NULL DEFAULT 'day' COMMENT 'Exchange duration unit' AFTER `exchange_duration`;

-- Verify the changes
DESCRIBE `books`;
