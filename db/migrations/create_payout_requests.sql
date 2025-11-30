-- Create payout requests table if it doesn't exist
-- This table tracks vendor requests for revenue payouts

CREATE TABLE IF NOT EXISTS fp_payout_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payout_method ENUM('mobile_money', 'bank_transfer', 'paystack') NOT NULL DEFAULT 'paystack',
    account_details TEXT NOT NULL, -- JSON with account info (phone, account number, etc.)
    request_status ENUM('pending', 'approved', 'processing', 'completed', 'rejected', 'failed') DEFAULT 'pending',
    paystack_transfer_code VARCHAR(100) NULL, -- Paystack transfer reference
    transaction_reference VARCHAR(100) NULL,
    processed_by INT NULL,
    processed_at DATETIME NULL,
    rejection_reason TEXT NULL,
    failure_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES fp_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES fp_users(user_id) ON DELETE SET NULL,
    INDEX idx_vendor (vendor_id),
    INDEX idx_status (request_status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

