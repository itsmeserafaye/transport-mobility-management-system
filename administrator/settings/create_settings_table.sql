-- Create system_settings table for storing configuration settings
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `setting_description` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_description`) VALUES
-- System Information
('system_name', 'Transport & Mobility Management System', 'Name of the system'),
('system_description', 'Comprehensive transport management solution for Caloocan City', 'System description'),
('system_version', '1.0.0', 'Current system version'),

-- Organization Details
('organization_name', 'Caloocan City Government', 'Organization name'),
('department', 'Transport & Traffic Management Office', 'Department name'),
('website_url', 'https://caloocan.gov.ph', 'Organization website'),

-- Contact Information
('contact_email', 'admin@tmms.gov.ph', 'System contact email'),
('contact_phone', '+63 2 1234 5678', 'System contact phone'),
('fax_number', '+63 2 1234 5679', 'Fax number'),

-- Address Information
('address', '10th Avenue, Grace Park', 'Street address'),
('city', 'Caloocan City', 'City'),
('province', 'Metro Manila', 'Province or state'),
('postal_code', '1403', 'Postal code'),
('country', 'Philippines', 'Country'),

-- Regional & Format Settings
('timezone', 'Asia/Manila', 'System timezone'),
('language', 'en', 'System language'),
('date_format', 'Y-m-d', 'Date format preference'),
('time_format', 'H:i:s', 'Time format preference'),
('currency', 'PHP', 'System currency'),
('currency_symbol', '₱', 'Currency symbol'),

-- System Preferences
('records_per_page', '25', 'Default records per page'),
('file_upload_limit', '10', 'File upload limit in MB'),
('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png', 'Allowed file extensions'),

-- Maintenance & Notifications
('maintenance_mode', '0', 'Enable maintenance mode'),
('maintenance_message', 'System is under maintenance. Please try again later.', 'Maintenance mode message'),
('enable_notifications', '1', 'Enable system notifications'),
('email_notifications', '1', 'Enable email notifications'),
('sms_notifications', '0', 'Enable SMS notifications'),
('auto_logout', '1', 'Enable auto logout on inactivity'),

-- Development Settings
('debug_mode', '0', 'Enable debug mode'),

-- Security Settings
('session_timeout', '30', 'Session timeout in minutes'),
('password_min_length', '8', 'Minimum password length'),
('password_require_uppercase', '1', 'Require uppercase in passwords'),
('password_require_lowercase', '1', 'Require lowercase in passwords'),
('password_require_numbers', '1', 'Require numbers in passwords'),
('password_require_symbols', '0', 'Require symbols in passwords'),
('max_login_attempts', '5', 'Maximum login attempts before lockout'),
('lockout_duration', '15', 'Account lockout duration in minutes'),
('two_factor_auth', '0', 'Enable two-factor authentication'),
('backup_frequency', 'daily', 'Database backup frequency')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
updated_at = CURRENT_TIMESTAMP;