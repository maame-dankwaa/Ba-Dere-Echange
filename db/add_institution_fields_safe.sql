-- Add institution-related fields to users table (SAFE VERSION)
-- This allows institutions (schools, libraries, bookstores) to register
-- Only adds columns if they don't already exist

-- Add account_type field to distinguish between individual and institution accounts
ALTER TABLE users
ADD COLUMN IF NOT EXISTS account_type ENUM('individual', 'institution') DEFAULT 'individual' AFTER user_role;

-- Add institution verification status
ALTER TABLE users
ADD COLUMN IF NOT EXISTS institution_verified TINYINT(1) DEFAULT 0 AFTER email_verified;

-- institution_name field should already exist from the original schema
-- Uncomment below if needed:
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS institution_name VARCHAR(255) NULL AFTER location;

-- Add additional institution fields (only if they don't exist)
ALTER TABLE users
ADD COLUMN IF NOT EXISTS institution_type VARCHAR(100) NULL COMMENT 'Type: University, Library, Bookstore, etc.' AFTER institution_name;

ALTER TABLE users
ADD COLUMN IF NOT EXISTS institution_registration_number VARCHAR(100) NULL COMMENT 'Business/Institution registration number' AFTER institution_type;

ALTER TABLE users
ADD COLUMN IF NOT EXISTS institution_address TEXT NULL AFTER institution_registration_number;

ALTER TABLE users
ADD COLUMN IF NOT EXISTS institution_website VARCHAR(255) NULL AFTER institution_address;

ALTER TABLE users
ADD COLUMN IF NOT EXISTS institution_verification_document VARCHAR(255) NULL COMMENT 'Path to verification document' AFTER institution_website;

-- Add index for account_type (won't error if exists)
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_account_type (account_type);
