-- Add LTO registration renewal system
ALTER TABLE lto_registrations 
ADD COLUMN IF NOT EXISTS registration_expiry DATE AFTER license_expiry,
ADD COLUMN IF NOT EXISTS renewal_count INT DEFAULT 0 AFTER registration_expiry,
ADD COLUMN IF NOT EXISTS color VARCHAR(50) AFTER body_type;

-- Create LTO renewal history table (without foreign key for now)
CREATE TABLE IF NOT EXISTS lto_renewal_history (
    renewal_id INT PRIMARY KEY AUTO_INCREMENT,
    lto_registration_id VARCHAR(20) NOT NULL,
    previous_expiry DATE NOT NULL,
    new_expiry DATE NOT NULL,
    renewal_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    renewal_fee DECIMAL(10,2) DEFAULT 0.00,
    processed_by VARCHAR(100)
);

-- Update existing records with registration expiry (1 year from registration date)
UPDATE lto_registrations 
SET registration_expiry = DATE_ADD(registration_date, INTERVAL 1 YEAR)
WHERE registration_expiry IS NULL;

-- Add index for expiry tracking
CREATE INDEX IF NOT EXISTS idx_registration_expiry ON lto_registrations(registration_expiry);
CREATE INDEX IF NOT EXISTS idx_renewal_date ON lto_renewal_history(renewal_date);

-- Add unique constraints for vehicle identification (ignore if already exists)
ALTER TABLE lto_registrations ADD UNIQUE INDEX IF NOT EXISTS unique_chassis_number (chassis_number);
ALTER TABLE lto_registrations ADD UNIQUE INDEX IF NOT EXISTS unique_engine_number (engine_number);
ALTER TABLE lto_registrations ADD UNIQUE INDEX IF NOT EXISTS unique_or_number (or_number);
ALTER TABLE lto_registrations ADD UNIQUE INDEX IF NOT EXISTS unique_cr_number (cr_number);
ALTER TABLE lto_registrations ADD UNIQUE INDEX IF NOT EXISTS unique_plate_number (plate_number);
ALTER TABLE lto_registrations ADD UNIQUE INDEX IF NOT EXISTS unique_insurance_policy (insurance_policy);