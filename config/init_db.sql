-- Database initialization script for Deb's Health Tracker
-- Run this script to create the database and tables

CREATE DATABASE IF NOT EXISTS deb_health_tracker;
USE deb_health_tracker;

-- Users table with role-based access
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'patient', 'viewer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Health logs table for tracking vital signs and symptoms
CREATE TABLE IF NOT EXISTS health_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    log_date DATETIME NOT NULL,
    systolic_bp INT,
    diastolic_bp INT,
    heart_rate INT,
    weight DECIMAL(5,2),
    temperature DECIMAL(4,2),
    symptoms TEXT,
    medications TEXT,
    notes TEXT,
    mood ENUM('excellent', 'good', 'fair', 'poor', 'critical'),
    activity_level ENUM('sedentary', 'light', 'moderate', 'active', 'very_active'),
    sleep_hours DECIMAL(3,1),
    stress_level INT CHECK (stress_level BETWEEN 1 AND 10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_log_date (log_date),
    INDEX idx_user_date (user_id, log_date)
);

-- Stroke warning signs tracking
CREATE TABLE IF NOT EXISTS stroke_warnings (
    warning_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    warning_date DATETIME NOT NULL,
    face_drooping BOOLEAN DEFAULT FALSE,
    arm_weakness BOOLEAN DEFAULT FALSE,
    speech_difficulty BOOLEAN DEFAULT FALSE,
    sudden_confusion BOOLEAN DEFAULT FALSE,
    vision_problems BOOLEAN DEFAULT FALSE,
    severe_headache BOOLEAN DEFAULT FALSE,
    dizziness BOOLEAN DEFAULT FALSE,
    loss_of_balance BOOLEAN DEFAULT FALSE,
    severity ENUM('mild', 'moderate', 'severe') NOT NULL,
    action_taken TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_warning_date (warning_date)
);

-- Medications tracking
CREATE TABLE IF NOT EXISTS medications (
    medication_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    medication_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50),
    frequency VARCHAR(50),
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    prescribing_doctor VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Access permissions for viewers
CREATE TABLE IF NOT EXISTS access_permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    viewer_id INT NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (viewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_permission (patient_id, viewer_id)
);

-- Alerts and notifications
CREATE TABLE IF NOT EXISTS alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    alert_type ENUM('critical_bp', 'warning_signs', 'medication_reminder', 'checkup_due', 'general') NOT NULL,
    alert_message TEXT NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
);

-- System settings and preferences
CREATE TABLE IF NOT EXISTS user_preferences (
    preference_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    theme VARCHAR(20) DEFAULT 'light',
    notifications_enabled BOOLEAN DEFAULT TRUE,
    bp_alert_threshold_systolic INT DEFAULT 140,
    bp_alert_threshold_diastolic INT DEFAULT 90,
    language VARCHAR(10) DEFAULT 'en',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_pref (user_id)
);

-- Insert default admin user (password: admin123 - should be changed after first login)
INSERT INTO users (username, password_hash, full_name, role, is_active) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', TRUE);

-- Insert Deb as the patient user (access code: 80087355)
INSERT INTO users (username, password_hash, full_name, role, is_active, created_by) 
VALUES ('deb', '$2y$10$YourHashedAccessCodeHere', 'Deb', 'patient', TRUE, 1);

-- Note: The password hash for '80087355' should be generated using password_hash() in PHP
-- Run the setup script to properly hash the access code
