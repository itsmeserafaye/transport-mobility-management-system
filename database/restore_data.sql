-- Quick data restoration script
-- Run this in phpMyAdmin or MySQL command line

-- Check if tables exist, if not create them
CREATE TABLE IF NOT EXISTS operators (
    operator_id VARCHAR(20) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    date_of_birth DATE,
    contact_number VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    license_number VARCHAR(50),
    license_expiry DATE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id VARCHAR(20) PRIMARY KEY,
    operator_id VARCHAR(20) NOT NULL,
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    vehicle_type ENUM('jeepney', 'bus', 'taxi', 'tricycle', 'van') NOT NULL,
    make VARCHAR(50),
    model VARCHAR(50),
    year_manufactured YEAR,
    engine_number VARCHAR(50),
    chassis_number VARCHAR(50),
    seating_capacity INT,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS violation_history (
    violation_id VARCHAR(20) PRIMARY KEY,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    violation_type VARCHAR(100) NOT NULL,
    violation_date DATETIME NOT NULL,
    fine_amount DECIMAL(10,2) NOT NULL,
    settlement_status ENUM('paid', 'unpaid', 'partial') DEFAULT 'unpaid',
    settlement_date DATE NULL,
    location VARCHAR(200),
    ticket_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

-- Insert sample operators
INSERT IGNORE INTO operators (operator_id, first_name, last_name, contact_number, email, address, license_number, license_expiry, status) VALUES
('OP-2024-001', 'Juan', 'Cruz', '+639123456789', 'juan.cruz@email.com', '123 Main St, Quezon City', 'LIC-001-2024', '2025-12-31', 'active'),
('OP-2024-002', 'Maria', 'Santos', '+639234567890', 'maria.santos@email.com', '456 Oak Ave, Manila', 'LIC-002-2024', '2025-11-30', 'active'),
('OP-2024-003', 'Pedro', 'Reyes', '+639345678901', 'pedro.reyes@email.com', '789 Pine St, Pasig', 'LIC-003-2024', '2025-10-31', 'active'),
('OP-2024-004', 'Ana', 'Garcia', '+639456789012', 'ana.garcia@email.com', '321 Elm St, Makati', 'LIC-004-2024', '2025-09-30', 'active'),
('OP-2024-005', 'Carlos', 'Lopez', '+639567890123', 'carlos.lopez@email.com', '654 Maple Ave, Taguig', 'LIC-005-2024', '2025-08-31', 'active'),
('OP-2024-006', 'Rosa', 'Mendoza', '+639678901234', 'rosa.mendoza@email.com', '987 Cedar St, Mandaluyong', 'LIC-006-2024', '2025-07-31', 'inactive'),
('OP-2024-007', 'Miguel', 'Torres', '+639789012345', 'miguel.torres@email.com', '147 Birch Ave, San Juan', 'LIC-007-2024', '2025-06-30', 'inactive'),
('OP-2024-008', 'Elena', 'Ramos', '+639890123456', 'elena.ramos@email.com', '258 Spruce St, Pasay', 'LIC-008-2024', '2025-05-31', 'active');

-- Insert sample vehicles
INSERT IGNORE INTO vehicles (vehicle_id, operator_id, plate_number, vehicle_type, make, model, year_manufactured, seating_capacity, status) VALUES
('VH-2024-001', 'OP-2024-001', 'ABC-1234', 'jeepney', 'Isuzu', 'Elf', 2020, 16, 'active'),
('VH-2024-002', 'OP-2024-002', 'DEF-5678', 'jeepney', 'Mitsubishi', 'Fuso', 2019, 18, 'active'),
('VH-2024-003', 'OP-2024-003', 'GHI-9012', 'bus', 'Hino', 'Grandia', 2021, 25, 'active'),
('VH-2024-004', 'OP-2024-004', 'JKL-3456', 'jeepney', 'Isuzu', 'Elf', 2018, 16, 'active'),
('VH-2024-005', 'OP-2024-005', 'MNO-7890', 'van', 'Toyota', 'Hiace', 2020, 15, 'active'),
('VH-2024-006', 'OP-2024-006', 'PQR-1234', 'jeepney', 'Mitsubishi', 'Fuso', 2017, 18, 'inactive'),
('VH-2024-007', 'OP-2024-007', 'STU-5678', 'tricycle', 'Honda', 'TMX', 2019, 6, 'inactive'),
('VH-2024-008', 'OP-2024-008', 'VWX-9012', 'jeepney', 'Isuzu', 'Elf', 2021, 16, 'active');

-- Insert sample violations
INSERT IGNORE INTO violation_history (violation_id, operator_id, vehicle_id, violation_type, violation_date, fine_amount, settlement_status, location, ticket_number) VALUES
('VIO-2024-001', 'OP-2024-001', 'VH-2024-001', 'Speeding', '2024-01-15 08:30:00', 1500.00, 'paid', 'EDSA Quezon City', 'TCK-001'),
('VIO-2024-002', 'OP-2024-002', 'VH-2024-002', 'Overloading', '2024-01-20 14:15:00', 2000.00, 'unpaid', 'Commonwealth Ave', 'TCK-002'),
('VIO-2024-003', 'OP-2024-003', 'VH-2024-003', 'Route Deviation', '2024-02-05 10:45:00', 1000.00, 'paid', 'Ortigas Center', 'TCK-003'),
('VIO-2024-004', 'OP-2024-004', 'VH-2024-004', 'No Franchise', '2024-02-10 16:20:00', 3000.00, 'unpaid', 'Makati CBD', 'TCK-004'),
('VIO-2024-005', 'OP-2024-001', 'VH-2024-001', 'Reckless Driving', '2024-02-15 09:10:00', 2500.00, 'partial', 'Shaw Boulevard', 'TCK-005'),
('VIO-2024-006', 'OP-2024-005', 'VH-2024-005', 'Speeding', '2024-02-20 11:30:00', 1500.00, 'unpaid', 'C5 Road', 'TCK-006'),
('VIO-2024-007', 'OP-2024-002', 'VH-2024-002', 'Overloading', '2024-03-01 13:45:00', 2000.00, 'paid', 'Katipunan Ave', 'TCK-007'),
('VIO-2024-008', 'OP-2024-008', 'VH-2024-008', 'Route Deviation', '2024-03-05 15:25:00', 1000.00, 'unpaid', 'Taguig BGC', 'TCK-008');