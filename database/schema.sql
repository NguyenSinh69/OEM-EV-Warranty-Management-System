-- OEM EV Warranty Management System - Main Database Schema
-- Updated for Ticket 2.1 - Admin System

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('SC_Staff', 'SC_Technician', 'EVM_Staff', 'Admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create service_centers table
CREATE TABLE IF NOT EXISTS service_centers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    location TEXT NOT NULL,
    contact_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create warranty_claims table
CREATE TABLE IF NOT EXISTS warranty_claims (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    service_center_id INT,
    component_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'in_progress', 'completed') DEFAULT 'pending',
    repair_cost DECIMAL(10,2),
    customer_satisfaction INT CHECK (customer_satisfaction BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_date TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id)
);

-- Create technician_assignments table
CREATE TABLE IF NOT EXISTS technician_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    technician_id INT NOT NULL,
    service_center_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (technician_id) REFERENCES users(id),
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id),
    UNIQUE KEY unique_assignment (technician_id, service_center_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password_hash, role) 
VALUES ('admin', '$2y$10$Aoth5ZHlrBvLNJFL4WzW4.qdPLUIH4o0wahHC/daSGTjM5/aguMlS', 'Admin')
ON DUPLICATE KEY UPDATE id = id;

-- Insert sample EVM staff
INSERT INTO users (username, password_hash, role) VALUES
('evmstaff1', '$2y$10$Aoth5ZHlrBvLNJFL4WzW4.qdPLUIH4o0wahHC/daSGTjM5/aguMlS', 'EVM_Staff'),
('evmstaff2', '$2y$10$Aoth5ZHlrBvLNJFL4WzW4.qdPLUIH4o0wahHC/daSGTjM5/aguMlS', 'EVM_Staff')
ON DUPLICATE KEY UPDATE id = id;

-- Insert sample service centers with more complete data
INSERT INTO service_centers (name, location, contact_info) VALUES
('EVM Service Center - HCMC', 'District 1, Ho Chi Minh City', 'Phone: +84 28 1234 5678\nEmail: hcmc@evmservice.com'),
('EVM Service Center - Hanoi', 'Ba Dinh District, Hanoi', 'Phone: +84 24 8765 4321\nEmail: hanoi@evmservice.com'),
('EVM Service Center - Da Nang', 'Hai Chau District, Da Nang', 'Phone: +84 236 9876 5432\nEmail: danang@evmservice.com')
ON DUPLICATE KEY UPDATE 
    location = VALUES(location),
    contact_info = VALUES(contact_info);

-- Insert sample technicians
INSERT INTO users (username, password_hash, role) VALUES
('tech1', '$2y$10$Aoth5ZHlrBvLNJFL4WzW4.qdPLUIH4o0wahHC/daSGTjM5/aguMlS', 'SC_Technician'),
('tech2', '$2y$10$Aoth5ZHlrBvLNJFL4WzW4.qdPLUIH4o0wahHC/daSGTjM5/aguMlS', 'SC_Technician'),
('tech3', '$2y$10$Aoth5ZHlrBvLNJFL4WzW4.qdPLUIH4o0wahHC/daSGTjM5/aguMlS', 'SC_Technician')
ON DUPLICATE KEY UPDATE role = VALUES(role);

-- Insert sample SC staff
INSERT INTO users (username, password_hash, role) VALUES
('scstaff1', '$2y$10$Aoth5ZHlrBvLNJFL4WzW4.qdPLUIH4o0wahHC/daSGTjM5/aguMlS', 'SC_Staff'),
('scstaff2', '$2y$10$Aoth5ZHlrBvLNJFL4WzW4.qdPLUIH4o0wahHC/daSGTjM5/aguMlS', 'SC_Staff')
ON DUPLICATE KEY UPDATE role = VALUES(role);

-- Insert sample warranty claims for testing analytics
INSERT INTO warranty_claims (customer_id, vehicle_id, service_center_id, component_type, description, status, repair_cost, completion_date) VALUES
(1, 1, 1, 'Battery', 'Battery not charging properly', 'completed', 1500.00, '2024-10-15 14:30:00'),
(2, 2, 2, 'Motor', 'Motor making unusual noise', 'in_progress', 800.00, NULL),
(3, 3, 1, 'Charging Port', 'Charging port damaged', 'approved', 300.00, NULL),
(4, 4, 3, 'Display', 'Touchscreen not responsive', 'pending', 0.00, NULL),
(5, 5, 2, 'Air Conditioning', 'AC not cooling effectively', 'completed', 450.00, '2024-10-20 16:45:00'),
(6, 6, 1, 'Battery', 'Battery capacity degraded', 'completed', 1200.00, '2024-11-01 10:15:00'),
(7, 7, 3, 'Motor', 'Motor overheating', 'in_progress', 900.00, NULL),
(8, 8, 2, 'Brakes', 'Brake system failure', 'approved', 600.00, NULL)
ON DUPLICATE KEY UPDATE 
    description = VALUES(description),
    status = VALUES(status),
    repair_cost = VALUES(repair_cost);

-- Assign technicians to service centers
INSERT INTO technician_assignments (technician_id, service_center_id) VALUES
((SELECT id FROM users WHERE username = 'tech1'), 1),
((SELECT id FROM users WHERE username = 'tech2'), 2),
((SELECT id FROM users WHERE username = 'tech3'), 3)
ON DUPLICATE KEY UPDATE service_center_id = VALUES(service_center_id);