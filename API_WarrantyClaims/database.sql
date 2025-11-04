CREATE DATABASE IF NOT EXISTS warranty_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE warranty_db;

CREATE TABLE warranty_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vin VARCHAR(50) NOT NULL,
    customer_id INT,
    description TEXT,
    status ENUM('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
    costs DECIMAL(10,2) DEFAULT 0,
    attachment VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
