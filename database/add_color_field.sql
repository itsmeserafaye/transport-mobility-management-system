-- Add color field to LTO registrations table
ALTER TABLE lto_registrations 
ADD COLUMN IF NOT EXISTS color VARCHAR(50) AFTER body_type;

-- Update existing records with default color
UPDATE lto_registrations 
SET color = 'White' 
WHERE color IS NULL OR color = '';