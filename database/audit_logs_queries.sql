-- Audit Logs Enhancement Queries
-- Add these to your user_management.sql file

-- Sample audit log entries for testing
INSERT IGNORE INTO audit_logs (log_id, user_id, action_type, action_details, performed_by, timestamp) VALUES
('LOG-2024-0001', 'USR001', 'login', 'User logged in successfully', 'USR001', NOW()),
('LOG-2024-0002', 'USR002', 'profile_update', 'User updated profile information', 'USR002', NOW()),
('LOG-2024-0003', 'USR003', 'verification_requested', 'User requested account verification', 'USR003', NOW()),
('LOG-2024-0004', 'USR002', 'password_change', 'User changed password', 'USR002', NOW()),
('LOG-2024-0005', 'USR001', 'user_created', 'New user account created', 'USR001', NOW()),
('LOG-2024-0006', 'USR001', 'user_status_changed', 'User status changed from active to inactive', 'USR001', NOW()),
('LOG-2024-0007', 'USR002', 'document_uploaded', 'User uploaded verification documents', 'USR002', NOW()),
('LOG-2024-0008', 'USR001', 'verification_approved', 'User verification approved by admin', 'USR001', NOW()),
('LOG-2024-0009', 'USR003', 'login_failed', 'Failed login attempt - incorrect password', 'USR003', NOW()),
('LOG-2024-0010', 'USR001', 'role_changed', 'User role changed from citizen to operator', 'USR001', NOW());

-- Audit log statistics view
CREATE OR REPLACE VIEW audit_log_statistics AS
SELECT 
    action_type,
    COUNT(*) as total_actions,
    COUNT(DISTINCT user_id) as unique_users,
    DATE(timestamp) as action_date,
    COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h,
    COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7d,
    COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30d
FROM audit_logs
GROUP BY action_type, DATE(timestamp)
ORDER BY action_date DESC, total_actions DESC;

-- Create indexes for better audit log performance
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_action ON audit_logs(user_id, action_type);
CREATE INDEX IF NOT EXISTS idx_audit_logs_performed_by ON audit_logs(performed_by);
CREATE INDEX IF NOT EXISTS idx_audit_logs_date ON audit_logs(DATE(timestamp));

-- Audit log search and filtering procedures
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS GetAuditLogs(
    IN p_limit INT DEFAULT 50,
    IN p_offset INT DEFAULT 0,
    IN p_user_id VARCHAR(20) DEFAULT NULL,
    IN p_action_type VARCHAR(50) DEFAULT NULL,
    IN p_date_from DATE DEFAULT NULL,
    IN p_date_to DATE DEFAULT NULL
)
BEGIN
    SET @sql = 'SELECT al.*, u.username, u.first_name, u.last_name, 
                       pb.username as performed_by_username
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.user_id
                LEFT JOIN users pb ON al.performed_by = pb.user_id
                WHERE 1=1';
    
    IF p_user_id IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND al.user_id = "', p_user_id, '"');
    END IF;
    
    IF p_action_type IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND al.action_type = "', p_action_type, '"');
    END IF;
    
    IF p_date_from IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND DATE(al.timestamp) >= "', p_date_from, '"');
    END IF;
    
    IF p_date_to IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND DATE(al.timestamp) <= "', p_date_to, '"');
    END IF;
    
    SET @sql = CONCAT(@sql, ' ORDER BY al.timestamp DESC LIMIT ', p_limit, ' OFFSET ', p_offset);
    
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END //

CREATE PROCEDURE IF NOT EXISTS GetAuditLogStatistics()
BEGIN
    SELECT 
        COUNT(*) as total_logs,
        COUNT(DISTINCT user_id) as unique_users,
        COUNT(DISTINCT action_type) as unique_actions,
        COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h,
        COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7d,
        COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30d
    FROM audit_logs;
END //

CREATE PROCEDURE IF NOT EXISTS GetTopActionTypes(
    IN p_limit INT DEFAULT 10
)
BEGIN
    SELECT 
        action_type,
        COUNT(*) as action_count,
        COUNT(DISTINCT user_id) as unique_users,
        MAX(timestamp) as last_occurrence
    FROM audit_logs
    GROUP BY action_type
    ORDER BY action_count DESC
    LIMIT p_limit;
END //

CREATE PROCEDURE IF NOT EXISTS GetUserActivitySummary(
    IN p_user_id VARCHAR(20)
)
BEGIN
    SELECT 
        action_type,
        COUNT(*) as action_count,
        MIN(timestamp) as first_occurrence,
        MAX(timestamp) as last_occurrence
    FROM audit_logs
    WHERE user_id = p_user_id
    GROUP BY action_type
    ORDER BY action_count DESC;
END //

-- Audit log cleanup procedure (for maintenance)
CREATE PROCEDURE IF NOT EXISTS CleanupOldAuditLogs(
    IN p_days_to_keep INT DEFAULT 365
)
BEGIN
    DELETE FROM audit_logs 
    WHERE timestamp < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY);
    
    SELECT ROW_COUNT() as deleted_records;
END //

DELIMITER ;