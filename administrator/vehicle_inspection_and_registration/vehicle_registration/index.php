<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Get LTO registration statistics
$stats = getLTORegistrationStats($conn);

// Get all LTO registrations
$registrations = getLTORegistrations($conn);

// Get operators and vehicles for dropdowns
$operators = getOperators($conn, 1000);
$vehicles_query = "SELECT * FROM vehicles ORDER BY plate_number";
$vehicles_stmt = $conn->prepare($vehicles_query);
$vehicles_stmt->execute();
$vehicles = $vehicles_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LTO Vehicle Registration & Compliance - Transport Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .card-hover {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dark-mode.css">
</head>
<body style="background-color: #FBFBFB;" class="dark:bg-slate-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-white border-r border-gray-200 dark:bg-slate-900 dark:border-slate-700 transform transition-transform duration-300 ease-in-out translate-x-0">
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <img src="../../../upload/Caloocan_City.png?v=<?php echo time(); ?>" alt="Caloocan City Logo" class="w-10 h-10 rounded-xl">
                    <div>
                        <h1 class="text-xl font-bold dark:text-white">TMM</h1>
                        <p class="text-xs text-slate-500">Admin Dashboard</p>
                    </div>
                </div>
            </div>
            <hr class="border-gray-200 dark:border-slate-700 mx-2">
            
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
                    <button onclick="toggleDropdown('vehicle-inspection')" class="w-full flex items-center justify-between p-2 rounded-xl transition-all" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.1);">
                        <div class="flex items-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Vehicle Inspection & Registration</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="vehicle-inspection-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="vehicle-inspection-menu" class="ml-8 space-y-1">
                        <a href="../inspection_scheduling/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Inspection Scheduling</a>
                        <a href="../inspection_result_recording/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Result Recording</a>
                        <a href="../inspection_history_tracking/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">History Tracking</a>
                        <a href="../vehicle_registration/" class="block p-2 text-sm rounded-lg font-medium" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.2);">LTO Registration</a>
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
            <div class="bg-white border-b border-gray-200 px-6 py-4 dark:bg-slate-800 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="toggleSidebar()" class="p-2 rounded-lg text-gray-500 hover:bg-gray-200 transition-colors duration-200">
                            <i data-lucide="menu" class="w-6 h-6"></i>
                        </button>
                        <div>
                            <h1 class="text-md font-bold dark:text-white">LTO VEHICLE REGISTRATION & COMPLIANCE</h1>
                            <span class="text-xs text-gray-500 font-bold">Vehicle Inspection & Registration Module</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="p-2 rounded-xl text-gray-600 hover:bg-gray-200">
                            <i data-lucide="bell" class="w-6 h-6"></i>
                        </button>
                        <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle Dark Mode">
                            <i class="fas fa-moon" id="darkModeIcon"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <main class="p-6 flex-1 overflow-y-auto" style="background-color: #FBFBFB;">

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total LTO Registrations</p>
                                <p class="text-3xl font-bold" style="color: #4A90E2;"><?php echo number_format($stats['total']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(74, 144, 226, 0.1);">
                                <i data-lucide="file-text" class="w-6 h-6" style="color: #4A90E2;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Registrations</p>
                                <p class="text-3xl font-bold" style="color: #4CAF50;"><?php echo number_format($stats['active']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(76, 175, 80, 0.1);">
                                <i data-lucide="check-circle" class="w-6 h-6" style="color: #4CAF50;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Expired Registrations</p>
                                <p class="text-3xl font-bold text-red-600"><?php echo number_format($stats['expired']); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="x-circle" class="w-6 h-6 text-red-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Expiring Soon</p>
                                <p class="text-3xl font-bold" style="color: #FDA811;"><?php echo number_format($stats['expiring_soon']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(253, 168, 17, 0.1);">
                                <i data-lucide="clock" class="w-6 h-6" style="color: #FDA811;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">LTO Registration Records</h3>
                        <button onclick="openAddModal()" class="text-white px-4 py-2 rounded-lg flex items-center transition-colors" style="background-color: #4A90E2;" onmouseover="this.style.backgroundColor='#357ABD'" onmouseout="this.style.backgroundColor='#4A90E2'">
                            <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                            Add LTO Registration
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OR/CR Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LTO Office</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($registrations as $registration): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($registration['or_number'] . '/' . $registration['cr_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($registration['plate_number'] . ' - ' . $registration['make'] . ' ' . $registration['model']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($registration['operator_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($registration['lto_office']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($registration['expiry_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $registration['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                       ($registration['status'] === 'expired' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php echo ucfirst($registration['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewRegistration('<?php echo $registration['lto_registration_id']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                                        <button onclick="editRegistration('<?php echo $registration['lto_registration_id']; ?>')" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                        <button onclick="deleteRegistration('<?php echo $registration['lto_registration_id']; ?>')" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Registration Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="modal-content bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
            <div class="modal-header px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Add New LTO Registration</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="addRegistrationForm" class="modal-body p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle</label>
                        <select name="vehicle_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['vehicle_id']; ?>">
                                <?php echo htmlspecialchars($vehicle['plate_number'] . ' - ' . $vehicle['vehicle_type']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Operator</label>
                        <select name="operator_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Operator</option>
                            <?php foreach ($operators as $operator): ?>
                            <option value="<?php echo $operator['operator_id']; ?>">
                                <?php echo htmlspecialchars($operator['first_name'] . ' ' . $operator['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OR Number</label>
                        <input type="text" name="or_number" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CR Number</label>
                        <input type="text" name="cr_number" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Plate Number</label>
                        <input type="text" name="plate_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Registration Type</label>
                        <select name="registration_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="new">New</option>
                            <option value="renewal">Renewal</option>
                            <option value="transfer">Transfer</option>
                            <option value="duplicate">Duplicate</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Registration Date</label>
                        <input type="date" name="registration_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                        <input type="date" name="expiry_date" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">LTO Office</label>
                        <input type="text" name="lto_office" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fees Paid</label>
                        <input type="number" step="0.01" name="fees_paid" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Document Path</label>
                        <input type="text" name="document_path" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Path to OR/CR document">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Add LTO Registration</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

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

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.flex-1.flex.flex-col');
            
            if (!sidebar || !mainContent) {
                console.error('Sidebar or main content element not found');
                return;
            }
            
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

        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addModal').classList.remove('flex');
        }

        function viewRegistration(id) {
            alert('View registration: ' + id);
        }

        function editRegistration(id) {
            alert('Edit registration: ' + id);
        }

        function deleteRegistration(id) {
            if (confirm('Are you sure you want to delete this registration?')) {
                alert('Delete registration: ' + id);
            }
        }

        document.getElementById('addRegistrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Add registration functionality to be implemented');
        });
    </script>
</body>
</html>