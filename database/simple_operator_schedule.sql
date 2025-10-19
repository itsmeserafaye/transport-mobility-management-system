-- Simple Operator Schedule System - For franchise management
-- Execute this single script only

-- 1. Simplify route_schedules for operator service commitments
ALTER TABLE route_schedules 
DROP COLUMN IF EXISTS departure_time,
DROP COLUMN IF EXISTS arrival_time,
DROP COLUMN IF EXISTS frequency_minutes,
DROP COLUMN IF EXISTS peak_frequency_minutes,
DROP COLUMN IF EXISTS off_peak_frequency_minutes,
DROP COLUMN IF EXISTS peak_hours_start,
DROP COLUMN IF EXISTS peak_hours_end,
DROP COLUMN IF EXISTS evening_peak_start,
DROP COLUMN IF EXISTS evening_peak_end,
ADD COLUMN IF NOT EXISTS service_start_time TIME NOT NULL DEFAULT '05:00:00',
ADD COLUMN IF NOT EXISTS service_end_time TIME NOT NULL DEFAULT '22:00:00',
ADD COLUMN IF NOT EXISTS service_frequency_minutes INT NOT NULL DEFAULT 30,
ADD COLUMN IF NOT EXISTS trips_per_day INT NOT NULL DEFAULT 20,
ADD COLUMN IF NOT EXISTS service_type ENUM('regular', 'express', 'limited') NOT NULL DEFAULT 'regular';

-- 2. Update existing schedules with simple operator commitments
UPDATE route_schedules 
SET service_start_time = '05:00:00',
    service_end_time = '22:00:00',
    service_frequency_minutes = 30,
    trips_per_day = 20,
    service_type = 'regular'
WHERE service_start_time IS NULL;