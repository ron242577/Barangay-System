-- OTP Codes Table for Password Reset
-- Run this in your database to create the otp_codes table

CREATE TABLE IF NOT EXISTS otp_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,         -- Hashed OTP code
    expires_at DATETIME NOT NULL,       -- Expiration timestamp (10 minutes)
    used BOOLEAN DEFAULT FALSE,         -- Flag to mark OTP as used/invalidated
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(email),
    INDEX(expires_at)
);
