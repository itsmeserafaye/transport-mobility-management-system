<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Get all roles with permission counts
function getAllRoles($conn) {
    try {
        $query = "SELECT r.role_id, r.role_name, r.role_description, r.is_system_role, r.created_at,
                         COUNT(rp.permission_id) as permission_count
                  FROM system_roles r
                  LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
                  GROUP BY r.role_id
                  ORDER BY r.is_system_role DESC, r.role_name";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [
            ['role_id' => 'ROLE001', 'role_name' => 'Super Administrator', 'role_description' => 'Full system access', 'is_system_role' => 1, 'permission_count' => 9, 'created_at' => date('Y-m-d H:i:s')],
            ['role_id' => 'ROLE002', 'role_name' => 'Administrator', 'role_description' => 'System administrator', 'is_system_role' => 1, 'permission_count' => 6, 'created_at' => date('Y-m-d H:i:s')],
            ['role_id' => 'ROLE003', 'role_name' => 'Operator', 'role_description' => 'Transport operator', 'is_system_role' => 1, 'permission_count' => 2, 'created_at' => date('Y-m-d H:i:s')],
            ['role_id' => 'ROLE004', 'role_name' => 'Citizen', 'role_description' => 'Regular citizen user', 'is_system_role' => 1, 'permission_count' => 2, 'created_at' => date('Y-m-d H:i:s')]
        ];
    }
}

// Get all permissions grouped by module
function getAllPermissions($conn) {
    try {
        $query = "SELECT permission_id, permission_name, permission_description, module_name, permission_type
                  FROM system_permissions
                  ORDER BY module_name, permission_type";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [
            ['permission_id' => 'PERM001', 'permission_name' => 'user_management_read', 'permission_description' => 'View user accounts', 'module_name' => 'user_management', 'permission_type' => 'read'],
            ['permission_id' => 'PERM002', 'permission_name' => 'user_management_write', 'permission_description' => 'Edit user accounts', 'module_name' => 'user_management', 'permission_type' => 'write'],
            ['permission_id' => 'PERM003', 'permission_name' => 'puv_database_read', 'permission_description' => 'View PUV records', 'module_name' => 'puv_database', 'permission_type' => 'read'],
            ['permission_id' => 'PERM004', 'permission_name' => 'franchise_read', 'permission_description' => 'View franchises', 'module_name' => 'franchise_management', 'permission_type' => 'read']
        ];
    }
}

// Get permissions for a specific role
function getRolePermissions($conn, $role_id) {
    $query = "SELECT p.permission_id, p.permission_name, p.module_name, p.permission_type
              FROM role_permissions rp
              JOIN system_permissions p ON rp.permission_id = p.permission_id
              WHERE rp.role_id = :role_id
              ORDER BY p.module_name, p.permission_type";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':role_id', $role_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get role statistics
function getRoleStats($conn) {
    try {
        $stats = [];
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM system_roles");
        $stats['total_roles'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM system_roles WHERE is_system_role = TRUE");
        $stats['system_roles'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM system_roles WHERE is_system_role = FALSE");
        $stats['custom_roles'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM system_permissions");
        $stats['total_permissions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    } catch (PDOException $e) {
        return ['total_roles' => 4, 'system_roles' => 4, 'custom_roles' => 0, 'total_permissions' => 9];
    }
}

$roles = getAllRoles($conn);
$permissions = getAllPermissions($conn);
$stats = getRoleStats($conn);

// Group permissions by module
$permissionsByModule = [];
foreach ($permissions as $permission) {
    $permissionsByModule[$permission['module_name']][] = $permission;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles & Permissions - User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-slate-50 dark:bg-slate-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-white border-r border-slate-200 dark:bg-slate-900 dark:border-slate-700 transform transition-transform duration-300 ease-in-out translate-x-0">
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <img src="../../../upload/Caloocan_City.png?v=<?php echo time(); ?>" alt="Caloocan City Logo" class="w-10 h-10 rounded-xl">
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
                    <button onclick="toggleDropdown('puv-database')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="database" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">PUV Database</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="puv-database-icon"></i>
                    </button>
                    <div id="puv-database-menu" class="hidden ml-8 space-y-1">
                        <a href="../../puv_database/vehicle_and_operator_records/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Vehicle & Operator Records</a>
                        <a href="../../puv_database/compliance_status_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Compliance Status Management</a>
                        <a href="../../puv_database/violation_history_integration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Violation History Integration</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('franchise-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="file-text" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Franchise Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="franchise-mgmt-icon"></i>
                    </button>
                    <div id="franchise-mgmt-menu" class="hidden ml-8 space-y-1">
                        <a href="../../franchise_management/franchise_application_workflow/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Application & Workflow</a>
                        <a href="../../franchise_management/document_repository/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Document Repository</a>
                        <a href="../../franchise_management/franchise_lifecycle_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Lifecycle Management</a>
                        <a href="../../franchise_management/route_and_schedule_publication/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Route & Schedule Publication</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('violation-ticketing')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Traffic Violation Ticketing</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="violation-ticketing-icon"></i>
                    </button>
                    <div id="violation-ticketing-menu" class="hidden ml-8 space-y-1">
                        <a href="../../traffic_violation_ticketing/violation_record_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Violation Record Management</a>
                        <a href="../../traffic_violation_ticketing/linking_and_analytics/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">TVT Analytics</a>
                        <a href="../../traffic_violation_ticketing/revenue_integration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Revenue Integration</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('vehicle-inspection')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Vehicle Inspection</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="vehicle-inspection-icon"></i>
                    </button>
                    <div id="vehicle-inspection-menu" class="hidden ml-8 space-y-1">
                        <a href="../../vehicle_inspection_and_registration/inspection_scheduling/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Inspection Scheduling</a>
                        <a href="../../vehicle_inspection_and_registration/inspection_result_recording/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Result Recording</a>
                        <a href="../../vehicle_inspection_and_registration/inspection_history_tracking/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">History Tracking</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('terminal-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="map-pin" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Terminal Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="terminal-mgmt-icon"></i>
                    </button>
                    <div id="terminal-mgmt-menu" class="hidden ml-8 space-y-1">
                        <a href="../../parking_and_terminal_management/terminal_assignment_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Terminal Assignment</a>
                        <a href="../../parking_and_terminal_management/roster_and_delivery/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Roster & Directory</a>
                        <a href="../../parking_and_terminal_management/public_transparency/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Public Transparency</a>
                    </div>
                </div>

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
                        <a href="../../user_management/roles_and_permissions/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Roles & Permissions</a>
                        <a href="../../user_management/audit_logs/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Audit Logs</a>
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
                            <h1 class="text-md font-bold dark:text-white">ROLES & PERMISSIONS</h1>
                            <span class="text-xs text-slate-500 font-bold">User Management > Roles & Permissions</span>
                        </div>
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
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Total Roles</p>
                                <p class="text-2xl font-bold text-blue-600"><?php echo $stats['total_roles']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="shield" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">System Roles</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo $stats['system_roles']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="shield-check" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Custom Roles</p>
                                <p class="text-2xl font-bold text-purple-600"><?php echo $stats['custom_roles']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="user-plus" class="w-6 h-6 text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Total Permissions</p>
                                <p class="text-2xl font-bold text-orange-600"><?php echo $stats['total_permissions']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="key" class="w-6 h-6 text-orange-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-900">Roles & Permissions</h2>
                    <div class="flex space-x-3">
                        <button onclick="openCreateRoleModal()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 flex items-center space-x-2">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Create Role</span>
                        </button>
                        <button onclick="openPermissionModal()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 flex items-center space-x-2">
                            <i data-lucide="settings" class="w-4 h-4"></i>
                            <span>Manage Permissions</span>
                        </button>
                    </div>
                </div>

                <!-- Roles Table -->
                <div class="bg-white rounded-xl border border-slate-200 mb-6">
                    <div class="p-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900">System Roles</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Permissions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php foreach ($roles as $role): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i data-lucide="shield" class="w-5 h-5 text-blue-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-slate-900"><?php echo $role['role_name']; ?></div>
                                                <div class="text-xs text-slate-500"><?php echo $role['role_id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900"><?php echo $role['role_description']; ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                                <?php echo $role['permission_count']; ?> permissions
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($role['is_system_role']): ?>
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">System</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Custom</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button onclick="viewRolePermissions('<?php echo $role['role_id']; ?>')" class="p-2 text-blue-600 hover:bg-blue-100 rounded" title="View Permissions">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="editRolePermissions('<?php echo $role['role_id']; ?>')" class="p-2 text-orange-600 hover:bg-orange-100 rounded" title="Edit Permissions">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
                                            <?php if (!$role['is_system_role']): ?>
                                                <button onclick="deleteRole('<?php echo $role['role_id']; ?>')" class="p-2 text-red-600 hover:bg-red-100 rounded" title="Delete Role">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Permissions by Module -->
                <div class="bg-white rounded-xl border border-slate-200">
                    <div class="p-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900">System Permissions</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($permissionsByModule as $module => $modulePermissions): ?>
                            <div class="border border-slate-200 rounded-lg p-4">
                                <h4 class="font-semibold text-slate-900 mb-3 capitalize"><?php echo str_replace('_', ' ', $module); ?></h4>
                                <div class="space-y-2">
                                    <?php foreach ($modulePermissions as $permission): ?>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-sm font-medium text-slate-700"><?php echo ucfirst($permission['permission_type']); ?></div>
                                            <div class="text-xs text-slate-500"><?php echo $permission['permission_description']; ?></div>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700 rounded">
                                            <?php echo $permission['permission_type']; ?>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
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

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }

        function openCreateRoleModal() {
            console.log('Create new role');
        }

        function openPermissionModal() {
            console.log('Manage permissions');
        }

        function viewRolePermissions(roleId) {
            console.log('View permissions for role:', roleId);
        }

        function editRolePermissions(roleId) {
            console.log('Edit permissions for role:', roleId);
        }

        function deleteRole(roleId) {
            if (confirm('Are you sure you want to delete this role?')) {
                console.log('Delete role:', roleId);
            }
        }
    </script>
</body>
</html>