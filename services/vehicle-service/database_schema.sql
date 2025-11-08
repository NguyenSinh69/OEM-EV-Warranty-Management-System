-- =============================================
-- OEM EV Warranty Management System Database Schema
-- Complete database for managing EV warranty system
-- =============================================

-- Database is already created by Docker Compose
-- CREATE DATABASE IF NOT EXISTS evm_vehicle_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE evm_vehicle_db;

-- =============================================
-- User Roles & Authentication
-- =============================================
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL, -- sc_staff, sc_technician, evm_staff, admin
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    permissions JSON, -- Array of permission strings
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role_id INT NOT NULL,
    service_center_id INT NULL, -- For SC Staff/Technicians
    employee_id VARCHAR(50) UNIQUE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_users_username (username),
    INDEX idx_users_email (email),
    INDEX idx_users_role (role_id),
    INDEX idx_users_status (status)
);

-- =============================================
-- Service Centers Table
-- =============================================
CREATE TABLE service_centers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    manager_name VARCHAR(100),
    manager_user_id INT NULL,
    province VARCHAR(100),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    capacity_per_day INT DEFAULT 20,
    working_hours JSON, -- {"open": "08:00", "close": "17:00", "days": ["mon", "tue", "wed", "thu", "fri"]}
    certification_level ENUM('basic', 'advanced', 'premium') DEFAULT 'basic',
    authorized_models JSON, -- Array of model IDs this SC can service
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sc_code (code),
    INDEX idx_sc_status (status),
    INDEX idx_sc_province (province)
);

-- =============================================
-- Customers Table (Reference)
-- =============================================
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) UNIQUE,
    address TEXT,
    id_number VARCHAR(50) UNIQUE,
    date_of_birth DATE,
    province VARCHAR(100),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_customer_phone (phone),
    INDEX idx_customer_email (email),
    INDEX idx_customer_id_number (id_number),
    INDEX idx_customer_status (status)
);

-- =============================================
-- Vehicle Models Table
-- =============================================
CREATE TABLE vehicle_models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL, -- VF8, VF9, VFe34, etc.
    full_name VARCHAR(255), -- VinFast VF8 Eco, VinFast VF9 Plus
    category ENUM('sedan', 'suv', 'crossover', 'hatchback') NOT NULL,
    battery_capacity DECIMAL(5,1), -- 87.7 kWh
    motor_power INT, -- 300 kW
    range_km INT, -- 420 km
    seats INT DEFAULT 5,
    price DECIMAL(15,2),
    warranty_years INT DEFAULT 2,
    battery_warranty_years INT DEFAULT 8,
    status ENUM('active', 'discontinued', 'coming_soon') DEFAULT 'active',
    release_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_model_name (name),
    INDEX idx_model_category (category),
    INDEX idx_model_status (status)
);

-- =============================================
-- Vehicles Table (Main)
-- =============================================
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vin VARCHAR(17) UNIQUE NOT NULL,
    model_id INT NOT NULL,
    year INT NOT NULL,
    color VARCHAR(100),
    customer_id INT NOT NULL,
    service_center_id INT NOT NULL,
    
    -- Purchase & Registration Info
    purchase_date DATE NOT NULL,
    registration_date DATE NOT NULL,
    license_plate VARCHAR(20) UNIQUE,
    
    -- Warranty Info  
    warranty_start_date DATE NOT NULL,
    warranty_end_date DATE NOT NULL,
    battery_warranty_end_date DATE,
    
    -- Vehicle Status
    status ENUM('registered', 'active', 'maintenance', 'recalled', 'deactivated') DEFAULT 'registered',
    mileage INT DEFAULT 0,
    last_service_date DATE,
    next_service_due_date DATE,
    
    -- Technical Specs (can override model defaults)
    battery_capacity DECIMAL(5,1),
    motor_power INT,
    
    -- Tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (model_id) REFERENCES vehicle_models(id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id) ON DELETE RESTRICT,
    
    UNIQUE KEY uk_vehicle_vin (vin),
    UNIQUE KEY uk_license_plate (license_plate),
    INDEX idx_vehicle_customer (customer_id),
    INDEX idx_vehicle_service_center (service_center_id),
    INDEX idx_vehicle_status (status),
    INDEX idx_vehicle_model (model_id),
    INDEX idx_vehicle_registration_date (registration_date),
    INDEX idx_vehicle_warranty_end (warranty_end_date)
);

-- =============================================
-- Vehicle History Table
-- =============================================
CREATE TABLE vehicle_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    action ENUM('registration', 'service', 'repair', 'recall', 'transfer', 'deactivation') NOT NULL,
    description TEXT,
    performed_by VARCHAR(255), -- staff name or system
    service_center_id INT,
    mileage_at_time INT,
    cost DECIMAL(12,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id) ON DELETE SET NULL,
    
    INDEX idx_history_vehicle (vehicle_id),
    INDEX idx_history_action (action),
    INDEX idx_history_date (created_at)
);

-- =============================================
-- Vehicle Inspections Table
-- =============================================
CREATE TABLE vehicle_inspections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    inspection_type ENUM('registration', 'periodic', 'warranty', 'recall') NOT NULL,
    inspector_name VARCHAR(255) NOT NULL,
    service_center_id INT NOT NULL,
    inspection_date DATE NOT NULL,
    mileage INT,
    
    -- Inspection Results
    battery_health_percent DECIMAL(5,2),
    motor_condition ENUM('excellent', 'good', 'fair', 'poor'),
    electrical_system ENUM('pass', 'fail', 'needs_attention'),
    overall_status ENUM('pass', 'conditional_pass', 'fail') NOT NULL,
    
    -- Issues found
    issues_found JSON, -- [{"component": "battery", "severity": "minor", "description": "..."}]
    recommendations TEXT,
    next_inspection_due DATE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id) ON DELETE RESTRICT,
    
    INDEX idx_inspection_vehicle (vehicle_id),
    INDEX idx_inspection_date (inspection_date),
    INDEX idx_inspection_type (inspection_type),
    INDEX idx_inspection_status (overall_status)
);

-- =============================================
-- Insert Sample Data
-- =============================================

-- Roles
INSERT INTO roles (name, display_name, description, permissions) VALUES
('admin', 'System Administrator', 'Full system access and management', '["system.manage", "users.manage", "reports.view", "analytics.view", "campaigns.manage"]'),
('evm_staff', 'EVM Staff', 'Manufacturer staff managing warranty and parts', '["claims.approve", "parts.manage", "campaigns.create", "inventory.manage", "reports.view"]'),
('sc_staff', 'Service Center Staff', 'Service center staff handling registrations and claims', '["vehicles.register", "claims.create", "customers.manage", "appointments.manage"]'),
('sc_technician', 'Service Center Technician', 'Technicians performing warranty work', '["claims.execute", "parts.install", "diagnostics.perform", "repairs.complete"]');

-- Users (Sample Data)
INSERT INTO users (username, email, password_hash, full_name, phone, role_id, employee_id, service_center_id) VALUES
('admin', 'admin@evmgroup.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '024-1234-5678', 1, 'EVM-ADM-001', NULL),
('evm.manager', 'manager@evmgroup.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Manager', '024-1234-5679', 2, 'EVM-MNG-001', NULL),
('sc.hn.staff', 'staff.hn@evmservice.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị Staff HN', '024-2345-6789', 3, 'SC-HN-STF-001', 1),
('sc.hn.tech', 'tech.hn@evmservice.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Văn Tech HN', '024-2345-6790', 4, 'SC-HN-TEC-001', 1),
('sc.hcm.staff', 'staff.hcm@evmservice.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Phạm Thị Staff HCM', '028-2345-6789', 3, 'SC-HCM-STF-001', 2),
('sc.hcm.tech', 'tech.hcm@evmservice.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hoàng Văn Tech HCM', '028-2345-6790', 4, 'SC-HCM-TEC-001', 2);

-- Service Centers  
INSERT INTO service_centers (name, code, address, phone, email, manager_name, manager_user_id, province, city, authorized_models) VALUES
('EVM Service Center Hà Nội', 'SC_HN_01', '123 Láng Hạ, Đống Đa, Hà Nội', '024-3456-7890', 'hanoi@evmservice.vn', 'Nguyễn Văn Quản', 3, 'Hà Nội', 'Hà Nội', '[1,2,3]'),
('EVM Service Center TP.HCM', 'SC_HCM_01', '456 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '028-3456-7890', 'hcm@evmservice.vn', 'Trần Thị Lan', 5, 'TP.HCM', 'TP.HCM', '[1,2,3]'),
('EVM Service Center Đà Nẵng', 'SC_DN_01', '789 Lê Duẩn, Hải Châu, Đà Nẵng', '0236-356-7890', 'danang@evmservice.vn', 'Lê Văn Hải', NULL, 'Đà Nẵng', 'Đà Nẵng', '[1,2]');

-- Parts Categories
INSERT INTO parts_categories (name, code, description, warranty_months, critical_part) VALUES
('Battery Pack', 'BATT', 'EV Battery systems and components', 96, TRUE),
('Electric Motor', 'MOTOR', 'Drive motors and related components', 60, TRUE),
('Power Electronics', 'POWER', 'Inverters, converters, and power management', 36, TRUE),
('Charging System', 'CHARGE', 'Onboard chargers and charging components', 24, FALSE),
('Body & Chassis', 'BODY', 'Body panels, chassis, and structural components', 36, FALSE),
('Interior', 'INT', 'Interior components and accessories', 12, FALSE),
('Software', 'SW', 'Firmware and software components', 12, FALSE);

-- Parts
INSERT INTO parts (part_number, name, category_id, manufacturer, description, compatible_models, unit_cost, warranty_months) VALUES
('BATT-VF8-87K', 'VF8 Battery Pack 87.7kWh', 1, 'CATL', 'Lithium Iron Phosphate battery pack for VF8', '[1]', 250000000, 96),
('BATT-VF9-123K', 'VF9 Battery Pack 123kWh', 1, 'CATL', 'Lithium Iron Phosphate battery pack for VF9', '[2]', 350000000, 96),
('MOT-FR-300K', 'Front Motor 300kW', 2, 'Bosch', 'Front axle electric motor 300kW', '[1,2]', 150000000, 60),
('MOT-RR-200K', 'Rear Motor 200kW', 2, 'Bosch', 'Rear axle electric motor 200kW', '[1,2]', 120000000, 60),
('INV-800V-400A', '800V Inverter 400A', 3, 'Continental', 'High voltage inverter for power conversion', '[1,2,3]', 80000000, 36),
('CHG-11KW-AC', 'AC Charger 11kW', 4, 'Delta Electronics', 'Onboard AC charger 11kW', '[1,2,3]', 15000000, 24),
('BMS-CTRL-V3', 'Battery Management System V3', 3, 'VinFast', 'Advanced BMS with thermal management', '[1,2,3]', 25000000, 36);

-- Add foreign key constraint after users table is populated
ALTER TABLE service_centers ADD FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Customers
INSERT INTO customers (name, phone, email, address, id_number, date_of_birth, province, city) VALUES
('Nguyễn Văn A', '0901234567', 'nguyenvana@example.com', '123 Phố Huế, Hai Bà Trưng, Hà Nội', '123456789', '1990-01-01', 'Hà Nội', 'Hà Nội'),
('Trần Thị B', '0912345678', 'tranthib@example.com', '456 Lê Lợi, Quận 1, TP.HCM', '987654321', '1985-05-15', 'TP.HCM', 'TP.HCM'),
('Lê Văn C', '0923456789', 'levanc@example.com', '789 Trần Phú, Hải Châu, Đà Nẵng', '456789123', '1988-03-20', 'Đà Nẵng', 'Đà Nẵng'),
('Phạm Thị D', '0934567890', 'phamthid@example.com', '321 Trần Hưng Đạo, Ninh Kiều, Cần Thơ', '321654987', '1992-07-10', 'Cần Thơ', 'Cần Thơ');

-- Vehicle Models
INSERT INTO vehicle_models (name, full_name, category, battery_capacity, motor_power, range_km, seats, price, warranty_years, battery_warranty_years, release_date) VALUES
('VF8', 'VinFast VF8 Eco', 'suv', 87.7, 300, 420, 5, 1200000000, 2, 8, '2023-01-01'),
('VF9', 'VinFast VF9 Plus', 'suv', 123.0, 300, 480, 7, 1500000000, 2, 8, '2023-06-01'),
('VFe34', 'VinFast VFe34', 'sedan', 75.3, 250, 380, 5, 900000000, 2, 8, '2024-01-01');

-- Vehicles
INSERT INTO vehicles (vin, model_id, year, color, customer_id, service_center_id, purchase_date, registration_date, license_plate, warranty_start_date, warranty_end_date, battery_warranty_end_date, status, mileage, last_service_date) VALUES
('VF3ABCDEF12345678', 1, 2024, 'Đen Kim Cương', 1, 1, '2024-01-15', '2024-01-15', '30A-12345', '2024-01-15', '2026-01-15', '2032-01-15', 'active', 5000, '2024-10-15'),
('VF3GHIJKL87654321', 2, 2024, 'Trắng Ngọc Trai', 2, 2, '2024-02-20', '2024-02-20', '51H-67890', '2024-02-20', '2026-02-20', '2032-02-20', 'active', 3000, '2024-09-20'),
('VF3MNOPQR11111111', 1, 2024, 'Xanh Đại Dương', 3, 3, '2024-11-01', '2024-11-05', '43B-98765', '2024-11-01', '2026-11-01', '2032-11-01', 'active', 500, NULL),
('VF3STUVWX22222222', 2, 2024, 'Đỏ Quyến Rũ', 4, 2, '2024-11-05', '2024-11-05', NULL, '2024-11-05', '2026-11-05', '2032-11-05', 'registered', 0, NULL);

-- Vehicle Parts (Installed parts with serial numbers)
INSERT INTO vehicle_parts (vehicle_id, part_id, serial_number, installation_date, installed_by_user_id, installation_mileage, warranty_start_date, warranty_end_date, location_on_vehicle) VALUES
-- VF8 Vehicle 1 parts
(1, 1, 'BATT-87K-240115-001', '2024-01-15', 4, 0, '2024-01-15', '2032-01-15', 'battery_pack'),
(1, 3, 'MOT-FR-240115-001', '2024-01-15', 4, 0, '2024-01-15', '2029-01-15', 'front_motor'),
(1, 4, 'MOT-RR-240115-001', '2024-01-15', 4, 0, '2024-01-15', '2029-01-15', 'rear_motor'),
(1, 5, 'INV-240115-001', '2024-01-15', 4, 0, '2024-01-15', '2027-01-15', 'inverter'),
(1, 6, 'CHG-240115-001', '2024-01-15', 4, 0, '2024-01-15', '2026-01-15', 'onboard_charger'),

-- VF9 Vehicle 2 parts
(2, 2, 'BATT-123K-240220-001', '2024-02-20', 6, 0, '2024-02-20', '2032-02-20', 'battery_pack'),
(2, 3, 'MOT-FR-240220-001', '2024-02-20', 6, 0, '2024-02-20', '2029-02-20', 'front_motor'),
(2, 4, 'MOT-RR-240220-001', '2024-02-20', 6, 0, '2024-02-20', '2029-02-20', 'rear_motor'),
(2, 5, 'INV-240220-001', '2024-02-20', 6, 0, '2024-02-20', '2027-02-20', 'inverter'),
(2, 6, 'CHG-240220-001', '2024-02-20', 6, 0, '2024-02-20', '2026-02-20', 'onboard_charger');

-- Parts Inventory (Sample stock levels)
INSERT INTO parts_inventory (part_id, service_center_id, quantity_available, quantity_reserved, minimum_stock_level, cost_per_unit) VALUES
-- HN Service Center
(1, 1, 2, 0, 1, 250000000), -- VF8 Battery
(3, 1, 5, 1, 2, 150000000), -- Front Motor
(4, 1, 5, 0, 2, 120000000), -- Rear Motor  
(5, 1, 8, 0, 3, 80000000),  -- Inverter
(6, 1, 10, 0, 5, 15000000), -- AC Charger

-- HCM Service Center
(1, 2, 1, 0, 1, 250000000), -- VF8 Battery
(2, 2, 1, 0, 1, 350000000), -- VF9 Battery
(3, 2, 3, 0, 2, 150000000), -- Front Motor
(4, 2, 4, 0, 2, 120000000), -- Rear Motor
(5, 2, 6, 0, 3, 80000000),  -- Inverter
(6, 2, 8, 0, 5, 15000000),  -- AC Charger

-- EVM Central Warehouse (service_center_id = NULL)
(1, NULL, 50, 5, 10, 240000000), -- VF8 Battery
(2, NULL, 30, 3, 8, 340000000),  -- VF9 Battery
(3, NULL, 100, 8, 20, 145000000), -- Front Motor
(4, NULL, 100, 5, 20, 115000000), -- Rear Motor
(5, NULL, 200, 10, 50, 78000000), -- Inverter
(6, NULL, 300, 15, 100, 14500000), -- AC Charger
(7, NULL, 150, 8, 30, 24000000);  -- BMS

-- Sample Warranty Claims
INSERT INTO warranty_claims (claim_number, vehicle_id, vehicle_part_id, service_center_id, created_by_user_id, issue_description, failure_date, failure_mileage, status, priority, estimated_cost) VALUES
('WC-2024-001', 1, 5, 1, 3, 'Inverter making unusual noise during acceleration', '2024-10-01', 4500, 'submitted', 'medium', 5000000),
('WC-2024-002', 2, 6, 2, 5, 'Onboard charger not working - no AC charging capability', '2024-10-15', 2800, 'approved', 'high', 8000000),
('WC-2024-003', 1, NULL, 1, 3, 'Battery showing reduced range - capacity check needed', '2024-11-01', 4800, 'under_review', 'medium', 15000000);

-- Sample Campaign (Recall)
INSERT INTO campaigns (campaign_number, title, campaign_type, description, affected_models, severity, start_date, status, created_by_user_id, estimated_repair_time_hours, parts_required) VALUES
('RC-2024-001', 'VF8/VF9 Inverter Software Update', 'recall', 'Software update to fix inverter performance issues in cold weather', '[1,2]', 'medium', '2024-11-01', 'active', 2, 1.5, '[5]');

-- Campaign Vehicles (Vehicles affected by recall)
INSERT INTO campaign_vehicles (campaign_id, vehicle_id, service_center_id, status, customer_notified, notification_date) VALUES
(1, 1, 1, 'notified', TRUE, '2024-11-02'),
(1, 2, 2, 'notified', TRUE, '2024-11-02');

-- Vehicle History
INSERT INTO vehicle_history (vehicle_id, action, description, performed_by, service_center_id, mileage_at_time) VALUES
(1, 'registration', 'Vehicle registered successfully', 'SC Staff - Hà Nội', 1, 0),
(1, 'service', 'Regular maintenance - 5000km service', 'Tech Nguyễn Văn X', 1, 5000),
(2, 'registration', 'Vehicle registered successfully', 'SC Staff - TP.HCM', 2, 0),
(2, 'service', 'Regular maintenance - 3000km service', 'Tech Trần Thị Y', 2, 3000),
(3, 'registration', 'Vehicle registered successfully', 'SC Staff - Đà Nẵng', 3, 0),
(4, 'registration', 'Vehicle registered - pending license plate', 'SC Staff - TP.HCM', 2, 0);

-- =============================================
-- EV Parts & Components
-- =============================================
CREATE TABLE parts_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    warranty_months INT DEFAULT 24,
    critical_part BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE parts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    part_number VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    manufacturer VARCHAR(100),
    description TEXT,
    compatible_models JSON, -- Array of model IDs
    unit_cost DECIMAL(12,2),
    warranty_months INT DEFAULT 24,
    weight_kg DECIMAL(8,3),
    dimensions JSON, -- {"length": 10, "width": 5, "height": 3}
    specifications JSON, -- Technical specs for the part
    status ENUM('active', 'discontinued', 'development') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES parts_categories(id) ON DELETE RESTRICT,
    INDEX idx_parts_number (part_number),
    INDEX idx_parts_category (category_id),
    INDEX idx_parts_status (status)
);

CREATE TABLE vehicle_parts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    part_id INT NOT NULL,
    serial_number VARCHAR(100) UNIQUE NOT NULL,
    installation_date DATE NOT NULL,
    installed_by_user_id INT NOT NULL,
    installation_mileage INT DEFAULT 0,
    warranty_start_date DATE NOT NULL,
    warranty_end_date DATE NOT NULL,
    status ENUM('installed', 'replaced', 'defective', 'recalled') DEFAULT 'installed',
    location_on_vehicle VARCHAR(100), -- "front_left_motor", "battery_pack_1", etc.
    replacement_reason TEXT NULL,
    replaced_by_part_id INT NULL, -- Reference to new part if replaced
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE RESTRICT,
    FOREIGN KEY (installed_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (replaced_by_part_id) REFERENCES vehicle_parts(id) ON DELETE SET NULL,
    
    INDEX idx_vehicle_parts_vehicle (vehicle_id),
    INDEX idx_vehicle_parts_serial (serial_number),
    INDEX idx_vehicle_parts_part (part_id),
    INDEX idx_vehicle_parts_warranty (warranty_end_date)
);

-- =============================================
-- Warranty Claims System
-- =============================================
CREATE TABLE warranty_claims (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_id INT NOT NULL,
    vehicle_part_id INT NULL, -- Specific part if applicable
    service_center_id INT NOT NULL,
    created_by_user_id INT NOT NULL, -- SC Staff who created
    assigned_technician_id INT NULL, -- SC Technician assigned
    
    -- Claim Details
    issue_description TEXT NOT NULL,
    symptoms TEXT,
    diagnosis_notes TEXT,
    failure_date DATE,
    failure_mileage INT,
    
    -- Status & Workflow
    status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected', 'parts_ordered', 'in_progress', 'completed', 'closed') DEFAULT 'draft',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    
    -- Financial
    estimated_cost DECIMAL(12,2),
    approved_cost DECIMAL(12,2),
    actual_cost DECIMAL(12,2),
    labor_hours DECIMAL(5,2),
    
    -- EVM Review (done by EVM Staff)
    reviewed_by_user_id INT NULL,
    review_date TIMESTAMP NULL,
    review_notes TEXT,
    rejection_reason TEXT,
    
    -- Completion
    completed_date TIMESTAMP NULL,
    completion_notes TEXT,
    customer_satisfaction_rating INT CHECK (customer_satisfaction_rating >= 1 AND customer_satisfaction_rating <= 5),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    FOREIGN KEY (vehicle_part_id) REFERENCES vehicle_parts(id) ON DELETE SET NULL,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_technician_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_warranty_claims_vehicle (vehicle_id),
    INDEX idx_warranty_claims_status (status),
    INDEX idx_warranty_claims_service_center (service_center_id),
    INDEX idx_warranty_claims_created_by (created_by_user_id),
    INDEX idx_warranty_claims_technician (assigned_technician_id),
    INDEX idx_warranty_claims_number (claim_number)
);

CREATE TABLE warranty_claim_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    warranty_claim_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'document', 'video', 'diagnostic_report') NOT NULL,
    file_size_mb DECIMAL(8,2),
    description TEXT,
    uploaded_by_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (warranty_claim_id) REFERENCES warranty_claims(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_attachments_claim (warranty_claim_id)
);

-- =============================================
-- Parts Inventory & Supply Chain
-- =============================================
CREATE TABLE parts_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    part_id INT NOT NULL,
    service_center_id INT NULL, -- NULL means EVM central warehouse
    quantity_available INT NOT NULL DEFAULT 0,
    quantity_reserved INT NOT NULL DEFAULT 0,
    minimum_stock_level INT DEFAULT 5,
    maximum_stock_level INT DEFAULT 100,
    last_restocked_date DATE,
    cost_per_unit DECIMAL(12,2),
    location_in_warehouse VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id) ON DELETE CASCADE,
    UNIQUE KEY uk_inventory_location (part_id, service_center_id),
    INDEX idx_inventory_part (part_id),
    INDEX idx_inventory_service_center (service_center_id),
    INDEX idx_inventory_stock_level (quantity_available)
);

CREATE TABLE parts_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    service_center_id INT NOT NULL,
    ordered_by_user_id INT NOT NULL,
    approved_by_user_id INT NULL,
    supplier VARCHAR(255),
    
    -- Order Status
    status ENUM('draft', 'submitted', 'approved', 'ordered', 'shipped', 'delivered', 'cancelled') DEFAULT 'draft',
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    
    -- Financial
    total_cost DECIMAL(12,2),
    shipping_cost DECIMAL(12,2),
    
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id) ON DELETE RESTRICT,
    FOREIGN KEY (ordered_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_parts_orders_service_center (service_center_id),
    INDEX idx_parts_orders_status (status)
);

CREATE TABLE parts_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parts_order_id INT NOT NULL,
    part_id INT NOT NULL,
    quantity_ordered INT NOT NULL,
    quantity_received INT DEFAULT 0,
    unit_cost DECIMAL(12,2),
    total_cost DECIMAL(12,2),
    warranty_claim_id INT NULL, -- If ordered for specific warranty claim
    
    FOREIGN KEY (parts_order_id) REFERENCES parts_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE RESTRICT,
    FOREIGN KEY (warranty_claim_id) REFERENCES warranty_claims(id) ON DELETE SET NULL,
    INDEX idx_order_items_order (parts_order_id),
    INDEX idx_order_items_part (part_id)
);

-- =============================================
-- Recall & Service Campaigns
-- =============================================
CREATE TABLE campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    campaign_type ENUM('recall', 'service_campaign', 'software_update') NOT NULL,
    description TEXT NOT NULL,
    
    -- Affected Vehicles
    affected_models JSON, -- Array of model IDs
    affected_vin_ranges JSON, -- [{"start": "VF3ABC...", "end": "VF3XYZ..."}]
    affected_date_ranges JSON, -- Manufacturing date ranges
    
    -- Campaign Details  
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    estimated_repair_time_hours DECIMAL(5,2),
    parts_required JSON, -- Array of part IDs needed
    repair_instructions TEXT,
    
    -- Status & Timeline
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    start_date DATE NOT NULL,
    end_date DATE,
    completion_deadline DATE,
    
    -- Management
    created_by_user_id INT NOT NULL,
    approved_by_user_id INT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_campaigns_type (campaign_type),
    INDEX idx_campaigns_status (status),
    INDEX idx_campaigns_number (campaign_number)
);

CREATE TABLE campaign_vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_center_id INT NOT NULL, -- Where it should be serviced
    
    -- Notification & Scheduling
    customer_notified BOOLEAN DEFAULT FALSE,
    notification_date DATE,
    notification_method ENUM('phone', 'email', 'sms', 'mail') DEFAULT 'email',
    
    appointment_date DATE NULL,
    appointment_time TIME NULL,
    
    -- Execution
    status ENUM('identified', 'notified', 'scheduled', 'in_progress', 'completed', 'declined') DEFAULT 'identified',
    work_started_date DATE NULL,
    work_completed_date DATE NULL,
    assigned_technician_id INT NULL,
    
    -- Results
    work_notes TEXT,
    parts_used JSON, -- Array of {part_id, serial_number, quantity}
    labor_hours DECIMAL(5,2),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_technician_id) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY uk_campaign_vehicle (campaign_id, vehicle_id),
    INDEX idx_campaign_vehicles_campaign (campaign_id),
    INDEX idx_campaign_vehicles_vehicle (vehicle_id),
    INDEX idx_campaign_vehicles_status (status)
);

-- Vehicle Inspections
INSERT INTO vehicle_inspections (vehicle_id, inspection_type, inspector_name, service_center_id, inspection_date, mileage, battery_health_percent, motor_condition, electrical_system, overall_status, next_inspection_due) VALUES
(1, 'registration', 'Inspector Nguyễn A', 1, '2024-01-15', 0, 100.00, 'excellent', 'pass', 'pass', '2025-01-15'),
(1, 'periodic', 'Inspector Nguyễn A', 1, '2024-10-15', 5000, 98.50, 'excellent', 'pass', 'pass', '2025-04-15'),
(2, 'registration', 'Inspector Trần B', 2, '2024-02-20', 0, 100.00, 'excellent', 'pass', 'pass', '2025-02-20'),
(2, 'periodic', 'Inspector Trần B', 2, '2024-09-20', 3000, 99.20, 'excellent', 'pass', 'pass', '2025-03-20'),
(3, 'registration', 'Inspector Lê C', 3, '2024-11-05', 500, 100.00, 'excellent', 'pass', 'pass', '2025-11-05');

-- =============================================
-- Useful Views
-- =============================================

-- Vehicle Details View
CREATE VIEW vehicle_details AS
SELECT 
    v.id,
    v.vin,
    vm.name as model_name,
    vm.full_name as model_full_name,
    v.year,
    v.color,
    v.license_plate,
    v.status,
    v.mileage,
    v.purchase_date,
    v.registration_date,
    v.warranty_end_date,
    v.battery_warranty_end_date,
    DATEDIFF(v.warranty_end_date, CURDATE()) as warranty_days_remaining,
    c.name as customer_name,
    c.phone as customer_phone,
    c.email as customer_email,
    sc.name as service_center_name,
    sc.code as service_center_code,
    v.created_at
FROM vehicles v
JOIN vehicle_models vm ON v.model_id = vm.id
JOIN customers c ON v.customer_id = c.id
JOIN service_centers sc ON v.service_center_id = sc.id;

-- Vehicle Statistics View  
CREATE VIEW vehicle_stats AS
SELECT
    COUNT(*) as total_vehicles,
    COUNT(CASE WHEN DATE(registration_date) = CURDATE() THEN 1 END) as today_registrations,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_vehicles,
    COUNT(CASE WHEN status = 'registered' THEN 1 END) as pending_vehicles,
    COUNT(CASE WHEN warranty_end_date > CURDATE() THEN 1 END) as active_warranties,
    COUNT(CASE WHEN vm.name LIKE '%VF8%' THEN 1 END) as vf8_count,
    COUNT(CASE WHEN vm.name LIKE '%VF9%' THEN 1 END) as vf9_count
FROM vehicles v
JOIN vehicle_models vm ON v.model_id = vm.id;

-- =============================================
-- Indexes for Performance
-- =============================================

-- Additional performance indexes
CREATE INDEX idx_vehicles_composite ON vehicles (status, service_center_id, registration_date);
CREATE INDEX idx_history_composite ON vehicle_history (vehicle_id, action, created_at);
CREATE INDEX idx_inspections_composite ON vehicle_inspections (service_center_id, inspection_date, overall_status);