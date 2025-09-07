<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

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
                    <button onclick="toggleDropdown('franchise-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-orange-600 bg-orange-50 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="file-text" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Franchise Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="franchise-mgmt-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="franchise-mgmt-menu" class="ml-8 space-y-1">
                        <a href="../../franchise_management/franchise_application_workflow/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Application & Workflow</a>
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
                                <p class="text-2xl font-bold text-blue-600">4</p>
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
                                <p class="text-2xl font-bold text-yellow-600">2</p>
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
                                <p class="text-2xl font-bold text-green-600">1</p>
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
                                <p class="text-2xl font-bold text-slate-900 dark:text-white">15 days</p>
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
                        <button onclick="openNewApplicationModal()" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 flex items-center space-x-2">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>New Application</span>
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
                                            <button onclick="openRouteWorkflowModal('<?php echo $app['application_id']; ?>')" class="p-1 text-orange-600 hover:bg-orange-100 rounded" title="Route Workflow">
                                                <i data-lucide="route" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="openSetTimelineModal('<?php echo $app['application_id']; ?>')" class="p-1 text-green-600 hover:bg-green-100 rounded" title="Set Timeline">
                                                <i data-lucide="clock" class="w-4 h-4"></i>
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



    <!-- New Application Modal -->
    <div id="newApplicationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="flex justify-between items-center p-6 border-b">
                    <h3 class="text-lg font-semibold">New Franchise Application</h3>
                    <button onclick="closeModal('newApplicationModal')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Operator</label>
                            <select id="operatorId" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="">Select Operator</option>
                                <?php 
                                $op_query = "SELECT operator_id, first_name, last_name FROM operators ORDER BY first_name";
                                $op_stmt = $conn->prepare($op_query);
                                $op_stmt->execute();
                                $operators = $op_stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($operators as $op): ?>
                                <option value="<?php echo $op['operator_id']; ?>"><?php echo $op['first_name'] . ' ' . $op['last_name'] . ' (' . $op['operator_id'] . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle</label>
                            <select id="vehicleId" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="">Select Vehicle</option>
                                <?php 
                                $v_query = "SELECT vehicle_id, plate_number, operator_id FROM vehicles ORDER BY plate_number";
                                $v_stmt = $conn->prepare($v_query);
                                $v_stmt->execute();
                                $vehicles = $v_stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($vehicles as $v): ?>
                                <option value="<?php echo $v['vehicle_id']; ?>" data-operator="<?php echo $v['operator_id']; ?>"><?php echo $v['plate_number'] . ' (' . $v['vehicle_id'] . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Application Type</label>
                            <select id="applicationType" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="new">New Franchise</option>
                                <option value="renewal">Renewal</option>
                                <option value="amendment">Amendment</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Route Requested</label>
                            <input type="text" id="routeRequested" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Enter route">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 p-6 border-t">
                    <button onclick="closeModal('newApplicationModal')" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="createApplication()" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                        Create Application
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
                    <button onclick="routeWorkflow()" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
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
                    <button onclick="setTimeline()" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                        Set Timeline
                    </button>
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
        


        function openNewApplicationModal() {
            document.getElementById('newApplicationModal').classList.remove('hidden');
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

        // Create new application
        function createApplication() {
            const operatorId = document.getElementById('operatorId').value;
            const vehicleId = document.getElementById('vehicleId').value;
            const applicationType = document.getElementById('applicationType').value;
            const routeRequested = document.getElementById('routeRequested').value;
            
            if (!operatorId || !vehicleId || !routeRequested) {
                alert('Please fill in all required fields');
                return;
            }
            
            fetch('simple_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'assign_application_id',
                    operator_id: operatorId,
                    vehicle_id: vehicleId,
                    application_type: applicationType,
                    route_requested: routeRequested
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('newApplicationModal');
                    alert(`Application created with ID: ${data.application_id}`);
                    location.reload();
                } else {
                    alert('Failed to create application');
                }
            })
            .catch(error => {
                console.error('Create error:', error);
                alert('Error creating application');
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