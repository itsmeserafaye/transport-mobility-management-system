-- Roles and Permissions System Tables
-- Run this SQL file to create the roles and permissions tables

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

-- Insert default roles
INSERT IGNORE INTO system_roles (role_id, role_name, role_description, is_system_role) VALUES
('ROLE001', 'Super Administrator', 'Full system access', TRUE),
('ROLE002', 'Administrator', 'System administrator', TRUE),
('ROLE003', 'Operator', 'Transport operator', TRUE),
('ROLE004', 'Citizen', 'Regular citizen user', TRUE);

-- Insert permissions
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

-- Assign permissions to roles
INSERT IGNORE INTO role_permissions (role_id, permission_id, granted_by) VALUES
('ROLE001', 'PERM001', 'USR001'), ('ROLE001', 'PERM002', 'USR001'), ('ROLE001', 'PERM003', 'USR001'),
('ROLE001', 'PERM004', 'USR001'), ('ROLE001', 'PERM005', 'USR001'), ('ROLE001', 'PERM006', 'USR001'),
('ROLE001', 'PERM007', 'USR001'), ('ROLE001', 'PERM008', 'USR001'), ('ROLE001', 'PERM009', 'USR001'),
('ROLE002', 'PERM001', 'USR001'), ('ROLE002', 'PERM002', 'USR001'), ('ROLE002', 'PERM004', 'USR001'),
('ROLE002', 'PERM005', 'USR001'), ('ROLE002', 'PERM006', 'USR001'), ('ROLE002', 'PERM007', 'USR001'),
('ROLE003', 'PERM004', 'USR001'), ('ROLE003', 'PERM006', 'USR001'),
('ROLE004', 'PERM004', 'USR001'), ('ROLE004', 'PERM006', 'USR001');