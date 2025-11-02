-- Vehicle Service Database Schema
-- Tạo database cho vehicle-service

-- Bảng ev_components: Quản lý linh kiện EV
CREATE TABLE IF NOT EXISTS ev_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    component_type ENUM('battery', 'motor', 'bms', 'inverter', 'charger', 'controller', 'other') NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    model VARCHAR(50) NOT NULL,
    specifications JSON,
    warranty_period INT NOT NULL COMMENT 'Warranty period in months',
    supplier_id INT,
    status ENUM('active', 'discontinued', 'recalled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_component_type (component_type),
    INDEX idx_model (model),
    INDEX idx_status (status)
);

-- Bảng warranty_policies: Chính sách bảo hành
CREATE TABLE IF NOT EXISTS warranty_policies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    component_id INT NOT NULL,
    policy_name VARCHAR(100) NOT NULL,
    warranty_duration INT NOT NULL COMMENT 'Duration in months',
    coverage_details JSON COMMENT 'Coverage details and conditions',
    conditions JSON COMMENT 'Warranty conditions',
    exclusions JSON COMMENT 'Warranty exclusions',
    effective_date DATE NOT NULL,
    expiry_date DATE,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (component_id) REFERENCES ev_components(id) ON DELETE CASCADE,
    INDEX idx_component_id (component_id),
    INDEX idx_status (status),
    INDEX idx_effective_date (effective_date)
);

-- Bảng campaigns: Chiến dịch recall và service campaigns
CREATE TABLE IF NOT EXISTS campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    campaign_type ENUM('recall', 'service_campaign', 'maintenance') NOT NULL,
    affected_models JSON COMMENT 'List of affected vehicle models',
    affected_vins JSON COMMENT 'Specific VINs affected (optional)',
    affected_components JSON COMMENT 'List of affected component IDs',
    priority_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    start_date DATE NOT NULL,
    end_date DATE,
    instructions TEXT,
    status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    total_affected_vehicles INT DEFAULT 0,
    notified_customers INT DEFAULT 0,
    completed_services INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_campaign_type (campaign_type),
    INDEX idx_status (status),
    INDEX idx_priority (priority_level),
    INDEX idx_start_date (start_date)
);

-- Bảng campaign_progress: Theo dõi tiến độ campaign
CREATE TABLE IF NOT EXISTS campaign_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    vin VARCHAR(17),
    customer_id INT,
    service_center_id INT,
    status ENUM('identified', 'notified', 'scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'identified',
    notification_sent_date DATETIME,
    scheduled_date DATETIME,
    completion_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_vin (vin),
    INDEX idx_status (status)
);

-- Insert sample data for ev_components
INSERT INTO ev_components (component_type, component_name, model, specifications, warranty_period, supplier_id) VALUES
('battery', 'Lithium Ion Battery Pack', 'LIB-2024-60kWh', '{"capacity": "60kWh", "voltage": "400V", "cells": 288, "chemistry": "LiFePO4"}', 96, 1),
('battery', 'Lithium Ion Battery Pack', 'LIB-2024-80kWh', '{"capacity": "80kWh", "voltage": "400V", "cells": 384, "chemistry": "LiFePO4"}', 96, 1),
('motor', 'Permanent Magnet Synchronous Motor', 'PMSM-150kW', '{"power": "150kW", "torque": "350Nm", "efficiency": "95%", "cooling": "liquid"}', 60, 2),
('motor', 'Permanent Magnet Synchronous Motor', 'PMSM-200kW', '{"power": "200kW", "torque": "450Nm", "efficiency": "95%", "cooling": "liquid"}', 60, 2),
('bms', 'Battery Management System', 'BMS-V2.1', '{"channels": 288, "balancing": "active", "communication": "CAN", "safety": "ISO26262"}', 60, 3),
('inverter', 'Power Inverter Unit', 'INV-150kW-SiC', '{"power": "150kW", "technology": "SiC", "efficiency": "98%", "switching_freq": "20kHz"}', 60, 4),
('charger', 'On-board Charger', 'OBC-11kW-AC', '{"power": "11kW", "input": "3-phase AC", "efficiency": "95%", "connector": "Type2"}', 36, 5),
('charger', 'DC Fast Charger', 'DCFC-150kW', '{"power": "150kW", "voltage": "200-750V", "current": "300A", "connector": "CCS2"}', 60, 5);

-- Insert sample data for warranty_policies
INSERT INTO warranty_policies (component_id, policy_name, warranty_duration, coverage_details, conditions, effective_date) VALUES
(1, 'EV Battery Standard Warranty', 96, '{"coverage": ["capacity_degradation", "manufacturing_defects", "thermal_runaway"], "degradation_limit": "80%"}', '{"usage": "normal_driving", "temperature": "-20C_to_60C", "charging": "standard_protocols"}', '2024-01-01'),
(3, 'Motor Assembly Warranty', 60, '{"coverage": ["manufacturing_defects", "bearing_failure", "winding_issues"], "labor_included": true}', '{"usage": "normal_operation", "maintenance": "scheduled_only"}', '2024-01-01'),
(5, 'BMS Extended Warranty', 60, '{"coverage": ["software_issues", "hardware_failure", "communication_errors"], "updates_included": true}', '{"installation": "certified_technician", "firmware": "official_only"}', '2024-01-01');

-- Insert sample data for campaigns  
INSERT INTO campaigns (title, description, campaign_type, affected_models, affected_components, priority_level, start_date, end_date, instructions, status) VALUES
('Battery Cooling System Recall', 'Recall for potential coolant leak in battery cooling system that may cause thermal management issues', 'recall', '["Model-X-2024", "Model-Y-2024"]', '[1, 2]', 'high', '2024-11-01', '2025-03-01', 'Inspect battery cooling connections and replace coolant lines if necessary. Estimated time: 2-3 hours.', 'active'),
('BMS Software Update Campaign', 'Service campaign to update BMS firmware to improve battery balancing algorithm', 'service_campaign', '["Model-X-2024", "Model-Y-2024", "Model-Z-2023"]', '[5]', 'medium', '2024-10-15', '2024-12-31', 'Update BMS firmware to version 2.1.3. Update can be performed remotely or at service center.', 'active'),
('Motor Bearing Preventive Maintenance', 'Preventive maintenance campaign for motor bearing inspection and lubrication', 'maintenance', '["Model-Z-2023"]', '[3, 4]', 'low', '2024-12-01', '2025-06-01', 'Inspect motor bearings and replace if wear exceeds specification. Apply new lubrication as per service manual.', 'draft');