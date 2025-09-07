-- PTSMD API Database Schema
-- This file contains the database structure for the PTSMD API

CREATE DATABASE IF NOT EXISTS transport_management;
USE transport_management;

-- Franchise Applications Table
CREATE TABLE IF NOT EXISTS franchise_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_name VARCHAR(255) NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    route_id INT,
    vehicle_capacity INT NOT NULL,
    application_type ENUM('new', 'renewal', 'transfer') DEFAULT 'new',
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    documents JSON,
    remarks TEXT,
    submitted_date DATE,
    processed_date DATE,
    processed_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Routes Table
CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_name VARCHAR(255) NOT NULL,
    origin VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    distance DECIMAL(10,2),
    fare DECIMAL(10,2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Operators Table
CREATE TABLE IF NOT EXISTS operators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    address TEXT,
    license_number VARCHAR(50),
    license_expiry DATE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vehicles Table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type ENUM('jeepney', 'bus', 'taxi', 'tricycle', 'van') NOT NULL,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    color VARCHAR(50),
    engine_number VARCHAR(100),
    chassis_number VARCHAR(100),
    seating_capacity INT NOT NULL,
    operator_id INT,
    route_id INT,
    registration_date DATE,
    expiry_date DATE,
    status ENUM('active', 'inactive', 'suspended', 'expired') DEFAULT 'active',
    insurance_policy VARCHAR(100),
    insurance_expiry DATE,
    last_inspection_date DATE,
    next_inspection_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(id),
    FOREIGN KEY (route_id) REFERENCES routes(id)
);

-- Traffic Violations Table
CREATE TABLE IF NOT EXISTS traffic_violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT,
    plate_number VARCHAR(20) NOT NULL,
    violation_type VARCHAR(255) NOT NULL,
    violation_date DATE NOT NULL,
    violation_time TIME,
    location TEXT NOT NULL,
    officer_name VARCHAR(255) NOT NULL,
    fine_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'contested', 'dismissed') DEFAULT 'pending',
    payment_date DATE,
    payment_method VARCHAR(50),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Terminals Table
CREATE TABLE IF NOT EXISTS terminals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    terminal_name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    capacity INT NOT NULL,
    operating_hours VARCHAR(100),
    contact_person VARCHAR(255),
    contact_number VARCHAR(20),
    facilities TEXT,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Terminal Assignments Table
CREATE TABLE IF NOT EXISTS terminal_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    terminal_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    shift_schedule VARCHAR(100),
    remarks TEXT,
    status ENUM('active', 'inactive', 'temporary') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (terminal_id) REFERENCES terminals(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Vehicle Inspections Table
CREATE TABLE IF NOT EXISTS vehicle_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    inspection_date DATE NOT NULL,
    next_inspection_date DATE,
    inspector_name VARCHAR(255) NOT NULL,
    inspection_type ENUM('regular', 'special', 'renewal') DEFAULT 'regular',
    location VARCHAR(255),
    overall_result ENUM('passed', 'failed', 'conditional') DEFAULT NULL,
    completion_date DATE,
    remarks TEXT,
    status ENUM('scheduled', 'in_progress', 'completed', 'failed', 'cancelled') DEFAULT 'scheduled',
    inspection_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Inspection Items Table
CREATE TABLE IF NOT EXISTS inspection_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id INT NOT NULL,
    item_category VARCHAR(100) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    status ENUM('passed', 'failed', 'pending', 'not_applicable') DEFAULT 'pending',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspection_id) REFERENCES vehicle_inspections(id)
);

-- Users Table (for API authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator', 'inspector', 'viewer') DEFAULT 'viewer',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- API Keys Table (for external integrations)
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(255) NOT NULL,
    key_value VARCHAR(255) UNIQUE NOT NULL,
    permissions JSON,
    status ENUM('active', 'inactive', 'revoked') DEFAULT 'active',
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    usage_count INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Rate Limits Table (for API rate limiting)
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_created (identifier, created_at),
    INDEX idx_created_at (created_at)
);

-- User Rate Limits Table
CREATE TABLE IF NOT EXISTS user_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample data
INSERT IGNORE INTO routes (route_name, origin, destination, distance, fare) VALUES
('Route 1', 'City Center', 'Airport', 25.5, 15.00),
('Route 2', 'Downtown', 'University', 12.3, 8.50),
('Route 3', 'Mall', 'Residential Area', 18.7, 12.00);

INSERT IGNORE INTO operators (first_name, last_name, contact_number, email, license_number) VALUES
('Juan', 'Dela Cruz', '09123456789', 'juan.delacruz@email.com', 'LIC123456'),
('Maria', 'Santos', '09234567890', 'maria.santos@email.com', 'LIC234567'),
('Pedro', 'Garcia', '09345678901', 'pedro.garcia@email.com', 'LIC345678');

INSERT IGNORE INTO terminals (terminal_name, location, address, capacity) VALUES
('Central Terminal', 'City Center', '123 Main Street, City Center', 50),
('North Terminal', 'North District', '456 North Avenue, North District', 30),
('South Terminal', 'South District', '789 South Road, South District', 40);

-- Create indexes for better performance
CREATE INDEX idx_franchise_status ON franchise_applications(status);
CREATE INDEX idx_franchise_date ON franchise_applications(submitted_date);
CREATE INDEX idx_vehicle_plate ON vehicles(plate_number);
CREATE INDEX idx_vehicle_operator ON vehicles(operator_id);
CREATE INDEX idx_violation_date ON traffic_violations(violation_date);
CREATE INDEX idx_violation_status ON traffic_violations(status);
CREATE INDEX idx_inspection_date ON vehicle_inspections(inspection_date);
CREATE INDEX idx_inspection_vehicle ON vehicle_inspections(vehicle_id);