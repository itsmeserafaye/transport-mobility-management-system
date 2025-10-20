-- Update Franchise Application Constraints
-- Execute this SQL to add validation constraints and indexes
-- Note: Run each statement individually if errors occur

-- 1. Add unique constraints (ignore errors if already exist)
ALTER TABLE franchise_applications ADD CONSTRAINT unique_plate_per_application UNIQUE (vehicle_plate_number);
ALTER TABLE franchise_applications ADD CONSTRAINT unique_engine_per_application UNIQUE (vehicle_engine_number);
ALTER TABLE franchise_applications ADD CONSTRAINT unique_chassis_per_application UNIQUE (vehicle_chassis_number);

-- 2. Add indexes for better performance (ignore errors if already exist)
CREATE INDEX idx_operators_license ON operators(license_number);
CREATE INDEX idx_vehicles_plate ON vehicles(plate_number);
CREATE INDEX idx_applications_license ON franchise_applications(operator_license_number);