<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Franchise application functions
function getStatistics($db) {
    $stats = [];
    
    // Total applications
    $query = "SELECT COUNT(*) as total FROM franchise_applications";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_applications'] = $stmt->fetchColumn();
    
    // Under review
    $query = "SELECT COUNT(*) as total FROM franchise_applications WHERE status = 'under_review'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['under_review'] = $stmt->fetchColumn();
    
    // Approved
    $query = "SELECT COUNT(*) as total FROM franchise_applications WHERE status = 'approved'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['approved'] = $stmt->fetchColumn();
    
    // Average processing time
    $query = "SELECT AVG(processing_timeline) as avg_time FROM franchise_applications WHERE status = 'approved'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['avg_processing_time'] = round($stmt->fetchColumn() ?? 15);
    
    return $stats;
}

// Handle filters
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['application_type'] ?? '';
$stage_filter = $_GET['workflow_stage'] ?? '';
$date_filter = $_GET['date_from'] ?? '';

$stats = getStatistics($conn);

// Get applications with filters
if ($status_filter || $type_filter || $stage_filter || $date_filter) {
    $applications = getFilteredApplications($conn, $status_filter, $type_filter, $stage_filter, $date_filter);
} else {
    $applications = getFranchiseApplications($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Franchise Application Workflow - Transport Management</title>
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
                    <button onclick="toggleDropdown('franchise-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl transition-all" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.1);">
                        <div class="flex items-center">
                            <i data-lucide="file-text" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Franchise Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="franchise-mgmt-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="franchise-mgmt-menu" class="ml-8 space-y-1">
                        <a href="../../franchise_management/franchise_application_workflow/" class="block p-2 text-sm rounded-lg font-medium" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.2);">Application & Workflow</a>
                        <a href="../../franchise_management/document_repository/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Document Repository</a>
                        <a href="../../franchise_management/franchise_lifecycle_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Lifecycle Management</a>
                        <a href="../../franchise_management/route_and_schedule_publication/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Route & Schedule Publication</a>
                    </div>
                </div>

                <!-- Traffic Violation Ticketing Module -->
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
                            <span class="text-xs text-slate-500 font-bold">Franchise Management > Application & Workflow</span>
                        </div>
                    </div>
                    <div class="flex-1 max-w-md mx-8">
                        <div class="relative">
                            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                            <input type="text" id="searchInput" placeholder="Search applications..." 
                                   class="w-full pl-10 pr-4 py-2 bg-slate-100 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-300"
                                   onkeyup="searchApplications()">
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
                                <p class="text-slate-500 text-sm">Total Applications</p>
                                <p class="text-2xl font-bold text-blue-600"><?php echo $stats['total_applications']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="file-plus" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Under Review</p>
                                <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['under_review']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="clock" class="w-6 h-6 text-yellow-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Approved</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo $stats['approved']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Avg Processing Time</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $stats['avg_processing_time']; ?> days</p>
                            </div>
                            <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-6 h-6 text-slate-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Franchise Application Workflow</h2>
                    <div class="flex space-x-3">
                        <button onclick="openFranchiseApplicationModal()" class="px-4 py-2 text-white rounded-lg flex items-center space-x-2 transition-colors" style="background-color: #4CAF50;" onmouseover="this.style.backgroundColor='#45A049'" onmouseout="this.style.backgroundColor='#4CAF50'">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Franchise Application</span>
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
                                <a href="export.php?format=word" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export as Word</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" class="bg-white p-4 rounded-xl border border-slate-200 mb-6 dark:bg-slate-800 dark:border-slate-700">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="under_review" <?php echo $status_filter == 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <select name="application_type" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Types</option>
                            <option value="new" <?php echo $type_filter == 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="renewal" <?php echo $type_filter == 'renewal' ? 'selected' : ''; ?>>Renewal</option>
                            <option value="amendment" <?php echo $type_filter == 'amendment' ? 'selected' : ''; ?>>Amendment</option>
                        </select>
                        <select name="workflow_stage" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Stages</option>
                            <option value="initial_review" <?php echo $stage_filter == 'initial_review' ? 'selected' : ''; ?>>Initial Review</option>
                            <option value="document_verification" <?php echo $stage_filter == 'document_verification' ? 'selected' : ''; ?>>Document Verification</option>
                            <option value="field_inspection" <?php echo $stage_filter == 'field_inspection' ? 'selected' : ''; ?>>Field Inspection</option>
                            <option value="approval" <?php echo $stage_filter == 'approval' ? 'selected' : ''; ?>>Approval</option>
                        </select>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_filter); ?>" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                        <button type="submit" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 flex items-center justify-center space-x-2">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                            <span>Apply Filters</span>
                        </button>
                    </div>
                </form>

                <!-- Applications Table -->
                <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Application</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Operator/Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Route</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Workflow Stage</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                <?php foreach ($applications as $app): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $app['application_id']; ?></div>
                                            <div class="text-sm text-slate-500"><?php echo ucfirst($app['application_type']); ?> | <?php echo date('M d, Y', strtotime($app['application_date'])); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-blue-600 font-medium"><?php echo strtoupper(substr($app['first_name'], 0, 1) . substr($app['last_name'], 0, 1)); ?></span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $app['first_name'] . ' ' . $app['last_name']; ?></div>
                                                <div class="text-sm text-slate-500"><?php echo $app['plate_number']; ?> - <?php echo ucfirst($app['vehicle_type']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo $app['route_requested']; ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $status_class = $app['status'] == 'approved' ? 'bg-green-100 text-green-800' : 
                                                       ($app['status'] == 'rejected' ? 'bg-red-100 text-red-800' : 
                                                       ($app['status'] == 'under_review' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'));
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $status_class; ?> rounded-full"><?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo ucfirst(str_replace('_', ' ', $app['workflow_stage'])); ?></div>
                                        <?php if ($app['assigned_to']): ?>
                                        <div class="text-xs text-slate-500">Assigned to: <?php echo $app['assigned_to']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button onclick="viewApplication('<?php echo $app['application_id']; ?>')" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="View Details">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                            <?php if ($app['status'] != 'approved'): ?>
                                            <button onclick="openRouteWorkflowModal('<?php echo $app['application_id']; ?>')" class="p-1 text-orange-600 hover:bg-orange-100 rounded" title="Route Workflow">
                                                <i data-lucide="route" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="openSetTimelineModal('<?php echo $app['application_id']; ?>')" class="p-1 text-green-600 hover:bg-green-100 rounded" title="Set Timeline">
                                                <i data-lucide="clock" class="w-4 h-4"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($app['workflow_stage'] == 'approval' && $app['status'] != 'approved' && $app['status'] != 'rejected'): ?>
                                            <button onclick="approveApplication('<?php echo $app['application_id']; ?>')" class="p-1 text-green-600 hover:bg-green-100 rounded" title="Approve">
                                                <i data-lucide="check" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="openRejectModal('<?php echo $app['application_id']; ?>')" class="p-1 text-red-600 hover:bg-red-100 rounded" title="Reject">
                                                <i data-lucide="x" class="w-4 h-4"></i>
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
            </div>
        </div>
    </div>



    <!-- Unified Franchise Application Modal -->
    <div id="franchiseApplicationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center p-6 border-b">
                    <h3 class="text-lg font-semibold">Franchise Application</h3>
                    <button onclick="closeModal('franchiseApplicationModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <!-- Select Operator -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">LTO Registered Operator</label>
                            <select id="operatorSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="loadOperatorVehicles()" required>
                                <option value="">Select an operator...</option>
                                <!-- Populated by JavaScript -->
                            </select>
                        </div>
                        
                        <!-- Operator Details (Auto-filled) -->
                        <div id="operatorDetails" class="hidden bg-gray-50 border border-gray-200 p-4 rounded-lg">
                            <h5 class="font-medium text-gray-800 mb-2">Operator Details</h5>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><strong>Name:</strong> <span id="operatorName"></span></div>
                                <div><strong>License:</strong> <span id="operatorLicense"></span></div>
                                <div class="col-span-2"><strong>Address:</strong> <span id="operatorAddress"></span></div>
                            </div>
                        </div>
                        
                        <!-- Select Vehicle -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Vehicle</label>
                            <select id="vehicleSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="loadVehicleDetails()" required disabled>
                                <option value="">Select operator first</option>
                            </select>
                        </div>
                        
                        <!-- Vehicle Details (Auto-filled) -->
                        <div id="vehicleDetails" class="hidden bg-green-50 border border-green-200 p-4 rounded-lg">
                            <h5 class="font-medium text-green-800 mb-2">Vehicle Details</h5>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><strong>Plate:</strong> <span id="vehiclePlate"></span></div>
                                <div><strong>Make/Model:</strong> <span id="vehicleMakeModel"></span></div>
                                <div><strong>Year:</strong> <span id="vehicleYear"></span></div>
                                <div><strong>OR/CR:</strong> <span id="vehicleORCR"></span></div>
                            </div>
                        </div>
                        
                        <!-- Route Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Route Requested</label>
                            <select id="routeSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                                <option value="">Select Route</option>
                                <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['route_name']; ?>"><?php echo $route['route_code'] . ' - ' . $route['route_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Hidden inputs -->
                        <input type="hidden" id="selectedOperatorId">
                        <input type="hidden" id="selectedVehicleId">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 p-6 border-t">
                    <button onclick="closeModal('franchiseApplicationModal')" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="submitApplication()" class="px-4 py-2 text-white rounded-lg transition-colors" style="background-color: #4CAF50;" onmouseover="this.style.backgroundColor='#45A049'" onmouseout="this.style.backgroundColor='#4CAF50'">
                        Submit Application
                    </button>
                </div>
            </div>
        </div>
    </div>



    <!-- Route Workflow Modal -->
    <div id="routeWorkflowModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex justify-between items-center p-6 border-b">
                    <h3 class="text-lg font-semibold">Route Workflow</h3>
                    <button onclick="closeModal('routeWorkflowModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Application ID</label>
                            <input type="text" id="workflowAppId" class="w-full border border-gray-300 rounded-lg px-3 py-2" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Workflow Stage</label>
                            <select id="workflowStage" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="initial_review">Initial Review</option>
                                <option value="document_verification">Document Verification</option>
                                <option value="field_inspection">Field Inspection</option>
                                <option value="approval">Final Approval</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Assigned To</label>
                            <select id="assignedTo" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="System Admin">System Admin</option>
                                <option value="Document Reviewer">Document Reviewer</option>
                                <option value="Field Inspector">Field Inspector</option>
                                <option value="Final Approver">Final Approver</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 p-6 border-t">
                    <button onclick="closeModal('routeWorkflowModal')" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="routeWorkflow()" class="px-4 py-2 text-white rounded-lg transition-colors" style="background-color: #4CAF50;" onmouseover="this.style.backgroundColor='#45A049'" onmouseout="this.style.backgroundColor='#4CAF50'">
                        Route Application
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Set Timeline Modal -->
    <div id="setTimelineModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex justify-between items-center p-6 border-b">
                    <h3 class="text-lg font-semibold">Set Processing Timeline</h3>
                    <button onclick="closeModal('setTimelineModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Application ID</label>
                            <input type="text" id="timelineAppId" class="w-full border border-gray-300 rounded-lg px-3 py-2" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Processing Timeline (Days)</label>
                            <input type="number" id="processingTimeline" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Enter days" value="30">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Priority Level</label>
                            <select id="priorityLevel" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 p-6 border-t">
                    <button onclick="closeModal('setTimelineModal')" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="setTimeline()" class="px-4 py-2 text-white rounded-lg transition-colors" style="background-color: #4CAF50;" onmouseover="this.style.backgroundColor='#45A049'" onmouseout="this.style.backgroundColor='#4CAF50'">
                        Set Timeline
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Application Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex justify-between items-center p-6 border-b">
                    <h3 class="text-lg font-semibold text-red-600">Reject Application</h3>
                    <button onclick="closeModal('rejectModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Application ID</label>
                            <input type="text" id="rejectAppId" class="w-full border border-gray-300 rounded-lg px-3 py-2" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason</label>
                            <select id="rejectionReason" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="">Select reason</option>
                                <option value="Incomplete Documentation">Incomplete Documentation</option>
                                <option value="Vehicle Safety Issues">Vehicle Safety Issues</option>
                                <option value="Route Oversaturation">Route Oversaturation</option>
                                <option value="Non-compliance with Regulations">Non-compliance with Regulations</option>
                                <option value="Failed Vehicle Inspection">Failed Vehicle Inspection</option>
                                <option value="Invalid Operator Credentials">Invalid Operator Credentials</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Remarks</label>
                            <textarea id="rejectionRemarks" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Provide detailed explanation for rejection..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 p-6 border-t">
                    <button onclick="closeModal('rejectModal')" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="rejectApplication()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Reject Application
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Renewal Application Modal -->
    <div id="renewalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center p-6 border-b">
                    <h3 class="text-lg font-semibold">Renew Existing Franchise</h3>
                    <button onclick="closeModal('renewalModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-4">
                        <h5 class="font-medium text-blue-800 mb-2">Franchise Renewal</h5>
                        <p class="text-sm text-blue-700">Select existing franchise to renew. All operator and vehicle details are already on file.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Existing Franchise</label>
                            <select id="existingFranchiseSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="loadFranchiseDetails()" required>
                                <option value="">Select Franchise to Renew</option>
                                <!-- Will be populated by JavaScript -->
                            </select>
                        </div>
                        
                        <div id="franchiseDetails" class="hidden bg-gray-50 p-4 rounded-lg">
                            <h5 class="font-medium text-gray-800 mb-2">Franchise Details:</h5>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><strong>Operator:</strong> <span id="renewalOperator"></span></div>
                                <div><strong>Vehicle:</strong> <span id="renewalVehicle"></span></div>
                                <div><strong>Route:</strong> <span id="renewalRoute"></span></div>
                                <div><strong>Expires:</strong> <span id="renewalExpiry"></span></div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Renewal Period</label>
                            <select id="renewalPeriod" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                                <option value="1">1 Year</option>
                                <option value="2">2 Years</option>
                                <option value="3">3 Years</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 p-6 border-t">
                    <button onclick="closeModal('renewalModal')" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button onclick="submitRenewal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Submit Renewal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-Vehicle Application Modal -->
    <div id="multiVehicleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center p-6 border-b">
                    <h3 class="text-lg font-semibold">Add Vehicle to Existing Operator</h3>
                    <button onclick="closeModal('multiVehicleModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="bg-purple-50 border border-purple-200 p-4 rounded-lg mb-4">
                        <h5 class="font-medium text-purple-800 mb-2">Additional Vehicle Franchise</h5>
                        <p class="text-sm text-purple-700">For existing operators adding another vehicle. Operator details are auto-filled.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Existing Operator</label>
                            <select id="existingOperatorSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="loadOperatorInfo()" required>
                                <option value="">Select Existing Operator</option>
                                <!-- Will be populated by JavaScript -->
                            </select>
                        </div>
                        
                        <div id="operatorInfo" class="hidden bg-gray-50 p-4 rounded-lg">
                            <h5 class="font-medium text-gray-800 mb-2">Operator Details:</h5>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><strong>Name:</strong> <span id="multiOperatorName"></span></div>
                                <div><strong>License:</strong> <span id="multiOperatorLicense"></span></div>
                                <div><strong>Address:</strong> <span id="multiOperatorAddress"></span></div>
                                <div><strong>Contact:</strong> <span id="multiOperatorContact"></span></div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New LTO Registered Vehicle</label>
                            <select id="multiVehicleSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="loadMultiVehicleDetails()" required>
                                <option value="">Select LTO Registered Vehicle</option>
                                <!-- Will be populated by JavaScript -->
                            </select>
                        </div>
                        
                        <div id="multiVehicleDetails" class="hidden bg-gray-50 p-4 rounded-lg">
                            <h5 class="font-medium text-gray-800 mb-2">Vehicle Details:</h5>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><strong>Plate:</strong> <span id="multiVehiclePlate"></span></div>
                                <div><strong>Make/Model:</strong> <span id="multiVehicleMakeModel"></span></div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Route Requested</label>
                            <select id="multiRouteRequested" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                                <option value="">Select Route</option>
                                <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['route_name']; ?>"><?php echo $route['route_code'] . ' - ' . $route['route_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 p-6 border-t">
                    <button onclick="closeModal('multiVehicleModal')" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button onclick="submitMultiVehicle()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Add Vehicle Franchise</button>
                </div>
            </div>
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
        

        
        function updateTable(data) {
            const tbody = document.querySelector('tbody');
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-slate-500">No applications found</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.map(row => `
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-slate-900">${row.application_id}</div>
                        <div class="text-sm text-slate-500">${row.application_type} | ${row.application_date}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 font-medium">${row.operator_name ? row.operator_name.split(' ').map(n => n[0]).join('') : 'N/A'}</span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-slate-900">${row.operator_name || 'N/A'}</div>
                                <div class="text-sm text-slate-500">${row.plate_number || 'N/A'} - ${row.vehicle_type || 'N/A'}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-900">${row.route_requested || 'N/A'}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-medium ${getStatusClass(row.status)} rounded-full">${row.status || 'pending'}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-900">${row.workflow_stage ? row.workflow_stage.replace('_', ' ') : 'N/A'}</div>
                        ${row.assigned_to ? `<div class="text-xs text-slate-500">Assigned to: ${row.assigned_to}</div>` : ''}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2">
                            <button onclick="viewApplication('${row.application_id}')" class="p-1 text-blue-600 hover:bg-blue-100 rounded">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="processApplication('${row.application_id}')" class="p-1 text-orange-600 hover:bg-orange-100 rounded">
                                <i data-lucide="play" class="w-4 h-4"></i>
                            </button>
                            <button onclick="assignApplication('${row.application_id}')" class="p-1 text-green-600 hover:bg-green-100 rounded">
                                <i data-lucide="user-plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        }
        
        function getStatusClass(status) {
            switch(status) {
                case 'approved': return 'bg-green-100 text-green-800';
                case 'rejected': return 'bg-red-100 text-red-800';
                case 'under_review': return 'bg-yellow-100 text-yellow-800';
                default: return 'bg-blue-100 text-blue-800';
            }
        }
        


        function openFranchiseApplicationModal() {
            loadLTOOperators();
            document.getElementById('franchiseApplicationModal').classList.remove('hidden');
        }
        
        function loadExistingFranchises() {
            fetch('get_existing_franchises.php')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('existingFranchiseSelect');
                    select.innerHTML = '<option value="">Select Franchise to Renew</option>';
                    if (data.success && data.franchises) {
                        data.franchises.forEach(franchise => {
                            select.innerHTML += `<option value="${franchise.franchise_id}">${franchise.operator_name} - ${franchise.plate_number} (${franchise.route})</option>`;
                        });
                    }
                })
                .catch(error => console.error('Error loading franchises:', error));
        }
        
        function loadFranchiseDetails() {
            const franchiseId = document.getElementById('existingFranchiseSelect').value;
            if (!franchiseId) {
                document.getElementById('franchiseDetails').classList.add('hidden');
                return;
            }
            
            fetch(`get_franchise_details.php?id=${franchiseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const franchise = data.franchise;
                        document.getElementById('renewalOperator').textContent = franchise.operator_name;
                        document.getElementById('renewalVehicle').textContent = `${franchise.plate_number} - ${franchise.vehicle_type}`;
                        document.getElementById('renewalRoute').textContent = franchise.route;
                        document.getElementById('renewalExpiry').textContent = franchise.expiry_date;
                        document.getElementById('franchiseDetails').classList.remove('hidden');
                    }
                })
                .catch(error => console.error('Error loading franchise details:', error));
        }
        
        function loadOperatorInfo() {
            const operatorId = document.getElementById('existingOperatorSelect').value;
            if (!operatorId) {
                document.getElementById('operatorInfo').classList.add('hidden');
                return;
            }
            
            fetch(`get_operator_info.php?id=${operatorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const operator = data.operator;
                        document.getElementById('multiOperatorName').textContent = `${operator.first_name} ${operator.last_name}`;
                        document.getElementById('multiOperatorLicense').textContent = operator.license_number;
                        document.getElementById('multiOperatorAddress').textContent = operator.address;
                        document.getElementById('multiOperatorContact').textContent = operator.contact_number;
                        document.getElementById('operatorInfo').classList.remove('hidden');
                        
                        // Load available vehicles for this operator
                        loadAvailableVehiclesForOperator(operatorId);
                    }
                })
                .catch(error => console.error('Error loading operator info:', error));
        }
        
        function loadAvailableVehiclesForOperator(operatorId) {
            fetch(`get_available_vehicles.php?operator_id=${operatorId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('multiVehicleSelect');
                    select.innerHTML = '<option value="">Select LTO Registered Vehicle</option>';
                    if (data.success && data.vehicles) {
                        data.vehicles.forEach(vehicle => {
                            select.innerHTML += `<option value="${vehicle.lto_registration_id}">${vehicle.plate_number} - ${vehicle.make} ${vehicle.model}</option>`;
                        });
                    }
                })
                .catch(error => console.error('Error loading vehicles:', error));
        }
        
        function loadMultiVehicleDetails() {
            const ltoId = document.getElementById('multiVehicleSelect').value;
            if (!ltoId) {
                document.getElementById('multiVehicleDetails').classList.add('hidden');
                return;
            }
            
            fetch(`get_lto_vehicle_details.php?id=${ltoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const vehicle = data.vehicle;
                        document.getElementById('multiVehiclePlate').textContent = vehicle.plate_number;
                        document.getElementById('multiVehicleMakeModel').textContent = `${vehicle.make} ${vehicle.model} (${vehicle.year_model})`;
                        document.getElementById('multiVehicleDetails').classList.remove('hidden');
                    }
                })
                .catch(error => console.error('Error loading vehicle details:', error));
        }
        
        function submitRenewal() {
            const franchiseId = document.getElementById('existingFranchiseSelect').value;
            const renewalPeriod = document.getElementById('renewalPeriod').value;
            
            if (!franchiseId || !renewalPeriod) {
                alert('Please select franchise and renewal period');
                return;
            }
            
            fetch('simple_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'renew_franchise',
                    franchise_id: franchiseId,
                    renewal_period: renewalPeriod
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('renewalModal');
                    alert('Franchise renewal application submitted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error occurred');
            });
        }
        
        function submitMultiVehicle() {
            const operatorId = document.getElementById('existingOperatorSelect').value;
            const ltoId = document.getElementById('multiVehicleSelect').value;
            const route = document.getElementById('multiRouteRequested').value;
            
            if (!operatorId || !ltoId || !route) {
                alert('Please fill all required fields');
                return;
            }
            
            fetch('simple_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'add_vehicle_franchise',
                    operator_id: operatorId,
                    lto_registration_id: ltoId,
                    route_requested: route
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('multiVehicleModal');
                    alert('Additional vehicle franchise application submitted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error occurred');
            });
        }
        

        
        function loadExistingOperators() {
            fetch('get_operators.php')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('existingOperatorSelect');
                    select.innerHTML = '<option value="">Select Operator</option>';
                    if (data.success && data.operators) {
                        data.operators.forEach(operator => {
                            select.innerHTML += `<option value="${operator.operator_id}">${operator.first_name} ${operator.last_name} (${operator.license_number})</option>`;
                        });
                    }
                })
                .catch(error => console.error('Error loading operators:', error));
        }
        
        function loadOperatorDetails() {
            const operatorId = document.getElementById('existingOperatorSelect').value;
            if (!operatorId) {
                document.getElementById('operatorDetails').classList.add('hidden');
                return;
            }
            
            fetch(`get_operator_details.php?id=${operatorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('detailName').textContent = `${data.operator.first_name} ${data.operator.last_name}`;
                        document.getElementById('detailLicense').textContent = data.operator.license_number;
                        document.getElementById('detailContact').textContent = data.operator.contact_number;
                        document.getElementById('detailEmail').textContent = data.operator.email || 'N/A';
                        document.getElementById('operatorDetails').classList.remove('hidden');
                    }
                })
                .catch(error => console.error('Error loading operator details:', error));
        }
        
        function loadLTOOperators() {
            fetch('get_lto_operators.php')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('ltoOperatorSelect');
                    select.innerHTML = '<option value="">Select an operator...</option>';
                    if (data.success && data.operators) {
                        data.operators.forEach(operator => {
                            select.innerHTML += `<option value="${operator.owner_name}">${operator.owner_name} (${operator.license_number})</option>`;
                        });
                    }
                })
                .catch(error => console.error('Error loading LTO operators:', error));
        }
        
        function loadOperatorVehicles() {
            const operatorName = document.getElementById('operatorSelect').value;
            const vehicleSelect = document.getElementById('vehicleSelect');
            const operatorDetails = document.getElementById('operatorDetails');
            
            if (!operatorName) {
                vehicleSelect.innerHTML = '<option value="">Select operator first</option>';
                vehicleSelect.disabled = true;
                operatorDetails.classList.add('hidden');
                return;
            }
            
            fetch(`get_operator_lto_vehicles.php?operator_name=${encodeURIComponent(operatorName)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Display operator details
                        if (data.operator) {
                            document.getElementById('operatorName').textContent = data.operator.owner_name;
                            document.getElementById('operatorLicense').textContent = data.operator.license_number;
                            document.getElementById('operatorAddress').textContent = data.operator.owner_address;
                            document.getElementById('selectedOperatorId').value = data.operator.lto_registration_id;
                            operatorDetails.classList.remove('hidden');
                        }
                        
                        // Load vehicles
                        vehicleSelect.innerHTML = '<option value="">Select vehicle...</option>';
                        if (data.vehicles && data.vehicles.length > 0) {
                            data.vehicles.forEach(vehicle => {
                                vehicleSelect.innerHTML += `<option value="${vehicle.lto_registration_id}">${vehicle.plate_number} - ${vehicle.make} ${vehicle.model} (${vehicle.year_model})</option>`;
                            });
                            vehicleSelect.disabled = false;
                        } else {
                            vehicleSelect.innerHTML = '<option value="">No vehicles found for this operator</option>';
                            vehicleSelect.disabled = true;
                        }
                    }
                })
                .catch(error => console.error('Error loading operator vehicles:', error));
        }
        
        function loadVehicleDetails() {
            const ltoId = document.getElementById('vehicleSelect').value;
            if (!ltoId) {
                document.getElementById('vehicleDetails').classList.add('hidden');
                return;
            }
            
            fetch(`get_lto_vehicle_details.php?id=${ltoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const vehicle = data.vehicle;
                        
                        // Display vehicle details
                        document.getElementById('vehiclePlate').textContent = vehicle.plate_number || 'Not assigned';
                        document.getElementById('vehicleMakeModel').textContent = `${vehicle.make} ${vehicle.model}`;
                        document.getElementById('vehicleYear').textContent = vehicle.year_model;
                        document.getElementById('vehicleORCR').textContent = `${vehicle.or_number}/${vehicle.cr_number}`;
                        
                        // Store selected vehicle LTO ID
                        document.getElementById('selectedVehicleId').value = ltoId;
                        
                        document.getElementById('vehicleDetails').classList.remove('hidden');
                    }
                })
                .catch(error => console.error('Error loading vehicle details:', error));
        }
        
        function createExistingOperatorApplication() {
            const operatorId = document.getElementById('existingOperatorSelect').value;
            const makeSelect = document.getElementById('existingVehicleMake');
            const modelSelect = document.getElementById('existingVehicleModel');
            const finalMake = makeSelect.value === 'Other' ? document.getElementById('existingVehicleMakeOther').value : makeSelect.value;
            const finalModel = modelSelect.value === 'Other' ? document.getElementById('existingVehicleModelOther').value : modelSelect.value;
            
            if (!operatorId) {
                alert('Please select an operator');
                return;
            }
            
            const vehicleData = {
                plate_number: document.getElementById('existingVehiclePlateNumber').value,
                vehicle_type: document.getElementById('existingVehicleType').value,
                make: finalMake,
                model: finalModel,
                year_manufactured: document.getElementById('existingVehicleYear').value,
                engine_number: document.getElementById('existingVehicleEngineNumber').value,
                chassis_number: document.getElementById('existingVehicleChassisNumber').value,
                color: document.getElementById('existingVehicleColor').value,
                seating_capacity: document.getElementById('existingVehicleSeatingCapacity').value
            };
            
            const routeRequested = document.getElementById('existingRouteRequested').value;
            
            if (!vehicleData.plate_number || !routeRequested) {
                alert('Please fill in all required fields');
                return;
            }
            
            const formData = new URLSearchParams({
                action: 'create_existing_operator_application',
                operator_id: operatorId,
                route_requested: routeRequested,
                ...Object.fromEntries(Object.entries(vehicleData).map(([k, v]) => [`vehicle_${k}`, v]))
            });
            
            fetch('simple_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('existingOperatorModal');
                    alert(`Application created with ID: ${data.application_id}`);
                    location.reload();
                } else {
                    alert('Failed to create application: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Create error:', error);
                alert('Error creating application');
            });
        }

        function openRouteWorkflowModal(appId) {
            document.getElementById('workflowAppId').value = appId;
            document.getElementById('routeWorkflowModal').classList.remove('hidden');
        }

        function openSetTimelineModal(appId) {
            document.getElementById('timelineAppId').value = appId;
            document.getElementById('setTimelineModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        
        function loadOperatorVehicles() {
            const operatorId = document.getElementById('operatorId').value;
            const vehiclesList = document.getElementById('vehicles-list');
            const noVehiclesMessage = document.getElementById('no-vehicles-message');
            const hiddenInput = document.getElementById('vehicleId');
            
            if (!operatorId) {
                vehiclesList.classList.add('hidden');
                noVehiclesMessage.classList.add('hidden');
                hiddenInput.value = '';
                return;
            }
            
            fetch('get_operator_vehicles.php?operator_id=' + operatorId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.vehicles.length > 0) {
                        vehiclesList.innerHTML = data.vehicles.map(vehicle => `
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer" 
                                 onclick="selectVehicle('${vehicle.vehicle_id}', this)">
                                <div class="flex items-center space-x-3">
                                    <input type="radio" name="vehicle_selection" value="${vehicle.vehicle_id}" 
                                           class="text-green-600 focus:ring-green-500">
                                    <div>
                                        <div class="font-medium text-gray-900">${vehicle.plate_number}</div>
                                        <div class="text-sm text-gray-500">${vehicle.vehicle_type} - ${vehicle.make} ${vehicle.model}</div>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${
                                    vehicle.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                }">${vehicle.status}</span>
                            </div>
                        `).join('');
                        vehiclesList.classList.remove('hidden');
                        noVehiclesMessage.classList.add('hidden');
                    } else {
                        vehiclesList.classList.add('hidden');
                        noVehiclesMessage.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error loading vehicles:', error);
                    vehiclesList.classList.add('hidden');
                    noVehiclesMessage.classList.remove('hidden');
                });
        }
        
        function selectVehicle(vehicleId, element) {
            document.getElementById('vehicleId').value = vehicleId;
            element.querySelector('input[type="radio"]').checked = true;
            
            // Remove selection from other vehicles
            document.querySelectorAll('#vehicles-list > div').forEach(div => {
                div.classList.remove('bg-green-50', 'border-green-300');
                div.classList.add('border-gray-200');
            });
            
            // Highlight selected vehicle
            element.classList.add('bg-green-50', 'border-green-300');
            element.classList.remove('border-gray-200');
        }

        function loadVehicles() {
            const operatorId = document.getElementById('operatorId').value;
            const vehicleSelect = document.getElementById('vehicleId');
            
            if (!operatorId) {
                vehicleSelect.innerHTML = '<option value="">Select operator first</option>';
                vehicleSelect.disabled = true;
                return;
            }
            
            vehicleSelect.innerHTML = '<option value="">Loading vehicles...</option>';
            vehicleSelect.disabled = true;
            
            fetch(`get_operator_vehicles.php?operator_id=${operatorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.vehicles.length > 0) {
                        vehicleSelect.innerHTML = '<option value="">Select Vehicle</option>' +
                            data.vehicles.map(vehicle => 
                                `<option value="${vehicle.vehicle_id}">${vehicle.plate_number} - ${vehicle.vehicle_type} (${vehicle.make} ${vehicle.model})</option>`
                            ).join('');
                        vehicleSelect.disabled = false;
                    } else {
                        vehicleSelect.innerHTML = '<option value="">No vehicles found</option>';
                        vehicleSelect.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error loading vehicles:', error);
                    vehicleSelect.innerHTML = '<option value="">Error loading vehicles</option>';
                    vehicleSelect.disabled = true;
                });
        }

        // Validation functions
        function validateLicenseNumber(license) {
            const pattern = /^(D\d{2}-\d{2}-\d{6}|[A-Z]\d{2}-\d{8}|\d{2}-\d{8})$/;
            return pattern.test(license);
        }
        
        function validatePlateNumber(plate) {
            const pattern = /^[A-Z]{3}\s?\d{3,4}$/;
            return pattern.test(plate);
        }
        
        function formatPlateNumber(plate) {
            return plate.replace(/^([A-Z]{3})(\d{3,4})$/, '$1 $2');
        }
        
        function validateEngineNumber(engine) {
            const pattern = /^[A-Z0-9-]{5,20}$/;
            return pattern.test(engine);
        }
        
        function validateChassisNumber(chassis) {
            const pattern = /^[A-HJ-NPR-Z0-9]{17}$/;
            return pattern.test(chassis);
        }
        
        // Vehicle make/model data
        const vehicleModels = {
            'Toyota': ['Hiace', 'Coaster', 'Innova', 'Vios', 'Avanza'],
            'Isuzu': ['Elf', 'Forward', 'Crosswind', 'D-Max', 'Traviz'],
            'Mitsubishi': ['L300', 'Fuso', 'Adventure', 'Montero', 'Mirage'],
            'Hyundai': ['County', 'H100', 'Starex', 'Accent', 'Tucson'],
            'Nissan': ['Urvan', 'Navara', 'Patrol', 'Almera', 'X-Trail']
        };
        
        function loadModels() {
            const make = document.getElementById('vehicleMake').value;
            const modelSelect = document.getElementById('vehicleModel');
            const makeOther = document.getElementById('vehicleMakeOther');
            const modelOther = document.getElementById('vehicleModelOther');
            
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
                if (make && vehicleModels[make]) {
                    vehicleModels[make].forEach(model => {
                        modelSelect.innerHTML += `<option value="${model}">${model}</option>`;
                    });
                    modelSelect.innerHTML += '<option value="Other">Other</option>';
                }
            }
        }
        
        function loadModelsExisting() {
            const make = document.getElementById('existingVehicleMake').value;
            const modelSelect = document.getElementById('existingVehicleModel');
            const makeOther = document.getElementById('existingVehicleMakeOther');
            const modelOther = document.getElementById('existingVehicleModelOther');
            
            if (make === 'Other') {
                makeOther.classList.remove('hidden');
                modelSelect.classList.add('hidden');
                modelOther.classList.remove('hidden');
            } else {
                makeOther.classList.add('hidden');
                modelSelect.classList.remove('hidden');
                modelOther.classList.add('hidden');
                
                modelSelect.innerHTML = '<option value="">Select Model</option>';
                if (make && vehicleModels[make]) {
                    vehicleModels[make].forEach(model => {
                        modelSelect.innerHTML += `<option value="${model}">${model}</option>`;
                    });
                    modelSelect.innerHTML += '<option value="Other">Other</option>';
                }
            }
        }
        
        // Add input event listeners for real-time validation
        document.addEventListener('DOMContentLoaded', function() {
            // Load operators for franchise application
            function loadLTOOperators() {
                fetch('get_lto_operators.php')
                    .then(response => response.json())
                    .then(data => {
                        const select = document.getElementById('operatorSelect');
                        if (select) {
                            select.innerHTML = '<option value="">Select an operator...</option>';
                            if (data.success && data.operators) {
                                data.operators.forEach(operator => {
                                    select.innerHTML += `<option value="${operator.owner_name}">${operator.owner_name} (${operator.license_number})</option>`;
                                });
                            }
                        }
                    })
                    .catch(error => console.error('Error loading LTO operators:', error));
            }
            
            const plateInput = document.getElementById('vehiclePlateNumber');
            const licenseInput = document.getElementById('operatorLicense');
            const engineInput = document.getElementById('vehicleEngineNumber');
            const chassisInput = document.getElementById('vehicleChassisNumber');
            
            plateInput.addEventListener('input', function() {
                let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                if (value.length >= 6) {
                    value = value.substring(0, 3) + ' ' + value.substring(3);
                }
                this.value = value;
            });
            
            licenseInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
            
            engineInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
            
            chassisInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });

        // Submit unified application
        function submitApplication() {
            const operatorId = document.getElementById('selectedOperatorId').value;
            const vehicleId = document.getElementById('selectedVehicleId').value;
            const route = document.getElementById('routeSelect').value;
            
            if (!operatorId || !vehicleId || !route) {
                alert('Please fill all required fields');
                return;
            }
            
            fetch('simple_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'create_new_franchise_application',
                    operator_lto_id: operatorId,
                    vehicle_lto_id: vehicleId,
                    application_type: 'new',
                    route_requested: route
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('franchiseApplicationModal');
                    alert(`Franchise application submitted successfully!\nApplication ID: ${data.application_id}`);
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Network error occurred');
            });
        }
        
        // Route workflow
        function routeWorkflow() {
            const appId = document.getElementById('workflowAppId').value;
            const workflowStage = document.getElementById('workflowStage').value;
            const assignedTo = document.getElementById('assignedTo').value;
            
            fetch('simple_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'route_workflow',
                    application_id: appId,
                    workflow_stage: workflowStage,
                    assigned_to: assignedTo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('routeWorkflowModal');
                    alert('Application workflow updated!');
                    location.reload();
                } else {
                    alert('Failed to update workflow');
                }
            })
            .catch(error => {
                console.error('Process error:', error);
                alert('Error processing application');
            });
        }
        
        // Set timeline
        function setTimeline() {
            const appId = document.getElementById('timelineAppId').value;
            const timeline = document.getElementById('processingTimeline').value;
            const priorityLevel = document.getElementById('priorityLevel').value;
            
            if (!timeline) {
                alert('Please enter processing timeline');
                return;
            }
            
            fetch('simple_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'update_timeline',
                    application_id: appId,
                    timeline: timeline,
                    priority_level: priorityLevel
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('setTimelineModal');
                    alert('Processing timeline updated!');
                    location.reload();
                } else {
                    alert('Failed to update timeline');
                }
            })
            .catch(error => {
                console.error('Timeline error:', error);
                alert('Error updating timeline');
            });
        }
        
        function approveApplication(applicationId) {
            if (confirm('Are you sure you want to approve this application?')) {
                fetch('simple_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'approve_application',
                        application_id: applicationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Application approved successfully!');
                        location.reload();
                    } else {
                        alert('Failed to approve application');
                    }
                })
                .catch(error => {
                    console.error('Approve error:', error);
                    alert('Error approving application');
                });
            }
        }
        
        function openRejectModal(applicationId) {
            document.getElementById('rejectAppId').value = applicationId;
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        
        function rejectApplication() {
            const appId = document.getElementById('rejectAppId').value;
            const reason = document.getElementById('rejectionReason').value;
            const remarks = document.getElementById('rejectionRemarks').value;
            
            if (!reason) {
                alert('Please select a rejection reason');
                return;
            }
            
            if (confirm('Are you sure you want to reject this application? This action cannot be undone.')) {
                fetch('simple_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'reject_application',
                        application_id: appId,
                        rejection_reason: reason,
                        remarks: remarks
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeModal('rejectModal');
                        alert('Application rejected successfully!');
                        location.reload();
                    } else {
                        alert('Failed to reject application: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Reject error:', error);
                    alert('Error rejecting application');
                });
            }
        }
        
        function viewApplication(applicationId) {
            fetch(`view_application.php?id=${applicationId}`)
                .then(response => response.text())
                .then(html => {
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
                    modal.innerHTML = `
                        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-bold">Application Details</h2>
                                <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                                    <i data-lucide="x" class="w-6 h-6"></i>
                                </button>
                            </div>
                            <div>${html}</div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    lucide.createIcons();
                })
                .catch(error => {
                    alert('Error loading application details');
                });
        }
        
        // Export menu toggle
        function toggleExportMenu() {
            const menu = document.getElementById('export-menu');
            menu.classList.toggle('hidden');
        }

        // Close export menu when clicking outside
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('export-menu');
            const button = e.target.closest('button');
            if (!button || !button.onclick) {
                menu.classList.add('hidden');
            }
        });
        
        function searchApplications() {
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