<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'get_setting':
                $key = $_POST['key'] ?? '';
                if (empty($key)) {
                    throw new Exception('Setting key is required');
                }
                
                $query = "SELECT setting_value FROM system_settings WHERE setting_key = :key";
                $stmt = $conn->prepare($query);
                $stmt->execute(['key' => $key]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'value' => $result ? $result['setting_value'] : null
                ]);
                break;
                
            case 'update_setting':
                $key = $_POST['key'] ?? '';
                $value = $_POST['value'] ?? '';
                
                if (empty($key)) {
                    throw new Exception('Setting key is required');
                }
                
                $query = "INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                         VALUES (:key, :value, NOW()) 
                         ON DUPLICATE KEY UPDATE 
                         setting_value = :value, updated_at = NOW()";
                
                $stmt = $conn->prepare($query);
                $stmt->execute(['key' => $key, 'value' => $value]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Setting updated successfully'
                ]);
                break;
                
            case 'get_all_settings':
                $query = "SELECT setting_key, setting_value FROM system_settings";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                
                echo json_encode([
                    'success' => true,
                    'settings' => $settings
                ]);
                break;
                
            case 'reset_to_defaults':
                // Reset to default values
                $defaults = [
                    // System Information
                    'system_name' => 'Transport & Mobility Management System',
                    'system_description' => 'Comprehensive transport management solution for Caloocan City',
                    'system_version' => '1.0.0',
                    // Organization Details
                    'organization_name' => 'Caloocan City Government',
                    'department' => 'Transport & Traffic Management Office',
                    'website_url' => 'https://caloocan.gov.ph',
                    // Contact Information
                    'contact_email' => 'admin@tmms.gov.ph',
                    'contact_phone' => '+63 2 1234 5678',
                    'fax_number' => '+63 2 1234 5679',
                    // Address Information
                    'address' => '10th Avenue, Grace Park',
                    'city' => 'Caloocan City',
                    'province' => 'Metro Manila',
                    'postal_code' => '1403',
                    'country' => 'Philippines',
                    // Regional & Format Settings
                    'timezone' => 'Asia/Manila',
                    'language' => 'en',
                    'date_format' => 'Y-m-d',
                    'time_format' => 'H:i:s',
                    'currency' => 'PHP',
                    'currency_symbol' => '₱',
                    // System Preferences
                    'records_per_page' => '25',
                    'file_upload_limit' => '10',
                    'allowed_file_types' => 'pdf,doc,docx,jpg,jpeg,png',
                    // Maintenance & Notifications
                    'maintenance_mode' => '0',
                    'maintenance_message' => 'System is under maintenance. Please try again later.',
                    'enable_notifications' => '1',
                    'email_notifications' => '1',
                    'sms_notifications' => '0',
                    'auto_logout' => '1',
                    // Development Settings
                    'debug_mode' => '0',
                    // Security Settings
                    'session_timeout' => '30',
                    'password_min_length' => '8',
                    'password_require_uppercase' => '1',
                    'password_require_lowercase' => '1',
                    'password_require_numbers' => '1',
                    'password_require_symbols' => '0',
                    'max_login_attempts' => '5',
                    'lockout_duration' => '15',
                    'two_factor_auth' => '0',
                    'backup_frequency' => 'daily'
                ];
                
                $conn->beginTransaction();
                
                foreach ($defaults as $key => $value) {
                    $query = "INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                             VALUES (:key, :value, NOW()) 
                             ON DUPLICATE KEY UPDATE 
                             setting_value = :value, updated_at = NOW()";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute(['key' => $key, 'value' => $value]);
                }
                
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Settings reset to defaults successfully'
                ]);
                break;
                
            case 'export_settings':
                $query = "SELECT setting_key, setting_value, setting_description, updated_at FROM system_settings ORDER BY setting_key";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $filename = 'system_settings_' . date('Y-m-d_H-i-s') . '.json';
                
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                echo json_encode([
                    'export_date' => date('Y-m-d H:i:s'),
                    'settings' => $settings
                ], JSON_PRETTY_PRINT);
                exit;
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>