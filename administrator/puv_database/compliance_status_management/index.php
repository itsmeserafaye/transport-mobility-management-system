<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';
require_once 'functions.php';

$database = new Database();
$conn = $database->getConnection();

// Get statistics
$stats = getStatistics($conn);

// Get compliance data
$compliance = getComplianceStatus($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Status Management - Transport Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body style="background-color: #FBFBFB;" class="dark:bg-slate-900">
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
                    <button onclick="toggleDropdown('puv-database')" class="w-full flex items-center justify-between p-2 rounded-xl transition-all" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.1);">
                        <div class="flex items-center">
                            <i data-lucide="database" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">PUV Database</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="puv-database-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="puv-database-menu" class="ml-8 space-y-1">
                        <a href="../../puv_database/vehicle_and_operator_records/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Vehicle & Operator Records</a>
                        <a href="../../puv_database/compliance_status_management/" class="block p-2 text-sm rounded-lg font-medium" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.2);">Compliance Status Management</a>
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
                            <h1 class="text-md font-bold dark:text-white">COMPLIANCE STATUS MANAGEMENT</h1>
                            <span class="text-xs text-gray-500 font-bold">PUV Database Management</span>
                        </div>
                    </div>
                    <div class="flex-1 max-w-md mx-8">
                        <div class="relative">
                            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                            <input type="text" id="searchInput" placeholder="Search compliance..." 
                                   class="w-full pl-10 pr-4 py-2 bg-slate-100 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-300"
                                   onkeyup="searchCompliance()">
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
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Compliant Vehicles</p>
                                <p class="text-2xl font-bold" style="color: #4CAF50;"><?php echo round($stats['active_vehicles'] * $stats['compliance_rate'] / 100); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(76, 175, 80, 0.1);">
                                <i data-lucide="check-circle" class="w-6 h-6" style="color: #4CAF50;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Overdue Inspections</p>
                                <p class="text-2xl font-bold text-red-600"><?php echo $stats['pending_inspections']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Expired Franchises</p>
                                <p class="text-2xl font-bold" style="color: #FDA811;"><?php echo round($stats['active_vehicles'] * 0.1); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(253, 168, 17, 0.1);">
                                <i data-lucide="clock" class="w-6 h-6" style="color: #FDA811;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Avg Compliance Score</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $stats['compliance_rate']; ?>%</p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(74, 144, 226, 0.1);">
                                <i data-lucide="trending-up" class="w-6 h-6" style="color: #4A90E2;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Compliance Status Management</h2>
                    <div class="flex space-x-3">
                        <div class="relative">
                            <button onclick="toggleExportMenu()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 flex items-center space-x-2">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>Export</span>
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </button>
                            <div id="export-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                <a href="export_compliance.php?format=csv" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export as CSV</a>
                                <a href="export_compliance.php?format=excel" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export as Excel</a>
                                <a href="export_compliance.php?format=pdf" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export as PDF</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 mb-6 dark:bg-slate-800 dark:border-slate-700">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <select id="franchise_filter" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Franchise Status</option>
                            <option value="valid">Valid</option>
                            <option value="expired">Expired</option>
                            <option value="pending">Pending</option>
                            <option value="revoked">Revoked</option>
                        </select>
                        <select id="inspection_filter" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Inspection Status</option>
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                        </select>
                        <select id="score_filter" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">Compliance Score</option>
                            <option value="90-100%">90-100%</option>
                            <option value="80-89%">80-89%</option>
                            <option value="70-79%">70-79%</option>
                            <option value="Below 70%">Below 70%</option>
                        </select>
                        <input id="date_filter" type="date" placeholder="Due Date" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                        <button onclick="applyFilters()" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 flex items-center justify-center space-x-2">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                            <span>Apply Filters</span>
                        </button>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Operator/Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Franchise Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Inspection Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Violations</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Compliance Score</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                <?php foreach ($compliance as $comp): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-blue-600 font-medium"><?php echo strtoupper(substr($comp['first_name'], 0, 1) . substr($comp['last_name'], 0, 1)); ?></span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $comp['first_name'] . ' ' . $comp['last_name']; ?></div>
                                                <div class="text-sm text-slate-500"><?php echo $comp['plate_number']; ?> | <?php echo $comp['compliance_id']; ?></div>
                                                <div class="text-xs text-slate-400"><?php echo ucfirst($comp['vehicle_type']) . ' - ' . $comp['make'] . ' ' . $comp['model']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $franchise_class = $comp['franchise_status'] == 'valid' ? 'bg-green-100 text-green-800' : 
                                                          ($comp['franchise_status'] == 'expired' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $franchise_class; ?> rounded-full"><?php echo ucfirst($comp['franchise_status']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $inspection_class = $comp['inspection_status'] == 'passed' ? 'bg-green-100 text-green-800' : 
                                                           ($comp['inspection_status'] == 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $inspection_class; ?> rounded-full"><?php echo ucfirst($comp['inspection_status']); ?></span>
                                        <?php if ($comp['next_inspection_due']): ?>
                                        <div class="text-xs text-slate-500 mt-1">Due: <?php echo date('M d, Y', strtotime($comp['next_inspection_due'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo $comp['violation_count']; ?> violations</div>
                                        <?php if ($comp['last_violation_date']): ?>
                                        <div class="text-xs text-slate-500">Last: <?php echo date('M Y', strtotime($comp['last_violation_date'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <?php 
                                            $score = $comp['compliance_score'] ?? 0;
                                            $color = $score >= 80 ? 'green' : ($score >= 60 ? 'yellow' : 'red');
                                            ?>
                                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-<?php echo $color; ?>-500 h-2 rounded-full" style="width: <?php echo $score; ?>%"></div>
                                            </div>
                                            <span class="text-sm font-medium text-<?php echo $color; ?>-600"><?php echo number_format($score, 1); ?>%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button onclick="viewCompliance('<?php echo $comp['compliance_id']; ?>')" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="View Details">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="editCompliance('<?php echo $comp['compliance_id']; ?>')" class="p-1 text-orange-600 hover:bg-orange-100 rounded" title="Edit Status">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>

                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-3 border-t border-slate-200 dark:border-slate-600">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-slate-500">
                                Showing 1 to 10 of 2,156 compliance records
                            </div>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 border border-slate-300 rounded text-slate-600 hover:bg-slate-50">Previous</button>
                                <button class="px-3 py-1 text-white rounded" style="background-color: #4CAF50;">1</button>
                                <button class="px-3 py-1 border border-slate-300 rounded text-slate-600 hover:bg-slate-50">2</button>
                                <button class="px-3 py-1 border border-slate-300 rounded text-slate-600 hover:bg-slate-50">3</button>
                                <button class="px-3 py-1 border border-slate-300 rounded text-slate-600 hover:bg-slate-50">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Compliance Modal -->
    <div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-900">Compliance Details</h3>
                <button onclick="closeModal('viewModal')" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="viewContent">Loading...</div>
        </div>
    </div>

    <!-- Edit Compliance Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl p-6 w-full max-w-lg mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-900">Edit Compliance Status</h3>
                <button onclick="closeModal('editModal')" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="editForm">
                <div id="editContent">Loading...</div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
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
        
        function toggleExportMenu() {
            const menu = document.getElementById('export-menu');
            menu.classList.toggle('hidden');
        }
        
        function toggleReportMenu() {
            const menu = document.getElementById('report-menu');
            menu.classList.toggle('hidden');
        }
        
        function viewCompliance(complianceId) {
            document.getElementById('viewModal').classList.remove('hidden');
            fetch(`view_compliance.php?id=${complianceId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('viewContent').innerHTML = data;
                    lucide.createIcons();
                })
                .catch(error => {
                    document.getElementById('viewContent').innerHTML = '<p class="text-red-600">Error loading compliance details</p>';
                });
        }
        
        function editCompliance(complianceId) {
            document.getElementById('editModal').classList.remove('hidden');
            fetch(`edit_compliance.php?id=${complianceId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('editContent').innerHTML = data;
                    lucide.createIcons();
                })
                .catch(error => {
                    document.getElementById('editContent').innerHTML = '<p class="text-red-600">Error loading form</p>';
                });
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        
        // Handle edit form submission
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('edit_compliance.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Compliance status updated successfully!');
                    closeModal('editModal');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating compliance status');
            });
        });
        

        

        

        
        // Filter functions
        function applyFilters() {
            // Get search value from any search input (desktop or mobile)
            const searchInputs = document.querySelectorAll('input[placeholder*="Search"]');
            let searchValue = '';
            searchInputs.forEach(input => {
                if (input.value) searchValue = input.value;
            });
            
            const filters = {
                franchise_status: document.getElementById('franchise_filter')?.value || '',
                inspection_status: document.getElementById('inspection_filter')?.value || '',
                compliance_score_range: document.getElementById('score_filter')?.value || '',
                due_date: document.getElementById('date_filter')?.value || '',
                search: searchValue
            };
            
            // Convert compliance score range to min/max
            if (filters.compliance_score_range) {
                switch(filters.compliance_score_range) {
                    case '90-100%': 
                        filters.compliance_score_min = 90; 
                        filters.compliance_score_max = 100; 
                        break;
                    case '80-89%': 
                        filters.compliance_score_min = 80; 
                        filters.compliance_score_max = 89; 
                        break;
                    case '70-79%': 
                        filters.compliance_score_min = 70; 
                        filters.compliance_score_max = 79; 
                        break;
                    case 'Below 70%': 
                        filters.compliance_score_min = 0; 
                        filters.compliance_score_max = 69; 
                        break;
                }
            }
            
            console.log('Applying filters:', filters);
            
            fetch('simple_filter.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'filter', ...filters})
            })
            .then(response => response.json())
            .then(data => {
                console.log('Filter response:', data);
                if (data.success) {
                    updateTable(data.data);
                } else {
                    console.error('Filter failed:', data.message);
                    alert('Filter failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Filter error:', error);
                alert('Error applying filters. Please try again.');
            });
        }
        
        function updateTable(data) {
            const tbody = document.querySelector('tbody');
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-slate-500">No records found</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.map(row => {
                const initials = row.operator_name ? row.operator_name.split(' ').map(n => n[0]).join('') : 'N/A';
                return `
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-medium">${initials}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-slate-900">${row.operator_name || 'N/A'}</div>
                                    <div class="text-sm text-slate-500">${row.plate_number || 'N/A'} | ${row.compliance_id || 'N/A'}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium ${getStatusClass(row.franchise_status)} rounded-full">${row.franchise_status || 'N/A'}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium ${getStatusClass(row.inspection_status)} rounded-full">${row.inspection_status || 'N/A'}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-900">${row.violation_count || 0} violations</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-${getScoreColor(row.compliance_score)}-500 h-2 rounded-full" style="width: ${row.compliance_score || 0}%"></div>
                                </div>
                                <span class="text-sm font-medium">${row.compliance_score || 0}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <button onclick="viewCompliance('${row.compliance_id}')" class="p-1 text-blue-600 hover:bg-blue-100 rounded">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                                <button onclick="editCompliance('${row.compliance_id}')" class="p-1 text-orange-600 hover:bg-orange-100 rounded">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>

                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
            lucide.createIcons();
        }
        
        function getStatusClass(status) {
            switch(status) {
                case 'valid': case 'passed': return 'bg-green-100 text-green-800';
                case 'expired': case 'failed': return 'bg-red-100 text-red-800';
                default: return 'bg-yellow-100 text-yellow-800';
            }
        }
        
        function getScoreColor(score) {
            return score >= 80 ? 'green' : score >= 60 ? 'yellow' : 'red';
        }
        

        
        function displayReport(data, type) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Compliance Report - ${type.toUpperCase()}</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <pre class="text-sm">${JSON.stringify(data, null, 2)}</pre>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button onclick="exportReport('${type}')" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                            Export CSV
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            lucide.createIcons();
        }
        
        function exportReport(type) {
            window.open(`ajax_handler.php?action=export_csv&report_type=${type}`, '_blank');
        }
        
        // Status update functions
        function updateComplianceStatus(complianceId, data) {
            fetch('ajax_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'update_status', compliance_id: complianceId, ...data})
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Status updated successfully!');
                    location.reload();
                } else {
                    alert('Error updating status');
                }
            });
        }
        
        function calculateScore(franchiseStatus, inspectionStatus, violationCount) {
            fetch('ajax_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'calculate_score',
                    franchise_status: franchiseStatus,
                    inspection_status: inspectionStatus,
                    violation_count: violationCount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('compliance_score').value = data.score;
                }
            });
        }
        

        
        function openUpdateModal() {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-xl p-6 w-full max-w-lg mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Bulk Update Status</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <form onsubmit="bulkUpdate(event)">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Franchise Status</label>
                                <select name="franchise_status" class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">No change</option>
                                    <option value="valid">Valid</option>
                                    <option value="expired">Expired</option>
                                    <option value="pending">Pending</option>
                                    <option value="revoked">Revoked</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Inspection Status</label>
                                <select name="inspection_status" class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">No change</option>
                                    <option value="passed">Passed</option>
                                    <option value="failed">Failed</option>
                                    <option value="pending">Pending</option>
                                    <option value="overdue">Overdue</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="this.closest('.fixed').remove()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Update All</button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);
            lucide.createIcons();
        }
        
        function bulkUpdate(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const updates = [];
            
            // Get selected records (implement checkbox selection)
            document.querySelectorAll('input[name="selected[]"]:checked').forEach(checkbox => {
                updates.push({
                    compliance_id: checkbox.value,
                    data: Object.fromEntries(formData)
                });
            });
            
            if (updates.length === 0) {
                alert('No records selected');
                return;
            }
            
            fetch('ajax_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'bulk_update', updates: JSON.stringify(updates)})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Bulk update completed!');
                    location.reload();
                } else {
                    alert('Error during bulk update');
                }
            });
        }
        
        function searchCompliance() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
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