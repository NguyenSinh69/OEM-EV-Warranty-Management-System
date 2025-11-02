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

-- Insert some sample service centers
INSERT INTO service_centers (name, location, contact_info) VALUES
('Service Center 1', 'Ho Chi Minh City', 'Phone: 0123456789, Email: sc1@example.com'),
('Service Center 2', 'Ha Noi', 'Phone: 0987654321, Email: sc2@example.com')
ON DUPLICATE KEY UPDATE id = id;