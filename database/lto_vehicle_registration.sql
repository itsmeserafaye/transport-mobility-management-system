-- LTO Vehicle Registration Tables
-- Government compliance and documentation tracking

CREATE TABLE IF NOT EXISTS lto_vehicle_registration (
    lto_registration_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id VARCHAR(20),
    operator_id VARCHAR(20),
    or_number VARCHAR(50) UNIQUE,
    cr_number VARCHAR(50) UNIQUE,
    plate_number VARCHAR(20),
    registration_type ENUM('new', 'renewal', 'transfer', 'duplicate') DEFAULT 'new',
    registration_date DATE,
    expiry_date DATE,
    lto_office VARCHAR(100),
    fees_paid DECIMAL(10,2),
    status ENUM('active', 'expired', 'suspended', 'cancelled') DEFAULT 'active',
    document_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id),
    FOREIGN KEY (operator_id) REFERENCES operators(operator_id)
);

CREATE TABLE IF NOT EXISTS lto_compliance_documents (
    compliance_id INT AUTO_INCREMENT PRIMARY KEY,
    lto_registration_id INT,
    document_type ENUM('or_cr', 'certificate_registration', 'emission_test', 'insurance', 'sticker') NOT NULL,
    document_number VARCHAR(100),
    issue_date DATE,
    expiry_date DATE,
    issuing_office VARCHAR(100),
    document_path VARCHAR(255),
    status ENUM('valid', 'expired', 'pending_renewal') DEFAULT 'valid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lto_registration_id) REFERENCES lto_vehicle_registration(lto_registration_id)
);