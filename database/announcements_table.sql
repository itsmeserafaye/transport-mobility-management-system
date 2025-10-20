-- Announcements table for administrator dashboard
-- Add this to your transport_and_mobility_management.sql file

CREATE TABLE announcements (
    announcement_id VARCHAR(20) PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(500) NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    target_audience ENUM('all', 'operators', 'citizens', 'staff') DEFAULT 'all',
    publish_date DATETIME NULL,
    expiry_date DATETIME NULL,
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sample announcements data
INSERT INTO announcements (announcement_id, title, content, priority, status, target_audience, publish_date, created_by) VALUES
('ANN-2024-001', 'System Maintenance Notice', 'The transport management system will undergo scheduled maintenance on March 15, 2024 from 2:00 AM to 6:00 AM. All services will be temporarily unavailable during this period.', 'high', 'published', 'all', '2024-03-10 08:00:00', 'System Administrator'),
('ANN-2024-002', 'New Franchise Application Process', 'Starting April 1, 2024, all franchise applications must be submitted through the new online portal. Please ensure all required documents are uploaded in PDF format.', 'medium', 'published', 'operators', '2024-03-12 09:00:00', 'Franchise Officer'),
('ANN-2024-003', 'Holiday Schedule Changes', 'Please be advised that during the Holy Week (March 24-31, 2024), inspection schedules will be adjusted. Check your notifications for updated appointment times.', 'medium', 'published', 'operators', '2024-03-15 10:00:00', 'Inspection Coordinator');