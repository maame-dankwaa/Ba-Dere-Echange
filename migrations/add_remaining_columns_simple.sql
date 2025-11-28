-- Migration: Add remaining rental and exchange period fields
-- Run each statement one at a time, skip if you get "Duplicate column" error
-- rental_price already exists, so skip that one

-- Add rental_period_unit (skip if you get duplicate error)
ALTER TABLE `fp_books` 
ADD COLUMN `rental_period_unit` ENUM('day','week','month') NULL DEFAULT 'day' COMMENT 'Rental pricing unit' AFTER `rental_price`;

-- Add rental_min_period (skip if you get duplicate error)
ALTER TABLE `fp_books` 
ADD COLUMN `rental_min_period` INT(11) NULL DEFAULT 1 COMMENT 'Minimum rental duration' AFTER `rental_period_unit`;

-- Add rental_max_period (skip if you get duplicate error)
ALTER TABLE `fp_books` 
ADD COLUMN `rental_max_period` INT(11) NULL DEFAULT 30 COMMENT 'Maximum rental duration' AFTER `rental_min_period`;

-- Add exchange_duration (skip if you get duplicate error)
ALTER TABLE `fp_books` 
ADD COLUMN `exchange_duration` INT(11) NULL DEFAULT 14 COMMENT 'Exchange duration value' AFTER `rental_max_period`;

-- Add exchange_duration_unit (skip if you get duplicate error)
ALTER TABLE `fp_books` 
ADD COLUMN `exchange_duration_unit` ENUM('day','week','month') NULL DEFAULT 'day' COMMENT 'Exchange duration unit' AFTER `exchange_duration`;

