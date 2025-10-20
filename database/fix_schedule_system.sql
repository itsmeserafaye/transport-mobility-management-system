-- Fix Route Schedule System - Replace fixed times with service frequency model
-- Execute this SQL to update the scheduling system

-- 1. Modify route_schedules table structure
ALTER TABLE route_schedules 
DROP COLUMN departure_time,
DROP COLUMN arrival_time,
ADD COLUMN first_trip_time TIME NOT NULL DEFAULT '05:00:00',
ADD COLUMN last_trip_time TIME NOT NULL DEFAULT '22:00:00',
ADD COLUMN peak_frequency_minutes INT NOT NULL DEFAULT 15,
ADD COLUMN off_peak_frequency_minutes INT NOT NULL DEFAULT 30,
ADD COLUMN peak_hours_start TIME NOT NULL DEFAULT '06:00:00',
ADD COLUMN peak_hours_end TIME NOT NULL DEFAULT '09:00:00',
ADD COLUMN evening_peak_start TIME NOT NULL DEFAULT '17:00:00',
ADD COLUMN evening_peak_end TIME NOT NULL DEFAULT '20:00:00';

-- 2. Update existing schedules with new frequency model
UPDATE route_schedules 
SET first_trip_time = '05:00:00',
    last_trip_time = '22:00:00',
    peak_frequency_minutes = 15,
    off_peak_frequency_minutes = 30,
    peak_hours_start = '06:00:00',
    peak_hours_end = '09:00:00',
    evening_peak_start = '17:00:00',
    evening_peak_end = '20:00:00'
WHERE first_trip_time IS NULL;

-- 3. Remove frequency_minutes column (replaced by peak/off-peak frequencies)
ALTER TABLE route_schedules DROP COLUMN frequency_minutes;

-- 4. Add service_type column to distinguish different service patterns
ALTER TABLE route_schedules 
ADD COLUMN service_type ENUM('regular', 'express', 'limited') NOT NULL DEFAULT 'regular';

-- 5. Create view for easy schedule display
CREATE OR REPLACE VIEW schedule_display AS
SELECT 
    rs.schedule_id,
    rs.route_id,
    rs.operator_id,
    rs.vehicle_id,
    rs.operating_days,
    rs.first_trip_time,
    rs.last_trip_time,
    rs.peak_frequency_minutes,
    rs.off_peak_frequency_minutes,
    rs.peak_hours_start,
    rs.peak_hours_end,
    rs.evening_peak_start,
    rs.evening_peak_end,
    rs.service_type,
    rs.status,
    rs.published_to_citizen,
    r.route_name,
    r.route_code,
    CONCAT(o.first_name, ' ', o.last_name) as operator_name,
    v.plate_number,
    v.vehicle_type,
    -- Calculate total daily trips
    ROUND(
        (TIME_TO_SEC(TIMEDIFF(rs.peak_hours_end, rs.peak_hours_start)) / 60 / rs.peak_frequency_minutes) +
        (TIME_TO_SEC(TIMEDIFF(rs.evening_peak_end, rs.evening_peak_start)) / 60 / rs.peak_frequency_minutes) +
        (TIME_TO_SEC(TIMEDIFF(rs.last_trip_time, rs.first_trip_time)) / 60 / rs.off_peak_frequency_minutes) -
        (TIME_TO_SEC(TIMEDIFF(rs.peak_hours_end, rs.peak_hours_start)) / 60 / rs.off_peak_frequency_minutes) -
        (TIME_TO_SEC(TIMEDIFF(rs.evening_peak_end, rs.evening_peak_start)) / 60 / rs.off_peak_frequency_minutes)
    ) as estimated_daily_trips
FROM route_schedules rs
LEFT JOIN official_routes r ON rs.route_id = r.route_id
LEFT JOIN operators o ON rs.operator_id = o.operator_id
LEFT JOIN vehicles v ON rs.vehicle_id = v.vehicle_id;