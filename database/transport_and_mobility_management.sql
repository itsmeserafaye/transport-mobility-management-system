-- Transport and Mobility Management System Database
-- Created: 2024
-- Purpose: Manage PUV operators, vehicles, compliance, and violations

CREATE DATABASE IF NOT EXISTS transport_mobility_db;
USE transport_mobility_db;

-- PUV Database Module Tables
CREATE TABLE operators (
    operator_id VARCHAR(20) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    address TEXT NOT NULL,
    contact_number VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    license_number VARCHAR(50) NOT NULL UNIQUE,
    license_expiry DATE NOT NULL,
    date_registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
);

CREATE TABLE vehicles (
    vehicle_id VARCHAR(20) PRIMARY KEY,
    operator_id VARCHAR(20) NOT NULL,
    plate_number VARCHAR(15) NOT NULL UNIQUE,
    vehicle_type ENUM('jeepney', 'bus', 'tricycle', 'taxi', 'van') NOT NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year_manufactured YEAR NOT NULL,
    engine_number VARCHAR(50) NOT NULL,
    chassis_number VARCHAR(50) NOT NULL,
    seating_capacity INT NOT NULL,
    date_registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'for_inspection', 'suspended') DEFAULT 'active',
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE
);

CREATE TABLE compliance_status (
    compliance_id VARCHAR(20) PRIMARY KEY,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    franchise_status ENUM('valid', 'expired', 'pending', 'revoked') DEFAULT 'pending',
    inspection_status ENUM('passed', 'failed', 'pending', 'overdue') DEFAULT 'pending',
    violation_count INT DEFAULT 0,
    last_inspection_date DATE,
    next_inspection_due DATE,
    compliance_score DECIMAL(3,2) DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

CREATE TABLE violation_history (
    violation_id VARCHAR(20) PRIMARY KEY,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    violation_type VARCHAR(100) NOT NULL,
    violation_date DATE NOT NULL,
    fine_amount DECIMAL(10,2) NOT NULL,
    settlement_status ENUM('paid', 'unpaid', 'partial') DEFAULT 'unpaid',
    settlement_date DATE NULL,
    location VARCHAR(200),
    ticket_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

-- Additional tables for compliance and violation management
CREATE TABLE franchise_records (
    franchise_id VARCHAR(20) PRIMARY KEY,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    franchise_number VARCHAR(50) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    route_assigned VARCHAR(200),
    status ENUM('valid', 'expired', 'pending', 'revoked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

CREATE TABLE inspection_records (
    inspection_id VARCHAR(20) PRIMARY KEY,
    vehicle_id VARCHAR(20) NOT NULL,
    inspection_date DATE NOT NULL,
    inspector_name VARCHAR(100) NOT NULL,
    inspection_type ENUM('annual', 'renewal', 'spot_check') NOT NULL,
    result ENUM('passed', 'failed', 'conditional', 'pending') NOT NULL DEFAULT 'pending',
    remarks TEXT,
    next_inspection_due DATE,
    certificate_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

CREATE TABLE violation_analytics (
    analytics_id VARCHAR(20) PRIMARY KEY,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    total_violations INT DEFAULT 0,
    last_violation_date DATE,
    repeat_offender_flag BOOLEAN DEFAULT FALSE,
    risk_level ENUM('low', 'medium', 'high') DEFAULT 'low',
    compliance_score DECIMAL(5,2) DEFAULT 100.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

CREATE TABLE compliance_reports (
    report_id VARCHAR(20) PRIMARY KEY,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    report_date DATE NOT NULL,
    franchise_compliance BOOLEAN DEFAULT FALSE,
    inspection_compliance BOOLEAN DEFAULT FALSE,
    violation_compliance BOOLEAN DEFAULT TRUE,
    overall_score DECIMAL(5,2) DEFAULT 0.00,
    recommendations TEXT,
    generated_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

-- Sample data for testing
INSERT INTO operators (operator_id, first_name, last_name, address, contact_number, license_number, license_expiry) VALUES
('OP-2024-001', 'Juan', 'Dela Cruz', '123 Main St, Quezon City', '+639123456789', 'N01-12-345678', '2024-12-31'),
('OP-2024-002', 'Maria', 'Santos', '456 Oak Ave, Manila', '+639176543210', 'N01-12-987654', '2024-01-15'),
('OP-2024-003', 'Roberto', 'Garcia', '789 Pine Rd, Pasig', '+639987654321', 'N01-12-456789', '2025-06-30'),
('OP-2024-004', 'Ana', 'Reyes', '321 Elm St, Makati', '+639234567890', 'N01-12-111222', '2025-03-15'),
('OP-2024-005', 'Carlos', 'Lopez', '654 Maple Ave, Taguig', '+639345678901', 'N01-12-333444', '2024-08-20'),
('OP-2024-006', 'Elena', 'Cruz', '987 Cedar Rd, Paranaque', '+639456789012', 'N01-12-555666', '2025-01-10'),
('OP-2024-007', 'Miguel', 'Torres', '147 Birch St, Las Pinas', '+639567890123', 'N01-12-777888', '2024-11-05'),
('OP-2024-008', 'Sofia', 'Mendoza', '258 Spruce Ave, Muntinlupa', '+639678901234', 'N01-12-999000', '2025-07-25');

INSERT INTO vehicles (vehicle_id, operator_id, plate_number, vehicle_type, make, model, year_manufactured, engine_number, chassis_number, seating_capacity) VALUES
('VH-2024-001', 'OP-2024-001', 'ABC-1234', 'jeepney', 'Toyota', 'Tamaraw', 2019, 'ENG123456', 'CHS123456', 16),
('VH-2024-002', 'OP-2024-002', 'XYZ-5678', 'bus', 'Isuzu', 'Elf', 2020, 'ENG789012', 'CHS789012', 25),
('VH-2024-003', 'OP-2024-003', 'DEF-9012', 'tricycle', 'Honda', 'TMX', 2021, 'ENG345678', 'CHS345678', 3),
('VH-2024-004', 'OP-2024-004', 'GHI-3456', 'taxi', 'Toyota', 'Vios', 2022, 'ENG456789', 'CHS456789', 4),
('VH-2024-005', 'OP-2024-005', 'JKL-7890', 'van', 'Nissan', 'Urvan', 2020, 'ENG567890', 'CHS567890', 15),
('VH-2024-006', 'OP-2024-006', 'MNO-2468', 'jeepney', 'Mitsubishi', 'L300', 2018, 'ENG678901', 'CHS678901', 18),
('VH-2024-007', 'OP-2024-007', 'PQR-1357', 'bus', 'Hyundai', 'County', 2021, 'ENG789012', 'CHS789012', 30),
('VH-2024-008', 'OP-2024-008', 'STU-9753', 'tricycle', 'Yamaha', 'Mio', 2023, 'ENG890123', 'CHS890123', 2);

INSERT INTO violation_history (violation_id, operator_id, vehicle_id, violation_type, violation_date, fine_amount, settlement_status, location, ticket_number) VALUES
('VIO-2024-0156', 'OP-2024-002', 'VH-2024-002', 'Overloading', '2024-01-15', 5000.00, 'unpaid', 'EDSA Cubao', 'TCK-2024-0156'),
('VIO-2024-0089', 'OP-2024-001', 'VH-2024-001', 'Route Deviation', '2023-12-20', 2500.00, 'paid', 'Commonwealth Ave', 'TCK-2024-0089'),
('VIO-2024-0234', 'OP-2024-003', 'VH-2024-003', 'Speeding', '2024-02-10', 1500.00, 'partial', 'Quezon Ave', 'TCK-2024-0234');

-- Insert compliance status data
INSERT INTO compliance_status (compliance_id, operator_id, vehicle_id, franchise_status, inspection_status, violation_count, last_inspection_date, next_inspection_due, compliance_score) VALUES
('CS-2024-001', 'OP-2024-001', 'VH-2024-001', 'valid', 'passed', 1, '2023-11-15', '2024-11-15', 95.00),
('CS-2024-002', 'OP-2024-002', 'VH-2024-002', 'expired', 'overdue', 1, '2023-10-10', '2024-01-10', 65.00),
('CS-2024-003', 'OP-2024-003', 'VH-2024-003', 'pending', 'pending', 1, NULL, '2024-03-15', 85.00),
('CS-2024-004', 'OP-2024-004', 'VH-2024-004', 'valid', 'passed', 0, '2024-01-20', '2025-01-20', 98.50),
('CS-2024-005', 'OP-2024-005', 'VH-2024-005', 'valid', 'passed', 2, '2023-12-05', '2024-12-05', 78.25),
('CS-2024-006', 'OP-2024-006', 'VH-2024-006', 'expired', 'failed', 3, '2023-09-15', '2024-02-15', 45.75),
('CS-2024-007', 'OP-2024-007', 'VH-2024-007', 'valid', 'passed', 0, '2024-02-10', '2025-02-10', 92.00),
('CS-2024-008', 'OP-2024-008', 'VH-2024-008', 'pending', 'pending', 0, NULL, '2024-04-01', 88.50);

-- Insert sample inspection records
INSERT INTO inspection_records (inspection_id, vehicle_id, inspection_date, inspector_name, inspection_type, result, remarks, next_inspection_due) VALUES
('INS-2024-0001', 'VH-2024-001', '2024-01-15', 'Inspector John Doe', 'annual', 'pending', 'Scheduled for inspection', '2025-01-15'),
('INS-2024-0002', 'VH-2024-002', '2024-01-10', 'Inspector Jane Smith', 'renewal', 'passed', 'All systems check passed', '2025-01-10'),
('INS-2024-0003', 'VH-2024-003', '2024-02-01', 'Inspector Mike Wilson', 'spot_check', 'pending', 'Awaiting inspection', '2024-08-01'),
('INS-2024-0004', 'VH-2024-004', '2024-01-20', 'Inspector Sarah Brown', 'annual', 'passed', 'Vehicle in good condition', '2025-01-20'),
('INS-2024-0005', 'VH-2024-005', '2024-01-25', 'Inspector Tom Davis', 'renewal', 'failed', 'Brake system needs repair', '2024-07-25');

-- Insert violation analytics data
INSERT INTO violation_analytics (analytics_id, operator_id, vehicle_id, total_violations, last_violation_date, repeat_offender_flag, risk_level, compliance_score) VALUES
('VA-2024-001', 'OP-2024-001', 'VH-2024-001', 1, '2023-12-20', FALSE, 'low', 95.00),
('VA-2024-002', 'OP-2024-002', 'VH-2024-002', 1, '2024-01-15', FALSE, 'medium', 65.00),
('VA-2024-003', 'OP-2024-003', 'VH-2024-003', 1, '2024-02-10', FALSE, 'low', 85.00),
('VA-2024-004', 'OP-2024-004', 'VH-2024-004', 0, NULL, FALSE, 'low', 98.50),
('VA-2024-005', 'OP-2024-005', 'VH-2024-005', 2, '2024-01-25', FALSE, 'medium', 78.25),
('VA-2024-006', 'OP-2024-006', 'VH-2024-006', 3, '2024-02-05', TRUE, 'high', 45.75),
('VA-2024-007', 'OP-2024-007', 'VH-2024-007', 0, NULL, FALSE, 'low', 92.00),
('VA-2024-008', 'OP-2024-008', 'VH-2024-008', 0, NULL, FALSE, 'low', 88.50);

-- Franchise Application Workflow Tables
CREATE TABLE franchise_applications (
    application_id VARCHAR(20) PRIMARY KEY,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    application_type ENUM('new', 'renewal', 'amendment') NOT NULL,
    route_requested VARCHAR(200) NOT NULL,
    application_date DATE NOT NULL,
    status ENUM('submitted', 'under_review', 'approved', 'rejected', 'pending_documents') DEFAULT 'submitted',
    workflow_stage ENUM('initial_review', 'document_verification', 'field_inspection', 'approval', 'completed') DEFAULT 'initial_review',
    assigned_to VARCHAR(100),
    processing_timeline INT DEFAULT 30,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

CREATE TABLE application_documents (
    document_id VARCHAR(20) PRIMARY KEY,
    application_id VARCHAR(20) NOT NULL,
    document_type ENUM('license', 'insurance', 'vehicle_registration', 'medical_certificate', 'clearance') NOT NULL,
    document_name VARCHAR(200) NOT NULL,
    file_path VARCHAR(500),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by VARCHAR(100),
    verification_date TIMESTAMP NULL,
    remarks TEXT,
    FOREIGN KEY (application_id) REFERENCES franchise_applications(application_id) ON DELETE CASCADE
);

CREATE TABLE workflow_history (
    history_id VARCHAR(20) PRIMARY KEY,
    application_id VARCHAR(20) NOT NULL,
    stage_from ENUM('initial_review', 'document_verification', 'field_inspection', 'approval', 'completed'),
    stage_to ENUM('initial_review', 'document_verification', 'field_inspection', 'approval', 'completed'),
    action_taken VARCHAR(200) NOT NULL,
    processed_by VARCHAR(100) NOT NULL,
    processing_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    FOREIGN KEY (application_id) REFERENCES franchise_applications(application_id) ON DELETE CASCADE
);

-- Sample franchise applications
INSERT INTO franchise_applications (application_id, operator_id, vehicle_id, application_type, route_requested, application_date, status, workflow_stage, assigned_to) VALUES
('FA-2024-001', 'OP-2024-001', 'VH-2024-001', 'renewal', 'Quezon City - Manila', '2024-01-15', 'approved', 'completed', 'John Reviewer'),
('FA-2024-002', 'OP-2024-002', 'VH-2024-002', 'new', 'EDSA - Cubao', '2024-02-01', 'under_review', 'document_verification', 'Maria Validator'),
('FA-2024-003', 'OP-2024-003', 'VH-2024-003', 'new', 'Pasig - Ortigas', '2024-02-10', 'pending_documents', 'initial_review', 'Carlos Inspector'),
('FA-2024-004', 'OP-2024-004', 'VH-2024-004', 'amendment', 'Makati - BGC', '2024-02-15', 'submitted', 'initial_review', NULL);

-- Document Repository Tables
CREATE TABLE document_repository (
    document_id VARCHAR(20) PRIMARY KEY,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20),
    application_id VARCHAR(20),
    document_category ENUM('legal', 'permit', 'license', 'insurance', 'certificate', 'clearance') NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_name VARCHAR(200) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    file_type VARCHAR(50),
    version_number INT DEFAULT 1,
    issue_date DATE,
    expiry_date DATE,
    status ENUM('active', 'expired', 'revoked', 'pending') DEFAULT 'active',
    uploaded_by VARCHAR(100),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by VARCHAR(100),
    verification_date TIMESTAMP NULL,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    metadata JSON,
    remarks TEXT,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE SET NULL,
    FOREIGN KEY (application_id) REFERENCES franchise_applications(application_id) ON DELETE SET NULL
);

CREATE TABLE document_versions (
    version_id VARCHAR(20) PRIMARY KEY,
    document_id VARCHAR(20) NOT NULL,
    version_number INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_by VARCHAR(100) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    change_notes TEXT,
    FOREIGN KEY (document_id) REFERENCES document_repository(document_id) ON DELETE CASCADE
);

-- Sample document repository data
INSERT INTO document_repository (document_id, operator_id, vehicle_id, document_category, document_type, document_name, file_path, file_type, issue_date, expiry_date, uploaded_by, verification_status) VALUES
('DOC-2024-001', 'OP-2024-001', 'VH-2024-001', 'license', 'Driver License', 'Juan_Dela_Cruz_License.pdf', '/documents/licenses/juan_license.pdf', 'pdf', '2020-01-15', '2025-01-15', 'System Admin', 'verified'),
('DOC-2024-002', 'OP-2024-001', 'VH-2024-001', 'insurance', 'Vehicle Insurance', 'ABC1234_Insurance_Policy.pdf', '/documents/insurance/abc1234_insurance.pdf', 'pdf', '2024-01-01', '2024-12-31', 'System Admin', 'verified'),
('DOC-2024-003', 'OP-2024-002', 'VH-2024-002', 'permit', 'Franchise Permit', 'XYZ5678_Franchise_Permit.pdf', '/documents/permits/xyz5678_permit.pdf', 'pdf', '2023-06-01', '2026-06-01', 'System Admin', 'verified'),
('DOC-2024-004', 'OP-2024-003', 'VH-2024-003', 'certificate', 'Medical Certificate', 'Roberto_Garcia_Medical.pdf', '/documents/certificates/roberto_medical.pdf', 'pdf', '2024-01-10', '2024-07-10', 'System Admin', 'pending'),
('DOC-2024-005', 'OP-2024-004', 'VH-2024-004', 'clearance', 'Police Clearance', 'Ana_Reyes_Police_Clearance.pdf', '/documents/clearances/ana_clearance.pdf', 'pdf', '2024-02-01', '2024-08-01', 'System Admin', 'verified'),
('DOC-2024-006', 'OP-2024-005', 'VH-2024-005', 'legal', 'Vehicle Registration', 'JKL7890_Registration.pdf', '/documents/legal/jkl7890_registration.pdf', 'pdf', '2020-03-15', '2025-03-15', 'System Admin', 'verified');

-- Franchise Lifecycle Management Tables
CREATE TABLE franchise_lifecycle (
    lifecycle_id VARCHAR(20) PRIMARY KEY,
    franchise_id VARCHAR(20) NOT NULL,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    lifecycle_stage ENUM('application', 'processing', 'active', 'renewal', 'amendment', 'suspension', 'revocation', 'expired') NOT NULL,
    stage_date DATE NOT NULL,
    expiry_date DATE,
    renewal_due_date DATE,
    action_required ENUM('none', 'renewal', 'inspection', 'document_update', 'compliance_check') DEFAULT 'none',
    processed_by VARCHAR(100),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (franchise_id) REFERENCES franchise_records(franchise_id) ON DELETE CASCADE,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

CREATE TABLE lifecycle_actions (
    action_id VARCHAR(20) PRIMARY KEY,
    lifecycle_id VARCHAR(20) NOT NULL,
    action_type ENUM('approve', 'reject', 'suspend', 'revoke', 'renew', 'amend', 'restore') NOT NULL,
    action_date DATE NOT NULL,
    reason TEXT,
    processed_by VARCHAR(100) NOT NULL,
    supporting_documents TEXT,
    effective_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lifecycle_id) REFERENCES franchise_lifecycle(lifecycle_id) ON DELETE CASCADE
);

-- Sample franchise records
INSERT INTO franchise_records (franchise_id, operator_id, vehicle_id, franchise_number, issue_date, expiry_date, route_assigned, status) VALUES
('FR-2024-001', 'OP-2024-001', 'VH-2024-001', 'FN-QC-001-2024', '2021-01-15', '2024-01-15', 'Quezon City - Manila', 'valid'),
('FR-2024-002', 'OP-2024-002', 'VH-2024-002', 'FN-MN-002-2024', '2021-06-01', '2024-06-01', 'EDSA - Cubao', 'expired'),
('FR-2024-003', 'OP-2024-003', 'VH-2024-003', 'FN-PS-003-2024', '2022-03-15', '2025-03-15', 'Pasig - Ortigas', 'valid'),
('FR-2024-004', 'OP-2024-004', 'VH-2024-004', 'FN-MK-004-2024', '2023-01-20', '2026-01-20', 'Makati - BGC', 'valid'),
('FR-2024-005', 'OP-2024-005', 'VH-2024-005', 'FN-TG-005-2024', '2020-12-05', '2023-12-05', 'Taguig - Bonifacio', 'expired'),
('FR-2024-006', 'OP-2024-006', 'VH-2024-006', 'FN-PR-006-2024', '2019-09-15', '2022-09-15', 'Paranaque - Sucat', 'revoked');

-- Sample franchise lifecycle data
INSERT INTO franchise_lifecycle (lifecycle_id, franchise_id, operator_id, vehicle_id, lifecycle_stage, stage_date, expiry_date, renewal_due_date, action_required, processed_by) VALUES
('LC-2024-001', 'FR-2024-001', 'OP-2024-001', 'VH-2024-001', 'renewal', '2024-01-15', '2024-01-15', '2023-12-15', 'renewal', 'System Admin'),
('LC-2024-002', 'FR-2024-002', 'OP-2024-002', 'VH-2024-002', 'expired', '2024-06-01', '2024-06-01', '2024-05-01', 'renewal', 'System Admin'),
('LC-2024-003', 'FR-2024-003', 'OP-2024-003', 'VH-2024-003', 'active', '2022-03-15', '2025-03-15', '2025-02-15', 'none', 'System Admin'),
('LC-2024-004', 'FR-2024-004', 'OP-2024-004', 'VH-2024-004', 'active', '2023-01-20', '2026-01-20', '2025-12-20', 'none', 'System Admin'),
('LC-2024-005', 'FR-2024-005', 'OP-2024-005', 'VH-2024-005', 'expired', '2023-12-05', '2023-12-05', '2023-11-05', 'renewal', 'System Admin'),
('LC-2024-006', 'FR-2024-006', 'OP-2024-006', 'VH-2024-006', 'revocation', '2022-09-15', '2022-09-15', NULL, 'compliance_check', 'Legal Officer');

-- Sample lifecycle actions
INSERT INTO lifecycle_actions (action_id, lifecycle_id, action_type, action_date, reason, processed_by, effective_date) VALUES
('LA-2024-001', 'LC-2024-001', 'renew', '2024-01-20', 'Renewal application approved', 'John Approver', '2024-01-20'),
('LA-2024-002', 'LC-2024-006', 'revoke', '2022-09-15', 'Multiple violations and non-compliance', 'Legal Officer', '2022-09-15'),
('LA-2024-003', 'LC-2024-003', 'approve', '2022-03-15', 'Initial franchise approval', 'Maria Validator', '2022-03-15');

-- Route & Schedule Publication Tables
CREATE TABLE official_routes (
    route_id VARCHAR(20) PRIMARY KEY,
    route_name VARCHAR(200) NOT NULL,
    route_code VARCHAR(20) NOT NULL UNIQUE,
    origin VARCHAR(200) NOT NULL,
    destination VARCHAR(200) NOT NULL,
    route_description TEXT,
    distance_km DECIMAL(5,2),
    estimated_travel_time INT,
    fare_amount DECIMAL(6,2),
    status ENUM('active', 'inactive', 'under_review') DEFAULT 'active',
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE route_schedules (
    schedule_id VARCHAR(20) PRIMARY KEY,
    route_id VARCHAR(20) NOT NULL,
    franchise_id VARCHAR(20),
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    frequency_minutes INT DEFAULT 30,
    operating_days ENUM('weekdays', 'weekends', 'daily') DEFAULT 'daily',
    effective_date DATE NOT NULL,
    expiry_date DATE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    published_to_citizen BOOLEAN DEFAULT FALSE,
    published_date TIMESTAMP NULL,
    published_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES official_routes(route_id) ON DELETE CASCADE,
    FOREIGN KEY (franchise_id) REFERENCES franchise_records(franchise_id) ON DELETE SET NULL,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

CREATE TABLE route_waypoints (
    waypoint_id VARCHAR(20) PRIMARY KEY,
    route_id VARCHAR(20) NOT NULL,
    waypoint_name VARCHAR(200) NOT NULL,
    waypoint_order INT NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    estimated_time_from_origin INT,
    is_terminal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES official_routes(route_id) ON DELETE CASCADE
);

CREATE TABLE citizen_portal_publications (
    publication_id VARCHAR(20) PRIMARY KEY,
    route_id VARCHAR(20) NOT NULL,
    schedule_id VARCHAR(20) NOT NULL,
    publication_type ENUM('route', 'schedule', 'both') NOT NULL,
    publication_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_by VARCHAR(100) NOT NULL,
    citizen_portal_status ENUM('published', 'unpublished', 'archived') DEFAULT 'published',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES official_routes(route_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES route_schedules(schedule_id) ON DELETE CASCADE
);

-- Sample official routes
INSERT INTO official_routes (route_id, route_name, route_code, origin, destination, route_description, distance_km, estimated_travel_time, fare_amount, created_by) VALUES
('RT-2024-001', 'Quezon City - Manila Route', 'QC-MNL-01', 'Quezon City Circle', 'Manila City Hall', 'Main route connecting QC to Manila via EDSA', 15.5, 45, 25.00, 'Route Officer'),
('RT-2024-002', 'EDSA - Cubao Route', 'EDSA-CUB-01', 'EDSA Shrine', 'Araneta Coliseum', 'EDSA corridor serving Cubao area', 8.2, 25, 15.00, 'Route Officer'),
('RT-2024-003', 'Pasig - Ortigas Route', 'PSG-ORT-01', 'Pasig City Hall', 'Ortigas Center', 'Business district connector route', 12.3, 35, 20.00, 'Route Officer'),
('RT-2024-004', 'Makati - BGC Route', 'MKT-BGC-01', 'Makati CBD', 'Bonifacio Global City', 'Premium business district route', 10.8, 30, 30.00, 'Route Officer'),
('RT-2024-005', 'Taguig - Bonifacio Route', 'TGG-BON-01', 'Taguig Market', 'Fort Bonifacio', 'Local Taguig circulation route', 6.5, 20, 12.00, 'Route Officer');

-- Sample route schedules
INSERT INTO route_schedules (schedule_id, route_id, franchise_id, operator_id, vehicle_id, departure_time, arrival_time, frequency_minutes, operating_days, effective_date, published_to_citizen, published_by) VALUES
('SCH-2024-001', 'RT-2024-001', 'FR-2024-001', 'OP-2024-001', 'VH-2024-001', '05:00:00', '05:45:00', 30, 'daily', '2024-01-15', TRUE, 'Schedule Manager'),
('SCH-2024-002', 'RT-2024-002', 'FR-2024-002', 'OP-2024-002', 'VH-2024-002', '06:00:00', '06:25:00', 20, 'weekdays', '2024-02-01', TRUE, 'Schedule Manager'),
('SCH-2024-003', 'RT-2024-003', 'FR-2024-003', 'OP-2024-003', 'VH-2024-003', '05:30:00', '06:05:00', 25, 'daily', '2024-02-10', FALSE, NULL),
('SCH-2024-004', 'RT-2024-004', 'FR-2024-004', 'OP-2024-004', 'VH-2024-004', '07:00:00', '07:30:00', 15, 'weekdays', '2024-02-15', TRUE, 'Schedule Manager'),
('SCH-2024-005', 'RT-2024-005', 'FR-2024-005', 'OP-2024-005', 'VH-2024-005', '06:30:00', '06:50:00', 40, 'daily', '2024-01-01', FALSE, NULL);

-- Sample route waypoints
INSERT INTO route_waypoints (waypoint_id, route_id, waypoint_name, waypoint_order, estimated_time_from_origin, is_terminal) VALUES
('WP-2024-001', 'RT-2024-001', 'Quezon City Circle', 1, 0, TRUE),
('WP-2024-002', 'RT-2024-001', 'EDSA Quezon Ave', 2, 10, FALSE),
('WP-2024-003', 'RT-2024-001', 'EDSA Ortigas', 3, 25, FALSE),
('WP-2024-004', 'RT-2024-001', 'Manila City Hall', 4, 45, TRUE),
('WP-2024-005', 'RT-2024-002', 'EDSA Shrine', 1, 0, TRUE),
('WP-2024-006', 'RT-2024-002', 'Gateway Mall', 2, 10, FALSE),
('WP-2024-007', 'RT-2024-002', 'Araneta Coliseum', 3, 25, TRUE);

-- Sample citizen portal publications
INSERT INTO citizen_portal_publications (publication_id, route_id, schedule_id, publication_type, published_by, citizen_portal_status) VALUES
('PUB-2024-001', 'RT-2024-001', 'SCH-2024-001', 'both', 'Portal Admin', 'published'),
('PUB-2024-002', 'RT-2024-002', 'SCH-2024-002', 'both', 'Portal Admin', 'published'),
('PUB-2024-003', 'RT-2024-004', 'SCH-2024-004', 'both', 'Portal Admin', 'published');

-- OCR Ticket Digitization Tables
CREATE TABLE ocr_ticket_scans (
    scan_id VARCHAR(20) PRIMARY KEY,
    ticket_image_path VARCHAR(500) NOT NULL,
    scan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scanned_by VARCHAR(100) NOT NULL,
    ocr_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    ocr_confidence DECIMAL(5,2),
    processing_time_seconds INT,
    raw_ocr_data JSON,
    extracted_data JSON,
    validation_status ENUM('pending', 'validated', 'rejected', 'needs_review') DEFAULT 'pending',
    validated_by VARCHAR(100),
    validation_date TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE digitized_tickets (
    digitized_id VARCHAR(20) PRIMARY KEY,
    scan_id VARCHAR(20) NOT NULL,
    ticket_number VARCHAR(50) NOT NULL,
    plate_number VARCHAR(15),
    violation_type VARCHAR(100) NOT NULL,
    violation_date DATE NOT NULL,
    violation_time TIME,
    location VARCHAR(200) NOT NULL,
    fine_amount DECIMAL(10,2) NOT NULL,
    issuing_officer VARCHAR(100),
    operator_id VARCHAR(20),
    vehicle_id VARCHAR(20),
    linking_status ENUM('unlinked', 'linked', 'failed') DEFAULT 'unlinked',
    linking_confidence DECIMAL(5,2),
    manual_review_required BOOLEAN DEFAULT FALSE,
    reviewed_by VARCHAR(100),
    review_date TIMESTAMP NULL,
    status ENUM('pending', 'processed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scan_id) REFERENCES ocr_ticket_scans(scan_id) ON DELETE CASCADE,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE SET NULL
);

CREATE TABLE ocr_validation_queue (
    queue_id VARCHAR(20) PRIMARY KEY,
    scan_id VARCHAR(20) NOT NULL,
    digitized_id VARCHAR(20),
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    validation_type ENUM('ocr_accuracy', 'data_linking', 'manual_review') NOT NULL,
    assigned_to VARCHAR(100),
    queue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL,
    status ENUM('queued', 'in_progress', 'completed', 'failed') DEFAULT 'queued',
    notes TEXT,
    FOREIGN KEY (scan_id) REFERENCES ocr_ticket_scans(scan_id) ON DELETE CASCADE,
    FOREIGN KEY (digitized_id) REFERENCES digitized_tickets(digitized_id) ON DELETE CASCADE
);

-- Sample OCR ticket scans
INSERT INTO ocr_ticket_scans (scan_id, ticket_image_path, scanned_by, ocr_status, ocr_confidence, processing_time_seconds, validation_status, validated_by) VALUES
('OCR-2024-001', '/uploads/tickets/ticket_001.jpg', 'OCR Operator', 'completed', 95.50, 3, 'validated', 'Validation Officer'),
('OCR-2024-002', '/uploads/tickets/ticket_002.jpg', 'OCR Operator', 'completed', 87.25, 5, 'validated', 'Validation Officer'),
('OCR-2024-003', '/uploads/tickets/ticket_003.jpg', 'OCR Operator', 'completed', 92.75, 4, 'needs_review', NULL),
('OCR-2024-004', '/uploads/tickets/ticket_004.jpg', 'OCR Operator', 'failed', 45.00, 8, 'rejected', 'Validation Officer'),
('OCR-2024-005', '/uploads/tickets/ticket_005.jpg', 'OCR Operator', 'processing', NULL, NULL, 'pending', NULL);

-- Sample digitized tickets
INSERT INTO digitized_tickets (digitized_id, scan_id, ticket_number, plate_number, violation_type, violation_date, violation_time, location, fine_amount, issuing_officer, operator_id, vehicle_id, linking_status, linking_confidence, status) VALUES
('DIG-2024-001', 'OCR-2024-001', 'TCK-2024-0156', 'XYZ-5678', 'Overloading', '2024-01-15', '14:30:00', 'EDSA Cubao', 5000.00, 'Officer Santos', 'OP-2024-002', 'VH-2024-002', 'linked', 98.50, 'processed'),
('DIG-2024-002', 'OCR-2024-002', 'TCK-2024-0089', 'ABC-1234', 'Route Deviation', '2023-12-20', '09:15:00', 'Commonwealth Ave', 2500.00, 'Officer Cruz', 'OP-2024-001', 'VH-2024-001', 'linked', 95.75, 'processed'),
('DIG-2024-003', 'OCR-2024-003', 'TCK-2024-0234', 'DEF-9012', 'Speeding', '2024-02-10', '16:45:00', 'Quezon Ave', 1500.00, 'Officer Reyes', 'OP-2024-003', 'VH-2024-003', 'linked', 89.25, 'pending');

-- Sample validation queue
INSERT INTO ocr_validation_queue (queue_id, scan_id, digitized_id, priority, validation_type, assigned_to, status) VALUES
('QUE-2024-001', 'OCR-2024-003', 'DIG-2024-003', 'high', 'manual_review', 'Review Officer', 'queued'),
('QUE-2024-002', 'OCR-2024-004', NULL, 'medium', 'ocr_accuracy', 'OCR Specialist', 'in_progress'),
('QUE-2024-003', 'OCR-2024-005', NULL, 'low', 'ocr_accuracy', NULL, 'queued');

-- Additional sample violation records for testing
INSERT INTO violation_history (violation_id, operator_id, vehicle_id, violation_type, violation_date, fine_amount, settlement_status, location, ticket_number) VALUES
('VIO-2024-0301', 'OP-2024-004', 'VH-2024-004', 'No Franchise', '2024-03-01', 3000.00, 'unpaid', 'Makati CBD', 'TCK-2024-0301'),
('VIO-2024-0302', 'OP-2024-005', 'VH-2024-005', 'Expired Documents', '2024-03-02', 2000.00, 'paid', 'Taguig Market', 'TCK-2024-0302'),
('VIO-2024-0303', 'OP-2024-006', 'VH-2024-006', 'Reckless Driving', '2024-03-03', 4000.00, 'partial', 'Paranaque Road', 'TCK-2024-0303'),
('VIO-2024-0304', 'OP-2024-007', 'VH-2024-007', 'Illegal Parking', '2024-03-04', 1000.00, 'unpaid', 'Las Pinas Terminal', 'TCK-2024-0304'),
('VIO-2024-0305', 'OP-2024-008', 'VH-2024-008', 'Overloading', '2024-03-05', 5000.00, 'paid', 'Muntinlupa Ave', 'TCK-2024-0305'),
('VIO-2024-0306', 'OP-2024-001', 'VH-2024-001', 'Speeding', '2024-03-06', 1500.00, 'unpaid', 'Commonwealth Ave', 'TCK-2024-0306'),
('VIO-2024-0307', 'OP-2024-002', 'VH-2024-002', 'Route Deviation', '2024-03-07', 2500.00, 'partial', 'EDSA Cubao', 'TCK-2024-0307'),
('VIO-2024-0308', 'OP-2024-003', 'VH-2024-003', 'No Franchise', '2024-03-08', 3000.00, 'paid', 'Quezon Ave', 'TCK-2024-0308');

-- Update violation analytics with new data
UPDATE violation_analytics SET total_violations = 2, last_violation_date = '2024-03-06', risk_level = 'medium', compliance_score = 85.00 WHERE analytics_id = 'VA-2024-001';
UPDATE violation_analytics SET total_violations = 2, last_violation_date = '2024-03-07', risk_level = 'medium', compliance_score = 75.00 WHERE analytics_id = 'VA-2024-002';
UPDATE violation_analytics SET total_violations = 2, last_violation_date = '2024-03-08', risk_level = 'medium', compliance_score = 80.00 WHERE analytics_id = 'VA-2024-003';
UPDATE violation_analytics SET total_violations = 1, last_violation_date = '2024-03-01', risk_level = 'low', compliance_score = 90.00 WHERE analytics_id = 'VA-2024-004';
UPDATE violation_analytics SET total_violations = 3, last_violation_date = '2024-03-02', repeat_offender_flag = TRUE, risk_level = 'high', compliance_score = 70.00 WHERE analytics_id = 'VA-2024-005';
UPDATE violation_analytics SET total_violations = 4, last_violation_date = '2024-03-03', repeat_offender_flag = TRUE, risk_level = 'high', compliance_score = 40.00 WHERE analytics_id = 'VA-2024-006';
UPDATE violation_analytics SET total_violations = 1, last_violation_date = '2024-03-04', risk_level = 'low', compliance_score = 90.00 WHERE analytics_id = 'VA-2024-007';
UPDATE violation_analytics SET total_violations = 1, last_violation_date = '2024-03-05', risk_level = 'low', compliance_score = 95.00 WHERE analytics_id = 'VA-2024-008';

-- Update compliance status violation counts
UPDATE compliance_status SET violation_count = 2, compliance_score = 85.00 WHERE compliance_id = 'CS-2024-001';
UPDATE compliance_status SET violation_count = 2, compliance_score = 75.00 WHERE compliance_id = 'CS-2024-002';
UPDATE compliance_status SET violation_count = 2, compliance_score = 80.00 WHERE compliance_id = 'CS-2024-003';
UPDATE compliance_status SET violation_count = 1, compliance_score = 90.00 WHERE compliance_id = 'CS-2024-004';
UPDATE compliance_status SET violation_count = 3, compliance_score = 70.00 WHERE compliance_id = 'CS-2024-005';
UPDATE compliance_status SET violation_count = 4, compliance_score = 40.00 WHERE compliance_id = 'CS-2024-006';
UPDATE compliance_status SET violation_count = 1, compliance_score = 90.00 WHERE compliance_id = 'CS-2024-007';
UPDATE compliance_status SET violation_count = 1, compliance_score = 95.00 WHERE compliance_id = 'CS-2024-008';

-- Additional digitized tickets for linking
INSERT INTO digitized_tickets (digitized_id, scan_id, ticket_number, plate_number, violation_type, violation_date, violation_time, location, fine_amount, issuing_officer, linking_status, status) VALUES
('DIG-2024-004', 'OCR-2024-004', 'TCK-2024-0301', 'GHI-3456', 'No Franchise', '2024-03-01', '10:30:00', 'Makati CBD', 3000.00, 'Officer Martinez', 'unlinked', 'pending'),
('DIG-2024-005', 'OCR-2024-005', 'TCK-2024-0302', 'JKL-7890', 'Expired Documents', '2024-03-02', '14:15:00', 'Taguig Market', 2000.00, 'Officer Lopez', 'unlinked', 'pending');

-- Terminal Assignment Management Tables
CREATE TABLE terminals (
    terminal_id VARCHAR(20) PRIMARY KEY,
    terminal_name VARCHAR(200) NOT NULL,
    terminal_code VARCHAR(20) NOT NULL UNIQUE,
    location VARCHAR(200) NOT NULL,
    address TEXT NOT NULL,
    capacity INT NOT NULL,
    current_occupancy INT DEFAULT 0,
    terminal_type ENUM('main', 'sub', 'temporary') DEFAULT 'main',
    operating_hours VARCHAR(50),
    contact_person VARCHAR(100),
    contact_number VARCHAR(15),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE terminal_assignments (
    assignment_id VARCHAR(20) PRIMARY KEY,
    terminal_id VARCHAR(20) NOT NULL,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    franchise_id VARCHAR(20),
    assignment_type ENUM('permanent', 'temporary', 'toda') NOT NULL,
    route_assigned VARCHAR(200),
    start_date DATE NOT NULL,
    end_date DATE,
    assignment_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'suspended', 'expired') DEFAULT 'active',
    assigned_by VARCHAR(100) NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (terminal_id) REFERENCES terminals(terminal_id) ON DELETE CASCADE,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    FOREIGN KEY (franchise_id) REFERENCES franchise_records(franchise_id) ON DELETE SET NULL
);

CREATE TABLE terminal_capacity_tracking (
    tracking_id VARCHAR(20) PRIMARY KEY,
    terminal_id VARCHAR(20) NOT NULL,
    date_recorded DATE NOT NULL,
    total_capacity INT NOT NULL,
    occupied_slots INT NOT NULL,
    available_slots INT NOT NULL,
    utilization_rate DECIMAL(5,2) NOT NULL,
    peak_hours VARCHAR(100),
    recorded_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (terminal_id) REFERENCES terminals(terminal_id) ON DELETE CASCADE
);

-- Sample terminals data
INSERT INTO terminals (terminal_id, terminal_name, terminal_code, location, address, capacity, current_occupancy, terminal_type, operating_hours, contact_person, contact_number) VALUES
('TRM-2024-001', 'Quezon City Main Terminal', 'QC-MAIN-01', 'Quezon City Circle', '123 Quezon Circle, Quezon City', 50, 35, 'main', '24/7', 'Juan Manager', '+639123456789'),
('TRM-2024-002', 'EDSA Cubao Terminal', 'EDSA-CUB-01', 'EDSA Cubao', '456 EDSA Cubao, Quezon City', 30, 22, 'main', '5:00-22:00', 'Maria Supervisor', '+639176543210'),
('TRM-2024-003', 'Pasig Terminal', 'PSG-MAIN-01', 'Pasig City Hall', '789 Pasig City Hall, Pasig', 25, 18, 'main', '5:30-21:30', 'Roberto Chief', '+639987654321'),
('TRM-2024-004', 'Makati CBD Terminal', 'MKT-CBD-01', 'Makati Central Business District', '321 Makati CBD, Makati', 40, 28, 'main', '6:00-20:00', 'Ana Coordinator', '+639234567890'),
('TRM-2024-005', 'Taguig Sub Terminal', 'TGG-SUB-01', 'Taguig Market Area', '654 Taguig Market, Taguig', 20, 12, 'sub', '6:00-19:00', 'Carlos Assistant', '+639345678901');

-- Sample terminal assignments
INSERT INTO terminal_assignments (assignment_id, terminal_id, operator_id, vehicle_id, franchise_id, assignment_type, route_assigned, start_date, assignment_date, assigned_by, status) VALUES
('TA-2024-001', 'TRM-2024-001', 'OP-2024-001', 'VH-2024-001', 'FR-2024-001', 'permanent', 'Route 1 - Quezon City Circle to EDSA', '2024-01-15', '2024-01-15', 'Terminal Manager', 'active'),
('TA-2024-002', 'TRM-2024-002', 'OP-2024-002', 'VH-2024-002', 'FR-2024-002', 'permanent', 'Route 2 - EDSA Cubao to Fairview', '2024-02-01', '2024-02-01', 'Terminal Manager', 'active'),
('TA-2024-003', 'TRM-2024-003', 'OP-2024-003', 'VH-2024-003', 'FR-2024-003', 'toda', 'Route 3 - Pasig to Ortigas', '2024-02-10', '2024-02-10', 'TODA Officer', 'active'),
('TA-2024-004', 'TRM-2024-004', 'OP-2024-004', 'VH-2024-004', 'FR-2024-004', 'permanent', 'Route 4 - Makati CBD to BGC', '2024-02-15', '2024-02-15', 'Terminal Manager', 'active'),
('TA-2024-005', 'TRM-2024-005', 'OP-2024-005', 'VH-2024-005', 'FR-2024-005', 'temporary', 'Route 5 - Taguig Market to FTI', '2024-01-01', '2024-01-01', 'Terminal Manager', 'expired'),
('TA-2024-006', 'TRM-2024-001', 'OP-2024-006', 'VH-2024-006', 'FR-2024-006', 'permanent', 'Route 6 - Commonwealth to Fairview', '2022-09-15', '2022-09-15', 'Terminal Manager', 'suspended'),
('TA-2024-007', 'TRM-2024-002', 'OP-2024-007', 'VH-2024-007', NULL, 'toda', 'Route 7 - Cubao to Marikina', '2024-03-01', '2024-03-01', 'TODA Officer', 'active'),
('TA-2024-008', 'TRM-2024-003', 'OP-2024-008', 'VH-2024-008', NULL, 'temporary', 'Route 8 - Pasig to Antipolo', '2024-03-05', '2024-03-05', 'Terminal Manager', 'active');

-- Sample terminal capacity tracking
INSERT INTO terminal_capacity_tracking (tracking_id, terminal_id, date_recorded, total_capacity, occupied_slots, available_slots, utilization_rate, peak_hours, recorded_by) VALUES
('TCT-2024-001', 'TRM-2024-001', '2024-03-01', 50, 35, 15, 70.00, '7:00-9:00, 17:00-19:00', 'System Monitor'),
('TCT-2024-002', 'TRM-2024-002', '2024-03-01', 30, 22, 8, 73.33, '6:30-8:30, 16:30-18:30', 'System Monitor'),
('TCT-2024-003', 'TRM-2024-003', '2024-03-01', 25, 18, 7, 72.00, '7:30-9:30, 17:30-19:30', 'System Monitor'),
('TCT-2024-004', 'TRM-2024-004', '2024-03-01', 40, 28, 12, 70.00, '8:00-10:00, 18:00-20:00', 'System Monitor'),
('TCT-2024-005', 'TRM-2024-005', '2024-03-01', 20, 12, 8, 60.00, '7:00-9:00, 17:00-19:00', 'System Monitor');

-- Useful queries for terminal assignment management:

-- Terminal utilization report
-- SELECT t.terminal_name, t.capacity, t.current_occupancy, ROUND((t.current_occupancy / t.capacity) * 100, 2) as utilization_rate FROM terminals t WHERE t.status = 'active';

-- Active assignments by terminal
-- SELECT t.terminal_name, COUNT(ta.assignment_id) as active_assignments FROM terminals t LEFT JOIN terminal_assignments ta ON t.terminal_id = ta.terminal_id WHERE ta.status = 'active' GROUP BY t.terminal_id;

-- Operator terminal assignments
-- SELECT CONCAT(o.first_name, ' ', o.last_name) as operator_name, v.plate_number, t.terminal_name, ta.assignment_type, ta.status FROM terminal_assignments ta JOIN operators o ON ta.operator_id = o.operator_id JOIN vehicles v ON ta.vehicle_id = v.vehicle_id JOIN terminals t ON ta.terminal_id = t.terminal_id;

-- Useful queries for violation record management reporting:

-- Monthly violation summary
-- SELECT DATE_FORMAT(violation_date, '%Y-%m') as month, COUNT(*) as total_violations, SUM(fine_amount) as total_fines FROM violation_history GROUP BY month;

-- Top violators report  
-- SELECT o.operator_id, CONCAT(o.first_name, ' ', o.last_name) as name, va.total_violations, va.risk_level FROM violation_analytics va JOIN operators o ON va.operator_id = o.operator_id ORDER BY va.total_violations DESC;

-- Settlement tracking
-- SELECT settlement_status, COUNT(*) as count, SUM(fine_amount) as amount FROM violation_history GROUP BY settlement_status;

-- Revenue Integration Tables
CREATE TABLE revenue_collections (
    collection_id VARCHAR(20) PRIMARY KEY,
    violation_id VARCHAR(20) NOT NULL,
    operator_id VARCHAR(20) NOT NULL,
    vehicle_id VARCHAR(20) NOT NULL,
    collection_date DATE NOT NULL,
    amount_collected DECIMAL(10,2) NOT NULL,
    receipt_number VARCHAR(50),
    collected_by VARCHAR(100) NOT NULL,
    status ENUM('pending', 'verified', 'deposited') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (violation_id) REFERENCES violation_history(violation_id) ON DELETE CASCADE,
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE
);

CREATE TABLE revenue_reports (
    report_id VARCHAR(20) PRIMARY KEY,
    report_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'annual') NOT NULL,
    report_period_start DATE NOT NULL,
    report_period_end DATE NOT NULL,
    total_collections DECIMAL(12,2) NOT NULL,
    total_violations INT NOT NULL,
    settlement_rate DECIMAL(5,2) NOT NULL,
    generated_by VARCHAR(100) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(500)
);

-- Sample revenue collections
INSERT INTO revenue_collections (collection_id, violation_id, operator_id, vehicle_id, collection_date, amount_collected, receipt_number, collected_by, status) VALUES
('RC-2024-001', 'VIO-2024-0089', 'OP-2024-001', 'VH-2024-001', '2024-01-05', 2500.00, 'RCP-001', 'Finance Officer', 'deposited'),
('RC-2024-002', 'VIO-2024-0156', 'OP-2024-002', 'VH-2024-002', '2024-03-10', 5000.00, 'RCP-002', 'Finance Officer', 'verified'),
('RC-2024-003', 'VIO-2024-0234', 'OP-2024-003', 'VH-2024-003', '2024-03-12', 1500.00, 'RCP-003', 'Finance Officer', 'pending');

-- Sample revenue reports
INSERT INTO revenue_reports (report_id, report_type, report_period_start, report_period_end, total_collections, total_violations, settlement_rate, generated_by) VALUES
('RPT-2024-001', 'monthly', '2024-01-01', '2024-01-31', 15000.00, 8, 62.50, 'System Admin'),
('RPT-2024-002', 'monthly', '2024-02-01', '2024-02-29', 18500.00, 12, 58.33, 'System Admin'),
('RPT-2024-003', 'monthly', '2024-03-01', '2024-03-31', 22000.00, 15, 66.67, 'System Admin');

-- Update some operators to have inactive status for testing
UPDATE operators SET status = 'inactive' WHERE operator_id IN ('OP-2024-006', 'OP-2024-007');
UPDATE vehicles SET status = 'inactive' WHERE vehicle_id IN ('VH-2024-006', 'VH-2024-007');