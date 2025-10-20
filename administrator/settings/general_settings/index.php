<?php
require_once '../../../config/config.php';
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_general') {
        $settings_data = [
            'system_name' => $_POST['system_name'] ?? '',
            'system_description' => $_POST['system_description'] ?? '',
            'system_version' => $_POST['system_version'] ?? '',
            'organization_name' => $_POST['organization_name'] ?? '',
            'department' => $_POST['department'] ?? '',
            'contact_email' => $_POST['contact_email'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'fax_number' => $_POST['fax_number'] ?? '',
            'website_url' => $_POST['website_url'] ?? '',
            'address' => $_POST['address'] ?? '',
            'city' => $_POST['city'] ?? '',
            'province' => $_POST['province'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'country' => $_POST['country'] ?? '',
            'timezone' => $_POST['timezone'] ?? '',
            'date_format' => $_POST['date_format'] ?? '',
            'time_format' => $_POST['time_format'] ?? '',
            'currency' => $_POST['currency'] ?? '',
            'currency_symbol' => $_POST['currency_symbol'] ?? '',
            'language' => $_POST['language'] ?? '',
            'records_per_page' => $_POST['records_per_page'] ?? '',
            'file_upload_limit' => $_POST['file_upload_limit'] ?? '',
            'allowed_file_types' => $_POST['allowed_file_types'] ?? '',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'maintenance_message' => $_POST['maintenance_message'] ?? '',
            'enable_notifications' => isset($_POST['enable_notifications']) ? '1' : '0',
            'email_notifications' => isset($_POST['email_notifications']) ? '1' : '0',
            'sms_notifications' => isset($_POST['sms_notifications']) ? '1' : '0',
            'auto_logout' => isset($_POST['auto_logout']) ? '1' : '0',
            'debug_mode' => isset($_POST['debug_mode']) ? '1' : '0'
        ];
        
        try {
            $conn->beginTransaction();
            
            foreach ($settings_data as $key => $value) {
                $query = "INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES 
                         (:key, :value, NOW()) ON DUPLICATE KEY UPDATE 
                         setting_value = :value, updated_at = NOW()";
                
                $stmt = $conn->prepare($query);
                $stmt->execute(['key' => $key, 'value' => $value]);
            }
            
            $conn->commit();
            $success_message = "General settings updated successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating settings: " . $e->getMessage();
        }
    }
}

// Get current settings
function getSetting($conn, $key, $default = '') {
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = :key";
    $stmt = $conn->prepare($query);
    $stmt->execute(['key' => $key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : $default;
}

$settings = [
    'system_name' => getSetting($conn, 'system_name', 'Transport & Mobility Management System'),
    'system_description' => getSetting($conn, 'system_description', 'Comprehensive transport management solution'),
    'system_version' => getSetting($conn, 'system_version', '1.0.0'),
    'organization_name' => getSetting($conn, 'organization_name', 'Caloocan City Government'),
    'department' => getSetting($conn, 'department', 'Transport & Traffic Management Office'),
    'contact_email' => getSetting($conn, 'contact_email', 'admin@tmms.gov.ph'),
    'contact_phone' => getSetting($conn, 'contact_phone', '+63 2 1234 5678'),
    'fax_number' => getSetting($conn, 'fax_number', '+63 2 1234 5679'),
    'website_url' => getSetting($conn, 'website_url', 'https://caloocan.gov.ph'),
    'address' => getSetting($conn, 'address', '10th Avenue, Grace Park'),
    'city' => getSetting($conn, 'city', 'Caloocan City'),
    'province' => getSetting($conn, 'province', 'Metro Manila'),
    'postal_code' => getSetting($conn, 'postal_code', '1403'),
    'country' => getSetting($conn, 'country', 'Philippines'),
    'timezone' => getSetting($conn, 'timezone', 'Asia/Manila'),
    'date_format' => getSetting($conn, 'date_format', 'Y-m-d'),
    'time_format' => getSetting($conn, 'time_format', 'H:i:s'),
    'currency' => getSetting($conn, 'currency', 'PHP'),
    'currency_symbol' => getSetting($conn, 'currency_symbol', '₱'),
    'language' => getSetting($conn, 'language', 'en'),
    'records_per_page' => getSetting($conn, 'records_per_page', '25'),
    'file_upload_limit' => getSetting($conn, 'file_upload_limit', '10'),
    'allowed_file_types' => getSetting($conn, 'allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png'),
    'maintenance_mode' => getSetting($conn, 'maintenance_mode', '0'),
    'maintenance_message' => getSetting($conn, 'maintenance_message', 'System is under maintenance. Please try again later.'),
    'enable_notifications' => getSetting($conn, 'enable_notifications', '1'),
    'email_notifications' => getSetting($conn, 'email_notifications', '1'),
    'sms_notifications' => getSetting($conn, 'sms_notifications', '0'),
    'auto_logout' => getSetting($conn, 'auto_logout', '1'),
    'debug_mode' => getSetting($conn, 'debug_mode', '0')
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Settings - Transport Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="../../" class="text-gray-500 hover:text-gray-700">
                        <i data-lucide="arrow-left" class="w-6 h-6"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">General Settings</h1>
                        <p class="text-sm text-gray-500">Configure system-wide settings and preferences</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <?php if (isset($success_message)): ?>
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">System Configuration</h2>
                    <p class="text-sm text-gray-600">Update basic system information and preferences</p>
                </div>

                <form method="POST" class="p-6 space-y-8">
                    <input type="hidden" name="action" value="update_general">

                    <!-- System Information -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">System Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">System Name *</label>
                                <input type="text" name="system_name" value="<?php echo htmlspecialchars($settings['system_name']); ?>" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">System Version</label>
                                <input type="text" name="system_version" value="<?php echo htmlspecialchars($settings['system_version']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">System Description</label>
                                <textarea name="system_description" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($settings['system_description']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Organization Details -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Organization Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Organization Name</label>
                                <input type="text" name="organization_name" value="<?php echo htmlspecialchars($settings['organization_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                                <input type="text" name="department" value="<?php echo htmlspecialchars($settings['department']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Website URL</label>
                                <input type="url" name="website_url" value="<?php echo htmlspecialchars($settings['website_url']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Contact Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
                                <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                                <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fax Number</label>
                                <input type="text" name="fax_number" value="<?php echo htmlspecialchars($settings['fax_number']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Address Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Street Address</label>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($settings['address']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($settings['city']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Province/State</label>
                                <input type="text" name="province" value="<?php echo htmlspecialchars($settings['province']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                <input type="text" name="postal_code" value="<?php echo htmlspecialchars($settings['postal_code']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                <select name="country" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="Philippines" <?php echo $settings['country'] === 'Philippines' ? 'selected' : ''; ?>>Philippines</option>
                                    <option value="United States" <?php echo $settings['country'] === 'United States' ? 'selected' : ''; ?>>United States</option>
                                    <option value="Canada" <?php echo $settings['country'] === 'Canada' ? 'selected' : ''; ?>>Canada</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Regional & Format Settings -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Regional & Format Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                                <select name="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="Asia/Manila" <?php echo $settings['timezone'] === 'Asia/Manila' ? 'selected' : ''; ?>>Asia/Manila (GMT+8)</option>
                                    <option value="UTC" <?php echo $settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC (GMT+0)</option>
                                    <option value="America/New_York" <?php echo $settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (GMT-5)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                                <select name="language" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="en" <?php echo $settings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="fil" <?php echo $settings['language'] === 'fil' ? 'selected' : ''; ?>>Filipino</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date Format</label>
                                <select name="date_format" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="Y-m-d" <?php echo $settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                    <option value="m/d/Y" <?php echo $settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                    <option value="d/m/Y" <?php echo $settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Time Format</label>
                                <select name="time_format" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="H:i:s" <?php echo $settings['time_format'] === 'H:i:s' ? 'selected' : ''; ?>>24-hour (HH:MM:SS)</option>
                                    <option value="h:i:s A" <?php echo $settings['time_format'] === 'h:i:s A' ? 'selected' : ''; ?>>12-hour (HH:MM:SS AM/PM)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                                <select name="currency" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="PHP" <?php echo $settings['currency'] === 'PHP' ? 'selected' : ''; ?>>Philippine Peso (PHP)</option>
                                    <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol</label>
                                <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- System Preferences -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">System Preferences</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Records Per Page</label>
                                <select name="records_per_page" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="10" <?php echo $settings['records_per_page'] === '10' ? 'selected' : ''; ?>>10</option>
                                    <option value="25" <?php echo $settings['records_per_page'] === '25' ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?php echo $settings['records_per_page'] === '50' ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $settings['records_per_page'] === '100' ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">File Upload Limit (MB)</label>
                                <input type="number" name="file_upload_limit" value="<?php echo htmlspecialchars($settings['file_upload_limit']); ?>" min="1" max="100"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Allowed File Types</label>
                                <input type="text" name="allowed_file_types" value="<?php echo htmlspecialchars($settings['allowed_file_types']); ?>"
                                       placeholder="pdf,doc,docx,jpg,jpeg,png"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Separate file extensions with commas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance & Notifications -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Maintenance & Notifications</h3>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Enable Maintenance Mode</span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Maintenance Message</label>
                                <textarea name="maintenance_message" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($settings['maintenance_message']); ?></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-center">
                                    <input type="checkbox" name="enable_notifications" <?php echo $settings['enable_notifications'] ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Enable System Notifications</span>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="email_notifications" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Email Notifications</span>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="sms_notifications" <?php echo $settings['sms_notifications'] ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">SMS Notifications</span>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="auto_logout" <?php echo $settings['auto_logout'] ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Auto Logout on Inactivity</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Development Settings -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Development Settings</h3>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" name="debug_mode" <?php echo $settings['debug_mode'] ? 'checked' : ''; ?>
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="ml-2 text-sm text-gray-700">Enable Debug Mode</span>
                                <span class="ml-2 text-xs text-red-500">(Not recommended for production)</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>