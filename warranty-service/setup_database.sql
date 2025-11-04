-- Script tạo database cho warranty service
-- Chạy script này trong phpMyAdmin hoặc MySQL command line

-- Tạo database
CREATE DATABASE IF NOT EXISTS warranty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Sử dụng database
USE warranty;

-- Tạo bảng claims theo migration gốc
CREATE TABLE IF NOT EXISTS claims (
	id CHAR(36) PRIMARY KEY,
	vin VARCHAR(64) NOT NULL,
	customer_id INT NOT NULL,
	status ENUM('PENDING','APPROVED','REJECTED','IN_PROGRESS','CLOSED') NOT NULL DEFAULT 'PENDING',
	description TEXT,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_claims_vin ON claims(vin);
CREATE INDEX idx_claims_status ON claims(status);

-- Thêm dữ liệu mẫu
INSERT INTO claims (id, vin, customer_id, status, description) VALUES
(UUID(), '1HGBH41JXMN109186', 1001, 'PENDING', 'Lỗi hệ thống điện động cơ'),
(UUID(), 'JH4TB2H26CC000001', 1002, 'APPROVED', 'Thu hồi do lỗi phanh'),
(UUID(), 'WBXHU9C58EP123456', 1003, 'CLOSED', 'Bảo trì định kỳ 50000km');

SELECT 'Database và bảng đã được tạo thành công!' as message;