-- Archive System for Transport and Mobility Management System
-- Creates archive tables and procedures for soft delete functionality

-- Archive table for operators
CREATE TABLE IF NOT EXISTS archived_operators (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    operator_id VARCHAR(20),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    address TEXT,
    contact_number VARCHAR(20),
    license_number VARCHAR(50),
    license_expiry DATE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    date_registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100) DEFAULT 'System'
);

-- Archive table for vehicles
CREATE TABLE IF NOT EXISTS archived_vehicles (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id VARCHAR(20),
    operator_id VARCHAR(20),
    plate_number VARCHAR(20),
    vehicle_type ENUM('jeepney', 'bus', 'tricycle', 'taxi', 'van'),
    make VARCHAR(50),
    model VARCHAR(50),
    year_manufactured YEAR,
    engine_number VARCHAR(50),
    chassis_number VARCHAR(50),
    seating_capacity INT,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    date_registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100) DEFAULT 'System'
);

-- Archive table for compliance status
CREATE TABLE IF NOT EXISTS archived_compliance_status (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    compliance_id VARCHAR(20),
    operator_id VARCHAR(20),
    vehicle_id VARCHAR(20),
    franchise_status ENUM('valid', 'expired', 'pending', 'revoked') DEFAULT 'pending',
    inspection_status ENUM('passed', 'failed', 'pending', 'overdue') DEFAULT 'pending',
    violation_count INT DEFAULT 0,
    compliance_score DECIMAL(5,2) DEFAULT 0.00,
    last_inspection_date DATE,
    next_inspection_due DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100) DEFAULT 'System'
);

-- Archive table for violation history
CREATE TABLE IF NOT EXISTS archived_violation_history (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    violation_id VARCHAR(20),
    operator_id VARCHAR(20),
    vehicle_id VARCHAR(20),
    violation_type VARCHAR(100),
    violation_date DATETIME,
    location TEXT,
    fine_amount DECIMAL(10,2),
    settlement_status ENUM('paid', 'unpaid', 'partial') DEFAULT 'unpaid',
    settlement_date DATETIME,
    officer_name VARCHAR(100),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100) DEFAULT 'System'
);

-- Archive table for inspection records
CREATE TABLE IF NOT EXISTS archived_inspection_records (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id VARCHAR(20),
    vehicle_id VARCHAR(20),
    operator_id VARCHAR(20),
    inspection_date DATETIME,
    inspector_name VARCHAR(100),
    result ENUM('passed', 'failed', 'scheduled', 'pending') DEFAULT 'scheduled',
    remarks TEXT,
    next_inspection_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100) DEFAULT 'System'
);

-- Archive table for franchise records
CREATE TABLE IF NOT EXISTS archived_franchise_records (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    franchise_id VARCHAR(20),
    operator_id VARCHAR(20),
    vehicle_id VARCHAR(20),
    franchise_number VARCHAR(50),
    route_assigned VARCHAR(100),
    issue_date DATE,
    expiry_date DATE,
    status ENUM('valid', 'expired', 'revoked', 'suspended') DEFAULT 'valid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100) DEFAULT 'System'
);