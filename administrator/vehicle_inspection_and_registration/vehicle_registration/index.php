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
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
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
                                <p class="text-sm font-medium text-gray-600">Pending Approval</p>
                                <p class="text-3xl font-bold" style="color: #FDA811;"><?php echo number_format($stats['pending']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(253, 168, 17, 0.1);">
                                <i data-lucide="clock" class="w-6 h-6" style="color: #FDA811;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Expiring Soon</p>
                                <p class="text-3xl font-bold" style="color: #FF6B35;"><?php echo number_format($stats['expiring_soon']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(255, 107, 53, 0.1);">
                                <i data-lucide="alert-triangle" class="w-6 h-6" style="color: #FF6B35;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">LTO Registration Records</h3>
                        <div class="flex space-x-2">
                            <button onclick="openAddModal()" class="text-white px-4 py-2 rounded-lg flex items-center transition-colors" style="background-color: #4A90E2;" onmouseover="this.style.backgroundColor='#357ABD'" onmouseout="this.style.backgroundColor='#4A90E2'">
                                <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                                New Registration
                            </button>
                            <button onclick="openNewVehicleModal()" class="text-white px-4 py-2 rounded-lg flex items-center transition-colors bg-purple-600 hover:bg-purple-700">
                                <i data-lucide="car" class="w-5 h-5 mr-2"></i>
                                Add Vehicle to Owner
                            </button>
                        </div>
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
                                        <div class="font-medium"><?php echo htmlspecialchars($registration['plate_number'] ?: 'Pending Assignment'); ?></div>
                                        <div class="text-gray-500"><?php echo htmlspecialchars($registration['make'] . ' ' . $registration['model'] . ' (' . $registration['year_model'] . ')'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="font-medium"><?php echo htmlspecialchars($registration['owner_first_name'] . ' ' . $registration['owner_last_name']); ?></div>
                                        <div class="text-gray-500"><?php echo htmlspecialchars($registration['classification']); ?></div>
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
                                                       ($registration['status'] === 'expired' ? 'bg-red-100 text-red-800' : 
                                                       ($registration['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                                       ($registration['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'))); ?>">
                                            <?php echo ucfirst($registration['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewRegistration('<?php echo $registration['lto_registration_id']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                                        <?php if ($registration['status'] === 'pending' && $registration['registration_type'] === 'new' && empty($registration['plate_number'])): ?>
                                        <button onclick="openApproveModal('<?php echo $registration['lto_registration_id']; ?>')" class="text-green-600 hover:text-green-900 mr-3">Approve & Assign Plate</button>
                                        <button onclick="openRejectModal('<?php echo $registration['lto_registration_id']; ?>')" class="text-red-600 hover:text-red-900 mr-3">Reject</button>
                                        <?php endif; ?>
                                        <?php if ($registration['status'] === 'active'): ?>
                                            <?php 
                                            $expiry_date = new DateTime($registration['expiry_date']);
                                            $today = new DateTime();
                                            $days_until_expiry = $today->diff($expiry_date)->days;
                                            $is_expired = $today > $expiry_date;
                                            $can_renew = $is_expired || $days_until_expiry <= 90;
                                            ?>
                                            <?php if ($can_renew): ?>
                                            <button onclick="openRenewalFormModalForRegistration('<?php echo $registration['lto_registration_id']; ?>')" class="text-orange-600 hover:text-orange-900 mr-3">Renew</button>
                                            <?php else: ?>
                                            <span class="text-gray-400 text-sm">Renew in <?php echo $days_until_expiry - 90; ?> days</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($registration['status'] === 'rejected'): ?>
                                        <span class="text-red-600 font-medium">✗ Rejected</span>
                                        <?php endif; ?>
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
        <div class="modal-content bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="modal-header px-6 py-4 border-b border-gray-200 sticky top-0 bg-white">
                <h3 class="text-lg font-semibold text-gray-900">Add New LTO Registration</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="addRegistrationForm" class="modal-body p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Owner Information -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Owner First Name</label>
                        <input type="text" name="owner_first_name" required pattern="^[A-Za-z\s]{2,50}$" title="2-50 characters, letters and spaces only" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Owner Last Name</label>
                        <input type="text" name="owner_last_name" required pattern="^[A-Za-z\s]{2,50}$" title="2-50 characters, letters and spaces only" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">License Number</label>
                        <input type="text" name="license_number" required pattern="^(?:[A-Z]\d{2}-\d{2}-\d{6}|\d{1,2}-\d{9})$" title="Old format: D12-34-567890 or New format: N-123456789" placeholder="D12-34-567890 or N-123456789" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <div class="text-xs text-gray-500 mt-1">Old: D12-34-567890 | New: N-123456789 or 02-123456789</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">License Expiry Date</label>
                        <input type="date" name="license_expiry" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Owner Address</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <input type="text" name="house_street" required placeholder="House No. & Street" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <select name="barangay" id="barangaySelect" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select City First</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-2">
                            <select name="city_municipality" id="citySelect" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Province First</option>
                            </select>
                            <select name="province" id="provinceSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Province</option>
                                <option value="Metro Manila">Metro Manila</option>
                                <option value="Bulacan">Bulacan</option>
                                <option value="Cavite">Cavite</option>
                                <option value="Laguna">Laguna</option>
                                <option value="Rizal">Rizal</option>
                            </select>
                            <input type="text" name="zip_code" id="zipCode" required pattern="^\d{4}$" title="4-digit ZIP code" placeholder="Auto-filled" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Format: House No. & Street, Barangay, City, Province, ZIP</div>
                    </div>
                    
                    <!-- Vehicle Information -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Make</label>
                        <select name="make" id="ltoMake" onchange="loadLTOModels()" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Make</option>
                            <option value="Toyota">Toyota</option>
                            <option value="Isuzu">Isuzu</option>
                            <option value="Mitsubishi">Mitsubishi</option>
                            <option value="Hyundai">Hyundai</option>
                            <option value="Nissan">Nissan</option>
                            <option value="Honda">Honda</option>
                            <option value="Suzuki">Suzuki</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" name="make_other" id="ltoMakeOther" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 mt-2 hidden" placeholder="Enter make">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                        <select name="model" id="ltoModel" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Make First</option>
                        </select>
                        <input type="text" name="model_other" id="ltoModelOther" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 mt-2 hidden" placeholder="Enter model">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year Model</label>
                        <input type="number" name="year_model" min="1990" max="2030" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Engine Number</label>
                        <input type="text" name="engine_number" required pattern="^[A-Z0-9-]{5,20}$" title="5-20 characters: letters, numbers, dashes only" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onblur="checkDuplicate('engine_number', this.value, 'engineValidation')">
                        <div class="text-xs text-gray-500 mt-1">5-20 characters: letters, numbers, dashes</div>
                        <div id="engineValidation" class="mt-1 hidden"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chassis Number (VIN)</label>
                        <input type="text" name="chassis_number" required pattern="^[A-HJ-NPR-Z0-9]{17}$" title="17-character VIN format" maxlength="17" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onblur="checkDuplicate('chassis_number', this.value, 'chassisValidation')">
                        <div class="text-xs text-gray-500 mt-1">17-character VIN (no I, O, Q)</div>
                        <div id="chassisValidation" class="mt-1 hidden"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Body Type</label>
                        <select name="body_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Body Type</option>
                            <option value="sedan">Sedan</option>
                            <option value="suv">SUV</option>
                            <option value="jeepney">Jeepney</option>
                            <option value="bus">Bus</option>
                            <option value="truck">Truck</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="van">Van</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                        <input type="text" name="color" required pattern="^[A-Za-z\s]{2,20}$" title="2-20 characters, letters and spaces only" placeholder="White, Blue, Red, etc." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <div class="text-xs text-gray-500 mt-1">Vehicle color for PUV color coding compliance</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OR Number (Official Receipt)</label>
                        <input type="text" name="or_number" required pattern="^\d{8}-\d{7}$" title="Format: YYYYMMDD-1234567" placeholder="20241215-1234567" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onblur="checkDuplicate('or_number', this.value, 'orValidation')">
                        <div class="text-xs text-gray-500 mt-1">Format: YYYYMMDD-1234567 (Date-Sequence)</div>
                        <div id="orValidation" class="mt-1 hidden"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CR Number (Certificate of Registration)</label>
                        <input type="text" name="cr_number" required pattern="^\d{4}-\d{6}-\d{1}$" title="Format: 1234-567890-1" placeholder="1234-567890-1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onblur="checkDuplicate('cr_number', this.value, 'crValidation')">
                        <div class="text-xs text-gray-500 mt-1">Format: 1234-567890-1</div>
                        <div id="crValidation" class="mt-1 hidden"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Plate Number (if assigned)</label>
                        <input type="text" name="plate_number" pattern="^[A-Z]{3}\s?\d{3,4}$" title="Format: ABC 123 or ABC 1234" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Leave blank - LTO will assign" onblur="checkDuplicate('plate_number', this.value, 'plateValidationForm')">
                        <div class="text-xs text-gray-500 mt-1">Leave blank for new registration - LTO will assign plate number</div>
                        <div id="plateValidationForm" class="mt-1 hidden"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Classification</label>
                        <select name="classification" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="private">Private</option>
                            <option value="commercial">Commercial</option>
                            <option value="government">Government</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Insurance Policy Number (CTPL)</label>
                        <input type="text" name="insurance_policy" required pattern="^[A-Z]{3}-\d{8}-\d{2}$" title="Format: AIG-12345678-24" placeholder="AIG-12345678-24" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onblur="checkDuplicate('insurance_policy', this.value, 'insuranceValidation')">
                        <div class="text-xs text-gray-500 mt-1">Compulsory Third Party Liability Insurance</div>
                        <div id="insuranceValidation" class="mt-1 hidden"></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Insurance Provider</label>
                        <select name="insurance_provider" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Insurance Provider</option>
                            <option value="AIG Philippines">AIG Philippines</option>
                            <option value="Malayan Insurance">Malayan Insurance</option>
                            <option value="MAPFRE Insurance">MAPFRE Insurance</option>
                            <option value="Pioneer Insurance">Pioneer Insurance</option>
                            <option value="Stronghold Insurance">Stronghold Insurance</option>
                            <option value="FGU Insurance">FGU Insurance</option>
                            <option value="UCPB General Insurance">UCPB General Insurance</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" name="insurance_provider_other" id="insuranceProviderOther" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 mt-2 hidden" placeholder="Enter insurance provider">
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
                        <input type="date" name="registration_date" id="registrationDate" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date (Auto-calculated)</label>
                        <input type="date" name="expiry_date" id="expiryDate" required readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <div class="text-xs text-gray-500 mt-1">Automatically set to 1 year from registration date</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">LTO Office</label>
                        <select name="lto_office" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select LTO Office</option>
                            <option value="LTO Caloocan City">LTO Caloocan City</option>
                            <option value="LTO Quezon City District Office">LTO Quezon City District Office</option>
                            <option value="LTO Manila Central">LTO Manila Central</option>
                            <option value="LTO Makati">LTO Makati</option>
                            <option value="LTO Pasig">LTO Pasig</option>
                            <option value="LTO Marikina">LTO Marikina</option>
                            <option value="LTO Las Piñas">LTO Las Piñas</option>
                            <option value="LTO Muntinlupa">LTO Muntinlupa</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" name="lto_office_other" id="ltoOfficeOther" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 mt-2 hidden" placeholder="Enter LTO office name">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fees Paid</label>
                        <input type="number" step="0.01" name="fees_paid" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">OR/CR Document (Optional)</label>
                        <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <div class="text-xs text-gray-500 mt-1">Upload scanned copy of OR/CR (PDF, JPG, PNG)</div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200 sticky bottom-0 bg-white">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Add LTO Registration</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Registration Modal -->
    <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">LTO Registration Details</h3>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="viewContent" class="p-6">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Approve Registration Modal -->
    <div id="approveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Approve Registration & Assign Plate</h3>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Plate Number</label>
                    <input type="text" id="plateNumberInput" pattern="^[A-Z]{3}\s?\d{3,4}$" title="Format: ABC 123 or ABC 1234" placeholder="ABC 1234" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    <div class="text-xs text-gray-500 mt-1">Format: ABC 123 (old) or ABC 1234 (new)</div>
                    <div id="plateValidation" class="mt-2 hidden">
                        <!-- Validation message will appear here -->
                    </div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-md p-3 mb-4">
                    <div class="flex">
                        <i data-lucide="check-circle" class="w-5 h-5 text-green-400 mr-2"></i>
                        <div>
                            <p class="text-sm font-medium text-green-800">Registration will be approved</p>
                            <p class="text-sm text-green-700">Plate number will be assigned</p>
                            <p class="text-sm text-green-700">Status will change to 'Active'</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeApproveModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                    <button id="approveButton" onclick="confirmApproval()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" disabled>Approve & Assign Plate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Registration Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Reject Registration</h3>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason</label>
                    <textarea id="rejectionReason" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Enter reason for rejection..."></textarea>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-md p-3 mb-4">
                    <div class="flex">
                        <i data-lucide="x-circle" class="w-5 h-5 text-red-400 mr-2"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800">Registration will be rejected</p>
                            <p class="text-sm text-red-700">Status will change to 'Rejected'</p>
                            <p class="text-sm text-red-700">Applicant will need to resubmit</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeRejectModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                    <button onclick="confirmRejection()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Reject Registration</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Renew Registration Modal -->
    <div id="renewalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Renew LTO Registration</h3>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Renewal Period</label>
                    <select id="renewalPeriod" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="1">1 Year</option>
                        <option value="2">2 Years</option>
                        <option value="3">3 Years</option>
                    </select>
                </div>
                <div class="bg-orange-50 border border-orange-200 rounded-md p-3 mb-4">
                    <div class="flex">
                        <i data-lucide="refresh-cw" class="w-5 h-5 text-orange-400 mr-2"></i>
                        <div>
                            <p class="text-sm font-medium text-orange-800">Registration will be renewed</p>
                            <p class="text-sm text-orange-700">New expiry date will be calculated</p>
                            <p class="text-sm text-orange-700">Renewal fee: ₱500.00</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeRenewalModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                    <button onclick="confirmRenewal()" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">Renew Registration</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Renewal Form Modal -->
    <div id="renewalFormModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Renew LTO Registration</h3>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Existing Registration</label>
                    <select id="existingRegistrationSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md" onchange="loadRegistrationDetails()" required>
                        <option value="">Select registration to renew...</option>
                    </select>
                </div>
                
                <div id="registrationDetails" class="hidden bg-gray-50 p-4 rounded-lg mb-4">
                    <h5 class="font-medium text-gray-800 mb-2">Registration Details (Auto-filled)</h5>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><strong>Owner:</strong> <span id="renewalOwnerName"></span></div>
                        <div><strong>Vehicle:</strong> <span id="renewalVehicle"></span></div>
                        <div><strong>Plate:</strong> <span id="renewalPlate"></span></div>
                        <div><strong>Current Expiry:</strong> <span id="renewalCurrentExpiry"></span></div>
                    </div>
                </div>
                
                <!-- Renewal Information -->
                <h4 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">Renewal Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Renewal Period</label>
                        <select id="renewalFormPeriod" class="w-full px-3 py-2 border border-gray-300 rounded-md" onchange="calculateRenewalFee()" required>
                            <option value="1">1 Year</option>
                            <option value="2">2 Years</option>
                            <option value="3">3 Years</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Renewal Fee</label>
                        <input type="number" id="renewalFee" value="500" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">New OR Number</label>
                        <input type="text" id="renewalORNumber" pattern="^\d{8}-\d{7}$" title="Format: YYYYMMDD-1234567" placeholder="20241215-1234567" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <div class="text-xs text-gray-500 mt-1">New Official Receipt for renewal</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Updated Insurance Policy</label>
                        <input type="text" id="renewalInsurancePolicy" pattern="^[A-Z]{3}-\d{8}-\d{2}$" title="Format: AIG-12345678-24" placeholder="AIG-12345678-24" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <div class="text-xs text-gray-500 mt-1">Updated CTPL Insurance Policy</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Insurance Provider</label>
                        <select id="renewalInsuranceProvider" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="">Select Insurance Provider</option>
                            <option value="AIG Philippines">AIG Philippines</option>
                            <option value="Malayan Insurance">Malayan Insurance</option>
                            <option value="MAPFRE Insurance">MAPFRE Insurance</option>
                            <option value="Pioneer Insurance">Pioneer Insurance</option>
                            <option value="Stronghold Insurance">Stronghold Insurance</option>
                            <option value="FGU Insurance">FGU Insurance</option>
                            <option value="UCPB General Insurance">UCPB General Insurance</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" id="renewalInsuranceProviderOther" class="w-full px-3 py-2 border border-gray-300 rounded-md mt-2 hidden" placeholder="Enter insurance provider">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">License Expiry Update</label>
                        <input type="date" id="renewalLicenseExpiry" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <div class="text-xs text-gray-500 mt-1">Updated license expiry date</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">LTO Office</label>
                        <select id="renewalLTOOffice" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="">Select LTO Office</option>
                            <option value="LTO Caloocan City">LTO Caloocan City</option>
                            <option value="LTO Quezon City District Office">LTO Quezon City District Office</option>
                            <option value="LTO Manila Central">LTO Manila Central</option>
                            <option value="LTO Makati">LTO Makati</option>
                            <option value="LTO Pasig">LTO Pasig</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" id="renewalLTOOfficeOther" class="w-full px-3 py-2 border border-gray-300 rounded-md mt-2 hidden" placeholder="Enter LTO office name">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Renewal Date</label>
                        <input type="date" id="renewalDate" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Renewal Documents (Optional)</label>
                        <input type="file" id="renewalDocuments" accept=".pdf,.jpg,.jpeg,.png" multiple class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <div class="text-xs text-gray-500 mt-1">Upload renewal OR/CR and insurance documents</div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t">
                <button onclick="closeRenewalFormModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                <button onclick="submitRenewalForm()" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">Process Renewal</button>
            </div>
        </div>
    </div>

    <!-- New Vehicle Modal -->
    <div id="newVehicleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Add Vehicle to Existing Owner</h3>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Existing Owner</label>
                    <select id="existingOwnerSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md" onchange="loadOwnerDetails()" required>
                        <option value="">Select owner...</option>
                    </select>
                </div>
                
                <div id="ownerDetails" class="hidden bg-gray-50 p-4 rounded-lg mb-4">
                    <h5 class="font-medium text-gray-800 mb-2">Owner Details (Auto-filled)</h5>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><strong>Name:</strong> <span id="newVehicleOwnerName"></span></div>
                        <div><strong>License:</strong> <span id="newVehicleOwnerLicense"></span></div>
                        <div class="col-span-2"><strong>Address:</strong> <span id="newVehicleOwnerAddress"></span></div>
                    </div>
                </div>
                
                <!-- Vehicle Information -->
                <h4 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">New Vehicle Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Make</label>
                        <select id="newVehicleMake" onchange="loadNewVehicleModels()" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="">Select Make</option>
                            <option value="Toyota">Toyota</option>
                            <option value="Isuzu">Isuzu</option>
                            <option value="Mitsubishi">Mitsubishi</option>
                            <option value="Hyundai">Hyundai</option>
                            <option value="Nissan">Nissan</option>
                            <option value="Honda">Honda</option>
                            <option value="Suzuki">Suzuki</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" id="newVehicleMakeOther" class="w-full px-3 py-2 border border-gray-300 rounded-md mt-2 hidden" placeholder="Enter make">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                        <select id="newVehicleModel" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="">Select Make First</option>
                        </select>
                        <input type="text" id="newVehicleModelOther" class="w-full px-3 py-2 border border-gray-300 rounded-md mt-2 hidden" placeholder="Enter model">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year Model</label>
                        <input type="number" id="newVehicleYear" min="1990" max="2030" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Body Type</label>
                        <select id="newVehicleBodyType" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="">Select Body Type</option>
                            <option value="sedan">Sedan</option>
                            <option value="suv">SUV</option>
                            <option value="jeepney">Jeepney</option>
                            <option value="bus">Bus</option>
                            <option value="truck">Truck</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="van">Van</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                        <input type="text" id="newVehicleColor" pattern="^[A-Za-z\s]{2,20}$" title="2-20 characters, letters and spaces only" placeholder="White, Blue, Red, etc." class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Engine Number</label>
                        <input type="text" id="newVehicleEngine" pattern="^[A-Z0-9-]{5,20}$" title="5-20 characters: letters, numbers, dashes only" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <div class="text-xs text-gray-500 mt-1">5-20 characters: letters, numbers, dashes</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chassis Number (VIN)</label>
                        <input type="text" id="newVehicleChassis" pattern="^[A-HJ-NPR-Z0-9]{17}$" title="17-character VIN format" maxlength="17" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <div class="text-xs text-gray-500 mt-1">17-character VIN (no I, O, Q)</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Classification</label>
                        <select id="newVehicleClassification" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="">Select Classification</option>
                            <option value="private">Private</option>
                            <option value="commercial">Commercial</option>
                            <option value="government">Government</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">OR Number (Official Receipt)</label>
                        <input type="text" id="newVehicleOR" pattern="^\d{8}-\d{7}$" title="Format: YYYYMMDD-1234567" placeholder="20241215-1234567" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <div class="text-xs text-gray-500 mt-1">Format: YYYYMMDD-1234567</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CR Number (Certificate of Registration)</label>
                        <input type="text" id="newVehicleCR" pattern="^\d{4}-\d{6}-\d{1}$" title="Format: 1234-567890-1" placeholder="1234-567890-1" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <div class="text-xs text-gray-500 mt-1">Format: 1234-567890-1</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Insurance Policy Number (CTPL)</label>
                        <input type="text" id="newVehicleInsurancePolicy" pattern="^[A-Z]{3}-\d{8}-\d{2}$" title="Format: AIG-12345678-24" placeholder="AIG-12345678-24" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <div class="text-xs text-gray-500 mt-1">Compulsory Third Party Liability Insurance</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Insurance Provider</label>
                        <select id="newVehicleInsuranceProvider" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="">Select Insurance Provider</option>
                            <option value="AIG Philippines">AIG Philippines</option>
                            <option value="Malayan Insurance">Malayan Insurance</option>
                            <option value="MAPFRE Insurance">MAPFRE Insurance</option>
                            <option value="Pioneer Insurance">Pioneer Insurance</option>
                            <option value="Stronghold Insurance">Stronghold Insurance</option>
                            <option value="FGU Insurance">FGU Insurance</option>
                            <option value="UCPB General Insurance">UCPB General Insurance</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" id="newVehicleInsuranceProviderOther" class="w-full px-3 py-2 border border-gray-300 rounded-md mt-2 hidden" placeholder="Enter insurance provider">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Registration Date</label>
                        <input type="date" id="newVehicleRegistrationDate" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date (Auto-calculated)</label>
                        <input type="date" id="newVehicleExpiryDate" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                        <div class="text-xs text-gray-500 mt-1">Automatically set to 1 year from registration date</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">LTO Office</label>
                        <select id="newVehicleLTOOffice" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="">Select LTO Office</option>
                            <option value="LTO Caloocan City">LTO Caloocan City</option>
                            <option value="LTO Quezon City District Office">LTO Quezon City District Office</option>
                            <option value="LTO Manila Central">LTO Manila Central</option>
                            <option value="LTO Makati">LTO Makati</option>
                            <option value="LTO Pasig">LTO Pasig</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" id="newVehicleLTOOfficeOther" class="w-full px-3 py-2 border border-gray-300 rounded-md mt-2 hidden" placeholder="Enter LTO office name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fees Paid</label>
                        <input type="number" step="0.01" id="newVehicleFeesPaid" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Documents (Optional)</label>
                        <input type="file" id="newVehicleDocuments" accept=".pdf,.jpg,.jpeg,.png" multiple class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <div class="text-xs text-gray-500 mt-1">Upload OR/CR and insurance documents</div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3 p-6 border-t">
                <button onclick="closeNewVehicleModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                <button onclick="submitNewVehicle()" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">Add Vehicle</button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        

        
        // Vehicle make/model data for LTO registration
        const ltoVehicleModels = {
            'Toyota': ['Hiace', 'Coaster', 'Innova', 'Vios', 'Avanza', 'Wigo', 'Rush'],
            'Isuzu': ['Elf', 'Forward', 'Crosswind', 'D-Max', 'Traviz', 'Alterra'],
            'Mitsubishi': ['L300', 'Fuso', 'Adventure', 'Montero', 'Mirage', 'Xpander'],
            'Hyundai': ['County', 'H100', 'Starex', 'Accent', 'Tucson', 'Eon'],
            'Nissan': ['Urvan', 'Navara', 'Patrol', 'Almera', 'X-Trail', 'Juke'],
            'Honda': ['City', 'Civic', 'CR-V', 'BR-V', 'Mobilio', 'Brio'],
            'Suzuki': ['Ertiga', 'Swift', 'Celerio', 'Jimny', 'Vitara', 'APV']
        };
        
        function loadLTOModels() {
            const make = document.getElementById('ltoMake').value;
            const modelSelect = document.getElementById('ltoModel');
            const makeOther = document.getElementById('ltoMakeOther');
            const modelOther = document.getElementById('ltoModelOther');
            
            if (make === 'Other') {
                makeOther.classList.remove('hidden');
                modelSelect.classList.add('hidden');
                modelOther.classList.remove('hidden');
                makeOther.required = true;
                modelOther.required = true;
                modelSelect.required = false;
            } else {
                makeOther.classList.add('hidden');
                modelSelect.classList.remove('hidden');
                modelOther.classList.add('hidden');
                makeOther.required = false;
                modelOther.required = false;
                modelSelect.required = true;
                
                modelSelect.innerHTML = '<option value="">Select Model</option>';
                if (make && ltoVehicleModels[make]) {
                    ltoVehicleModels[make].forEach(model => {
                        modelSelect.innerHTML += `<option value="${model}">${model}</option>`;
                    });
                    modelSelect.innerHTML += '<option value="Other">Other</option>';
                }
            }
            
            // Handle model Other option
            modelSelect.addEventListener('change', function() {
                if (this.value === 'Other') {
                    modelOther.classList.remove('hidden');
                    modelOther.required = true;
                } else {
                    modelOther.classList.add('hidden');
                    modelOther.required = false;
                }
            });
        }
        
        // Add input formatting for validation
        document.addEventListener('DOMContentLoaded', function() {
            // Plate number validation for approve modal
            const plateInput = document.getElementById('plateNumberInput');
            if (plateInput) {
                plateInput.addEventListener('input', function() {
                    let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    if (value.length >= 6) {
                        value = value.substring(0, 3) + ' ' + value.substring(3);
                    }
                    this.value = value;
                    
                    // Debounce validation
                    clearTimeout(this.validationTimeout);
                    this.validationTimeout = setTimeout(validatePlateNumber, 500);
                });
            }
            
            const addFormPlateInput = document.querySelector('input[name="plate_number"]');
            const engineInput = document.querySelector('input[name="engine_number"]');
            const chassisInput = document.querySelector('input[name="chassis_number"]');
            const orInput = document.querySelector('input[name="or_number"]');
            const crInput = document.querySelector('input[name="cr_number"]');
            const insuranceInput = document.querySelector('input[name="insurance_policy"]');
            const insuranceProvider = document.querySelector('select[name="insurance_provider"]');
            const ltoOffice = document.querySelector('select[name="lto_office"]');
            const zipInput = document.querySelector('input[name="zip_code"]');
            const registrationDate = document.getElementById('registrationDate');
            const expiryDate = document.getElementById('expiryDate');
            
            if (addFormPlateInput) {
                addFormPlateInput.addEventListener('input', function() {
                    let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    if (value.length >= 6) {
                        value = value.substring(0, 3) + ' ' + value.substring(3);
                    }
                    this.value = value;
                });
            }
            
            if (engineInput) {
                engineInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }
            
            if (chassisInput) {
                chassisInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }
            
            // OR Number formatting (YYYYMMDD-1234567)
            if (orInput) {
                orInput.addEventListener('input', function() {
                    let value = this.value.replace(/[^0-9]/g, '');
                    if (value.length > 8) {
                        value = value.substring(0, 8) + '-' + value.substring(8, 15);
                    }
                    this.value = value;
                });
            }
            
            // CR Number formatting (1234-567890-1)
            if (crInput) {
                crInput.addEventListener('input', function() {
                    let value = this.value.replace(/[^0-9]/g, '');
                    if (value.length > 4 && value.length <= 10) {
                        value = value.substring(0, 4) + '-' + value.substring(4);
                    } else if (value.length > 10) {
                        value = value.substring(0, 4) + '-' + value.substring(4, 10) + '-' + value.substring(10, 11);
                    }
                    this.value = value;
                });
            }
            
            // License Number formatting (D12-34-567890 or N-123456789)
            const licenseInput = document.querySelector('input[name="license_number"]');
            if (licenseInput) {
                licenseInput.addEventListener('input', function() {
                    let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    
                    // Old format: D12-34-567890 (letter + 2 digits + dash + 2 digits + dash + 6 digits)
                    if (value.length > 0 && /^[A-Z]/.test(value)) {
                        if (value.length > 3 && value.length <= 5) {
                            value = value.substring(0, 3) + '-' + value.substring(3);
                        } else if (value.length > 5) {
                            value = value.substring(0, 3) + '-' + value.substring(3, 5) + '-' + value.substring(5, 11);
                        }
                    }
                    // New format: N-123456789 or 02-123456789 (1-2 digits + dash + 9 digits)
                    else if (value.length > 0 && /^\d/.test(value)) {
                        if (value.length > 2) {
                            value = value.substring(0, 2) + '-' + value.substring(2, 11);
                        }
                    }
                    
                    this.value = value;
                });
            }
            
            // Insurance Policy formatting (AIG-12345678-24)
            if (insuranceInput) {
                insuranceInput.addEventListener('input', function() {
                    let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    if (value.length > 3 && value.length <= 11) {
                        value = value.substring(0, 3) + '-' + value.substring(3);
                    } else if (value.length > 11) {
                        value = value.substring(0, 3) + '-' + value.substring(3, 11) + '-' + value.substring(11, 13);
                    }
                    this.value = value;
                });
            }
            
            // Insurance Provider Other option
            if (insuranceProvider) {
                insuranceProvider.addEventListener('change', function() {
                    const otherInput = document.getElementById('insuranceProviderOther');
                    if (this.value === 'Other') {
                        otherInput.classList.remove('hidden');
                        otherInput.required = true;
                    } else {
                        otherInput.classList.add('hidden');
                        otherInput.required = false;
                    }
                });
            }
            
            // LTO Office Other option
            if (ltoOffice) {
                ltoOffice.addEventListener('change', function() {
                    const otherInput = document.getElementById('ltoOfficeOther');
                    if (this.value === 'Other') {
                        otherInput.classList.remove('hidden');
                        otherInput.required = true;
                    } else {
                        otherInput.classList.add('hidden');
                        otherInput.required = false;
                    }
                });
            }
            
            // Address data with ZIP codes per barangay
            const addressData = {
                'Metro Manila': {
                    'Caloocan City': {
                        barangays: {
                            'Barangay 1': '1400',
                            'Barangay 2': '1401', 
                            'Bagong Silang': '1428',
                            'Camarin': '1422',
                            'Novaliches': '1123'
                        }
                    },
                    'Manila': {
                        barangays: {
                            'Ermita': '1000',
                            'Malate': '1004',
                            'Intramuros': '1002',
                            'Binondo': '1006',
                            'Quiapo': '1001'
                        }
                    },
                    'Quezon City': {
                        barangays: {
                            'Commonwealth': '1121',
                            'Fairview': '1118',
                            'Novaliches': '1123',
                            'Project 4': '1109',
                            'Diliman': '1101'
                        }
                    },
                    'Makati': {
                        barangays: {
                            'Poblacion': '1210',
                            'Bel-Air': '1209',
                            'Forbes Park': '1220',
                            'Salcedo Village': '1227',
                            'Legazpi Village': '1229'
                        }
                    }
                },
                'Bulacan': {
                    'Malolos': {
                        barangays: {
                            'Barasoain': '3000',
                            'Bulihan': '3000',
                            'Canalate': '3000',
                            'Dakila': '3000',
                            'Guinhawa': '3000'
                        }
                    }
                }
            };
            
            function loadCities() {
                const province = document.getElementById('provinceSelect').value;
                const citySelect = document.getElementById('citySelect');
                const barangaySelect = document.getElementById('barangaySelect');
                const zipCode = document.getElementById('zipCode');
                
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                barangaySelect.innerHTML = '<option value="">Select City First</option>';
                zipCode.value = '';
                
                if (province && addressData[province]) {
                    Object.keys(addressData[province]).forEach(city => {
                        citySelect.innerHTML += `<option value="${city}">${city}</option>`;
                    });
                }
            }
            
            function loadBarangays() {
                const province = document.getElementById('provinceSelect').value;
                const city = document.getElementById('citySelect').value;
                const barangaySelect = document.getElementById('barangaySelect');
                const zipCode = document.getElementById('zipCode');
                
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                zipCode.value = ''; // Clear ZIP until barangay is selected
                
                if (province && city && addressData[province] && addressData[province][city]) {
                    const cityData = addressData[province][city];
                    
                    // Load barangays
                    Object.keys(cityData.barangays).forEach(barangay => {
                        barangaySelect.innerHTML += `<option value="${barangay}">${barangay}</option>`;
                    });
                }
            }
            
            function updateZipCode() {
                const province = document.getElementById('provinceSelect').value;
                const city = document.getElementById('citySelect').value;
                const barangay = document.getElementById('barangaySelect').value;
                const zipCode = document.getElementById('zipCode');
                
                if (province && city && barangay && addressData[province] && addressData[province][city]) {
                    const zipValue = addressData[province][city].barangays[barangay];
                    if (zipValue) {
                        zipCode.value = zipValue;
                    }
                }
            }
            
            // Add event listeners
            if (document.getElementById('provinceSelect')) {
                document.getElementById('provinceSelect').addEventListener('change', loadCities);
            }
            
            if (document.getElementById('citySelect')) {
                document.getElementById('citySelect').addEventListener('change', loadBarangays);
            }
            
            if (document.getElementById('barangaySelect')) {
                document.getElementById('barangaySelect').addEventListener('change', updateZipCode);
            }
            
            // Auto-calculate expiry date (1 year from registration)
            if (registrationDate) {
                registrationDate.addEventListener('change', function() {
                    if (this.value) {
                        const regDate = new Date(this.value);
                        const expDate = new Date(regDate);
                        expDate.setFullYear(regDate.getFullYear() + 1);
                        
                        // Format date as YYYY-MM-DD for input field
                        const formattedDate = expDate.toISOString().split('T')[0];
                        expiryDate.value = formattedDate;
                    }
                });
            }
            
            // Add validation for renewal form fields
            const renewalORInput = document.getElementById('renewalORNumber');
            const renewalInsuranceInput = document.getElementById('renewalInsurancePolicy');
            const renewalInsuranceProvider = document.getElementById('renewalInsuranceProvider');
            const renewalLTOOffice = document.getElementById('renewalLTOOffice');
            
            if (renewalORInput) {
                renewalORInput.addEventListener('input', function() {
                    let value = this.value.replace(/[^0-9]/g, '');
                    if (value.length > 8) {
                        value = value.substring(0, 8) + '-' + value.substring(8, 15);
                    }
                    this.value = value;
                });
            }
            
            if (renewalInsuranceInput) {
                renewalInsuranceInput.addEventListener('input', function() {
                    let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    if (value.length > 3 && value.length <= 11) {
                        value = value.substring(0, 3) + '-' + value.substring(3);
                    } else if (value.length > 11) {
                        value = value.substring(0, 3) + '-' + value.substring(3, 11) + '-' + value.substring(11, 13);
                    }
                    this.value = value;
                });
            }
            
            if (renewalInsuranceProvider) {
                renewalInsuranceProvider.addEventListener('change', function() {
                    const otherInput = document.getElementById('renewalInsuranceProviderOther');
                    if (this.value === 'Other') {
                        otherInput.classList.remove('hidden');
                        otherInput.required = true;
                    } else {
                        otherInput.classList.add('hidden');
                        otherInput.required = false;
                    }
                });
            }
            
            if (renewalLTOOffice) {
                renewalLTOOffice.addEventListener('change', function() {
                    const otherInput = document.getElementById('renewalLTOOfficeOther');
                    if (this.value === 'Other') {
                        otherInput.classList.remove('hidden');
                        otherInput.required = true;
                    } else {
                        otherInput.classList.add('hidden');
                        otherInput.required = false;
                    }
                });
            }
            
            // Add validation for new vehicle form fields
            const newVehicleEngine = document.getElementById('newVehicleEngine');
            const newVehicleChassis = document.getElementById('newVehicleChassis');
            const newVehicleOR = document.getElementById('newVehicleOR');
            const newVehicleCR = document.getElementById('newVehicleCR');
            const newVehicleInsurancePolicy = document.getElementById('newVehicleInsurancePolicy');
            const newVehicleInsuranceProvider = document.getElementById('newVehicleInsuranceProvider');
            const newVehicleLTOOffice = document.getElementById('newVehicleLTOOffice');
            const newVehicleRegistrationDate = document.getElementById('newVehicleRegistrationDate');
            const newVehicleExpiryDate = document.getElementById('newVehicleExpiryDate');
            
            if (newVehicleEngine) {
                newVehicleEngine.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }
            
            if (newVehicleChassis) {
                newVehicleChassis.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }
            
            if (newVehicleOR) {
                newVehicleOR.addEventListener('input', function() {
                    let value = this.value.replace(/[^0-9]/g, '');
                    if (value.length > 8) {
                        value = value.substring(0, 8) + '-' + value.substring(8, 15);
                    }
                    this.value = value;
                });
            }
            
            if (newVehicleCR) {
                newVehicleCR.addEventListener('input', function() {
                    let value = this.value.replace(/[^0-9]/g, '');
                    if (value.length > 4 && value.length <= 10) {
                        value = value.substring(0, 4) + '-' + value.substring(4);
                    } else if (value.length > 10) {
                        value = value.substring(0, 4) + '-' + value.substring(4, 10) + '-' + value.substring(10, 11);
                    }
                    this.value = value;
                });
            }
            
            if (newVehicleInsurancePolicy) {
                newVehicleInsurancePolicy.addEventListener('input', function() {
                    let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    if (value.length > 3 && value.length <= 11) {
                        value = value.substring(0, 3) + '-' + value.substring(3);
                    } else if (value.length > 11) {
                        value = value.substring(0, 3) + '-' + value.substring(3, 11) + '-' + value.substring(11, 13);
                    }
                    this.value = value;
                });
            }
            
            if (newVehicleInsuranceProvider) {
                newVehicleInsuranceProvider.addEventListener('change', function() {
                    const otherInput = document.getElementById('newVehicleInsuranceProviderOther');
                    if (this.value === 'Other') {
                        otherInput.classList.remove('hidden');
                        otherInput.required = true;
                    } else {
                        otherInput.classList.add('hidden');
                        otherInput.required = false;
                    }
                });
            }
            
            if (newVehicleLTOOffice) {
                newVehicleLTOOffice.addEventListener('change', function() {
                    const otherInput = document.getElementById('newVehicleLTOOfficeOther');
                    if (this.value === 'Other') {
                        otherInput.classList.remove('hidden');
                        otherInput.required = true;
                    } else {
                        otherInput.classList.add('hidden');
                        otherInput.required = false;
                    }
                });
            }
            
            // Auto-calculate expiry date for new vehicle
            if (newVehicleRegistrationDate) {
                newVehicleRegistrationDate.addEventListener('change', function() {
                    if (this.value) {
                        const regDate = new Date(this.value);
                        const expDate = new Date(regDate);
                        expDate.setFullYear(regDate.getFullYear() + 1);
                        
                        const formattedDate = expDate.toISOString().split('T')[0];
                        newVehicleExpiryDate.value = formattedDate;
                    }
                });
            }
        });

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

        let currentLtoId = null;

        function viewRegistration(ltoId) {
            fetch('lto_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'get_registration_details',
                    lto_id: ltoId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('viewContent').innerHTML = data.html;
                    document.getElementById('viewModal').classList.remove('hidden');
                    document.getElementById('viewModal').classList.add('flex');
                } else {
                    alert('Error loading registration details');
                }
            })
            .catch(error => {
                alert('Network error occurred');
            });
        }

        function openApproveModal(ltoId) {
            currentLtoId = ltoId;
            document.getElementById('plateNumberInput').value = '';
            document.getElementById('plateValidation').classList.add('hidden');
            document.getElementById('approveButton').disabled = true;
            document.getElementById('approveModal').classList.remove('hidden');
            document.getElementById('approveModal').classList.add('flex');
        }

        function openRejectModal(ltoId) {
            currentLtoId = ltoId;
            document.getElementById('rejectionReason').value = '';
            document.getElementById('rejectModal').classList.remove('hidden');
            document.getElementById('rejectModal').classList.add('flex');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('viewModal').classList.remove('flex');
        }

        function closeApproveModal() {
            document.getElementById('approveModal').classList.add('hidden');
            document.getElementById('approveModal').classList.remove('flex');
            currentLtoId = null;
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejectModal').classList.remove('flex');
            currentLtoId = null;
        }

        function openRenewalModal(ltoId) {
            currentLtoId = ltoId;
            document.getElementById('renewalPeriod').value = '1';
            document.getElementById('renewalModal').classList.remove('hidden');
            document.getElementById('renewalModal').classList.add('flex');
        }

        function closeRenewalModal() {
            document.getElementById('renewalModal').classList.add('hidden');
            document.getElementById('renewalModal').classList.remove('flex');
            currentLtoId = null;
        }

        function validatePlateNumber() {
            const plateInput = document.getElementById('plateNumberInput');
            const validation = document.getElementById('plateValidation');
            const approveButton = document.getElementById('approveButton');
            const plateNumber = plateInput.value.trim().toUpperCase();
            
            if (!plateNumber) {
                validation.classList.add('hidden');
                approveButton.disabled = true;
                return;
            }
            
            // Format validation
            const platePattern = /^[A-Z]{3}\s?\d{3,4}$/;
            if (!platePattern.test(plateNumber)) {
                validation.innerHTML = '<div class="flex items-center text-red-600"><i data-lucide="x-circle" class="w-4 h-4 mr-1"></i><span class="text-xs">Invalid format. Use ABC 123 or ABC 1234</span></div>';
                validation.classList.remove('hidden');
                approveButton.disabled = true;
                lucide.createIcons();
                return;
            }
            
            // Check if plate exists
            fetch('lto_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'check_plate_exists',
                    plate_number: plateNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    validation.innerHTML = '<div class="flex items-center text-red-600"><i data-lucide="x-circle" class="w-4 h-4 mr-1"></i><span class="text-xs">Plate number already exists</span></div>';
                    validation.classList.remove('hidden');
                    approveButton.disabled = true;
                } else {
                    validation.innerHTML = '<div class="flex items-center text-green-600"><i data-lucide="check-circle" class="w-4 h-4 mr-1"></i><span class="text-xs">Plate number available</span></div>';
                    validation.classList.remove('hidden');
                    approveButton.disabled = false;
                }
                lucide.createIcons();
            })
            .catch(error => {
                validation.innerHTML = '<div class="flex items-center text-red-600"><i data-lucide="x-circle" class="w-4 h-4 mr-1"></i><span class="text-xs">Error checking plate number</span></div>';
                validation.classList.remove('hidden');
                approveButton.disabled = true;
                lucide.createIcons();
            });
        }
        
        function confirmApproval() {
            const plateNumber = document.getElementById('plateNumberInput').value.trim().toUpperCase();
            if (!currentLtoId || !plateNumber) return;
            
            fetch('lto_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'approve_registration',
                    lto_id: currentLtoId,
                    plate_number: plateNumber
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registration approved and plate number assigned: ' + plateNumber);
                    closeApproveModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error occurred');
            });
        }

        function confirmRejection() {
            const reason = document.getElementById('rejectionReason').value.trim();
            if (!reason) {
                alert('Please enter a rejection reason');
                return;
            }
            if (!currentLtoId) return;
            
            fetch('lto_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'reject_registration',
                    lto_id: currentLtoId,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registration rejected successfully');
                    closeRejectModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error occurred');
            });
        }

        function confirmRenewal() {
            const renewalPeriod = document.getElementById('renewalPeriod').value;
            if (!currentLtoId) return;
            
            fetch('lto_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'renew_registration',
                    lto_id: currentLtoId,
                    renewal_period: renewalPeriod
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registration renewed successfully! New expiry: ' + data.new_expiry);
                    closeRenewalModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error occurred');
            });
        }

        function openRenewalFormModal() {
            loadActiveRegistrations();
            document.getElementById('renewalFormModal').classList.remove('hidden');
            document.getElementById('renewalFormModal').classList.add('flex');
        }
        
        function openRenewalFormModalForRegistration(ltoId) {
            loadActiveRegistrations();
            document.getElementById('renewalFormModal').classList.remove('hidden');
            document.getElementById('renewalFormModal').classList.add('flex');
            
            // Pre-select the registration after a short delay to ensure options are loaded
            setTimeout(() => {
                const select = document.getElementById('existingRegistrationSelect');
                select.value = ltoId;
                loadRegistrationDetails();
            }, 500);
        }

        function closeRenewalFormModal() {
            document.getElementById('renewalFormModal').classList.add('hidden');
            document.getElementById('renewalFormModal').classList.remove('flex');
        }

        function openNewVehicleModal() {
            loadExistingOwners();
            document.getElementById('newVehicleModal').classList.remove('hidden');
            document.getElementById('newVehicleModal').classList.add('flex');
        }

        function closeNewVehicleModal() {
            document.getElementById('newVehicleModal').classList.add('hidden');
            document.getElementById('newVehicleModal').classList.remove('flex');
        }

        function loadActiveRegistrations() {
            fetch('lto_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'get_active_registrations'
                })
            })
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('existingRegistrationSelect');
                select.innerHTML = '<option value="">Select registration to renew...</option>';
                if (data.success && data.registrations) {
                    data.registrations.forEach(reg => {
                        select.innerHTML += `<option value="${reg.lto_registration_id}">${reg.owner_name} - ${reg.plate_number} (${reg.make} ${reg.model})</option>`;
                    });
                }
            })
            .catch(error => console.error('Error loading registrations:', error));
        }

        function loadRegistrationDetails() {
            const ltoId = document.getElementById('existingRegistrationSelect').value;
            if (!ltoId) {
                document.getElementById('registrationDetails').classList.add('hidden');
                return;
            }
            
            fetch('lto_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'get_registration_details',
                    lto_id: ltoId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.registration) {
                    const reg = data.registration;
                    document.getElementById('renewalOwnerName').textContent = `${reg.owner_first_name} ${reg.owner_last_name}`;
                    document.getElementById('renewalVehicle').textContent = `${reg.make} ${reg.model} (${reg.year_model})`;
                    document.getElementById('renewalPlate').textContent = reg.plate_number;
                    document.getElementById('renewalCurrentExpiry').textContent = new Date(reg.expiry_date).toLocaleDateString();
                    document.getElementById('registrationDetails').classList.remove('hidden');
                }
            })
            .catch(error => console.error('Error loading registration details:', error));
        }

        function loadExistingOwners() {
            fetch('lto_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'get_existing_owners'
                })
            })
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('existingOwnerSelect');
                select.innerHTML = '<option value="">Select owner...</option>';
                if (data.success && data.owners) {
                    data.owners.forEach(owner => {
                        select.innerHTML += `<option value="${owner.lto_registration_id}">${owner.owner_name} (${owner.license_number})</option>`;
                    });
                }
            })
            .catch(error => console.error('Error loading owners:', error));
        }

        function loadOwnerDetails() {
            const ltoId = document.getElementById('existingOwnerSelect').value;
            if (!ltoId) {
                document.getElementById('ownerDetails').classList.add('hidden');
                return;
            }
            
            fetch('lto_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'get_registration_details',
                    lto_id: ltoId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.registration) {
                    const reg = data.registration;
                    document.getElementById('newVehicleOwnerName').textContent = `${reg.owner_first_name} ${reg.owner_last_name}`;
                    document.getElementById('newVehicleOwnerLicense').textContent = reg.license_number;
                    document.getElementById('newVehicleOwnerAddress').textContent = reg.owner_address;
                    document.getElementById('ownerDetails').classList.remove('hidden');
                }
            })
            .catch(error => console.error('Error loading owner details:', error));
        }

        function loadNewVehicleModels() {
            const make = document.getElementById('newVehicleMake').value;
            const modelSelect = document.getElementById('newVehicleModel');
            const makeOther = document.getElementById('newVehicleMakeOther');
            const modelOther = document.getElementById('newVehicleModelOther');
            
            if (make === 'Other') {
                makeOther.classList.remove('hidden');
                modelSelect.classList.add('hidden');
                modelOther.classList.remove('hidden');
                makeOther.required = true;
                modelOther.required = true;
                modelSelect.required = false;
            } else {
                makeOther.classList.add('hidden');
                modelSelect.classList.remove('hidden');
                modelOther.classList.add('hidden');
                makeOther.required = false;
                modelOther.required = false;
                modelSelect.required = true;
                
                modelSelect.innerHTML = '<option value="">Select Model</option>';
                if (make && ltoVehicleModels[make]) {
                    ltoVehicleModels[make].forEach(model => {
                        modelSelect.innerHTML += `<option value="${model}">${model}</option>`;
                    });
                    modelSelect.innerHTML += '<option value="Other">Other</option>';
                }
            }
            
            // Handle model Other option
            modelSelect.addEventListener('change', function() {
                if (this.value === 'Other') {
                    modelOther.classList.remove('hidden');
                    modelOther.required = true;
                } else {
                    modelOther.classList.add('hidden');
                    modelOther.required = false;
                }
            });
        }
        
        function calculateRenewalFee() {
            const period = document.getElementById('renewalFormPeriod').value;
            const feeInput = document.getElementById('renewalFee');
            const baseFee = 500;
            const totalFee = baseFee * parseInt(period);
            feeInput.value = totalFee;
        }
        
        function checkDuplicate(fieldName, value, validationId) {
            const validationDiv = document.getElementById(validationId);
            
            if (!value || value.length < 3) {
                validationDiv.classList.add('hidden');
                return;
            }
            
            fetch('lto_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'check_duplicate_field',
                    field_name: fieldName,
                    field_value: value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    validationDiv.innerHTML = '<div class="flex items-center text-red-600"><i data-lucide="x-circle" class="w-4 h-4 mr-1"></i><span class="text-xs">This ' + fieldName.replace('_', ' ') + ' already exists</span></div>';
                    validationDiv.classList.remove('hidden');
                } else {
                    validationDiv.innerHTML = '<div class="flex items-center text-green-600"><i data-lucide="check-circle" class="w-4 h-4 mr-1"></i><span class="text-xs">Available</span></div>';
                    validationDiv.classList.remove('hidden');
                }
                lucide.createIcons();
            })
            .catch(error => {
                validationDiv.innerHTML = '<div class="flex items-center text-gray-500"><i data-lucide="alert-circle" class="w-4 h-4 mr-1"></i><span class="text-xs">Unable to check</span></div>';
                validationDiv.classList.remove('hidden');
                lucide.createIcons();
            });
        }

        function submitRenewalForm() {
            const ltoId = document.getElementById('existingRegistrationSelect').value;
            const renewalPeriod = document.getElementById('renewalFormPeriod').value;
            const renewalFee = document.getElementById('renewalFee').value;
            const orNumber = document.getElementById('renewalORNumber').value;
            const insurancePolicy = document.getElementById('renewalInsurancePolicy').value;
            const insuranceProvider = document.getElementById('renewalInsuranceProvider').value;
            const insuranceProviderOther = document.getElementById('renewalInsuranceProviderOther').value;
            const licenseExpiry = document.getElementById('renewalLicenseExpiry').value;
            const ltoOffice = document.getElementById('renewalLTOOffice').value;
            const ltoOfficeOther = document.getElementById('renewalLTOOfficeOther').value;
            const renewalDate = document.getElementById('renewalDate').value;
            
            // Validation
            if (!ltoId) {
                alert('Please select a registration to renew');
                return;
            }
            
            const finalInsuranceProvider = insuranceProvider === 'Other' ? insuranceProviderOther : insuranceProvider;
            const finalLTOOffice = ltoOffice === 'Other' ? ltoOfficeOther : ltoOffice;
            
            if (!renewalPeriod || !orNumber || !insurancePolicy || !finalInsuranceProvider || !licenseExpiry || !finalLTOOffice || !renewalDate) {
                alert('Please fill all required fields');
                return;
            }
            
            // Validate Other fields
            if (insuranceProvider === 'Other' && !insuranceProviderOther) {
                alert('Please specify the insurance provider');
                return;
            }
            
            if (ltoOffice === 'Other' && !ltoOfficeOther) {
                alert('Please specify the LTO office');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'renew_registration_full');
            formData.append('lto_id', ltoId);
            formData.append('renewal_period', renewalPeriod);
            formData.append('renewal_fee', renewalFee);
            formData.append('or_number', orNumber);
            formData.append('insurance_policy', insurancePolicy);
            formData.append('insurance_provider', finalInsuranceProvider);
            formData.append('license_expiry', licenseExpiry);
            formData.append('lto_office', finalLTOOffice);
            formData.append('renewal_date', renewalDate);
            
            // Add documents if any
            const documents = document.getElementById('renewalDocuments').files;
            if (documents.length > 0) {
                for (let i = 0; i < documents.length; i++) {
                    formData.append('documents[]', documents[i]);
                }
            }
            
            fetch('lto_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registration renewed successfully! New expiry: ' + data.new_expiry);
                    closeRenewalFormModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error occurred');
            });
        }

        function submitNewVehicle() {
            const ownerLtoId = document.getElementById('existingOwnerSelect').value;
            const make = document.getElementById('newVehicleMake').value;
            const makeOther = document.getElementById('newVehicleMakeOther').value;
            const model = document.getElementById('newVehicleModel').value;
            const modelOther = document.getElementById('newVehicleModelOther').value;
            const year = document.getElementById('newVehicleYear').value;
            const bodyType = document.getElementById('newVehicleBodyType').value;
            const color = document.getElementById('newVehicleColor').value;
            const engine = document.getElementById('newVehicleEngine').value;
            const chassis = document.getElementById('newVehicleChassis').value;
            const classification = document.getElementById('newVehicleClassification').value;
            const orNumber = document.getElementById('newVehicleOR').value;
            const crNumber = document.getElementById('newVehicleCR').value;
            const insurancePolicy = document.getElementById('newVehicleInsurancePolicy').value;
            const insuranceProvider = document.getElementById('newVehicleInsuranceProvider').value;
            const insuranceProviderOther = document.getElementById('newVehicleInsuranceProviderOther').value;
            const registrationDate = document.getElementById('newVehicleRegistrationDate').value;
            const expiryDate = document.getElementById('newVehicleExpiryDate').value;
            const ltoOffice = document.getElementById('newVehicleLTOOffice').value;
            const ltoOfficeOther = document.getElementById('newVehicleLTOOfficeOther').value;
            const feesPaid = document.getElementById('newVehicleFeesPaid').value;
            
            // Validation
            if (!ownerLtoId) {
                alert('Please select an owner');
                return;
            }
            
            const finalMake = make === 'Other' ? makeOther : make;
            const finalModel = model === 'Other' ? modelOther : model;
            const finalInsuranceProvider = insuranceProvider === 'Other' ? insuranceProviderOther : insuranceProvider;
            const finalLTOOffice = ltoOffice === 'Other' ? ltoOfficeOther : ltoOffice;
            
            if (!finalMake || !finalModel || !year || !bodyType || !color || !engine || !chassis || !classification || !orNumber || !crNumber || !insurancePolicy || !finalInsuranceProvider || !registrationDate || !finalLTOOffice || !feesPaid) {
                alert('Please fill all required fields');
                return;
            }
            
            // Validate Other fields
            if (make === 'Other' && !makeOther) {
                alert('Please specify the vehicle make');
                return;
            }
            
            if (model === 'Other' && !modelOther) {
                alert('Please specify the vehicle model');
                return;
            }
            
            if (insuranceProvider === 'Other' && !insuranceProviderOther) {
                alert('Please specify the insurance provider');
                return;
            }
            
            if (ltoOffice === 'Other' && !ltoOfficeOther) {
                alert('Please specify the LTO office');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'add_vehicle_to_owner');
            formData.append('owner_lto_id', ownerLtoId);
            formData.append('make', finalMake);
            formData.append('model', finalModel);
            formData.append('year_model', year);
            formData.append('body_type', bodyType);
            formData.append('color', color);
            formData.append('engine_number', engine);
            formData.append('chassis_number', chassis);
            formData.append('classification', classification);
            formData.append('or_number', orNumber);
            formData.append('cr_number', crNumber);
            formData.append('insurance_policy', insurancePolicy);
            formData.append('insurance_provider', finalInsuranceProvider);
            formData.append('registration_date', registrationDate);
            formData.append('expiry_date', expiryDate);
            formData.append('lto_office', finalLTOOffice);
            formData.append('fees_paid', feesPaid);
            
            // Add documents if any
            const documents = document.getElementById('newVehicleDocuments').files;
            if (documents.length > 0) {
                for (let i = 0; i < documents.length; i++) {
                    formData.append('documents[]', documents[i]);
                }
            }
            
            fetch('lto_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Vehicle added successfully! Registration ID: ' + data.lto_id);
                    closeNewVehicleModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error occurred');
            });
        }

        document.getElementById('addRegistrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate required fields
            const formData = new FormData(this);
            const make = formData.get('make');
            const model = formData.get('model');
            const insuranceProvider = formData.get('insurance_provider');
            const ltoOffice = formData.get('lto_office');
            
            // Handle Other options validation
            if (make === 'Other' && !formData.get('make_other')) {
                alert('Please specify the vehicle make');
                return;
            }
            
            if (model === 'Other' && !formData.get('model_other')) {
                alert('Please specify the vehicle model');
                return;
            }
            
            if (insuranceProvider === 'Other' && !formData.get('insurance_provider_other')) {
                alert('Please specify the insurance provider');
                return;
            }
            
            if (ltoOffice === 'Other' && !formData.get('lto_office_other')) {
                alert('Please specify the LTO office');
                return;
            }
            
            // Add action to form data
            formData.append('action', 'add_lto_registration');
            
            // Submit to backend
            fetch('lto_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('LTO Registration added successfully! ID: ' + data.lto_id);
                    closeAddModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
            });
        });
    </script>
</body>
</html>