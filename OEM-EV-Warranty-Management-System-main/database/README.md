# Database Migration Script for EVM Warranty System

## 🗄️ Database Setup Options

### Option 1: MySQL/MariaDB Setup

```sql
-- Create database
CREATE DATABASE evm_warranty_system;
USE evm_warranty_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    id_number VARCHAR(50),
    role ENUM('admin', 'evm_staff', 'sc_staff', 'technician', 'customer') NOT NULL,
    service_center_id INT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Service Centers table
CREATE TABLE service_centers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    manager_name VARCHAR(255),
    region VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vehicles table
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vin VARCHAR(17) UNIQUE NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    color VARCHAR(50),
    customer_id INT NOT NULL,
    purchase_date DATE,
    warranty_start_date DATE,
    warranty_end_date DATE,
    mileage INT DEFAULT 0,
    battery_capacity VARCHAR(20),
    motor_power VARCHAR(20),
    status ENUM('active', 'inactive', 'recalled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id)
);

-- Warranty Claims table
CREATE TABLE warranty_claims (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    vehicle_vin VARCHAR(17) NOT NULL,
    description TEXT NOT NULL,
    issue_type ENUM('battery', 'motor', 'electrical', 'mechanical', 'software') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('pending', 'in_progress', 'approved', 'rejected', 'completed', 'closed') DEFAULT 'pending',
    estimated_cost DECIMAL(15,2) DEFAULT 0,
    actual_cost DECIMAL(15,2) DEFAULT 0,
    technician_id INT NULL,
    service_center_id INT NOT NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (vehicle_vin) REFERENCES vehicles(vin),
    FOREIGN KEY (technician_id) REFERENCES users(id),
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Parts Catalog table
CREATE TABLE parts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    part_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    price DECIMAL(15,2) NOT NULL,
    warranty_months INT DEFAULT 12,
    compatible_models JSON,
    status ENUM('active', 'discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory table
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_center_id INT NOT NULL,
    part_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    min_quantity INT DEFAULT 0,
    max_quantity INT DEFAULT 100,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_center_id) REFERENCES service_centers(id),
    FOREIGN KEY (part_id) REFERENCES parts(id),
    UNIQUE KEY unique_inventory (service_center_id, part_id)
);

-- Claim Attachments table
CREATE TABLE claim_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video', 'document') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES warranty_claims(id) ON DELETE CASCADE
);

-- Work Orders table
CREATE TABLE work_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_id INT NOT NULL,
    technician_id INT NOT NULL,
    description TEXT,
    status ENUM('assigned', 'in_progress', 'completed', 'on_hold') DEFAULT 'assigned',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES warranty_claims(id),
    FOREIGN KEY (technician_id) REFERENCES users(id)
);

-- Parts Used table
CREATE TABLE parts_used (
    id INT PRIMARY KEY AUTO_INCREMENT,
    work_order_id INT NOT NULL,
    part_id INT NOT NULL,
    quantity_used INT NOT NULL,
    cost DECIMAL(15,2),
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
    FOREIGN KEY (part_id) REFERENCES parts(id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Audit Log table
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Option 2: PostgreSQL Setup

```sql
-- Similar structure but with PostgreSQL syntax
-- Use JSONB instead of JSON for better performance
-- Use SERIAL instead of AUTO_INCREMENT
-- Use TEXT arrays for compatible_models instead of JSON
```

### Option 3: Docker Compose with Database

```yaml
# Add to docker-compose.yml
version: '3.8'
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: evm_warranty_system
      MYSQL_USER: evm_user
      MYSQL_PASSWORD: evm_password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./database/init.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - evm_network

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    networks:
      - evm_network

volumes:
  mysql_data:

networks:
  evm_network:
    driver: bridge
```

## 🔧 PHP Database Configuration

```php
<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? 'evm_warranty_system';
        $username = $_ENV['DB_USER'] ?? 'evm_user';
        $password = $_ENV['DB_PASS'] ?? 'evm_password';
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
?>
```

## 📊 Sample Data Migration

```php
<?php
// Migration script to populate initial data
require_once 'database.php';

function migrateData() {
    $db = Database::getInstance();
    
    // Insert sample service centers
    $db->query("INSERT INTO service_centers (name, address, phone, email, manager_name, region) VALUES 
        ('VinFast Service Center Hà Nội', '123 Đường ABC, Hà Nội', '024-1234567', 'hanoi@vinfast.vn', 'Nguyễn Quản Lý', 'North'),
        ('VinFast Service Center TP.HCM', '456 Đường XYZ, TP.HCM', '028-7654321', 'hcm@vinfast.vn', 'Trần Quản Lý', 'South')");
    
    // Insert sample users
    $db->query("INSERT INTO users (name, email, password_hash, phone, address, role, service_center_id) VALUES
        ('Admin User', 'admin@evm.com', :password, '0999999999', 'VinFast HQ', 'admin', NULL),
        ('EVM Staff', 'staff@evm.com', :password, '0888888888', 'VinFast Office', 'evm_staff', NULL),
        ('Nguyễn Văn A', 'nguyenvana@example.com', :password, '0901234567', 'Hà Nội', 'customer', NULL)",
        ['password' => password_hash('password123', PASSWORD_DEFAULT)]);
    
    // Continue with other tables...
}
?>
```

## 🚀 Migration Commands

```bash
# Create database structure
php database/migrate.php

# Seed sample data
php database/seed.php

# Reset database
php database/reset.php
```