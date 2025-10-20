<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/administrator/puv_database/compliance_status_management/functions.php';

$database = new Database();
$conn = $database->getConnection();

// Get violation records
$violations = getViolationRecords($conn);

// Get statistics for violation record management
$stats = getViolationRecordStats($conn);

function getViolationRecordStats($conn) {
    $stats = [];
    
    // Total violations
    $query = "SELECT COUNT(*) as count FROM violation_history";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['total_violations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Unpaid fines
    $query = "SELECT SUM(fine_amount) as total FROM violation_history WHERE settlement_status = 'unpaid'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['unpaid_fines'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Repeat offenders
    $query = "SELECT COUNT(DISTINCT operator_id) as count FROM (
                SELECT operator_id FROM violation_history 
                GROUP BY operator_id HAVING COUNT(*) >= 3
              ) as repeat_ops";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['repeat_offenders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Settlement rate
    $query = "SELECT ROUND(AVG(CASE WHEN settlement_status = 'paid' THEN 100 ELSE 0 END), 1) as rate
              FROM violation_history";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['settlement_rate'] = $stmt->fetch(PDO::FETCH_ASSOC)['rate'] ?? 0;
    
    return $stats;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Violation Record Management - Transport Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body style="background-color: #FBFBFB;" class="dark:bg-slate-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-white border-r border-gray-200 dark:bg-slate-900 dark:border-slate-700 transform transition-transform duration-300 ease-in-out translate-x-0">
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <img src="../../../upload/Caloocan_City.png" alt="Caloocan City Logo" class="w-10 h-10 rounded-xl">
                    <div>
                        <h1 class="text-xl font-bold dark:text-white">TMM</h1>
                        <p class="text-xs text-slate-500">Admin Dashboard</p>
                    </div>
                </div>
            </div>
            <hr class="border-gray-200 dark:border-slate-700 mx-2">
            
            <!-- Navigation -->
            <nav class="p-4 space-y-2">
                <!-- Dashboard -->
                <a href="../../index.php" class="w-full flex items-center p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                    <i data-lucide="home" class="w-5 h-5 mr-3"></i>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>

                <!-- PUV Database Module -->
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

                <!-- Franchise Management Module -->
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
                    <button onclick="toggleDropdown('violation-ticketing')" class="w-full flex items-center justify-between p-2 rounded-xl transition-all" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.1);">
                        <div class="flex items-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Traffic Violation Ticketing</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="violation-ticketing-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="violation-ticketing-menu" class="ml-8 space-y-1">
                        <a href="../violation_record_management/" class="block p-2 text-sm rounded-lg font-medium" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.2);">Violation Record Management</a>
                        <a href="../linking_and_analytics/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">TVT Analytics</a>
                        <a href="../revenue_integration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Revenue Integration</a>
                    </div>
                </div>

                <!-- Vehicle Inspection & Registration Module -->
                <div class="space-y-1">
                    <button onclick="toggleDropdown('vehicle-inspection')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Vehicle Inspection & Registration</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="vehicle-inspection-icon"></i>
                    </button>
                    <div id="vehicle-inspection-menu" class="hidden ml-8 space-y-1">
                        <a href="../../vehicle_inspection_and_registration/inspection_scheduling/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Inspection Scheduling</a>
                        <a href="../../vehicle_inspection_and_registration/inspection_result_recording/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Result Recording</a>
                        <a href="../../vehicle_inspection_and_registration/inspection_history_tracking/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">History Tracking</a>
                        <a href="../../vehicle_inspection_and_registration/vehicle_registration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">LTO Registration</a>
                    </div>
                </div>

                <!-- Terminal Management Module -->
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
                    <button onclick="toggleDropdown('user-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="users" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">User Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="user-mgmt-icon"></i>
                    </button>
                    <div id="user-mgmt-menu" class="hidden ml-8 space-y-1">
                        <a href="../../user_management/account_registry/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Account Registry</a>
                        <a href="../../user_management/verification_queue/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Verification Queue</a>
                        <a href="../../user_management/account_maintenance/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Account Maintenance</a>
                        <a href="../../user_management/roles_and_permissions/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Roles & Permissions</a>
                        <a href="../../user_management/audit_logs/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Audit Logs</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('settings')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="settings" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Settings</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="settings-icon"></i>
                    </button>
                    <div id="settings-menu" class="hidden ml-8 space-y-1">
                        <a href="../../settings/system_configuration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">System Configuration</a>
                        <a href="../../settings/backup_and_restore/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Backup & Restore</a>
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
                            <h1 class="text-md font-bold dark:text-white">TRANSPORT & MOBILITY MANAGEMENT</h1>
                            <span class="text-xs text-slate-500 font-bold">Traffic Violation Ticketing > Violation Record Management</span>
                        </div>
                    </div>
                    <div class="flex-1 max-w-md mx-8">
                        <div class="relative">
                            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                            <input type="text" id="searchInput" placeholder="Search violations..." 
                                   class="w-full pl-10 pr-4 py-2 bg-slate-100 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-300">
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
                <?php if (isset($_GET['message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
                <?php endif; ?>
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Total Violations</p>
                                <p class="text-2xl font-bold text-red-600"><?php echo $stats['total_violations']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="alert-octagon" class="w-6 h-6 text-red-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Unpaid Fines</p>
                                <p class="text-2xl font-bold text-orange-600">₱<?php echo number_format($stats['unpaid_fines'], 0); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="credit-card" class="w-6 h-6 text-orange-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Repeat Offenders</p>
                                <p class="text-2xl font-bold text-purple-600"><?php echo $stats['repeat_offenders']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="repeat" class="w-6 h-6 text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Settlement Rate</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo $stats['settlement_rate']; ?>%</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Violation Record Management</h2>
                    <div class="flex space-x-3">
                        <button onclick="openAddModal()" class="px-4 py-2 text-white rounded-lg flex items-center space-x-2 transition-colors" style="background-color: #4CAF50;" onmouseover="this.style.backgroundColor='#45A049'" onmouseout="this.style.backgroundColor='#4CAF50'">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Add Violation</span>
                        </button>
                        <button onclick="showAnalytics()" class="px-4 py-2 text-white rounded-lg flex items-center space-x-2 transition-colors" style="background-color: #4A90E2;" onmouseover="this.style.backgroundColor='#357ABD'" onmouseout="this.style.backgroundColor='#4A90E2'">
                            <i data-lucide="bar-chart" class="w-4 h-4"></i>
                            <span>Analytics</span>
                        </button>
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

                <!-- Data Table -->
                <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="w-full" id="violationTable">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Operator/Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Violation Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Fine Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Settlement Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                <?php foreach ($violations as $violation): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                                <span class="text-red-600 font-medium"><?php echo strtoupper(substr($violation['first_name'], 0, 1) . substr($violation['last_name'], 0, 1)); ?></span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $violation['first_name'] . ' ' . $violation['last_name']; ?></div>
                                                <div class="text-sm text-slate-500"><?php echo $violation['plate_number'] . ' | ' . $violation['operator_id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo $violation['violation_type']; ?></div>
                                        <div class="text-sm text-slate-500"><?php echo $violation['violation_id']; ?></div>
                                        <div class="text-xs text-slate-400"><?php echo date('M d, Y', strtotime($violation['violation_date'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">₱<?php echo number_format($violation['fine_amount'], 2); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $statusClass = $violation['settlement_status'] == 'paid' ? 'bg-green-100 text-green-800' : 
                                                      ($violation['settlement_status'] == 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo ucfirst($violation['settlement_status']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button onclick="openViewModal('<?php echo $violation['violation_id']; ?>')" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="View Details">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="openEditModal('<?php echo $violation['violation_id']; ?>')" class="p-1 text-orange-600 hover:bg-orange-100 rounded" title="Edit">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
                                            <?php if ($violation['settlement_status'] !== 'paid'): ?>
                                            <button onclick="markAsPaid('<?php echo $violation['violation_id']; ?>')" class="p-1 text-green-600 hover:bg-green-100 rounded" title="Mark as Paid">
                                                <i data-lucide="credit-card" class="w-4 h-4"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button onclick="deleteViolation('<?php echo $violation['violation_id']; ?>')" class="p-1 text-red-600 hover:bg-red-100 rounded" title="Delete">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
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

    <!-- Add Violation Modal -->
    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Add New Violation</h2>
                <button onclick="closeModal('addModal')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="addViolationForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Operator</label>
                        <select name="operator_id" id="operatorSelect" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" onchange="loadOperatorVehicles()">
                            <option value="">Select Operator</option>
                            <?php 
                            $operators = getOperators($conn);
                            foreach ($operators as $op): ?>
                            <option value="<?php echo $op['operator_id']; ?>"><?php echo $op['first_name'] . ' ' . $op['last_name'] . ' (' . $op['operator_id'] . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Vehicle</label>
                        <select name="vehicle_id" id="vehicleSelect" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">Select Operator First</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Violation Category</label>
                        <select name="violation_category" id="categorySelect" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" onchange="loadViolationTypes()">
                            <option value="">Select Category</option>
                            <?php 
                            $cat_query = "SELECT * FROM violation_categories ORDER BY category_name";
                            $cat_stmt = $conn->prepare($cat_query);
                            $cat_stmt->execute();
                            $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Violation Type</label>
                        <select name="violation_type" id="typeSelect" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">Select Category First</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fine Amount</label>
                        <input type="number" name="fine_amount" step="0.01" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Violation Date</label>
                        <input type="datetime-local" name="violation_date" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <input type="text" name="location" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ticket Number</label>
                    <input type="text" name="ticket_number" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">Add Violation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Violation Modal -->
    <div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Violation Details</h2>
                <button onclick="closeModal('viewModal')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="viewModalContent"></div>
        </div>
    </div>

    <!-- Edit Violation Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Edit Violation</h2>
                <button onclick="closeModal('editModal')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="editModalContent"></div>
        </div>
    </div>

    <!-- Analytics Modal -->
    <div id="analyticsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-6xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Violation Analytics</h2>
                <button onclick="closeModal('analyticsModal')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="analyticsModalContent"></div>
        </div>
    </div>

    <!-- Settlement Modal -->
    <div id="settlementModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Mark as Paid</h2>
                <button onclick="closeModal('settlementModal')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="settlementForm" class="space-y-4">
                <input type="hidden" id="settlementViolationId" name="violation_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                    <select name="payment_method" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="Cash">Cash</option>
                        <option value="Check">Check</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Online Payment">Online Payment</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks (Optional)</label>
                    <textarea name="remarks" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Additional notes about the payment..."></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('settlementModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">Mark as Paid</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize Lucide icons after DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            
            // Show Traffic Violation menu and highlight current page
            const violationMenu = document.getElementById('violation-ticketing-menu');
            const violationIcon = document.getElementById('violation-ticketing-icon');
            if (violationMenu && violationIcon) {
                violationMenu.classList.remove('hidden');
                violationIcon.style.transform = 'rotate(180deg)';
                
                // Highlight current page
                const currentLink = violationMenu.querySelector('a[href*="violation_record_management"]');
                if (currentLink) {
                    currentLink.style.color = '#4CAF50';
                    currentLink.style.backgroundColor = 'rgba(76, 175, 80, 0.2)';
                    currentLink.classList.add('font-medium');
                }
            }
        });
        
        // Also initialize icons immediately
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        function toggleDropdown(menuId) {
            const allMenus = document.querySelectorAll('[id$="-menu"]');
            const allIcons = document.querySelectorAll('[id$="-icon"]');
            
            allMenus.forEach(menu => {
                if (menu.id !== menuId + '-menu') {
                    menu.classList.add('hidden');
                }
            });
            
            allIcons.forEach(icon => {
                if (icon.id !== menuId + '-icon') {
                    icon.style.transform = 'rotate(0deg)';
                }
            });
            
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

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('violationTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // Modal Functions
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }

        function openViewModal(violationId) {
            fetch(`view_violation.php?id=${violationId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('viewModalContent').innerHTML = html;
                    document.getElementById('viewModal').classList.remove('hidden');
                })
                .catch(error => {
                    alert('Error loading violation details');
                });
        }

        function openEditModal(violationId) {
            fetch(`edit_modal.php?id=${violationId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('editModalContent').innerHTML = html;
                    document.getElementById('editModal').classList.remove('hidden');
                    
                    setTimeout(() => {
                        const form = document.getElementById('editViolationForm');
                        if (form) {
                            form.addEventListener('submit', handleEditSubmit);
                        }
                    }, 100);
                })
                .catch(error => {
                    alert('Error loading edit form');
                });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function deleteViolation(violationId) {
            if (confirm('Are you sure you want to delete this violation record?')) {
                window.location.href = `delete.php?id=${violationId}`;
            }
        }

        function toggleExportMenu() {
            const menu = document.getElementById('export-menu');
            menu.classList.toggle('hidden');
        }

        function showAnalytics() {
            fetch('analytics_modal.php')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('analyticsModalContent').innerHTML = html;
                    document.getElementById('analyticsModal').classList.remove('hidden');
                })
                .catch(error => {
                    alert('Error loading analytics data');
                });
        }

        // Load vehicles based on selected operator
        function loadOperatorVehicles() {
            const operatorId = document.getElementById('operatorSelect').value;
            const vehicleSelect = document.getElementById('vehicleSelect');
            
            if (!operatorId) {
                vehicleSelect.innerHTML = '<option value="">Select Operator First</option>';
                return;
            }
            
            vehicleSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`get_operator_vehicles.php?operator_id=${operatorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.vehicles.length > 0) {
                        vehicleSelect.innerHTML = '<option value="">Select Vehicle</option>' +
                            data.vehicles.map(vehicle => 
                                `<option value="${vehicle.vehicle_id}">${vehicle.plate_number} - ${vehicle.vehicle_type}</option>`
                            ).join('');
                    } else {
                        vehicleSelect.innerHTML = '<option value="">No vehicles found</option>';
                    }
                })
                .catch(error => {
                    vehicleSelect.innerHTML = '<option value="">Error loading vehicles</option>';
                });
        }
        
        // Load violation types based on selected category
        function loadViolationTypes() {
            const categoryId = document.getElementById('categorySelect').value;
            const typeSelect = document.getElementById('typeSelect');
            
            if (!categoryId) {
                typeSelect.innerHTML = '<option value="">Select Category First</option>';
                return;
            }
            
            typeSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`get_violation_types.php?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.types.length > 0) {
                        typeSelect.innerHTML = '<option value="">Select Violation Type</option>' +
                            data.types.map(type => 
                                `<option value="${type.type_name}">${type.type_name}</option>`
                            ).join('');
                    } else {
                        typeSelect.innerHTML = '<option value="">No types found</option>';
                    }
                })
                .catch(error => {
                    typeSelect.innerHTML = '<option value="">Error loading types</option>';
                });
        }

        // Handle add violation form submission
        document.getElementById('addViolationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('add_violation_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Violation added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error adding violation');
            });
        });

        // Handle edit violation form submission
        function handleEditSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            fetch('edit_violation_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Violation updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating violation');
            });
        }

        // Mark violation as paid
        function markAsPaid(violationId) {
            document.getElementById('settlementViolationId').value = violationId;
            document.getElementById('settlementModal').classList.remove('hidden');
        }

        // Handle settlement form submission
        document.getElementById('settlementForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_settlement_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settlement status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating settlement status');
            });
        });

        // Close export menu when clicking outside
        document.addEventListener('click', function(event) {
            const exportMenu = document.getElementById('export-menu');
            const exportButton = event.target.closest('button');
            
            if (!exportButton || !exportButton.onclick || exportButton.onclick.toString().indexOf('toggleExportMenu') === -1) {
                exportMenu.classList.add('hidden');
            }
        });

        // Enhanced sidebar toggle function with smooth animations
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.flex-1.flex.flex-col');
            
            // Add null checks for safety
            if (!sidebar || !mainContent) {
                console.error('Sidebar or main content element not found');
                return;
            }
            
            // Toggle sidebar visibility
            if (sidebar.classList.contains('-translate-x-full')) {
                // Show sidebar
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                
                // Adjust main content
                mainContent.style.marginLeft = '0';
                mainContent.style.width = 'calc(100% - 16rem)';
            } else {
                // Hide sidebar
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                
                // Expand main content to full width
                mainContent.style.marginLeft = '-16rem';
                mainContent.style.width = '100%';
            }
        }
    </script>
</body>
</html>