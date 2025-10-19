-- Update franchise_applications table to support LTO-based applications
ALTER TABLE franchise_applications 
ADD COLUMN IF NOT EXISTS operator_lto_id INT AFTER vehicle_seating_capacity,
ADD COLUMN IF NOT EXISTS vehicle_lto_id INT AFTER operator_lto_id,
ADD COLUMN IF NOT EXISTS parent_application_id VARCHAR(20) AFTER vehicle_lto_id,
ADD COLUMN IF NOT EXISTS renewal_period INT DEFAULT 1 AFTER parent_application_id;

-- Add foreign key constraints
ALTER TABLE franchise_applications 
ADD CONSTRAINT IF NOT EXISTS fk_operator_lto 
FOREIGN KEY (operator_lto_id) REFERENCES lto_registrations(lto_registration_id) ON DELETE SET NULL,
ADD CONSTRAINT IF NOT EXISTS fk_vehicle_lto 
FOREIGN KEY (vehicle_lto_id) REFERENCES lto_registrations(lto_registration_id) ON DELETE SET NULL;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_operator_lto_id ON franchise_applications(operator_lto_id);
CREATE INDEX IF NOT EXISTS idx_vehicle_lto_id ON franchise_applications(vehicle_lto_id);
CREATE INDEX IF NOT EXISTS idx_parent_application ON franchise_applications(parent_application_id);