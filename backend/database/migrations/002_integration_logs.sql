-- Integration logs table for tracking CS sync operations
CREATE TABLE IF NOT EXISTS integration_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    identifier VARCHAR(255) NOT NULL,
    status ENUM('success', 'failed', 'pending') NOT NULL,
    error_message TEXT NULL,
    request_data JSON NULL,
    response_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_action (action),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_identifier (identifier)
);