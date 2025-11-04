-- Initialize warranty database
CREATE DATABASE IF NOT EXISTS warranty_db;
USE warranty_db;

-- Grant privileges
GRANT ALL PRIVILEGES ON warranty_db.* TO 'warranty_user'@'%';
FLUSH PRIVILEGES;