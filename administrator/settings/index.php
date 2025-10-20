<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Get current settings for overview
function getSetting($conn, $key, $default = '') {
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = :key";
    $stmt = $conn->prepare($query);
    $stmt->execute(['key' => $key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : $default;
}

$system_name = getSetting($conn, 'system_name', 'Transport & Mobility Management System');
$last_backup = getSetting($conn, 'last_backup_date', 'Never');
$session_timeout = getSetting($conn, 'session_timeout', '30');
$two_factor_enabled = getSetting($conn, 'two_factor_auth', '0');

// Get settings statistics
$query = "SELECT COUNT(*) as total_settings FROM system_settings";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_settings = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as recent_updates FROM system_settings WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_updates = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Transport Management</title>
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
                    <a href="../" class="text-gray-500 hover:text-gray-700">
                        <i data-lucide="arrow-left" class="w-6 h-6"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">System Settings</h1>
                        <p class="text-sm text-gray-500">Configure system preferences and security policies</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="exportSettings()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        <i data-lucide="download" class="w-4 h-4 inline mr-2"></i>
                        Export
                    </button>
                    <button onclick="resetToDefaults()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        <i data-lucide="refresh-cw" class="w-4 h-4 inline mr-2"></i>
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i data-lucide="settings" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Settings</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo $total_settings; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i data-lucide="clock" class="w-6 h-6 text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Session Timeout</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo $session_timeout; ?>m</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-<?php echo $two_factor_enabled ? 'green' : 'red'; ?>-100 rounded-lg flex items-center justify-center mr-4">
                            <i data-lucide="shield" class="w-6 h-6 text-<?php echo $two_factor_enabled ? 'green' : 'red'; ?>-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">2FA Status</p>
                            <p class="text-lg font-bold text-<?php echo $two_factor_enabled ? 'green' : 'red'; ?>-600">
                                <?php echo $two_factor_enabled ? 'Enabled' : 'Disabled'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                            <i data-lucide="activity" class="w-6 h-6 text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Recent Updates</p>
                            <p class="text-2xl font-bold text-orange-600"><?php echo $recent_updates; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Modules -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- General Settings -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                <i data-lucide="settings" class="w-6 h-6 text-blue-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">General Settings</h3>
                                <p class="text-sm text-gray-600">System configuration and preferences</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">System Name</span>
                                <span class="text-sm font-medium"><?php echo htmlspecialchars($system_name); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Timezone</span>
                                <span class="text-sm font-medium"><?php echo getSetting($conn, 'timezone', 'Asia/Manila'); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Currency</span>
                                <span class="text-sm font-medium"><?php echo getSetting($conn, 'currency', 'PHP'); ?></span>
                            </div>
                        </div>
                        
                        <a href="general_settings/" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                            <i data-lucide="arrow-right" class="w-4 h-4 mr-2"></i>
                            Configure General Settings
                        </a>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                                <i data-lucide="shield" class="w-6 h-6 text-red-600"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Security Settings</h3>
                                <p class="text-sm text-gray-600">Authentication and security policies</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Password Min Length</span>
                                <span class="text-sm font-medium"><?php echo getSetting($conn, 'password_min_length', '8'); ?> chars</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Max Login Attempts</span>
                                <span class="text-sm font-medium"><?php echo getSetting($conn, 'max_login_attempts', '5'); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Two-Factor Auth</span>
                                <span class="text-sm font-medium <?php echo $two_factor_enabled ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $two_factor_enabled ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <a href="security_settings/" class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center">
                            <i data-lucide="arrow-right" class="w-4 h-4 mr-2"></i>
                            Configure Security Settings
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Settings Changes -->
            <div class="mt-8 bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Changes</h3>
                    <p class="text-sm text-gray-600">Latest configuration updates</p>
                </div>
                <div class="p-6">
                    <div id="recent-changes" class="space-y-4">
                        <!-- Recent changes will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function exportSettings() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'handler.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'export_settings';
            
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function resetToDefaults() {
            if (confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')) {
                fetch('handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=reset_to_defaults'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Settings reset to defaults successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while resetting settings.');
                });
            }
        }

        function loadRecentChanges() {
            // This would typically load from audit logs or settings history
            const recentChanges = document.getElementById('recent-changes');
            recentChanges.innerHTML = `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <i data-lucide="settings" class="w-4 h-4 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">System configuration updated</p>
                            <p class="text-sm text-gray-600">General settings modified</p>
                        </div>
                    </div>
                    <span class="text-sm text-gray-500">Today</span>
                </div>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                            <i data-lucide="shield" class="w-4 h-4 text-red-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Security policy updated</p>
                            <p class="text-sm text-gray-600">Password requirements changed</p>
                        </div>
                    </div>
                    <span class="text-sm text-gray-500">Yesterday</span>
                </div>
            `;
            lucide.createIcons();
        }

        // Load recent changes on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentChanges();
        });
    </script>
</body>
</html>