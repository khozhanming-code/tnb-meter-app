-- TNB Electricity Meter Monitoring App
-- Database schema (import this in phpMyAdmin on XAMPP)

CREATE DATABASE IF NOT EXISTS tnb_meter_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tnb_meter_app;

-- ============================
-- Users (Admin + normal Users)
-- ============================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================
-- Meters / Buildings
-- ============================
CREATE TABLE meters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,        -- e.g. "Building A - Main Meter"
    location VARCHAR(255) DEFAULT NULL,
    tnb_account_no VARCHAR(100) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================
-- Which user can submit for which meter
-- ============================
CREATE TABLE user_meters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meter_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (meter_id) REFERENCES meters(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_meter (user_id, meter_id)
);

-- ============================
-- Meter Readings
-- ============================
CREATE TABLE meter_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id INT NOT NULL,
    user_id INT NOT NULL,
    reading_value DECIMAL(12,2) NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    capture_method ENUM('v1_upload','v2_live_camera') NOT NULL DEFAULT 'v1_upload',
    reading_date DATE NOT NULL,        -- Malaysia date of submission
    reading_time TIME NOT NULL,        -- Malaysia time of submission
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meter_id) REFERENCES meters(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_meter_date (meter_id, reading_date)
);

-- ============================
-- App Settings (key/value)
-- ============================
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value VARCHAR(500) NOT NULL
);

INSERT INTO settings (setting_key, setting_value) VALUES
('daily_usage_limit_kwh', '80'),
('submission_deadline_time', '10:00'),
('whatsapp_admin_number', '60123456789'),
('whatsapp_api_url', 'https://api.fonnte.com/send'),
('whatsapp_api_token', 'PUT_YOUR_WHATSAPP_API_TOKEN_HERE'),
('app_version', '1');
-- app_version: '1' = normal upload allowed, '2' = force live camera capture only

-- ============================
-- Notification log (for audit / avoid duplicate alerts)
-- ============================
CREATE TABLE notification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('over_usage','missing_reading') NOT NULL,
    meter_id INT NOT NULL,
    log_date DATE NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent','failed') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_notif (type, meter_id, log_date)
);

-- ============================
-- Seed default admin + demo user
-- IMPORTANT: password column below is a temporary placeholder (NOT a valid hash).
-- After importing this file, open setup_create_accounts.php in your browser ONCE
-- to set real, working bcrypt passwords for these two accounts (admin123 / user123),
-- then delete setup_create_accounts.php for security.
-- ============================
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@example.com', 'CHANGE_ME_VIA_SETUP_SCRIPT', 'admin');

-- ============================
-- Seed sample meter + assign to a sample user (optional)
-- ============================
INSERT INTO meters (name, location) VALUES ('Building A - Main Meter', 'Ground Floor, Building A');

INSERT INTO users (name, email, password, role) VALUES
('User Demo', 'user@example.com', 'CHANGE_ME_VIA_SETUP_SCRIPT', 'user');

INSERT INTO user_meters (user_id, meter_id) VALUES (2, 1);
