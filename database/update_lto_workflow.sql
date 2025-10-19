-- Update LTO Registration Workflow
-- Run this to fix the existing LTO registration system

-- Add missing status options and remarks field
ALTER TABLE lto_registrations 
MODIFY COLUMN status ENUM('pending', 'active', 'expired', 'suspended', 'rejected') DEFAULT 'pending';

-- Add remarks field if it doesn't exist
ALTER TABLE lto_registrations 
ADD COLUMN IF NOT EXISTS remarks TEXT AFTER status;

-- Update existing 'active' new registrations without plate numbers to 'pending'
UPDATE lto_registrations 
SET status = 'pending', 
    remarks = 'Awaiting LTO approval and plate assignment'
WHERE status = 'active' 
  AND registration_type = 'new' 
  AND (plate_number IS NULL OR plate_number = '');

-- Update existing registrations with plate numbers to remain active
UPDATE lto_registrations 
SET remarks = 'Registration approved and plate number assigned'
WHERE status = 'active' 
  AND plate_number IS NOT NULL 
  AND plate_number != '';

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_lto_status_type ON lto_registrations(status, registration_type);
CREATE INDEX IF NOT EXISTS idx_lto_pending_new ON lto_registrations(status, registration_type, plate_number);