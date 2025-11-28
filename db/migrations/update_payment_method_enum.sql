-- Update payment_method ENUM to include 'paystack'
-- Run this migration to fix the checkout error

ALTER TABLE transactions
MODIFY COLUMN payment_method ENUM('mobile_money', 'visa', 'bank_transfer', 'cash', 'paystack') NOT NULL;
