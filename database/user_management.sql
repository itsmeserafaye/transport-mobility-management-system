-- User Management Module Database Schema
-- Transport and Mobility Management System
-- Note: Main tables already exist, adding missing columns and helper functions

-- Add missing columns to existing users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20),
ADD COLUMN IF NOT EXISTS verification_documents TEXT,
ADD COLUMN IF NOT EXISTS verification_notes TEXT,
ADD COLUMN IF NOT EXISTS verification_requested_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS account_locked_until TIMESTAMP NULL;

-- User sessions for tracking (if not exists)
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Password reset tokens (if not exists)
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Sample users for testing (only insert if not exists)
INSERT IGNORE INTO users (user_id, username, email, password_hash, first_name, last_name, role, status, verification_status, verification_requested_at) VALUES
('USR001', 'admin', 'admin@caloocan.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', 'active', 'verified', NOW()),
('USR002', 'jdoe', 'john.doe@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'citizen', 'active', 'pending', NOW()),
('USR003', 'msmith', 'mary.smith@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary', 'Smith', 'operator', 'active', 'pending', NOW());

-- Helper functions for user management
DROP FUNCTION IF EXISTS getUserStatistics;

DELIMITER //
CREATE FUNCTION getUserStatistics() 
RETURNS JSON
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_users INT DEFAULT 0;
    DECLARE active_users INT DEFAULT 0;
    DECLARE pending_verification INT DEFAULT 0;
    
    SELECT COUNT(*) INTO total_users FROM users;
    SELECT COUNT(*) INTO active_users FROM users WHERE status = 'active';
    SELECT COUNT(*) INTO pending_verification FROM users WHERE verification_status = 'pending';
    
    RETURN JSON_OBJECT(
        'total_users', total_users,
        'active_users', active_users,
        'pending_verification', pending_verification
    );
END //
DELIMITER ;

-- Account Maintenance Stored Procedures
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS UpdateUserProfile(
    IN p_user_id VARCHAR(20),
    IN p_first_name VARCHAR(50),
    IN p_last_name VARCHAR(50),
    IN p_email VARCHAR(100),
    IN p_phone_number VARCHAR(20),
    IN p_updated_by VARCHAR(20)
)
BEGIN
    UPDATE users 
    SET first_name = p_first_name,
        last_name = p_last_name,
        email = p_email,
        phone_number = p_phone_number,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    INSERT INTO audit_logs (user_id, action_type, action_details, performed_by, timestamp)
    VALUES (p_user_id, 'profile_updated', 'User profile information updated', p_updated_by, NOW());
END //

CREATE PROCEDURE IF NOT EXISTS ResetUserPassword(
    IN p_user_id VARCHAR(20),
    IN p_new_password_hash VARCHAR(255),
    IN p_reset_by VARCHAR(20)
)
BEGIN
    UPDATE users 
    SET password_hash = p_new_password_hash,
        failed_login_attempts = 0,
        account_locked_until = NULL,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    INSERT INTO audit_logs (user_id, action_type, action_details, performed_by, timestamp)
    VALUES (p_user_id, 'password_reset', 'Password reset by administrator', p_reset_by, NOW());
END //

CREATE PROCEDURE IF NOT EXISTS ToggleUserStatus(
    IN p_user_id VARCHAR(20),
    IN p_new_status ENUM('active', 'inactive', 'banned'),
    IN p_reason TEXT,
    IN p_updated_by VARCHAR(20)
)
BEGIN
    UPDATE users 
    SET status = p_new_status,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    INSERT INTO audit_logs (user_id, action_type, action_details, performed_by, timestamp)
    VALUES (p_user_id, 'status_changed', CONCAT('Status changed to ', p_new_status, '. Reason: ', COALESCE(p_reason, 'None')), p_updated_by, NOW());
END //

CREATE PROCEDURE IF NOT EXISTS UnlockUserAccount(
    IN p_user_id VARCHAR(20),
    IN p_unlocked_by VARCHAR(20)
)
BEGIN
    UPDATE users 
    SET failed_login_attempts = 0,
        account_locked_until = NULL,
        updated_at = NOW()
    WHERE user_id = p_user_id;
    
    INSERT INTO audit_logs (user_id, action_type, action_details, performed_by, timestamp)
    VALUES (p_user_id, 'account_unlocked', 'Account unlocked by administrator', p_unlocked_by, NOW());
END //

DELIMITER ;

-- View for account maintenance dashboard
CREATE OR REPLACE VIEW account_maintenance_summary AS
SELECT u.user_id, u.username, u.first_name, u.last_name, u.email, u.phone_number,
       u.role, u.status, u.verification_status, u.last_login, u.created_at,
       u.failed_login_attempts, u.account_locked_until,
       COUNT(al.log_id) as total_activities,
       MAX(al.timestamp) as last_activity
FROM users u
LEFT JOIN audit_logs al ON u.user_id = al.user_id
GROUP BY u.user_id
ORDER BY u.updated_at DESC;

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_users_verification_requested ON users(verification_requested_at);
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_expires_at ON user_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_audit_logs_timestamp ON audit_logs(timestamp);
CREATE INDEX IF NOT EXISTS idx_verification_queue_status ON verification_queue(status);
CREATE INDEX IF NOT EXISTS idx_verification_queue_priority ON verification_queue(priority);

-- Views for easy data access
CREATE OR REPLACE VIEW pending_verifications AS
SELECT u.user_id, u.username, u.first_name, u.last_name, u.email, u.role, 
       u.verification_status, u.verification_requested_at, u.created_at,
       ud.phone_number, ud.verification_documents
FROM users u
LEFT JOIN user_documents ud ON u.user_id = ud.user_id
WHERE u.verification_status = 'pending'
ORDER BY u.verification_requested_at ASC;

-- Stored procedures for common operations
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS ApproveUserVerification(
    IN p_user_id VARCHAR(20),
    IN p_verified_by VARCHAR(20),
    IN p_notes TEXT
)
BEGIN
    UPDATE users 
    SET verification_status = 'verified',
        verified_by = p_verified_by,
        verified_at = NOW(),
        verification_notes = p_notes
    WHERE user_id = p_user_id;
    
    INSERT INTO audit_logs (user_id, action_type, action_details, performed_by, timestamp)
    VALUES (p_user_id, 'verification_approved', CONCAT('User verification approved. Notes: ', COALESCE(p_notes, 'None')), p_verified_by, NOW());
END //

CREATE PROCEDURE IF NOT EXISTS RejectUserVerification(
    IN p_user_id VARCHAR(20),
    IN p_verified_by VARCHAR(20),
    IN p_notes TEXT
)
BEGIN
    UPDATE users 
    SET verification_status = 'rejected',
        verified_by = p_verified_by,
        verified_at = NOW(),
        verification_notes = p_notes
    WHERE user_id = p_user_id;
    
    INSERT INTO audit_logs (user_id, action_type, action_details, performed_by, timestamp)
    VALUES (p_user_id, 'verification_rejected', CONCAT('User verification rejected. Notes: ', COALESCE(p_notes, 'None')), p_verified_by, NOW());
END //

DELIMITER ;

-- Roles and Permissions System
CREATE TABLE IF NOT EXISTS system_roles (
    role_id VARCHAR(20) PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    role_description TEXT,
    is_system_role BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS system_permissions (
    permission_id VARCHAR(20) PRIMARY KEY,
    permission_name VARCHAR(100) UNIQUE NOT NULL,
    permission_description TEXT,
    module_name VARCHAR(50) NOT NULL,
    permission_type ENUM('read', 'write', 'delete', 'admin') NOT NULL
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_permission_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id VARCHAR(20) NOT NULL,
    permission_id VARCHAR(20) NOT NULL,
    granted_by VARCHAR(20) NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
);

-- Insert default roles and permissions
INSERT IGNORE INTO system_roles (role_id, role_name, role_description, is_system_role) VALUES
('ROLE001', 'Super Administrator', 'Full system access', TRUE),
('ROLE002', 'Administrator', 'System administrator', TRUE),
('ROLE003', 'Operator', 'Transport operator', TRUE),
('ROLE004', 'Citizen', 'Regular citizen user', TRUE);

INSERT IGNORE INTO system_permissions (permission_id, permission_name, permission_description, module_name, permission_type) VALUES
('PERM001', 'user_management_read', 'View user accounts', 'user_management', 'read'),
('PERM002', 'user_management_write', 'Edit user accounts', 'user_management', 'write'),
('PERM003', 'user_management_admin', 'Full user management', 'user_management', 'admin'),
('PERM004', 'puv_database_read', 'View PUV records', 'puv_database', 'read'),
('PERM005', 'puv_database_write', 'Edit PUV records', 'puv_database', 'write'),
('PERM006', 'franchise_read', 'View franchises', 'franchise_management', 'read'),
('PERM007', 'franchise_write', 'Process franchises', 'franchise_management', 'write'),
('PERM008', 'violation_read', 'View violations', 'traffic_violations', 'read'),
('PERM009', 'violation_write', 'Manage violations', 'traffic_violations', 'write');

-- Assign default permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id, granted_by) VALUES
('ROLE001', 'PERM001', 'USR001'), ('ROLE001', 'PERM002', 'USR001'), ('ROLE001', 'PERM003', 'USR001'),
('ROLE001', 'PERM004', 'USR001'), ('ROLE001', 'PERM005', 'USR001'), ('ROLE001', 'PERM006', 'USR001'),
('ROLE001', 'PERM007', 'USR001'), ('ROLE001', 'PERM008', 'USR001'), ('ROLE001', 'PERM009', 'USR001'),
('ROLE002', 'PERM001', 'USR001'), ('ROLE002', 'PERM002', 'USR001'), ('ROLE002', 'PERM004', 'USR001'),
('ROLE002', 'PERM005', 'USR001'), ('ROLE002', 'PERM006', 'USR001'), ('ROLE002', 'PERM007', 'USR001'),
('ROLE003', 'PERM004', 'USR001'), ('ROLE003', 'PERM006', 'USR001'),
('ROLE004', 'PERM004', 'USR001'), ('ROLE004', 'PERM006', 'USR001');

-- Roles and Permissions System
CREATE TABLE IF NOT EXISTS system_roles (
    role_id VARCHAR(20) PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    role_description TEXT,
    is_system_role BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS system_permissions (
    permission_id VARCHAR(20) PRIMARY KEY,
    permission_name VARCHAR(100) UNIQUE NOT NULL,
    permission_description TEXT,
    module_name VARCHAR(50) NOT NULL,
    permission_type ENUM('read', 'write', 'delete', 'admin') NOT NULL
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_permission_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id VARCHAR(20) NOT NULL,
    permission_id VARCHAR(20) NOT NULL,
    granted_by VARCHAR(20) NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
);

-- Insert default roles and permissions
INSERT IGNORE INTO system_roles (role_id, role_name, role_description, is_system_role) VALUES
('ROLE001', 'Super Administrator', 'Full system access', TRUE),
('ROLE002', 'Administrator', 'System administrator', TRUE),
('ROLE003', 'Operator', 'Transport operator', TRUE),
('ROLE004', 'Citizen', 'Regular citizen user', TRUE);

INSERT IGNORE INTO system_permissions (permission_id, permission_name, permission_description, module_name, permission_type) VALUES
('PERM001', 'user_management_read', 'View user accounts', 'user_management', 'read'),
('PERM002', 'user_management_write', 'Edit user accounts', 'user_management', 'write'),
('PERM003', 'user_management_admin', 'Full user management', 'user_management', 'admin'),
('PERM004', 'puv_database_read', 'View PUV records', 'puv_database', 'read'),
('PERM005', 'puv_database_write', 'Edit PUV records', 'puv_database', 'write'),
('PERM006', 'franchise_read', 'View franchises', 'franchise_management', 'read'),
('PERM007', 'franchise_write', 'Process franchises', 'franchise_management', 'write'),
('PERM008', 'violation_read', 'View violations', 'traffic_violations', 'read'),
('PERM009', 'violation_write', 'Manage violations', 'traffic_violations', 'write');

-- Assign default permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id, granted_by) VALUES
('ROLE001', 'PERM001', 'USR001'), ('ROLE001', 'PERM002', 'USR001'), ('ROLE001', 'PERM003', 'USR001'),
('ROLE001', 'PERM004', 'USR001'), ('ROLE001', 'PERM005', 'USR001'), ('ROLE001', 'PERM006', 'USR001'),
('ROLE001', 'PERM007', 'USR001'), ('ROLE001', 'PERM008', 'USR001'), ('ROLE001', 'PERM009', 'USR001'),
('ROLE002', 'PERM001', 'USR001'), ('ROLE002', 'PERM002', 'USR001'), ('ROLE002', 'PERM004', 'USR001'),
('ROLE002', 'PERM005', 'USR001'), ('ROLE002', 'PERM006', 'USR001'), ('ROLE002', 'PERM007', 'USR001'),
('ROLE003', 'PERM004', 'USR001'), ('ROLE003', 'PERM006', 'USR001'),
('ROLE004', 'PERM004', 'USR001'), ('ROLE004', 'PERM006', 'USR001');

-- Roles management procedures
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS AssignPermissionToRole(
    IN p_role_id VARCHAR(20),
    IN p_permission_id VARCHAR(20),
    IN p_granted_by VARCHAR(20)
)
BEGIN
    INSERT IGNORE INTO role_permissions (role_id, permission_id, granted_by)
    VALUES (p_role_id, p_permission_id, p_granted_by);
END //

CREATE PROCEDURE IF NOT EXISTS RevokePermissionFromRole(
    IN p_role_id VARCHAR(20),
    IN p_permission_id VARCHAR(20)
)
BEGIN
    DELETE FROM role_permissions 
    WHERE role_id = p_role_id AND permission_id = p_permission_id;
END //

DELIMITER ;