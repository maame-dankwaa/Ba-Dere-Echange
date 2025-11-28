-- Vendor Applications Table
-- Stores applications from customers who want to become vendors

CREATE TABLE IF NOT EXISTS fp_vendor_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_name VARCHAR(255),
    business_description TEXT,
    phone VARCHAR(20),
    id_document VARCHAR(255) COMMENT 'Path to uploaded ID document',
    application_reason TEXT NOT NULL COMMENT 'Why they want to become a vendor',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT NULL COMMENT 'Admin user_id who reviewed',
    reviewed_at DATETIME NULL,
    rejection_reason TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES fp_users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES fp_users(user_id) ON DELETE SET NULL,

    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
