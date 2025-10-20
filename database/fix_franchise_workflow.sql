-- Fix Franchise Workflow - Application creates PUV Database entries
-- Execute this SQL to restructure the workflow

-- 1. Add all operator fields to franchise_applications table
ALTER TABLE franchise_applications 
ADD COLUMN IF NOT EXISTS operator_first_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS operator_last_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS operator_address TEXT,
ADD COLUMN IF NOT EXISTS operator_contact_number VARCHAR(20),
ADD COLUMN IF NOT EXISTS operator_license_number VARCHAR(50),
ADD COLUMN IF NOT EXISTS operator_license_expiry DATE,
ADD COLUMN IF NOT EXISTS operator_email VARCHAR(100),
ADD COLUMN IF NOT EXISTS operator_date_of_birth DATE,
ADD COLUMN IF NOT EXISTS operator_emergency_contact VARCHAR(100),
ADD COLUMN IF NOT EXISTS operator_emergency_phone VARCHAR(20);

-- 2. Add all vehicle fields to franchise_applications table
ALTER TABLE franchise_applications 
ADD COLUMN IF NOT EXISTS vehicle_plate_number VARCHAR(20),
ADD COLUMN IF NOT EXISTS vehicle_type VARCHAR(50),
ADD COLUMN IF NOT EXISTS vehicle_make VARCHAR(50),
ADD COLUMN IF NOT EXISTS vehicle_model VARCHAR(50),
ADD COLUMN IF NOT EXISTS vehicle_year_manufactured YEAR,
ADD COLUMN IF NOT EXISTS vehicle_engine_number VARCHAR(50),
ADD COLUMN IF NOT EXISTS vehicle_chassis_number VARCHAR(50),
ADD COLUMN IF NOT EXISTS vehicle_color VARCHAR(30),
ADD COLUMN IF NOT EXISTS vehicle_seating_capacity INT,
ADD COLUMN IF NOT EXISTS vehicle_fuel_type VARCHAR(20),
ADD COLUMN IF NOT EXISTS vehicle_body_type VARCHAR(50);

-- 3. Add status tracking for PUV database creation
ALTER TABLE franchise_applications 
ADD COLUMN IF NOT EXISTS puv_entry_created BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS puv_operator_id VARCHAR(20),
ADD COLUMN IF NOT EXISTS puv_vehicle_id VARCHAR(20);

-- 4. Add status field to operators and vehicles for workflow tracking
ALTER TABLE operators 
ADD COLUMN IF NOT EXISTS entry_source ENUM('direct', 'franchise_application') DEFAULT 'direct',
ADD COLUMN IF NOT EXISTS application_id VARCHAR(20);

ALTER TABLE vehicles 
ADD COLUMN IF NOT EXISTS entry_source ENUM('direct', 'franchise_application') DEFAULT 'direct',
ADD COLUMN IF NOT EXISTS application_id VARCHAR(20);

-- 5. Create trigger to auto-create PUV entries when franchise is approved
DELIMITER //
CREATE TRIGGER create_puv_entries_on_approval
AFTER UPDATE ON franchise_applications
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' AND OLD.status != 'approved' AND NEW.puv_entry_created = FALSE THEN
        -- Check if operator already exists by license number
        SET @existing_operator_id = (SELECT operator_id FROM operators WHERE license_number = NEW.operator_license_number LIMIT 1);
        
        -- If operator doesn't exist, create new operator
        IF @existing_operator_id IS NULL THEN
            SET @operator_id = CONCAT('OP-', YEAR(NOW()), '-', LPAD((SELECT COUNT(*) + 1 FROM operators WHERE operator_id LIKE CONCAT('OP-', YEAR(NOW()), '-%')), 3, '0'));
            
            INSERT INTO operators (
                operator_id, first_name, last_name, address, contact_number, 
                license_number, license_expiry, email, date_of_birth, 
                emergency_contact, emergency_phone, entry_source, application_id, status
            ) VALUES (
                @operator_id, NEW.operator_first_name, NEW.operator_last_name, 
                NEW.operator_address, NEW.operator_contact_number, NEW.operator_license_number, 
                NEW.operator_license_expiry, NEW.operator_email, NEW.operator_date_of_birth,
                NEW.operator_emergency_contact, NEW.operator_emergency_phone, 
                'franchise_application', NEW.application_id, 'active'
            );
        ELSE
            -- Use existing operator
            SET @operator_id = @existing_operator_id;
        END IF;
        
        -- Always create new vehicle entry (each application = one vehicle)
        SET @vehicle_id = CONCAT('VH-', YEAR(NOW()), '-', LPAD((SELECT COUNT(*) + 1 FROM vehicles WHERE vehicle_id LIKE CONCAT('VH-', YEAR(NOW()), '-%')), 3, '0'));
        SET @compliance_id = CONCAT('CS-', YEAR(NOW()), '-', LPAD((SELECT COUNT(*) + 1 FROM compliance_status WHERE compliance_id LIKE CONCAT('CS-', YEAR(NOW()), '-%')), 3, '0'));
        
        INSERT INTO vehicles (
            vehicle_id, operator_id, plate_number, vehicle_type, make, model,
            year_manufactured, engine_number, chassis_number, color, seating_capacity,
            fuel_type, body_type, entry_source, application_id, status
        ) VALUES (
            @vehicle_id, @operator_id, NEW.vehicle_plate_number, NEW.vehicle_type,
            NEW.vehicle_make, NEW.vehicle_model, NEW.vehicle_year_manufactured,
            NEW.vehicle_engine_number, NEW.vehicle_chassis_number, NEW.vehicle_color,
            NEW.vehicle_seating_capacity, NEW.vehicle_fuel_type, NEW.vehicle_body_type,
            'franchise_application', NEW.application_id, 'active'
        );
        
        -- Create compliance status entry
        INSERT INTO compliance_status (
            compliance_id, operator_id, vehicle_id, franchise_status, 
            inspection_status, violation_count, compliance_score
        ) VALUES (
            @compliance_id, @operator_id, @vehicle_id, 'valid', 'pending', 0, 85.00
        );
        
        -- Update franchise application with created IDs
        UPDATE franchise_applications 
        SET puv_entry_created = TRUE, puv_operator_id = @operator_id, puv_vehicle_id = @vehicle_id
        WHERE application_id = NEW.application_id;
    END IF;
END//
DELIMITER ;

-- 6. Add missing vehicle fields to vehicles table if they don't exist
ALTER TABLE vehicles 
ADD COLUMN IF NOT EXISTS fuel_type VARCHAR(20) DEFAULT 'gasoline',
ADD COLUMN IF NOT EXISTS body_type VARCHAR(50) DEFAULT 'standard';

-- 7. Add missing operator fields to operators table if they don't exist
ALTER TABLE operators 
ADD COLUMN IF NOT EXISTS email VARCHAR(100),
ADD COLUMN IF NOT EXISTS date_of_birth DATE,
ADD COLUMN IF NOT EXISTS emergency_contact VARCHAR(100),
ADD COLUMN IF NOT EXISTS emergency_phone VARCHAR(20);

-- 8. Add unique constraints to prevent duplicate entries
ALTER TABLE franchise_applications 
ADD CONSTRAINT unique_plate_per_application UNIQUE (vehicle_plate_number),
ADD CONSTRAINT unique_engine_per_application UNIQUE (vehicle_engine_number),
ADD CONSTRAINT unique_chassis_per_application UNIQUE (vehicle_chassis_number);

-- 9. Add indexes for better performance on license number lookups
CREATE INDEX idx_operators_license ON operators(license_number);
CREATE INDEX idx_vehicles_plate ON vehicles(plate_number);
CREATE INDEX idx_applications_license ON franchise_applications(operator_license_number);

-- 10. Add comment explaining multiple applications policy
-- BUSINESS RULE: Same operator can have multiple franchise applications for different vehicles
-- Each application represents one vehicle franchise request
-- Operator records are reused if license number matches existing operator