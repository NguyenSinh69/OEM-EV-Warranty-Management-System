-- Create database and tables for OEM EV Warranty Management System

CREATE DATABASE IF NOT EXISTS warranty_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE warranty_db;

-- Users table for authentication
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'manager', 'technician', 'customer_service', 'customer') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Customers table
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    customer_code VARCHAR(50) NOT NULL UNIQUE,
    company_name VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Vietnam',
    tax_id VARCHAR(50),
    contact_person VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer_code (customer_code),
    INDEX idx_company_name (company_name)
);

-- Vehicles table
CREATE TABLE vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    vin VARCHAR(17) NOT NULL UNIQUE,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year YEAR NOT NULL,
    color VARCHAR(50),
    battery_capacity DECIMAL(8,2),
    motor_power DECIMAL(8,2),
    manufacturing_date DATE,
    delivery_date DATE,
    mileage BIGINT UNSIGNED DEFAULT 0,
    status ENUM('active', 'inactive', 'recalled', 'totaled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_vin (vin),
    INDEX idx_customer_id (customer_id),
    INDEX idx_make_model (make, model),
    INDEX idx_year (year)
);

-- Warranty policies table
CREATE TABLE warranty_policies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(255) NOT NULL,
    policy_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    coverage_type ENUM('vehicle', 'battery', 'motor', 'electronics', 'comprehensive') NOT NULL,
    duration_months INT NOT NULL,
    mileage_limit BIGINT UNSIGNED,
    terms_conditions TEXT,
    exclusions TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_policy_code (policy_code),
    INDEX idx_coverage_type (coverage_type),
    INDEX idx_is_active (is_active)
);

-- Vehicle warranties table
CREATE TABLE vehicle_warranties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    policy_id BIGINT UNSIGNED NOT NULL,
    warranty_number VARCHAR(100) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'voided', 'transferred') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (policy_id) REFERENCES warranty_policies(id) ON DELETE RESTRICT,
    INDEX idx_warranty_number (warranty_number),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- Warranty claims table
CREATE TABLE warranty_claims (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    claim_number VARCHAR(100) NOT NULL UNIQUE,
    vehicle_warranty_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    claim_type ENUM('repair', 'replacement', 'refund', 'recall') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('draft', 'submitted', 'under_review', 'investigating', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    issue_description TEXT NOT NULL,
    symptoms TEXT,
    fault_code VARCHAR(50),
    mileage_at_claim BIGINT UNSIGNED,
    incident_date DATE,
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estimated_cost DECIMAL(12,2),
    approved_amount DECIMAL(12,2),
    notes TEXT,
    created_by BIGINT UNSIGNED,
    assigned_to BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vehicle_warranty_id) REFERENCES vehicle_warranties(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_claim_number (claim_number),
    INDEX idx_vehicle_warranty_id (vehicle_warranty_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_claim_type (claim_type),
    INDEX idx_reported_date (reported_date)
);

-- Claim approval workflow table
CREATE TABLE claim_approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    claim_id BIGINT UNSIGNED NOT NULL,
    approver_id BIGINT UNSIGNED NOT NULL,
    approval_level TINYINT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'escalated') NOT NULL DEFAULT 'pending',
    comments TEXT,
    approved_amount DECIMAL(12,2),
    decision_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (claim_id) REFERENCES warranty_claims(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_claim_id (claim_id),
    INDEX idx_approver_id (approver_id),
    INDEX idx_status (status),
    INDEX idx_approval_level (approval_level)
);

-- Claim documents table
CREATE TABLE claim_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    claim_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM('invoice', 'receipt', 'photo', 'video', 'report', 'estimate', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT UNSIGNED,
    mime_type VARCHAR(100),
    description TEXT,
    uploaded_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (claim_id) REFERENCES warranty_claims(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_claim_id (claim_id),
    INDEX idx_document_type (document_type)
);

-- Service centers table
CREATE TABLE service_centers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    center_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Vietnam',
    phone VARCHAR(20),
    email VARCHAR(255),
    manager_name VARCHAR(255),
    certification_level ENUM('authorized', 'certified', 'premium') NOT NULL DEFAULT 'authorized',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_center_code (center_code),
    INDEX idx_city (city),
    INDEX idx_certification_level (certification_level),
    INDEX idx_is_active (is_active)
);

-- Claim services table (repairs/services performed)
CREATE TABLE claim_services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    claim_id BIGINT UNSIGNED NOT NULL,
    service_center_id BIGINT UNSIGNED,
    service_type ENUM('diagnostic', 'repair', 'replacement', 'maintenance', 'recall_service') NOT NULL,
    component_name VARCHAR(255),
    part_number VARCHAR(100),
    labor_hours DECIMAL(5,2),
    labor_cost DECIMAL(10,2),
    parts_cost DECIMAL(10,2),
    total_cost DECIMAL(10,2),
    service_date DATE,
    completion_date DATE,
    technician_name VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (claim_id) REFERENCES warranty_claims(id) ON DELETE CASCADE,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id) ON DELETE SET NULL,
    INDEX idx_claim_id (claim_id),
    INDEX idx_service_center_id (service_center_id),
    INDEX idx_service_type (service_type),
    INDEX idx_service_date (service_date)
);

-- Notifications table
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_id BIGINT UNSIGNED NOT NULL,
    type ENUM('email', 'sms', 'system', 'push') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_type ENUM('warranty_claim', 'vehicle', 'policy', 'service', 'user') NULL,
    related_id BIGINT UNSIGNED NULL,
    status ENUM('pending', 'sent', 'delivered', 'failed', 'read') NOT NULL DEFAULT 'pending',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_related (related_type, related_id),
    INDEX idx_scheduled_at (scheduled_at)
);

-- System logs table
CREATE TABLE system_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100),
    entity_id BIGINT UNSIGNED NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
);

-- Insert default data
INSERT INTO warranty_policies (policy_name, policy_code, description, coverage_type, duration_months, mileage_limit, terms_conditions) VALUES
('Standard Vehicle Warranty', 'STD-VEH-001', 'Standard warranty coverage for electric vehicles', 'comprehensive', 36, 100000, 'Covers manufacturing defects and component failures under normal use'),
('Extended Battery Warranty', 'EXT-BAT-001', 'Extended warranty for battery systems', 'battery', 96, 200000, 'Covers battery degradation and performance issues'),
('Motor System Warranty', 'MOT-SYS-001', 'Warranty for electric motor systems', 'motor', 60, 150000, 'Covers motor and drivetrain components');

INSERT INTO users (username, email, password_hash, first_name, last_name, role, status) VALUES
('admin', 'admin@oem-ev.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', 'active'),
('manager', 'manager@oem-ev.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Warranty', 'Manager', 'manager', 'active');

INSERT INTO service_centers (center_code, name, address, city, state, postal_code, phone, email, manager_name, certification_level) VALUES
('SC-HCM-001', 'Ho Chi Minh Service Center', '123 Nguyen Hue Street, District 1', 'Ho Chi Minh City', 'Ho Chi Minh', '700000', '+84-28-1234-5678', 'hcm@oem-ev.com', 'Nguyen Van A', 'premium'),
('SC-HN-001', 'Hanoi Service Center', '456 Ba Dinh Street, Ba Dinh District', 'Hanoi', 'Hanoi', '100000', '+84-24-1234-5678', 'hanoi@oem-ev.com', 'Tran Thi B', 'certified');