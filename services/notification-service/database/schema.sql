-- ========================================
-- EVM Notification Service Database Schema
-- ========================================

-- 1. Notifications Table - Quản lý thông báo
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error', 'warranty_claim', 'appointment', 'maintenance', 'campaign') NOT NULL DEFAULT 'info',
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed') NOT NULL DEFAULT 'pending',
    
    -- Channels: email, sms, push, in_app
    channels JSON NULL,
    
    -- Metadata cho notification
    data JSON NULL,
    
    -- Email/SMS tracking
    email_sent_at TIMESTAMP NULL,
    sms_sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    
    -- Related entities
    related_type VARCHAR(50) NULL, -- 'warranty_claim', 'appointment', 'vehicle'
    related_id INT NULL,
    
    -- Scheduling
    scheduled_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_priority (priority),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_created_at (created_at),
    INDEX idx_related (related_type, related_id)
);

-- 2. Appointments Table - Lịch hẹn
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    vehicle_vin VARCHAR(17) NOT NULL,
    service_center_id INT NOT NULL,
    technician_id INT NULL,
    
    -- Appointment details
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type ENUM('maintenance', 'repair', 'warranty', 'inspection', 'consultation') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    
    -- Scheduling
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_minutes INT NOT NULL DEFAULT 60,
    
    -- Status management
    status ENUM('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'scheduled',
    
    -- Contact info
    contact_phone VARCHAR(20) NULL,
    contact_email VARCHAR(255) NULL,
    
    -- Notes and requirements
    customer_notes TEXT NULL,
    technician_notes TEXT NULL,
    completion_notes TEXT NULL,
    
    -- Service details
    estimated_cost DECIMAL(15,2) NULL,
    actual_cost DECIMAL(15,2) NULL,
    parts_needed JSON NULL, -- Array of part IDs
    
    -- Confirmation tracking
    confirmed_at TIMESTAMP NULL,
    confirmed_by INT NULL, -- staff_id
    
    -- Completion tracking
    completed_at TIMESTAMP NULL,
    completed_by INT NULL, -- technician_id
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL, -- who created the appointment
    
    -- Indexes
    INDEX idx_customer_id (customer_id),
    INDEX idx_vehicle_vin (vehicle_vin),
    INDEX idx_service_center_id (service_center_id),
    INDEX idx_technician_id (technician_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_date_time (appointment_date, start_time),
    INDEX idx_created_at (created_at)
);

-- 3. Inventory Table - Tồn kho phụ tùng
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    part_number VARCHAR(50) NOT NULL UNIQUE,
    part_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NOT NULL, -- 'battery', 'motor', 'electronics', 'body', 'interior'
    
    -- Vehicle compatibility
    compatible_models JSON NOT NULL, -- Array of vehicle models
    
    -- Stock information
    current_stock INT NOT NULL DEFAULT 0,
    reserved_stock INT NOT NULL DEFAULT 0, -- allocated but not used
    available_stock INT GENERATED ALWAYS AS (current_stock - reserved_stock) STORED,
    
    -- Thresholds
    min_stock_level INT NOT NULL DEFAULT 5,
    max_stock_level INT NOT NULL DEFAULT 100,
    reorder_point INT NOT NULL DEFAULT 10,
    reorder_quantity INT NOT NULL DEFAULT 50,
    
    -- Pricing
    unit_cost DECIMAL(15,2) NOT NULL,
    selling_price DECIMAL(15,2) NOT NULL,
    
    -- Warehouse location
    warehouse_location VARCHAR(100) NULL,
    shelf_location VARCHAR(50) NULL,
    
    -- Supplier information
    supplier_id INT NULL,
    supplier_part_number VARCHAR(50) NULL,
    
    -- Status
    status ENUM('active', 'discontinued', 'backordered', 'obsolete') NOT NULL DEFAULT 'active',
    
    -- Weight and dimensions
    weight_kg DECIMAL(8,3) NULL,
    dimensions VARCHAR(50) NULL, -- "L x W x H in cm"
    
    -- Tracking
    last_restocked_at TIMESTAMP NULL,
    last_restocked_by INT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_part_number (part_number),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_stock_levels (current_stock, min_stock_level),
    INDEX idx_available_stock (available_stock),
    INDEX idx_supplier_id (supplier_id),
    FULLTEXT KEY ft_search (part_name, description)
);

-- 4. Notification Campaigns Table - Chiến dịch thông báo
CREATE TABLE notification_campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type ENUM('marketing', 'maintenance_reminder', 'recall_notice', 'promotion', 'system_update', 'warranty_expiry') NOT NULL,
    
    -- Campaign content
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    email_template TEXT NULL,
    sms_template TEXT NULL,
    
    -- Targeting
    target_criteria JSON NOT NULL, -- Customer segmentation criteria
    estimated_recipients INT NULL,
    
    -- Scheduling
    scheduled_at TIMESTAMP NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    
    -- Status
    status ENUM('draft', 'scheduled', 'running', 'paused', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    
    -- Analytics
    total_sent INT DEFAULT 0,
    total_delivered INT DEFAULT 0,
    total_opened INT DEFAULT 0,
    total_clicked INT DEFAULT 0,
    total_failed INT DEFAULT 0,
    
    -- Priority
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL, -- admin/staff who created campaign
    
    -- Indexes
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_created_by (created_by),
    INDEX idx_dates (start_date, end_date)
);

-- 5. Inventory Transactions Table - Lịch sử giao dịch kho
CREATE TABLE inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inventory_id INT NOT NULL,
    type ENUM('stock_in', 'stock_out', 'allocation', 'deallocation', 'adjustment', 'return') NOT NULL,
    quantity INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    
    -- Reference information
    reference_type VARCHAR(50) NULL, -- 'appointment', 'warranty_claim', 'manual'
    reference_id INT NULL,
    
    -- Cost tracking
    unit_cost DECIMAL(15,2) NULL,
    total_cost DECIMAL(15,2) NULL,
    
    notes TEXT NULL,
    performed_by INT NOT NULL, -- staff_id
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_inventory_id (inventory_id),
    INDEX idx_type (type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created_at (created_at),
    INDEX idx_performed_by (performed_by)
);

-- 6. Notification Queue Table - Queue hệ thống
CREATE TABLE notification_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id INT NULL,
    campaign_id INT NULL,
    
    -- Message details
    recipient_type ENUM('customer', 'staff', 'technician') NOT NULL DEFAULT 'customer',
    recipient_id INT NOT NULL,
    channel ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    
    -- Content
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    recipient_email VARCHAR(255) NULL,
    recipient_phone VARCHAR(20) NULL,
    
    -- Status
    status ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    
    -- Scheduling
    scheduled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    
    -- Error tracking
    error_message TEXT NULL,
    last_attempt_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES notification_campaigns(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_recipient (recipient_type, recipient_id),
    INDEX idx_channel (channel),
    INDEX idx_notification_id (notification_id),
    INDEX idx_campaign_id (campaign_id)
);

-- Insert some sample data
INSERT INTO inventory (part_number, part_name, description, category, compatible_models, current_stock, min_stock_level, reorder_point, unit_cost, selling_price) VALUES
('BATT-VF8-001', 'VinFast VF8 Battery Pack', 'Main battery pack for VF8 87.7kWh', 'battery', '["VF8"]', 25, 3, 5, 15000.00, 18000.00),
('MOTOR-VF8-FR', 'VF8 Front Motor', 'Front motor assembly for VF8', 'motor', '["VF8"]', 12, 2, 3, 8000.00, 10000.00),
('MOTOR-VF9-FR', 'VF9 Front Motor', 'Front motor assembly for VF9', 'motor', '["VF9"]', 8, 2, 3, 9000.00, 11000.00),
('ECU-001', 'Electronic Control Unit', 'Main ECU for all VF models', 'electronics', '["VF8", "VF9"]', 45, 5, 10, 2500.00, 3200.00),
('BRAKE-PAD-VF8', 'VF8 Brake Pads Set', 'Complete brake pad set for VF8', 'brakes', '["VF8"]', 150, 20, 30, 150.00, 220.00),
('TIRE-VF8-255', 'VF8 Tire 255/45R20', 'Original tire for VF8', 'tires', '["VF8"]', 80, 16, 24, 250.00, 350.00);

INSERT INTO notification_campaigns (name, description, type, title, message, target_criteria, status, created_by) VALUES
('Bảo dưỡng định kỳ Q4 2024', 'Chiến dịch nhắc nhở bảo dưỡng định kỳ', 'maintenance_reminder', 'Thời gian bảo dưỡng xe của bạn đã đến', 'Xe của bạn đã đến thời gian bảo dưỡng định kỳ. Vui lòng đặt lịch hẹn.', '{"vehicle_age_months": ">6", "last_maintenance": ">3_months"}', 'draft', 1),
('VF8 Battery Recall', 'Thông báo triệu hồi pin VF8 batch cũ', 'recall_notice', 'Thông báo quan trọng về pin xe VF8', 'Xe VF8 của bạn thuộc lô cần kiểm tra pin. Vui lòng liên hệ ngay.', '{"model": "VF8", "production_year": "2023"}', 'scheduled', 1);