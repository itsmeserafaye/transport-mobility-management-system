<?php
require_once '../../../config/config.php';
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_security') {
        $session_timeout = $_POST['session_timeout'] ?? 30;
        $password_min_length = $_POST['password_min_length'] ?? 8;
        $password_require_uppercase = isset($_POST['password_require_uppercase']) ? 1 : 0;
        $password_require_lowercase = isset($_POST['password_require_lowercase']) ? 1 : 0;
        $password_require_numbers = isset($_POST['password_require_numbers']) ? 1 : 0;
        $password_require_symbols = isset($_POST['password_require_symbols']) ? 1 : 0;
        $max_login_attempts = $_POST['max_login_attempts'] ?? 5;
        $lockout_duration = $_POST['lockout_duration'] ?? 15;
        $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
        $backup_frequency = $_POST['backup_frequency'] ?? 'daily';
        
        try {
            $query = "INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES 
                     ('session_timeout', :session_timeout, NOW()),
                     ('password_min_length', :password_min_length, NOW()),
                     ('password_require_uppercase', :password_require_uppercase, NOW()),
                     ('password_require_lowercase', :password_require_lowercase, NOW()),
                     ('password_require_numbers', :password_require_numbers, NOW()),
                     ('password_require_symbols', :password_require_symbols, NOW()),
                     ('max_login_attempts', :max_login_attempts, NOW()),
                     ('lockout_duration', :lockout_duration, NOW()),
                     ('two_factor_auth', :two_factor_auth, NOW()),
                     ('backup_frequency', :backup_frequency, NOW())
                     ON DUPLICATE KEY UPDATE 
                     setting_value = VALUES(setting_value), updated_at = NOW()";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                'session_timeout' => $session_timeout,
                'password_min_length' => $password_min_length,
                'password_require_uppercase' => $password_require_uppercase,
                'password_require_lowercase' => $password_require_lowercase,
                'password_require_numbers' => $password_require_numbers,
                'password_require_symbols' => $password_require_symbols,
                'max_login_attempts' => $max_login_attempts,
                'lockout_duration' => $lockout_duration,
                'two_factor_auth' => $two_factor_auth,
                'backup_frequency' => $backup_frequency
            ]);
            
            $success_message = "Security settings updated successfully!";
        } catch (Exception $e) {
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
    'session_timeout' => getSetting($conn, 'session_timeout', '30'),
    'password_min_length' => getSetting($conn, 'password_min_length', '8'),
    'password_require_uppercase' => getSetting($conn, 'password_require_uppercase', '1'),
    'password_require_lowercase' => getSetting($conn, 'password_require_lowercase', '1'),
    'password_require_numbers' => getSetting($conn, 'password_require_numbers', '1'),
    'password_require_symbols' => getSetting($conn, 'password_require_symbols', '0'),
    'max_login_attempts' => getSetting($conn, 'max_login_attempts', '5'),
    'lockout_duration' => getSetting($conn, 'lockout_duration', '15'),
    'two_factor_auth' => getSetting($conn, 'two_factor_auth', '0'),
    'backup_frequency' => getSetting($conn, 'backup_frequency', 'daily')
];

// Get security statistics
$query = "SELECT COUNT(*) as failed_attempts FROM audit_logs WHERE action_type = 'login_failed' AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$stmt = $conn->prepare($query);
$stmt->execute();
$failed_logins_24h = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as active_sessions FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$stmt = $conn->prepare($query);
$stmt->execute();
$active_sessions = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings - Transport Management</title>
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
                        <h1 class="text-xl font-bold text-gray-900">Security Settings</h1>
                        <p class="text-sm text-gray-500">Configure security policies and authentication settings</p>
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

            <!-- Security Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                            <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Failed Logins (24h)</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo $failed_logins_24h; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i data-lucide="users" class="w-6 h-6 text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Active Sessions</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo $active_sessions; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i data-lucide="shield" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Security Status</p>
                            <p class="text-lg font-bold text-blue-600">Active</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Security Configuration</h2>
                    <p class="text-sm text-gray-600">Configure authentication and security policies</p>
                </div>

                <form method="POST" class="p-6 space-y-8">
                    <input type="hidden" name="action" value="update_security">

                    <!-- Session Management -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Session Management</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Session Timeout (minutes)</label>
                                <input type="number" name="session_timeout" value="<?php echo htmlspecialchars($settings['session_timeout']); ?>" 
                                       min="5" max="480" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Password Policy -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Password Policy</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Length</label>
                                <input type="number" name="password_min_length" value="<?php echo htmlspecialchars($settings['password_min_length']); ?>" 
                                       min="6" max="32" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div class="mt-4 space-y-3">
                            <label class="flex items-center">
                                <input type="checkbox" name="password_require_uppercase" <?php echo $settings['password_require_uppercase'] ? 'checked' : ''; ?> 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Require uppercase letters</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" name="password_require_lowercase" <?php echo $settings['password_require_lowercase'] ? 'checked' : ''; ?> 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Require lowercase letters</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" name="password_require_numbers" <?php echo $settings['password_require_numbers'] ? 'checked' : ''; ?> 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Require numbers</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" name="password_require_symbols" <?php echo $settings['password_require_symbols'] ? 'checked' : ''; ?> 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Require special characters</span>
                            </label>
                        </div>
                    </div>

                    <!-- Login Security -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Login Security</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Max Login Attempts</label>
                                <input type="number" name="max_login_attempts" value="<?php echo htmlspecialchars($settings['max_login_attempts']); ?>" 
                                       min="3" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Lockout Duration (minutes)</label>
                                <input type="number" name="lockout_duration" value="<?php echo htmlspecialchars($settings['lockout_duration']); ?>" 
                                       min="5" max="60" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="two_factor_auth" <?php echo $settings['two_factor_auth'] ? 'checked' : ''; ?> 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Enable Two-Factor Authentication</span>
                            </label>
                        </div>
                    </div>

                    <!-- Backup Settings -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Backup & Recovery</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Backup Frequency</label>
                                <select name="backup_frequency" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="daily" <?php echo $settings['backup_frequency'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="weekly" <?php echo $settings['backup_frequency'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="monthly" <?php echo $settings['backup_frequency'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i data-lucide="shield-check" class="w-4 h-4 inline mr-2"></i>
                            Update Security Settings
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