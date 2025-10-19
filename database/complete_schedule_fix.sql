-- Complete Schedule System Fix - Realistic route-based with vehicle adjustments
-- Execute this single script only

-- 1. Remove old fixed-time columns and add new frequency model
ALTER TABLE route_schedules 
DROP COLUMN IF EXISTS departure_time,
DROP COLUMN IF EXISTS arrival_time,
DROP COLUMN IF EXISTS frequency_minutes,
ADD COLUMN IF NOT EXISTS first_trip_time TIME NOT NULL DEFAULT '05:00:00',
ADD COLUMN IF NOT EXISTS last_trip_time TIME NOT NULL DEFAULT '22:00:00',
ADD COLUMN IF NOT EXISTS service_type ENUM('regular', 'express', 'limited') NOT NULL DEFAULT 'regular',
ADD COLUMN IF NOT EXISTS assigned_frequency_peak INT NOT NULL DEFAULT 15,
ADD COLUMN IF NOT EXISTS assigned_frequency_off_peak INT NOT NULL DEFAULT 30,
ADD COLUMN IF NOT EXISTS peak_hours_start TIME NOT NULL DEFAULT '06:00:00',
ADD COLUMN IF NOT EXISTS peak_hours_end TIME NOT NULL DEFAULT '09:00:00',
ADD COLUMN IF NOT EXISTS evening_peak_start TIME NOT NULL DEFAULT '17:00:00',
ADD COLUMN IF NOT EXISTS evening_peak_end TIME NOT NULL DEFAULT '20:00:00';

-- 2. Create route service standards
CREATE TABLE IF NOT EXISTS route_service_standards (
    route_id VARCHAR(20) PRIMARY KEY,
    min_peak_frequency INT NOT NULL DEFAULT 15,
    min_off_peak_frequency INT NOT NULL DEFAULT 30,
    route_classification ENUM('urban', 'suburban', 'provincial') DEFAULT 'urban',
    FOREIGN KEY (route_id) REFERENCES official_routes(route_id)
);

-- 3. Create vehicle frequency factors
CREATE TABLE IF NOT EXISTS vehicle_frequency_factors (
    vehicle_type VARCHAR(50) PRIMARY KEY,
    frequency_multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00
);

INSERT IGNORE INTO vehicle_frequency_factors VALUES
('jeepney', 1.00),
('bus', 1.50),
('mini_bus', 1.25),
('uv_express', 0.75),
('tricycle', 0.50);

-- 4. Populate route standards for existing routes
INSERT IGNORE INTO route_service_standards (route_id, min_peak_frequency, min_off_peak_frequency, route_classification)
SELECT route_id, 15, 30, 'urban' FROM official_routes;

-- 5. Update existing schedules with realistic frequencies
UPDATE route_schedules rs
JOIN vehicles v ON rs.vehicle_id = v.vehicle_id
JOIN vehicle_frequency_factors vff ON v.vehicle_type = vff.vehicle_type
JOIN route_service_standards rss ON rs.route_id = rss.route_id
SET 
    rs.assigned_frequency_peak = ROUND(rss.min_peak_frequency * vff.frequency_multiplier),
    rs.assigned_frequency_off_peak = ROUND(rss.min_off_peak_frequency * vff.frequency_multiplier),
    rs.first_trip_time = '05:00:00',
    rs.last_trip_time = '22:00:00',
    rs.peak_hours_start = '06:00:00',
    rs.peak_hours_end = '09:00:00',
    rs.evening_peak_start = '17:00:00',
    rs.evening_peak_end = '20:00:00';