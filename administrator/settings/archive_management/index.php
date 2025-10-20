<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Handle restore action
if (isset($_POST['action']) && $_POST['action'] === 'restore' && isset($_POST['operator_id'])) {
    $result = restoreOperator($conn, $_POST['operator_id'], 'Administrator');
    $message = $result['message'];
    $message_type = $result['success'] ? 'success' : 'error';
}

$archived_operators = getArchivedOperators($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Management - Transport Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body style="background-color: #FBFBFB;">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out translate-x-0">
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <img src="../../../upload/Caloocan_City.png" alt="Caloocan City Logo" class="w-10 h-10 rounded-xl">
                    <div>
                        <h1 class="text-xl font-bold">TMM</h1>
                        <p class="text-xs text-slate-500">Admin Dashboard</p>
                    </div>
                </div>
            </div>
            <hr class="border-gray-200 mx-2">
            
            <nav class="p-4 space-y-2">
                <a href="../../index.php" class="w-full flex items-center p-2 rounded-xl text-slate-600 hover:bg-slate-200 transition-all">
                    <i data-lucide="home" class="w-5 h-5 mr-3"></i>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('settings')" class="w-full flex items-center justify-between p-2 rounded-xl transition-all" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.1);">
                        <div class="flex items-center">
                            <i data-lucide="settings" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Settings</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="settings-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="settings-menu" class="ml-8 space-y-1">
                        <a href="../general_settings/" class="block p-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">General Settings</a>
                        <a href="../security_settings/" class="block p-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg">Security Settings</a>
                        <a href="../archive_management/" class="block p-2 text-sm rounded-lg font-medium" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.2);">Archive Management</a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <div class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="toggleSidebar()" class="p-2 rounded-lg text-gray-500 hover:bg-gray-200">
                            <i data-lucide="menu" class="w-6 h-6"></i>
                        </button>
                        <div>
                            <h1 class="text-md font-bold">ARCHIVE MANAGEMENT</h1>
                            <span class="text-xs text-gray-500 font-bold">Settings Module</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 p-6 overflow-auto">
                <?php if (isset($message)): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-6">Archived Operators</h2>
                    
                    <?php if (empty($archived_operators)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i data-lucide="archive" class="w-12 h-12 mx-auto mb-4 text-gray-300"></i>
                        <p>No archived operators found.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Operator</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Archived Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($archived_operators as $operator): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                                <span class="text-red-600 font-medium"><?php echo strtoupper(substr($operator['first_name'], 0, 1) . substr($operator['last_name'], 0, 1)); ?></span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo $operator['first_name'] . ' ' . $operator['last_name']; ?></div>
                                                <div class="text-sm text-gray-500"><?php echo $operator['operator_id']; ?></div>
                                                <div class="text-xs text-gray-400"><?php echo $operator['contact_number']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo $operator['plate_number'] ?? 'N/A'; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo ucfirst($operator['vehicle_type'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($operator['archived_at'])); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo date('g:i A', strtotime($operator['archived_at'])); ?></div>
                                        <div class="text-xs text-gray-400">By: <?php echo $operator['archived_by']; ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to restore this operator? This will move all related records back to active tables.')">
                                            <input type="hidden" name="action" value="restore">
                                            <input type="hidden" name="operator_id" value="<?php echo $operator['operator_id']; ?>">
                                            <button type="submit" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 flex items-center space-x-1">
                                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                                <span>Restore</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleDropdown(menuId) {
            const menu = document.getElementById(menuId + '-menu');
            const icon = document.getElementById(menuId + '-icon');
            
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                menu.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.flex-1.flex.flex-col');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                mainContent.style.marginLeft = '0';
                mainContent.style.width = 'calc(100% - 16rem)';
            } else {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                mainContent.style.marginLeft = '-16rem';
                mainContent.style.width = '100%';
            }
        }
    </script>
</body>
</html>