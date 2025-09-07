<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Handle search and filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$vehicle_type_filter = $_GET['vehicle_type'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get operators data with filters
if ($search || $status_filter || $vehicle_type_filter) {
    $operators = searchOperators($conn, $search, $status_filter, $vehicle_type_filter);
} else {
    $operators = getOperators($conn, $limit, $offset);
}

// Get statistics
$stats = getStatistics($conn);
$total_operators = getTotalOperators($conn);
$total_pages = ceil($total_operators / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle & Operator Records - Transport Management</title>
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
                <a href="../../index.php" class="w-full flex items-center p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                    <i data-lucide="home" class="w-5 h-5 mr-3"></i>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('puv-database')" class="w-full flex items-center justify-between p-2 rounded-xl text-orange-600 bg-orange-50 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="database" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">PUV Database</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="puv-database-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="puv-database-menu" class="ml-8 space-y-1">
                        <a href="../../puv_database/vehicle_and_operator_records/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Vehicle & Operator Records</a>
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
                            <span class="text-xs text-slate-500 font-bold">PUV Database > Vehicle & Operator Records</span>
                        </div>
                    </div>
                    <div class="flex-1 max-w-md mx-8">
                        <form method="GET" class="relative">
                            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search operators or vehicles..." 
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
                                <p class="text-slate-500 text-sm">Total Operators</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $stats['total_operators']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Active Vehicles</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $stats['active_vehicles']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="truck" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Compliant</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $stats['compliance_rate']; ?>%</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-orange-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Pending Inspection</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $stats['pending_inspections']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="alert-circle" class="w-6 h-6 text-red-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compliance Legend -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-semibold text-blue-800 mb-2">Compliance Status Guide</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-400 rounded-full mr-2"></div>
                            <span class="text-green-700"><strong>Active:</strong> Score ≥80%, Valid franchise, Current inspection</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-yellow-400 rounded-full mr-2"></div>
                            <span class="text-yellow-700"><strong>Pending:</strong> Score ≥60%, Pending documents or inspection</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-red-400 rounded-full mr-2"></div>
                            <span class="text-red-700"><strong>Inactive:</strong> Score <60%, Expired/suspended franchise, or overdue inspection</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Vehicle & Operator Records</h2>
                    <div class="flex space-x-3">
                        <button onclick="openAddModal()" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 flex items-center space-x-2">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Add Operator</span>
                        </button>
                        <button onclick="openVehicleModal()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 flex items-center space-x-2">
                            <i data-lucide="car" class="w-4 h-4"></i>
                            <span>Register Vehicle</span>
                        </button>
                        <button onclick="updateComplianceScores()" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 flex items-center space-x-2">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            <span>Update Scores</span>
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
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                        <select name="vehicle_type" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Vehicle Types</option>
                            <option value="jeepney" <?php echo $vehicle_type_filter == 'jeepney' ? 'selected' : ''; ?>>Jeepney</option>
                            <option value="bus" <?php echo $vehicle_type_filter == 'bus' ? 'selected' : ''; ?>>Bus</option>
                            <option value="tricycle" <?php echo $vehicle_type_filter == 'tricycle' ? 'selected' : ''; ?>>Tricycle</option>
                            <option value="taxi" <?php echo $vehicle_type_filter == 'taxi' ? 'selected' : ''; ?>>Taxi</option>
                            <option value="van" <?php echo $vehicle_type_filter == 'van' ? 'selected' : ''; ?>>Van</option>
                        </select>
                        <select class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option>All Compliance</option>
                            <option>Compliant</option>
                            <option>Non-Compliant</option>
                            <option>Pending</option>
                        </select>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <input type="date" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                        <button type="submit" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 flex items-center justify-center space-x-2">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                            <span>Apply Filters</span>
                        </button>
                    </div>
                </form>

                <!-- Data Table -->
                <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Operator</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Vehicle Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">License</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Compliance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                <?php foreach ($operators as $operator): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-blue-600 font-medium"><?php echo strtoupper(substr($operator['first_name'], 0, 1) . substr($operator['last_name'], 0, 1)); ?></span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $operator['first_name'] . ' ' . $operator['last_name']; ?></div>
                                                <div class="text-sm text-slate-500"><?php echo $operator['operator_id']; ?></div>
                                                <div class="text-xs text-slate-400"><?php echo $operator['contact_number']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo $operator['plate_number'] ?? 'N/A'; ?></div>
                                        <div class="text-sm text-slate-500"><?php echo ucfirst($operator['vehicle_type'] ?? 'N/A') . ' - ' . ($operator['make'] ?? 'N/A') . ' ' . ($operator['model'] ?? 'N/A'); ?></div>
                                        <div class="text-xs text-slate-400">License: <?php echo $operator['license_number']; ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo $operator['license_number']; ?></div>
                                        <div class="text-xs <?php echo (strtotime($operator['license_expiry']) < time()) ? 'text-red-500' : 'text-slate-500'; ?>">Expires: <?php echo date('M d, Y', strtotime($operator['license_expiry'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $score = $operator['compliance_score'] ?? 0;
                                        $franchise_status = $operator['franchise_status'] ?? 'pending';
                                        $inspection_status = $operator['inspection_status'] ?? 'pending';
                                        
                                        // Determine compliance based on multiple factors
                                        $is_compliant = ($score >= 80 && $franchise_status == 'valid' && $inspection_status == 'passed');
                                        $is_pending = ($score >= 60 && ($franchise_status == 'pending' || $inspection_status == 'pending'));
                                        
                                        if ($is_compliant) {
                                            $comp_color = 'green';
                                            $comp_text = 'Compliant';
                                        } elseif ($is_pending) {
                                            $comp_color = 'yellow';
                                            $comp_text = 'Pending';
                                        } else {
                                            $comp_color = 'red';
                                            $comp_text = 'Non-Compliant';
                                        }
                                        ?>
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 bg-<?php echo $comp_color; ?>-400 rounded-full mr-2"></div>
                                            <span class="text-sm text-<?php echo $comp_color; ?>-600"><?php echo $comp_text; ?></span>
                                        </div>
                                        <div class="text-xs text-slate-500">Score: <?php echo number_format($score, 1); ?>% | <?php echo ucfirst($franchise_status); ?> | <?php echo ucfirst($inspection_status); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        // Calculate status based on compliance rules
                                        $calculated_status = $is_compliant ? 'active' : ($is_pending ? 'pending' : 'inactive');
                                        $current_db_status = $operator['status'] ?? 'active';
                                        
                                        // Auto-update database if calculated status differs from stored status
                                        if ($calculated_status != $current_db_status) {
                                            $update_query = "UPDATE operators SET status = ? WHERE operator_id = ?";
                                            $update_stmt = $conn->prepare($update_query);
                                            $update_stmt->execute([$calculated_status, $operator['operator_id']]);
                                        }
                                        
                                        $statusClass = $calculated_status == 'active' ? 'bg-green-100 text-green-800' : 
                                                      ($calculated_status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo ucfirst($calculated_status); ?></span>
                                        <div class="text-xs text-slate-500 mt-1">
                                            <?php if ($calculated_status == 'active'): ?>
                                                All requirements met
                                            <?php elseif ($calculated_status == 'pending'): ?>
                                                Pending compliance items
                                            <?php else: ?>
                                                Action required
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button onclick="openViewModal('<?php echo $operator['operator_id']; ?>')" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="View Details">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="openEditModal('<?php echo $operator['operator_id']; ?>')" class="p-1 text-orange-600 hover:bg-orange-100 rounded" title="Edit">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="deleteOperator('<?php echo $operator['operator_id']; ?>')" class="p-1 text-red-600 hover:bg-red-100 rounded" title="Delete">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
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
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_operators); ?> of <?php echo $total_operators; ?> results
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&vehicle_type=<?php echo urlencode($vehicle_type_filter); ?>" 
                                   class="px-3 py-1 border border-slate-300 rounded text-slate-600 hover:bg-slate-50">Previous</a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&vehicle_type=<?php echo urlencode($vehicle_type_filter); ?>" 
                                   class="px-3 py-1 <?php echo $i == $page ? 'bg-orange-500 text-white' : 'border border-slate-300 text-slate-600 hover:bg-slate-50'; ?> rounded"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&vehicle_type=<?php echo urlencode($vehicle_type_filter); ?>" 
                                   class="px-3 py-1 border border-slate-300 rounded text-slate-600 hover:bg-slate-50">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Operator Modal -->
    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Add New Operator</h2>
                <button onclick="closeModal('addModal')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="addOperatorForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" name="first_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" name="last_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <textarea name="address" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="text" name="contact_number" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">License Number</label>
                        <input type="text" name="license_number" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">License Expiry</label>
                    <input type="date" name="license_expiry" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">Add Operator</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Operator Modal -->
    <div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Operator Details</h2>
                <button onclick="closeModal('viewModal')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="viewModalContent"></div>
        </div>
    </div>

    <!-- Edit Operator Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Edit Operator</h2>
                <button onclick="closeModal('editModal')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="editModalContent"></div>
        </div>
    </div>

    <!-- Register Vehicle Modal -->
    <div id="vehicleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Register New Vehicle</h2>
                <button onclick="closeModal('vehicleModal')" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="vehicleModalContent"></div>
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

        // Modal Functions
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }
        
        function openVehicleModal() {
            fetch('register_vehicle_modal.php')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('vehicleModalContent').innerHTML = html;
                    document.getElementById('vehicleModal').classList.remove('hidden');
                    
                    setTimeout(() => {
                        const form = document.getElementById('registerVehicleForm');
                        if (form) {
                            form.addEventListener('submit', handleVehicleSubmit);
                        }
                    }, 100);
                });
        }
        
        function handleVehicleSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            fetch('register_vehicle_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Vehicle registered successfully!');
                    closeModal('vehicleModal');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to register vehicle');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        function openViewModal(operatorId) {
            fetch(`view_modal.php?id=${operatorId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('viewModalContent').innerHTML = html;
                    document.getElementById('viewModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error loading view modal:', error);
                    alert('Error loading operator details');
                });
        }

        function openEditModal(operatorId) {
            console.log('Opening edit modal for operator:', operatorId);
            fetch(`edit_modal.php?id=${operatorId}`)
                .then(response => {
                    console.log('Edit modal response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    console.log('Edit modal HTML loaded');
                    document.getElementById('editModalContent').innerHTML = html;
                    document.getElementById('editModal').classList.remove('hidden');
                    
                    // Attach event listener to the form after it's loaded
                    setTimeout(() => {
                        const form = document.getElementById('editOperatorForm');
                        if (form) {
                            console.log('Attaching event listener to edit form');
                            form.addEventListener('submit', handleEditSubmit);
                        } else {
                            console.error('Edit form not found after loading');
                        }
                    }, 100);
                })
                .catch(error => {
                    console.error('Error loading edit modal:', error);
                    alert('Error loading edit form');
                });
        }
        
        function handleEditSubmit(e) {
            e.preventDefault();
            console.log('Edit form submitted');
            
            const formData = new FormData(e.target);
            
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            fetch('edit_operator_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    if (data.success) {
                        alert('Operator updated successfully!');
                        closeModal('editModal');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to update operator');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('Server error: ' + text);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Network error: ' + error.message);
            });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Handle add operator form submission
        document.getElementById('addOperatorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('add_operator_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('addModal');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to add operator');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });

        function deleteOperator(operatorId) {
            if (confirm('Are you sure you want to delete this operator?')) {
                window.location.href = `delete.php?id=${operatorId}`;
            }
        }

        // Search on Enter key
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });

        // Auto-submit search after typing (debounced)
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });

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
        
        function updateComplianceScores() {
            if (confirm('Update all compliance scores based on current status?')) {
                fetch('../compliance_status_management/update_scores.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({action: 'update_scores'})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Compliance scores updated successfully!');
                        location.reload();
                    } else {
                        alert('Error updating scores: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    alert('Error updating scores');
                });
            }
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.flex-1.flex.flex-col');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                // Show sidebar
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                if (mainContent) {
                    mainContent.style.marginLeft = '0';
                    mainContent.style.width = 'calc(100% - 16rem)';
                }
            } else {
                // Hide sidebar
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
                if (mainContent) {
                    mainContent.style.marginLeft = '-16rem';
                    mainContent.style.width = '100%';
                }
            }
        }
    </script>
</body>
</html>