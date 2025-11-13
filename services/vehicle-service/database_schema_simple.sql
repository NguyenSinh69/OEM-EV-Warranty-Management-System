-- =============================================
-- OEM EV Warranty Management System Database Schema
-- Simple schema creation without foreign key constraints
-- =============================================

-- Set foreign key checks to 0 for initial setup
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- User Roles & Authentication
-- =============================================
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    permissions JSON,
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
    employee_id VARCHAR(50) UNIQUE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- Service Centers
-- =============================================
CREATE TABLE IF NOT EXISTS service_centers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    manager_name VARCHAR(255),
    manager_user_id INT NULL,
    province VARCHAR(100),
    city VARCHAR(100),
    authorized_models JSON,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- EV Models & Configurations
-- =============================================
CREATE TABLE IF NOT EXISTS ev_models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    model_year INT NOT NULL,
    manufacturer VARCHAR(100) DEFAULT 'VinFast',
    category ENUM('SUV', 'Sedan', 'Hatchback', 'Truck') DEFAULT 'SUV',
    battery_capacity_kwh DECIMAL(5,2),
    range_km INT,
    motor_power_kw DECIMAL(6,2),
    warranty_months INT DEFAULT 60,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Customers
-- =============================================
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    address TEXT,
    id_number VARCHAR(20),
    date_of_birth DATE,
    province VARCHAR(100),
    city VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- Parts Management
-- =============================================
CREATE TABLE IF NOT EXISTS parts_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    warranty_months INT DEFAULT 24,
    critical_part BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vehicle_parts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    part_name VARCHAR(255) NOT NULL,
    part_number VARCHAR(100) UNIQUE NOT NULL,
    category_id INT,
    model_compatibility JSON,
    serial_number VARCHAR(100),
    batch_number VARCHAR(100),
    manufacturer VARCHAR(100),
    manufacture_date DATE,
    warranty_months INT DEFAULT 24,
    cost_usd DECIMAL(10,2),
    supplier_info JSON,
    specifications JSON,
    installation_guide TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Vehicles
-- =============================================
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
    battery_health_percentage DECIMAL(5,2) DEFAULT 100.00,
    last_service_date DATE,
    status ENUM('active', 'under_warranty', 'out_of_warranty', 'maintenance', 'recalled') DEFAULT 'under_warranty',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- Vehicle Parts Installation
-- =============================================
CREATE TABLE IF NOT EXISTS vehicle_parts_installation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    part_id INT NOT NULL,
    installation_date DATE NOT NULL,
    installed_by_technician_id INT,
    service_center_id INT,
    installation_mileage INT,
    warranty_start_date DATE NOT NULL,
    warranty_end_date DATE NOT NULL,
    installation_notes TEXT,
    status ENUM('installed', 'replaced', 'removed', 'defective') DEFAULT 'installed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Warranty Claims
-- =============================================
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
    diagnosis TEXT,
    root_cause TEXT,
    repair_description TEXT,
    parts_used JSON,
    labor_hours DECIMAL(4,2),
    total_cost_usd DECIMAL(10,2),
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('draft', 'submitted', 'under_review', 'approved', 'in_progress', 'completed', 'rejected', 'closed') DEFAULT 'draft',
    submitted_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    approved_by_user_id INT NULL,
    completed_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- Service History
-- =============================================
CREATE TABLE IF NOT EXISTS service_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    service_center_id INT NOT NULL,
    technician_id INT,
    service_type ENUM('warranty_repair', 'maintenance', 'recall', 'inspection', 'upgrade') NOT NULL,
    description TEXT NOT NULL,
    parts_replaced JSON,
    labor_hours DECIMAL(4,2),
    service_cost_usd DECIMAL(10,2),
    mileage_at_service INT,
    service_date DATE NOT NULL,
    next_service_date DATE,
    warranty_claim_id INT NULL,
    recall_campaign_id INT NULL,
    notes TEXT,
    customer_satisfaction_rating INT, -- 1-5
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- Recall Campaigns
-- =============================================
CREATE TABLE IF NOT EXISTS recall_campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    campaign_type ENUM('recall', 'service_bulletin', 'software_update') DEFAULT 'recall',
    affected_models JSON NOT NULL,
    affected_vin_ranges JSON,
    affected_part_numbers JSON,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    fix_description TEXT,
    estimated_repair_time_hours DECIMAL(4,2),
    parts_needed JSON,
    instructions TEXT,
    status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    start_date DATE,
    target_completion_date DATE,
    created_by_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- Recall Campaign Execution
-- =============================================
CREATE TABLE IF NOT EXISTS recall_campaign_vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recall_campaign_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    notification_sent_date DATE,
    customer_contacted_date DATE,
    appointment_scheduled_date DATE,
    repair_completed_date DATE,
    service_center_id INT,
    technician_id INT,
    status ENUM('identified', 'notified', 'scheduled', 'in_progress', 'completed', 'customer_declined') DEFAULT 'identified',
    completion_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- Basic Sample Data
-- =============================================

-- Insert roles
INSERT IGNORE INTO roles (name, display_name, description, permissions) VALUES
('sc_staff', 'Service Center Staff', 'Service center reception and customer service', '["vehicle_registration", "warranty_claim_creation", "customer_management"]'),
('sc_technician', 'Service Center Technician', 'Technical repair and maintenance staff', '["warranty_execution", "parts_installation", "diagnosis", "repair_reports"]'),
('evm_staff', 'EVM Staff', 'Electric Vehicle Manufacturer staff', '["warranty_approval", "parts_supply", "recall_management", "cost_analysis"]'),
('admin', 'System Administrator', 'Full system access and management', '["user_management", "system_config", "analytics", "ai_analysis"]');

-- Sample data will be inserted later via API calls

-- Insert service centers
INSERT IGNORE INTO service_centers (name, code, address, phone, email, manager_name, province, city, authorized_models, status) VALUES
('EVM Service Center Hà Nội', 'SC_HN_01', '123 Láng Hạ, Đống Đa, Hà Nội', '024-3456-7890', 'hanoi@evmservice.vn', 'Nguyễn Văn Quản', 'Hà Nội', 'Hà Nội', '[1,2,3]', 'active'),
('EVM Service Center TP.HCM', 'SC_HCM_01', '456 Nguyễn Thị Minh Khai, Quận 1, TP.HCM', '028-3456-7890', 'hcm@evmservice.vn', 'Trần Thị Lan', 'TP.HCM', 'TP.HCM', '[1,2,3]', 'active'),
('EVM Service Center Đà Nẵng', 'SC_DN_01', '789 Lê Duẩn, Hải Châu, Đà Nẵng', '0236-356-7890', 'danang@evmservice.vn', 'Lê Văn Hải', 'Đà Nẵng', 'Đà Nẵng', '[1,2]', 'active');

-- Insert EV models
INSERT IGNORE INTO ev_models (name, full_name, model_year, manufacturer, category, battery_capacity_kwh, range_km, motor_power_kw, warranty_months) VALUES
('VF8', 'VinFast VF8 Eco', 2024, 'VinFast', 'SUV', 87.7, 420, 260, 60),
('VF9', 'VinFast VF9 Plus', 2024, 'VinFast', 'SUV', 123.0, 438, 300, 60),
('VFe34', 'VinFast VFe34', 2024, 'VinFast', 'Sedan', 75.3, 380, 150, 60);

-- Insert parts categories
INSERT IGNORE INTO parts_categories (name, code, description, warranty_months, critical_part) VALUES
('Battery Pack', 'BATT', 'EV Battery systems and components', 96, TRUE),
('Electric Motor', 'MOTOR', 'Drive motors and related components', 60, TRUE),
('Power Electronics', 'POWER', 'Inverters, converters, and power management', 36, TRUE),
('Charging System', 'CHARGE', 'On-board chargers and charging components', 24, FALSE),
('Thermal Management', 'THERMAL', 'Battery and motor cooling systems', 24, FALSE);

-- Insert sample customers (using available columns)
INSERT IGNORE INTO customers (full_name, phone, email, address, province, city) VALUES
('Nguyễn Văn An', '0901234567', 'nvana@gmail.com', '123 Trần Hưng Đạo, Hà Nội', 'Hà Nội', 'Hà Nội'),
('Trần Thị Bình', '0912345678', 'ttbinh@gmail.com', '456 Lê Lai, Quận 1, TP.HCM', 'TP.HCM', 'TP.HCM'),
('Lê Văn Cường', '0923456789', 'lvcuong@gmail.com', '789 Hùng Vương, Đà Nẵng', 'Đà Nẵng', 'Đà Nẵng');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;