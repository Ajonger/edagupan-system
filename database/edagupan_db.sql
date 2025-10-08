-- Create Database
CREATE DATABASE IF NOT EXISTS edagupan_db;
USE edagupan_db;

-- Users Table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    barangay VARCHAR(100),
    user_type ENUM('citizen', 'admin', 'staff') DEFAULT 'citizen',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permits Table
CREATE TABLE permits (
    permit_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    permit_type ENUM('business', 'event', 'construction', 'fishing') NOT NULL,
    business_name VARCHAR(200),
    business_type VARCHAR(100),
    business_address TEXT,
    event_name VARCHAR(200),
    event_date DATE,
    event_location TEXT,
    construction_type VARCHAR(100),
    construction_address TEXT,
    fishing_vessel_name VARCHAR(100),
    fishing_permit_type VARCHAR(50),
    documents JSON,
    status ENUM('pending', 'processing', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    remarks TEXT,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_type (permit_type),
    INDEX idx_reference (reference_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Complaints/Issues Table
CREATE TABLE complaints (
    complaint_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    category ENUM('waste_management', 'road_repair', 'flood', 'streetlight', 'water', 'traffic', 'other') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location TEXT NOT NULL,
    barangay VARCHAR(100),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    attachments JSON,
    status ENUM('pending', 'acknowledged', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    remarks TEXT,
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_reference (reference_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Certificates Table
CREATE TABLE certificates (
    cert_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    certificate_type ENUM('barangay_clearance', 'residency', 'indigency', 'business_clearance', 'good_moral', 'fishing_permit') NOT NULL,
    purpose TEXT NOT NULL,
    valid_until DATE,
    documents JSON,
    status ENUM('pending', 'processing', 'issued', 'rejected', 'cancelled') DEFAULT 'pending',
    remarks TEXT,
    issued_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    issued_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_type (certificate_type),
    INDEX idx_reference (reference_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Complaint Updates Table (for tracking complaint progress)
CREATE TABLE complaint_updates (
    update_id INT PRIMARY KEY AUTO_INCREMENT,
    complaint_id INT NOT NULL,
    updated_by INT NOT NULL,
    status ENUM('pending', 'acknowledged', 'in_progress', 'resolved', 'closed') NOT NULL,
    remarks TEXT,
    attachments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_complaint (complaint_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Announcements Table
CREATE TABLE announcements (
    announcement_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    category ENUM('system', 'maintenance', 'holiday', 'update', 'general') DEFAULT 'general',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications Table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('permit', 'complaint', 'certificate', 'system', 'general') NOT NULL,
    reference_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Logs Table
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    module ENUM('permit', 'complaint', 'certificate', 'user', 'system') NOT NULL,
    reference_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_module (module),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Admin User (password: admin123)
INSERT INTO users (full_name, email, password, user_type, status, created_at) VALUES 
('ADMIN', 'admin@dagupan.gov.ph', 'ADMINPASSWORD', 'admin', 'active', NOW());

-- Insert Sample Announcements
INSERT INTO announcements (title, content, category, status, created_by, created_at) VALUES 
('New Online Payment Option', 'You can now pay permit fees online through GCASH and bank transfer', 'update', 'active', 1, NOW()),
('System Maintenance Complete', 'All services are now back online with improved performance', 'maintenance', 'active', 1, NOW()),
('Holiday Schedule', 'City offices will be closed on February 25 for People Power Day', 'holiday', 'active', 1, NOW());