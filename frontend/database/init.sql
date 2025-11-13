-- Database for EV Warranty Management System
CREATE DATABASE IF NOT EXISTS ev_warranty;
USE ev_warranty;

-- Bảng người dùng
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng hãng xe
CREATE TABLE manufacturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country VARCHAR(50),
    website VARCHAR(255),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng mẫu xe
CREATE TABLE vehicle_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    manufacturer_id INT,
    name VARCHAR(100) NOT NULL,
    year INT,
    battery_capacity INT, -- dung lượng pin (kWh)
    range_km INT, -- quãng đường di chuyển (km)
    warranty_period_months INT DEFAULT 24, -- thời hạn bảo hành (tháng)
    description TEXT,
    price DECIMAL(15,2),
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id)
);

-- Bảng đăng ký xe
CREATE TABLE vehicle_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    vehicle_model_id INT,
    vin VARCHAR(17) UNIQUE NOT NULL, -- Vehicle Identification Number
    license_plate VARCHAR(20),
    purchase_date DATE NOT NULL,
    warranty_start_date DATE NOT NULL,
    warranty_end_date DATE NOT NULL,
    dealer_name VARCHAR(100),
    dealer_contact VARCHAR(100),
    mileage INT DEFAULT 0, -- số km đã đi
    status ENUM('active', 'expired', 'transferred', 'recalled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (vehicle_model_id) REFERENCES vehicle_models(id)
);

-- Bảng danh mục lỗi/vấn đề
CREATE TABLE issue_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    warranty_covered BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng yêu cầu bảo hành
CREATE TABLE warranty_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    vehicle_registration_id INT,
    issue_category_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'in_review', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    current_mileage INT,
    estimated_cost DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    labor_hours DECIMAL(5,2),
    assigned_staff_id INT,
    reviewer_id INT,
    review_notes TEXT,
    completion_notes TEXT,
    requested_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_date TIMESTAMP NULL,
    approved_date TIMESTAMP NULL,
    completed_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (vehicle_registration_id) REFERENCES vehicle_registrations(id),
    FOREIGN KEY (issue_category_id) REFERENCES issue_categories(id),
    FOREIGN KEY (assigned_staff_id) REFERENCES users(id),
    FOREIGN KEY (reviewer_id) REFERENCES users(id)
);

-- Bảng file đính kèm
CREATE TABLE warranty_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warranty_request_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    description TEXT,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warranty_request_id) REFERENCES warranty_requests(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Bảng lịch sử trạng thái
CREATE TABLE warranty_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warranty_request_id INT,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    changed_by INT,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warranty_request_id) REFERENCES warranty_requests(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- Bảng phụ tùng/linh kiện
CREATE TABLE spare_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    part_number VARCHAR(50) UNIQUE,
    description TEXT,
    unit_price DECIMAL(10,2),
    stock_quantity INT DEFAULT 0,
    minimum_stock INT DEFAULT 0,
    supplier VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng chi tiết phụ tùng sử dụng trong bảo hành
CREATE TABLE warranty_spare_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warranty_request_id INT,
    spare_part_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2),
    total_price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warranty_request_id) REFERENCES warranty_requests(id),
    FOREIGN KEY (spare_part_id) REFERENCES spare_parts(id)
);

-- Bảng FAQ
CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng hỗ trợ khách hàng
CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- Thêm dữ liệu mẫu

-- Admin user
INSERT INTO users (username, email, password, full_name, role) VALUES 
('admin', 'admin@evwarranty.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin'),
('staff1', 'staff1@evwarranty.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A', 'staff'),
('customer1', 'customer1@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị B', 'customer');

-- Manufacturers
INSERT INTO manufacturers (name, country, description) VALUES 
('VinFast', 'Vietnam', 'Hãng xe Việt Nam'),
('Tesla', 'USA', 'Hãng xe điện hàng đầu thế giới'),
('BYD', 'China', 'Hãng xe điện Trung Quốc');

-- Vehicle Models
INSERT INTO vehicle_models (manufacturer_id, name, year, battery_capacity, range_km, warranty_period_months, price) VALUES 
(1, 'VF 8', 2023, 87, 420, 60, 1200000000),
(1, 'VF 9', 2023, 123, 594, 60, 1500000000),
(2, 'Model 3', 2023, 75, 448, 48, 1400000000),
(3, 'Han EV', 2023, 85, 550, 36, 1100000000);

-- Issue Categories
INSERT INTO issue_categories (name, description, warranty_covered) VALUES 
('Pin/Battery', 'Các vấn đề liên quan đến pin xe', TRUE),
('Động cơ điện', 'Sự cố về động cơ điện', TRUE),
('Hệ thống sạc', 'Vấn đề về sạc pin', TRUE),
('Điện tử', 'Lỗi các thiết bị điện tử', TRUE),
('Ngoại thất', 'Vấn đề về thân xe, sơn', FALSE),
('Nội thất', 'Lỗi nội thất xe', FALSE);

-- Spare Parts
INSERT INTO spare_parts (name, part_number, unit_price, stock_quantity) VALUES 
('Pin Lithium 87kWh', 'BAT-VF8-87', 150000000, 5),
('Động cơ điện 150kW', 'MOT-VF8-150', 80000000, 3),
('Bộ sạc AC', 'CHG-AC-11KW', 15000000, 10),
('Màn hình cảm ứng 15.6"', 'SCR-156-TFT', 25000000, 8);

-- FAQs
INSERT INTO faqs (question, answer, category) VALUES 
('Thời gian bảo hành xe điện là bao lâu?', 'Thời gian bảo hành phụ thuộc vào từng mẫu xe, thường từ 2-5 năm hoặc 100,000-150,000 km.', 'Bảo hành'),
('Làm sao để đăng ký bảo hành xe?', 'Bạn có thể đăng ký trực tuyến trên website hoặc đến trực tiếp các đại lý được ủy quyền.', 'Đăng ký'),
('Pin xe điện có được bảo hành không?', 'Pin xe điện được bảo hành riêng với thời gian dài hơn, thường từ 8-10 năm.', 'Pin');