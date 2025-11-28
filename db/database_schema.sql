-- Ba Dɛre Exchange Database Schema
-- Database: ba_dere_exchange

CREATE DATABASE IF NOT EXISTS ba_dere_exchange 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE ba_dere_exchange;

-- Users Table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255),
    bio TEXT,
    location VARCHAR(100),
    user_role ENUM('admin', 'vendor', 'customer') NOT NULL DEFAULT 'customer',
    
    -- Vendor/Institution specific fields
    institution_name VARCHAR(200),
    institution_type ENUM('individual', 'bookstore', 'publisher', 'library', 'university') NULL,
    business_registration_number VARCHAR(100),
    tax_id VARCHAR(100),
    vendor_verified BOOLEAN DEFAULT FALSE,
    vendor_verification_date DATETIME,
    store_description TEXT,
    store_logo VARCHAR(255),
    business_address TEXT,
    business_phone VARCHAR(20),
    business_email VARCHAR(100),
    website_url VARCHAR(255),
    social_media_links TEXT, -- JSON: {facebook, twitter, instagram}
    
    -- Customer specific fields
    shipping_addresses TEXT, -- JSON array of addresses
    
    -- Common fields
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(100),
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_sales INT DEFAULT 0,
    total_purchases INT DEFAULT 0,
    total_rentals_given INT DEFAULT 0,
    total_rentals_received INT DEFAULT 0,
    account_balance DECIMAL(10,2) DEFAULT 0.00, -- For vendors to track earnings
    is_active BOOLEAN DEFAULT TRUE,
    is_suspended BOOLEAN DEFAULT FALSE,
    suspended_until DATETIME,
    suspension_reason TEXT,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_user_role (user_role),
    INDEX idx_location (location),
    INDEX idx_vendor_verified (vendor_verified)
) ENGINE=InnoDB;

-- Categories Table
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    parent_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- Books Table
CREATE TABLE books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(20),
    category_id INT NOT NULL,
    description TEXT,
    condition_type ENUM('like_new', 'good', 'acceptable', 'poor') NOT NULL,
    cover_image VARCHAR(255),
    additional_images TEXT, -- JSON array of image paths
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2),
    rental_price_daily DECIMAL(10,2),
    rental_price_weekly DECIMAL(10,2),
    rental_price_monthly DECIMAL(10,2),
    is_rentable BOOLEAN DEFAULT FALSE,
    is_exchangeable BOOLEAN DEFAULT FALSE,
    quantity INT DEFAULT 1,
    available_quantity INT DEFAULT 1,
    publisher VARCHAR(100),
    publication_year YEAR,
    edition VARCHAR(50),
    language VARCHAR(50) DEFAULT 'English',
    pages INT,
    status ENUM('active', 'sold', 'rented', 'inactive') DEFAULT 'active',
    views_count INT DEFAULT 0,
    favorites_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    featured_until DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_category (category_id),
    INDEX idx_seller (seller_id),
    INDEX idx_status (status),
    INDEX idx_price (price),
    FULLTEXT idx_search (title, author, description)
) ENGINE=InnoDB;

-- Activity Log Table
CREATE TABLE activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;
-- Transactions Table
CREATE TABLE transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_code VARCHAR(50) UNIQUE NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    book_id INT NOT NULL,
    transaction_type ENUM('purchase', 'rental', 'exchange') NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    seller_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('mobile_money', 'visa', 'bank_transfer', 'cash', 'paystack') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'pending',
    payment_reference VARCHAR(100),
    delivery_method ENUM('pickup', 'delivery') NOT NULL,
    delivery_address TEXT,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    delivery_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    tracking_number VARCHAR(100),
    notes TEXT,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(user_id),
    FOREIGN KEY (seller_id) REFERENCES users(user_id),
    FOREIGN KEY (book_id) REFERENCES books(book_id),
    INDEX idx_buyer (buyer_id),
    INDEX idx_seller (seller_id),
    INDEX idx_transaction_code (transaction_code),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Rentals Table
CREATE TABLE rentals (
    rental_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    book_id INT NOT NULL,
    renter_id INT NOT NULL,
    owner_id INT NOT NULL,
    rental_start_date DATE NOT NULL,
    rental_end_date DATE NOT NULL,
    expected_return_date DATE NOT NULL,
    actual_return_date DATE,
    rental_duration_days INT NOT NULL,
    daily_rate DECIMAL(10,2) NOT NULL,
    total_rental_fee DECIMAL(10,2) NOT NULL,
    deposit_amount DECIMAL(10,2) DEFAULT 0.00,
    late_fee DECIMAL(10,2) DEFAULT 0.00,
    damage_fee DECIMAL(10,2) DEFAULT 0.00,
    rental_status ENUM('active', 'returned', 'overdue', 'cancelled') DEFAULT 'active',
    return_condition ENUM('like_new', 'good', 'acceptable', 'damaged') NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id),
    FOREIGN KEY (book_id) REFERENCES books(book_id),
    FOREIGN KEY (renter_id) REFERENCES users(user_id),
    FOREIGN KEY (owner_id) REFERENCES users(user_id),
    INDEX idx_renter (renter_id),
    INDEX idx_status (rental_status),
    INDEX idx_dates (rental_start_date, rental_end_date)
) ENGINE=InnoDB;

-- Reviews Table
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    transaction_id INT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    seller_response TEXT,
    responded_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id),
    INDEX idx_book (book_id),
    INDEX idx_reviewer (reviewer_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB;

-- Wishlists Table
CREATE TABLE wishlists (
    wishlist_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    notify_on_availability BOOLEAN DEFAULT FALSE,
    notify_on_price_drop BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book (user_id, book_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Messages Table
CREATE TABLE messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    book_id INT NULL,
    subject VARCHAR(255),
    message_text TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    parent_message_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id),
    FOREIGN KEY (recipient_id) REFERENCES users(user_id),
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE SET NULL,
    FOREIGN KEY (parent_message_id) REFERENCES messages(message_id) ON DELETE CASCADE,
    INDEX idx_recipient (recipient_id),
    INDEX idx_sender (sender_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB;

-- Featured Listings Table
CREATE TABLE featured_listings (
    featured_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    seller_id INT NOT NULL,
    package_type ENUM('basic', 'premium', 'gold') NOT NULL,
    price_paid DECIMAL(10,2) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(user_id),
    INDEX idx_active (is_active, end_date),
    INDEX idx_book (book_id)
) ENGINE=InnoDB;

-- Vendor Applications Table
CREATE TABLE vendor_applications (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    institution_name VARCHAR(200) NOT NULL,
    institution_type ENUM('individual', 'bookstore', 'publisher', 'library', 'university') NOT NULL,
    business_registration_number VARCHAR(100),
    tax_id VARCHAR(100),
    business_address TEXT NOT NULL,
    business_phone VARCHAR(20) NOT NULL,
    business_email VARCHAR(100) NOT NULL,
    website_url VARCHAR(255),
    description TEXT NOT NULL,
    supporting_documents TEXT, -- JSON array of document paths
    application_status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME,
    rejection_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (application_status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Admin Actions Log Table
CREATE TABLE admin_actions (
    action_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action_type ENUM('user_suspend', 'user_activate', 'vendor_approve', 'vendor_reject', 
                     'book_remove', 'transaction_refund') NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id INT NOT NULL,
    description TEXT NOT NULL,
    previous_state TEXT, -- JSON of previous values
    new_state TEXT, -- JSON of new values
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_admin (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Vendor Payouts Table
CREATE TABLE vendor_payouts (
    payout_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payout_method ENUM('mobile_money', 'bank_transfer') NOT NULL,
    account_details TEXT NOT NULL, -- JSON with account info
    payout_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    transaction_reference VARCHAR(100),
    processed_by INT NULL,
    processed_at DATETIME,
    failure_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_vendor (vendor_id),
    INDEX idx_status (payout_status)
) ENGINE=InnoDB;

-- Notifications Table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Insert default categories
INSERT INTO categories (name, slug, description, icon, parent_id) VALUES
('Academic', 'academic', 'Academic books and textbooks', 'academic-icon.svg', NULL),
('Fiction', 'fiction', 'Fictional books and novels', 'fiction-icon.svg', NULL),
('Non-Fiction', 'non-fiction', 'Non-fictional books', 'nonfiction-icon.svg', NULL),
('Science', 'science', 'Science textbooks', 'science-icon.svg', 1),
('Mathematics', 'mathematics', 'Mathematics textbooks', 'math-icon.svg', 1),
('Literature', 'literature', 'Literature books', 'literature-icon.svg', 1),
('Business', 'business', 'Business and economics books', 'business-icon.svg', 3),
('Self-Help', 'self-help', 'Self-help and personal development', 'selfhelp-icon.svg', 3);

-- Insert default admin user (password: Admin@123456)
INSERT INTO users (username, email, password_hash, full_name, user_role, admin_level, email_verified, is_active) VALUES
('admin', 'admin@badereexchange.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'super_admin', TRUE, TRUE);
