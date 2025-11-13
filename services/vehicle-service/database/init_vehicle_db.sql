-- Database Schema for Vehicle Service
-- Create tables for EVM Vehicle Database

USE evm_vehicle_db;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS campaign_vehicles;
DROP TABLE IF EXISTS campaigns;
DROP TABLE IF EXISTS warranty_claims;
DROP TABLE IF EXISTS vehicle_parts;
DROP TABLE IF EXISTS parts_categories;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS ev_models;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS service_centers;

-- Service Centers Table
CREATE TABLE service_centers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    city VARCHAR(100),
    province VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('admin', 'sc_staff', 'evm_staff', 'technician', 'customer') NOT NULL,
    service_center_id INT NULL,
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers Table
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    id_number VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    date_of_birth DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customers_email (email),
    INDEX idx_customers_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EV Models Table
CREATE TABLE ev_models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255),
    manufacturer VARCHAR(100),
    year INT,
    battery_capacity DECIMAL(10,2),
    range_km INT,
    warranty_years INT DEFAULT 2,
    battery_warranty_years INT DEFAULT 8,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vehicles Table
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vin VARCHAR(17) UNIQUE NOT NULL,
    license_plate VARCHAR(20) UNIQUE,
    model_id INT NOT NULL,
    year INT NOT NULL,
    color VARCHAR(50),
    customer_id INT NOT NULL,
    service_center_id INT NOT NULL,
    purchase_date DATE,
    warranty_start_date DATE,
    warranty_end_date DATE,
    battery_warranty_end_date DATE,
    current_mileage INT DEFAULT 0,
    status ENUM('under_warranty', 'warranty_expired', 'recalled', 'scrapped') DEFAULT 'under_warranty',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (model_id) REFERENCES ev_models(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id),
    INDEX idx_vehicles_vin (vin),
    INDEX idx_vehicles_customer (customer_id),
    INDEX idx_vehicles_service_center (service_center_id),
    INDEX idx_vehicles_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parts Categories Table
CREATE TABLE parts_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vehicle Parts Table
CREATE TABLE vehicle_parts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    part_name VARCHAR(255) NOT NULL,
    part_number VARCHAR(100) UNIQUE NOT NULL,
    category_id INT,
    manufacturer VARCHAR(100),
    warranty_months INT DEFAULT 24,
    price DECIMAL(12,2),
    status ENUM('active', 'discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES parts_categories(id),
    INDEX idx_parts_number (part_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Warranty Claims Table
CREATE TABLE warranty_claims (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_id INT NOT NULL,
    vehicle_part_id INT NULL,
    service_center_id INT NOT NULL,
    created_by_user_id INT NOT NULL,
    issue_description TEXT NOT NULL,
    symptoms TEXT,
    failure_date DATE,
    failure_mileage INT,
    status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    estimated_cost DECIMAL(12,2),
    approved_cost DECIMAL(12,2),
    actual_cost DECIMAL(12,2),
    reviewed_by_user_id INT NULL,
    review_date TIMESTAMP NULL,
    review_notes TEXT,
    rejection_reason TEXT,
    completed_date TIMESTAMP NULL,
    completion_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (vehicle_part_id) REFERENCES vehicle_parts(id) ON DELETE SET NULL,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id),
    FOREIGN KEY (created_by_user_id) REFERENCES users(id),
    FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_warranty_claims_vehicle (vehicle_id),
    INDEX idx_warranty_claims_status (status),
    INDEX idx_warranty_claims_service_center (service_center_id),
    INDEX idx_warranty_claims_number (claim_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campaigns (Recalls) Table
CREATE TABLE campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    affected_models TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campaign Vehicles Table
CREATE TABLE campaign_vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_center_id INT NOT NULL,
    status ENUM('identified', 'notified', 'scheduled', 'completed', 'declined') DEFAULT 'identified',
    notification_date TIMESTAMP NULL,
    scheduled_date DATE NULL,
    completion_date TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id),
    INDEX idx_campaign_vehicles_campaign (campaign_id),
    INDEX idx_campaign_vehicles_vehicle (vehicle_id),
    INDEX idx_campaign_vehicles_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Data

-- Service Centers
INSERT INTO service_centers (name, code, address, phone, city, province) VALUES
('Trung tâm bảo hành Hà Nội', 'SC-HN', '123 Đường ABC, Quận Cầu Giấy', '024-1234-5678', 'Hà Nội', 'Hà Nội'),
('Trung tâm bảo hành TP.HCM', 'SC-HCM', '456 Đường XYZ, Quận 1', '028-8765-4321', 'TP.HCM', 'TP.HCM'),
('Trung tâm bảo hành Đà Nẵng', 'SC-DN', '789 Đường GHI, Quận Hải Châu', '0236-3456-789', 'Đà Nẵng', 'Đà Nẵng');

-- Users
INSERT INTO users (username, email, password, full_name, role, service_center_id, phone) VALUES
('admin', 'admin@evm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin', NULL, '0900000000'),
('sc.hn.staff', 'staff.hn@evm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị Staff HN', 'sc_staff', 1, '0901111111'),
('sc.hcm.staff', 'staff.hcm@evm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Staff HCM', 'sc_staff', 2, '0902222222');

-- Customers
INSERT INTO customers (full_name, email, phone, id_number, address, city, province, date_of_birth) VALUES
('Nguyễn Văn An', 'nguyenvanan@example.com', '0901234567', '001090012345', '123 Phố Huế, Hai Bà Trưng', 'Hà Nội', 'Hà Nội', '1990-01-15'),
('Trần Thị Bình', 'tranthibinh@example.com', '0912345678', '001085054321', '456 Lý Thường Kiệt, Q.10', 'TP.HCM', 'TP.HCM', '1985-05-20'),
('Lê Văn Công', 'levancong@example.com', '0923456789', '001088034567', '789 Nguyễn Văn Linh, Thanh Khê', 'Đà Nẵng', 'Đà Nẵng', '1988-03-10');

-- EV Models
INSERT INTO ev_models (name, full_name, manufacturer, year, battery_capacity, range_km, warranty_years, battery_warranty_years) VALUES
('VF8', 'VinFast VF8 Eco', 'VinFast', 2024, 87.70, 471, 2, 8),
('VF9', 'VinFast VF9 Plus', 'VinFast', 2024, 123.00, 594, 2, 8),
('VF5', 'VinFast VF5 Plus', 'VinFast', 2024, 42.00, 326, 2, 8),
('VFe34', 'VinFast VFe34', 'VinFast', 2025, 85.00, 450, 2, 8);

-- Vehicles
INSERT INTO vehicles (vin, license_plate, model_id, year, color, customer_id, service_center_id, purchase_date, warranty_start_date, warranty_end_date, current_mileage) VALUES
('VF3ABCDEF12345678', '29A-12345', 1, 2024, 'Đỏ', 1, 1, '2024-01-15', '2024-01-15', '2026-01-15', 15000),
('VF5XYZ78901234567', '29B-67890', 2, 2023, 'Xanh dương', 1, 1, '2023-06-10', '2023-06-10', '2025-06-10', 32000),
('VF8GHI45678901234', '51C-11111', 1, 2024, 'Trắng', 2, 2, '2024-03-20', '2024-03-20', '2026-03-20', 8000);

-- Parts Categories
INSERT INTO parts_categories (name, description) VALUES
('Battery', 'Pin và các bộ phận liên quan'),
('Motor', 'Động cơ điện'),
('Inverter', 'Bộ nghịch lưu'),
('Charger', 'Bộ sạc'),
('Electronics', 'Thiết bị điện tử'),
('Body Parts', 'Phụ tùng thân xe');

-- Vehicle Parts
INSERT INTO vehicle_parts (part_name, part_number, category_id, manufacturer, warranty_months, price) VALUES
('Battery Pack 87kWh', 'BAT-VF8-87', 1, 'VinFast', 96, 150000000),
('Electric Motor 150kW', 'MOT-VF8-150', 2, 'VinFast', 24, 50000000),
('Inverter 200A', 'INV-VF8-200', 3, 'VinFast', 24, 25000000),
('Onboard Charger 11kW', 'CHG-VF8-11', 4, 'VinFast', 24, 15000000);

-- Warranty Claims
INSERT INTO warranty_claims (claim_number, vehicle_id, service_center_id, created_by_user_id, issue_description, symptoms, failure_date, failure_mileage, status, priority) VALUES
('WC-2024-001', 1, 1, 2, 'Pin sạc không đầy', 'Chỉ sạc được tối đa 80%', '2024-10-01', 14500, 'under_review', 'high'),
('WC-2024-002', 2, 1, 2, 'Động cơ có tiếng kêu bất thường', 'Tiếng kêu khi tăng tốc', '2024-09-15', 31800, 'approved', 'medium');

-- Campaigns
INSERT INTO campaigns (campaign_number, title, description, affected_models, start_date, status) VALUES
('RC-2024-001', 'Cập nhật phần mềm BMS', 'Cập nhật phần mềm quản lý pin phiên bản mới', 'VF8, VF9', '2024-11-01', 'active');

-- Campaign Vehicles
INSERT INTO campaign_vehicles (campaign_id, vehicle_id, service_center_id, status) VALUES
(1, 1, 1, 'identified'),
(1, 2, 1, 'notified');
