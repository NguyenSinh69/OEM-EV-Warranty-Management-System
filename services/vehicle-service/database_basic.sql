-- =============================================
-- OEM EV Warranty Management System Database Schema
-- Basic schema creation only
-- =============================================

SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- Basic Tables
-- =============================================
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role_id INT NOT NULL,
    service_center_id INT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS service_centers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    province VARCHAR(100),
    city VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ev_models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    model_year INT NOT NULL,
    manufacturer VARCHAR(100) DEFAULT 'VinFast',
    warranty_months INT DEFAULT 60,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    address TEXT,
    province VARCHAR(100),
    city VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vin VARCHAR(17) UNIQUE NOT NULL,
    model_id INT NOT NULL,
    year INT NOT NULL,
    color VARCHAR(100),
    license_plate VARCHAR(20),
    customer_id INT NOT NULL,
    service_center_id INT,
    purchase_date DATE NOT NULL,
    warranty_start_date DATE NOT NULL,
    warranty_end_date DATE NOT NULL,
    current_mileage INT DEFAULT 0,
    status ENUM('active', 'under_warranty', 'out_of_warranty') DEFAULT 'under_warranty',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS warranty_claims (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_id INT NOT NULL,
    customer_id INT NOT NULL,
    service_center_id INT NOT NULL,
    created_by_user_id INT NOT NULL,
    issue_description TEXT NOT NULL,
    symptoms TEXT,
    failure_date DATE,
    failure_mileage INT,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('draft', 'submitted', 'under_review', 'approved', 'in_progress', 'completed', 'rejected') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- Insert Basic Data
-- =============================================

-- Insert roles
INSERT IGNORE INTO roles (name, display_name, description) VALUES
('sc_staff', 'Service Center Staff', 'Service center operations'),
('sc_technician', 'Service Center Technician', 'Technical repairs'),
('evm_staff', 'EVM Staff', 'Manufacturer staff'),
('admin', 'Administrator', 'System administration');

-- Insert EV models
INSERT IGNORE INTO ev_models (name, full_name, model_year, manufacturer, warranty_months) VALUES
('VF8', 'VinFast VF8 Eco', 2024, 'VinFast', 60),
('VF9', 'VinFast VF9 Plus', 2024, 'VinFast', 60),
('VFe34', 'VinFast VFe34', 2024, 'VinFast', 60);

-- Insert service centers
INSERT IGNORE INTO service_centers (name, code, address, phone, email, province, city) VALUES
('EVM Service Center Hà Nội', 'SC_HN_01', '123 Láng Hạ, Đống Đa, Hà Nội', '024-3456-7890', 'hanoi@evmservice.vn', 'Hà Nội', 'Hà Nội'),
('EVM Service Center TP.HCM', 'SC_HCM_01', '456 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '028-3456-7890', 'hcm@evmservice.vn', 'TP.HCM', 'TP.HCM');

-- Insert sample customers
INSERT IGNORE INTO customers (full_name, phone, email, address, province, city) VALUES
('Nguyễn Văn An', '0901234567', 'nvana@gmail.com', '123 Trần Hưng Đạo, Hà Nội', 'Hà Nội', 'Hà Nội'),
('Trần Thị Bình', '0912345678', 'ttbinh@gmail.com', '456 Lê Lai, Quận 1, TP.HCM', 'TP.HCM', 'TP.HCM'),
('Lê Văn Cường', '0923456789', 'lvcuong@gmail.com', '789 Hùng Vương, Đà Nẵng', 'Đà Nẵng', 'Đà Nẵng');

-- Insert sample users
INSERT IGNORE INTO users (username, email, password_hash, full_name, phone, role_id, service_center_id) VALUES
('admin', 'admin@evm.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '0901000001', 4, NULL),
('staff_hn', 'staff.hn@evm.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A', '0901000002', 1, 1),
('tech_hn', 'tech.hn@evm.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Văn B', '0901000003', 2, 1);

SET FOREIGN_KEY_CHECKS = 1;