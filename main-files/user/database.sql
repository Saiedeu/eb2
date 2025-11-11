-- Database create করার পরিবর্তে শুধু USE করুন
USE if0_38847374_User;

-- অথবা এভাবে লিখুন
CREATE DATABASE IF NOT EXISTS if0_38847374_User CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE if0_38847374_User;

-- Tables create করুন
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NULL, -- NULL for Google users
    google_id VARCHAR(50) NULL,
    profile_image VARCHAR(255) NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(100) NULL,
    login_type ENUM('email', 'google') DEFAULT 'email',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Index for faster search
CREATE INDEX IF NOT EXISTS idx_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_verification_token ON users(verification_token);
CREATE INDEX IF NOT EXISTS idx_google_id ON users(google_id);