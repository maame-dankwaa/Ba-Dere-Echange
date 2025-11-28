-- Add featured listing functionality
-- Allows vendors to pay to feature their listings for a specific duration

-- Add missing featured columns to books table (if they don't exist)
-- Note: is_featured and featured_until already exist, only add new ones

-- Create featured_listing_transactions table
CREATE TABLE IF NOT EXISTS featured_listing_transactions (
    featured_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    duration_days INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    featured_from DATETIME NOT NULL,
    featured_until DATETIME NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'paystack',
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_book (book_id),
    INDEX idx_user (user_id),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
