-- Migration: Add rental duration fields to transactions table
-- Run this SQL in your database (ba_dere_exchange)

-- Add rental duration fields to track rental period for transactions
ALTER TABLE `transactions`
ADD COLUMN `rental_duration` INT(11) NULL DEFAULT NULL COMMENT 'Rental duration (number of periods)' AFTER `transaction_type`,
ADD COLUMN `rental_period_unit` ENUM('day','week','month') NULL DEFAULT NULL COMMENT 'Rental period unit' AFTER `rental_duration`;

-- Verify the changes
DESCRIBE `transactions`;
