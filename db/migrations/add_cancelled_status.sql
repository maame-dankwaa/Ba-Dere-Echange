-- Add 'cancelled' status to payment_status and delivery_status ENUMs
-- Run this migration to support transaction cancellation

ALTER TABLE transactions
MODIFY COLUMN payment_status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'pending';

ALTER TABLE transactions
MODIFY COLUMN delivery_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending';
