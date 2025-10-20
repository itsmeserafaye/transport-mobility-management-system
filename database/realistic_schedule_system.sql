-- Realistic Route Schedule System - Route-based with vehicle type adjustments

-- 1. Create route service standards table
CREATE TABLE IF NOT EXISTS route_service_standards (
    route_id VARCHAR(20) PRIMARY KEY,
    min_peak_frequency INT NOT NULL DEFAULT 15,
    min_off_peak_frequency INT NOT NULL DEFAULT 30,
    peak_hours_start TIME NOT NULL DEFAULT '06:00:00',
    peak_hours_end TIME NOT NULL DEFAULT '09:00:00',
    evening_peak_start TIME NOT NULL DEFAULT '17:00:00',
    evening_peak_end TIME NOT NULL DEFAULT '20:00:00',
    service_hours_start TIME NOT NULL DEFAULT '05:00:00',
    service_hours_end TIME NOT NULL DEFAULT '22:00:00',
    route_classification ENUM('urban', 'suburban', 'provincial') DEFAULT 'urban',
    FOREIGN KEY (route_id) REFERENCES official_routes(route_id)
);

-- 2. Create vehicle type frequency adjustments
CREATE TABLE IF NOT EXISTS vehicle_frequency_factors (
    vehicle_type VARCHAR(50) PRIMARY KEY,
    capacity_factor DECIMAL(3,2) NOT NULL DEFAULT 1.00,
    frequency_multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00
);

-- 3. Insert standard vehicle factors
INSERT INTO vehicle_frequency_factors VALUES
('jeepney', 1.00, 1.00),
('bus', 2.50, 1.50),
('mini_bus', 1.80, 1.25),
('uv_express', 0.60, 0.75),
('tricycle', 0.25, 0.50);

-- 4. Modify route_schedules to reference standards
ALTER TABLE route_schedules 
ADD COLUMN assigned_frequency_peak INT,
ADD COLUMN assigned_frequency_off_peak INT,
ADD COLUMN capacity_utilization DECIMAL(5,2) DEFAULT 75.00;

-- 5. Create function to calculate realistic frequency
DELIMITER //
CREATE FUNCTION calculate_frequency(
    base_frequency INT,
    vehicle_type VARCHAR(50),
    route_classification VARCHAR(20)
) RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE multiplier DECIMAL(3,2) DEFAULT 1.00;
    DECLARE class_factor DECIMAL(3,2) DEFAULT 1.00;
    
    SELECT frequency_multiplier INTO multiplier 
    FROM vehicle_frequency_factors 
    WHERE vehicle_type = vehicle_type;
    
    CASE route_classification
        WHEN 'urban' THEN SET class_factor = 1.00;
        WHEN 'suburban' THEN SET class_factor = 1.25;
        WHEN 'provincial' THEN SET class_factor = 1.50;
    END CASE;
    
    RETURN ROUND(base_frequency * multiplier * class_factor);
END//
DELIMITER ;

-- 6. Update existing schedules with realistic frequencies
UPDATE route_schedules rs
JOIN vehicles v ON rs.vehicle_id = v.vehicle_id
JOIN official_routes r ON rs.route_id = r.route_id
SET 
    rs.assigned_frequency_peak = calculate_frequency(15, v.vehicle_type, 'urban'),
    rs.assigned_frequency_off_peak = calculate_frequency(30, v.vehicle_type, 'urban');