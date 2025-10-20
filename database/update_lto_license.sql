-- Add license fields and registration expiry to LTO registrations table
ALTER TABLE lto_registrations 
ADD COLUMN IF NOT EXISTS license_number VARCHAR(20) AFTER owner_address,
ADD COLUMN IF NOT EXISTS license_expiry DATE AFTER license_number,
ADD COLUMN IF NOT EXISTS registration_expiry DATE AFTER license_expiry;

-- Add unique constraint for license number
ALTER TABLE lto_registrations 
ADD UNIQUE KEY IF NOT EXISTS unique_license_number (license_number);

-- Add indexes
CREATE INDEX IF NOT EXISTS idx_license_number ON lto_registrations(license_number);
CREATE INDEX IF NOT EXISTS idx_registration_expiry ON lto_registrations(registration_expiry);

-- Update existing records with placeholder data
UPDATE lto_registrations 
SET license_number = CONCAT('D12-34-', LPAD(FLOOR(RAND() * 1000000), 6, '0')),
    license_expiry = DATE_ADD(CURDATE(), INTERVAL 3 YEAR),
    registration_expiry = DATE_ADD(CURDATE(), INTERVAL 1 YEAR)
WHERE license_number IS NULL;