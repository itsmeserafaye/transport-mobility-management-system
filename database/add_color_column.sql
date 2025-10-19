-- Add color column to vehicles table
ALTER TABLE vehicles ADD COLUMN color VARCHAR(50) AFTER chassis_number;