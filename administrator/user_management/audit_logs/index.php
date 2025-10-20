<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Handle search and filters
$search = $_GET['search'] ?? '';
$action_filter = $_GET['action_type'] ?? '';
$user_filter = $_GET['user_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Get audit logs with filters
$query = "SELECT al.*, u.username, u.first_name, u.last_name, 
                 pb.username as performed_by_username, pb.first_name as pb_first_name, pb.last_name as pb_last_name
          FROM audit_logs al
          LEFT JOIN users u ON al.user_id = u.user_id
          LEFT JOIN users pb ON al.performed_by = pb.user_id
          WHERE 1=1";

$params = [];

if ($search) {
    $query .= " AND (u.username LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR al.action_details LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if ($action_filter) {
    $query .= " AND al.action_type = :action_type";
    $params['action_type'] = $action_filter;
}

if ($user_filter) {
    $query .= " AND al.user_id = :user_id";
    $params['user_id'] = $user_filter;
}

if ($date_from) {
    $query .= " AND DATE(al.timestamp) >= :date_from";
    $params['date_from'] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(al.timestamp) <= :date_to";
    $params['date_to'] = $date_to;
}

$query .= " ORDER BY al.timestamp DESC LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_logs,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT action_type) as unique_actions,
    COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h,
    COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7d,
    COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30d
FROM audit_logs";
$stmt = $conn->prepare($stats_query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get action types for filter
$action_types_query = "SELECT DISTINCT action_type FROM audit_logs ORDER BY action_type";
$stmt = $conn->prepare($action_types_query);
$stmt->execute();
$action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get users for filter
$users_query = "SELECT user_id, username, first_name, last_name FROM users ORDER BY username";
$stmt = $conn->prepare($users_query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-slate-50 dark:bg-slate-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-white border-r border-slate-200 dark:bg-slate-900 dark:border-slate-700 transform transition-transform duration-300 ease-in-out translate-x-0">
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <img src="../../../upload/Caloocan_City.png" alt="Caloocan City Logo" class="w-10 h-10 rounded-xl">
                    <div>
                        <h1 class="text-xl font-bold dark:text-white">TMM</h1>
                        <p class="text-xs text-slate-500">Admin Dashboard</p>
                    </div>
                </div>
            </div>
            <hr class="border-slate-200 dark:border-slate-700 mx-2">
            
            <!-- Navigation -->
            <nav class="p-4 space-y-2">
                <a href="../../index.php" class="w-full flex items-center p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                    <i data-lucide="home" class="w-5 h-5 mr-3"></i>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('user-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-orange-600 bg-orange-50 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="users" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">User Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="user-mgmt-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="user-mgmt-menu" class="ml-8 space-y-1">
                        <a href="../../user_management/account_registry/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Account Registry</a>
                        <a href="../../user_management/verification_queue/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Verification Queue</a>
                        <a href="../../user_management/account_maintenance/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Account Maintenance</a>
                        <a href="../../user_management/roles_and_permissions/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Roles & Permissions</a>
                        <a href="../../user_management/audit_logs/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Audit Logs</a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col transition-all duration-300 ease-in-out">
            <!-- Header -->
            <div class="bg-white border-b border-slate-200 px-6 py-4 dark:bg-slate-800 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="toggleSidebar()" class="p-2 rounded-lg text-slate-500 hover:bg-slate-200 transition-colors duration-200">
                            <i data-lucide="menu" class="w-6 h-6"></i>
                        </button>
                        <div>
                            <h1 class="text-md font-bold dark:text-white">TRANSPORT PUBLIC RECORD SYSTEM - ADMIN</h1>
                            <span class="text-xs text-slate-500 font-bold">User Management > Audit Logs</span>
                        </div>
                    </div>
                    <div class="flex-1 max-w-md mx-8">
                        <form method="GET" class="relative">
                            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search logs..." 
                                   class="w-full pl-10 pr-4 py-2 bg-slate-100 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-300">
                        </form>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="p-2 rounded-xl text-slate-600 hover:bg-slate-200">
                            <i data-lucide="bell" class="w-6 h-6"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="flex-1 p-6 overflow-auto">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Total Logs</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo number_format($stats['total_logs']); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="file-text" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Last 24 Hours</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo number_format($stats['last_24h']); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="clock" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Unique Users</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo number_format($stats['unique_users']); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6 text-orange-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Action Types</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo number_format($stats['unique_actions']); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="activity" class="w-6 h-6 text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Audit Logs</h2>
                    <div class="flex space-x-3">
                        <div class="relative">
                            <button onclick="toggleExportMenu()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 flex items-center space-x-2">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>Export</span>
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </button>
                            <div id="export-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                <a href="export.php?format=csv" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export as CSV</a>
                                <a href="export.php?format=excel" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export as Excel</a>
                                <a href="export.php?format=pdf" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export as PDF</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <form id="filterForm" method="GET" class="bg-white p-4 rounded-xl border border-slate-200 mb-6 dark:bg-slate-800 dark:border-slate-700">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <select name="action_type" id="actionFilter" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Actions</option>
                            <?php foreach ($action_types as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $action_filter == $type ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $type)); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="user_id" id="userFilter" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>" <?php echo $user_filter == $user['user_id'] ? 'selected' : ''; ?>><?php echo $user['username'] . ' (' . $user['first_name'] . ' ' . $user['last_name'] . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300" placeholder="From Date">
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300" placeholder="To Date">
                        <button type="button" onclick="clearFilters()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Clear</button>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </form>

                <!-- Data Table -->
                <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Timestamp</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Action</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Performed By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo date('M d, Y', strtotime($log['timestamp'])); ?></div>
                                        <div class="text-xs text-slate-500"><?php echo date('H:i:s', strtotime($log['timestamp'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-blue-600 font-medium text-xs"><?php echo strtoupper(substr($log['first_name'] ?? 'U', 0, 1) . substr($log['last_name'] ?? 'U', 0, 1)); ?></span>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo ($log['first_name'] ?? 'Unknown') . ' ' . ($log['last_name'] ?? 'User'); ?></div>
                                                <div class="text-xs text-slate-500"><?php echo $log['username'] ?? 'N/A'; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $actionClass = 'bg-blue-100 text-blue-800';
                                        if (strpos($log['action_type'], 'delete') !== false || strpos($log['action_type'], 'ban') !== false) {
                                            $actionClass = 'bg-red-100 text-red-800';
                                        } elseif (strpos($log['action_type'], 'create') !== false || strpos($log['action_type'], 'approve') !== false) {
                                            $actionClass = 'bg-green-100 text-green-800';
                                        } elseif (strpos($log['action_type'], 'update') !== false || strpos($log['action_type'], 'edit') !== false) {
                                            $actionClass = 'bg-orange-100 text-orange-800';
                                        }
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $actionClass; ?> rounded-full"><?php echo ucfirst(str_replace('_', ' ', $log['action_type'])); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white max-w-xs truncate" title="<?php echo htmlspecialchars($log['action_details']); ?>">
                                            <?php echo htmlspecialchars($log['action_details']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo ($log['pb_first_name'] ?? 'System') . ' ' . ($log['pb_last_name'] ?? ''); ?></div>
                                        <div class="text-xs text-slate-500"><?php echo $log['performed_by_username'] ?? 'system'; ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleDropdown(menuId) {
            const menu = document.getElementById(menuId + '-menu');
            const icon = document.getElementById(menuId + '-icon');
            
            if (menu && icon) {
                if (menu.classList.contains('hidden')) {
                    menu.classList.remove('hidden');
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    menu.classList.add('hidden');
                    icon.style.transform = 'rotate(0deg)';
                }
            }
        }

        // Auto-submit filters when dropdown values change
        document.getElementById('actionFilter').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        document.getElementById('userFilter').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        document.querySelector('input[name="date_from"]').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        document.querySelector('input[name="date_to"]').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        function clearFilters() {
            window.location.href = window.location.pathname;
        }

        function toggleExportMenu() {
            const menu = document.getElementById('export-menu');
            menu.classList.toggle('hidden');
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }
    </script>
</body>
</html>