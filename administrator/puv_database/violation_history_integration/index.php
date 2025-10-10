<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/administrator/puv_database/compliance_status_management/functions.php';

$database = new Database();
$conn = $database->getConnection();

// Handle search and filters
$search = $_GET['search'] ?? '';
$settlement_filter = $_GET['settlement_status'] ?? '';
$violation_type_filter = $_GET['violation_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Get violations data with filters
$query = "SELECT vh.*, o.first_name, o.last_name, o.operator_id, v.plate_number, v.vehicle_type, v.make, v.model,
                 (SELECT COUNT(*) FROM violation_history vh2 WHERE vh2.operator_id = vh.operator_id) as total_violations
          FROM violation_history vh
          JOIN operators o ON vh.operator_id = o.operator_id
          LEFT JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
          WHERE 1=1";

$params = [];

if ($search) {
    $query .= " AND (o.first_name LIKE ? OR o.last_name LIKE ? OR v.plate_number LIKE ? OR vh.violation_type LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($settlement_filter) {
    $query .= " AND vh.settlement_status = ?";
    $params[] = $settlement_filter;
}

if ($violation_type_filter) {
    $query .= " AND vh.violation_type = ?";
    $params[] = $violation_type_filter;
}

if ($date_from) {
    $query .= " AND vh.violation_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND vh.violation_date <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY vh.violation_date DESC";

if (!($search || $settlement_filter || $violation_type_filter || $date_from || $date_to)) {
    $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$violations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = getStatistics($conn);

// Add violation-specific statistics if not present
if (!isset($stats['total_violations'])) {
    $stats['total_violations'] = 0;
    $stats['unpaid_fines'] = 0;
    $stats['repeat_offenders'] = 0;
    $stats['settlement_rate'] = 0;
    
    // Get violation statistics from database
    try {
        $query = "SELECT COUNT(*) as total FROM violation_history";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['total_violations'] = $stmt->fetchColumn() ?: 0;
        
        $query = "SELECT COALESCE(SUM(fine_amount), 0) as total FROM violation_history WHERE settlement_status = 'unpaid'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['unpaid_fines'] = $stmt->fetchColumn() ?: 0;
        
        $query = "SELECT COUNT(DISTINCT operator_id) as total FROM violation_history WHERE operator_id IN (SELECT operator_id FROM violation_history GROUP BY operator_id HAVING COUNT(*) > 3)";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['repeat_offenders'] = $stmt->fetchColumn() ?: 0;
        
        $query = "SELECT COUNT(*) as total, SUM(CASE WHEN settlement_status = 'paid' THEN 1 ELSE 0 END) as paid FROM violation_history";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['settlement_rate'] = $result['total'] > 0 ? round(($result['paid'] / $result['total']) * 100, 1) : 0;
    } catch (Exception $e) {
        // Keep default values if queries fail
    }
}
if (function_exists('getTotalViolations')) {
    $total_violations = getTotalViolations($conn);
} else {
    $total_violations = count($violations);
}
$total_pages = ceil($total_violations / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Violation History Integration - Transport Management</title>
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
                    <button onclick="toggleDropdown('puv-database')" class="w-full flex items-center justify-between p-2 rounded-xl transition-all" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.1);">
                        <div class="flex items-center">
                            <i data-lucide="database" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">PUV Database</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="puv-database-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="puv-database-menu" class="ml-8 space-y-1">
                        <a href="../../puv_database/vehicle_and_operator_records/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Vehicle & Operator Records</a>
                        <a href="../../puv_database/compliance_status_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Compliance Status Management</a>
                        <a href="../../puv_database/violation_history_integration/" class="block p-2 text-sm rounded-lg font-medium" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.2);">Violation History Integration</a>
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
                            <h1 class="text-md font-bold dark:text-white">VIOLATION HISTORY INTEGRATION</h1>
                            <span class="text-xs text-gray-500 font-bold">PUV Database Management</span>
                        </div>
                    </div>
                    <div class="flex-1 max-w-md mx-8">
                        <div class="relative">
                            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                            <input type="text" id="searchInput" placeholder="Search violations..." 
                                   class="w-full pl-10 pr-4 py-2 bg-slate-100 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-300"
                                   onkeyup="searchViolations()">
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
                                <p class="text-2xl font-bold text-red-600"><?php echo $stats['total_violations'] ?? 0; ?></p>
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
                                <p class="text-2xl font-bold" style="color: #FDA811;">₱<?php echo number_format($stats['unpaid_fines'] ?? 0, 0); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(253, 168, 17, 0.1);">
                                <i data-lucide="credit-card" class="w-6 h-6" style="color: #FDA811;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Repeat Offenders</p>
                                <p class="text-2xl font-bold" style="color: #4A90E2;"><?php echo $stats['repeat_offenders'] ?? 0; ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(74, 144, 226, 0.1);">
                                <i data-lucide="repeat" class="w-6 h-6" style="color: #4A90E2;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Settlement Rate</p>
                                <p class="text-2xl font-bold" style="color: #4CAF50;"><?php echo $stats['settlement_rate'] ?? 0; ?>%</p>
                            </div>
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(76, 175, 80, 0.1);">
                                <i data-lucide="trending-up" class="w-6 h-6" style="color: #4CAF50;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Violation History Integration</h2>
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
                                <a href="export.php?format=word" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export as Word</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" class="bg-white p-4 rounded-xl border border-slate-200 mb-6 dark:bg-slate-800 dark:border-slate-700">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <select name="settlement_status" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Settlement Status</option>
                            <option value="paid" <?php echo $settlement_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="unpaid" <?php echo $settlement_filter == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="partial" <?php echo $settlement_filter == 'partial' ? 'selected' : ''; ?>>Partial</option>
                        </select>
                        <select name="violation_type" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Violation Types</option>
                            <option value="Speeding" <?php echo $violation_type_filter == 'Speeding' ? 'selected' : ''; ?>>Speeding</option>
                            <option value="Overloading" <?php echo $violation_type_filter == 'Overloading' ? 'selected' : ''; ?>>Overloading</option>
                            <option value="Route Deviation" <?php echo $violation_type_filter == 'Route Deviation' ? 'selected' : ''; ?>>Route Deviation</option>
                            <option value="No Franchise" <?php echo $violation_type_filter == 'No Franchise' ? 'selected' : ''; ?>>No Franchise</option>
                        </select>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From Date" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To Date" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Operator/Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Violation Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Fine Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Settlement Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Violation Trend</th>
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
                                                <div class="text-xs text-slate-400"><?php echo ucfirst($violation['vehicle_type']) . ' - ' . $violation['make'] . ' ' . $violation['model']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo $violation['violation_type']; ?></div>
                                        <div class="text-sm text-slate-500"><?php echo $violation['violation_id']; ?></div>
                                        <div class="text-xs text-slate-400"><?php echo date('M d, Y', strtotime($violation['violation_date'])) . ' - ' . ($violation['location'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">₱<?php echo number_format($violation['fine_amount'], 2); ?></div>
                                        <div class="text-xs text-slate-500">Original fine</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $statusClass = $violation['settlement_status'] == 'paid' ? 'bg-green-100 text-green-800' : 
                                                      ($violation['settlement_status'] == 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo ucfirst($violation['settlement_status']); ?></span>
                                        <div class="text-xs text-slate-500 mt-1"><?php echo $violation['settlement_date'] ? 'Paid: ' . date('M d, Y', strtotime($violation['settlement_date'])) : 'Due: ' . date('M d, Y', strtotime($violation['violation_date'] . ' +30 days')); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $totalViolations = $violation['total_violations'] ?? 1;
                                        $riskLevel = $violation['risk_level'] ?? 'low';
                                        $riskColor = $riskLevel == 'high' ? 'red' : ($riskLevel == 'medium' ? 'yellow' : 'green');
                                        $riskLabel = $totalViolations > 3 ? 'Repeat Offender' : ($totalViolations > 1 ? 'Medium Risk' : 'First Time');
                                        ?>
                                        <div class="text-sm text-<?php echo $riskColor; ?>-600 font-medium"><?php echo $totalViolations; ?> violation<?php echo $totalViolations > 1 ? 's' : ''; ?></div>
                                        <div class="text-xs text-slate-500">Last 6 months</div>
                                        <span class="px-2 py-1 text-xs font-medium bg-<?php echo $riskColor; ?>-100 text-<?php echo $riskColor; ?>-800 rounded-full"><?php echo $riskLabel; ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button onclick="openViewModal('<?php echo $violation['violation_id']; ?>')" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="View Details">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="openEditModal('<?php echo $violation['violation_id']; ?>')" class="p-1 text-orange-600 hover:bg-orange-100 rounded" title="Edit">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
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
                    
                    <!-- Pagination -->
                    <div class="px-6 py-3 border-t border-slate-200 dark:border-slate-600">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-slate-500">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_violations); ?> of <?php echo $total_violations; ?> results
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&settlement_status=<?php echo urlencode($settlement_filter); ?>&violation_type=<?php echo urlencode($violation_type_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                   class="px-3 py-1 border border-slate-300 rounded text-slate-600 hover:bg-slate-50">Previous</a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&settlement_status=<?php echo urlencode($settlement_filter); ?>&violation_type=<?php echo urlencode($violation_type_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                   class="px-3 py-1 <?php echo $i == $page ? 'text-white' : 'border border-gray-300 text-gray-600 hover:bg-gray-50'; ?> rounded" <?php echo $i == $page ? 'style="background-color: #4CAF50;"' : ''; ?>><?php echo $i; ?></a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&settlement_status=<?php echo urlencode($settlement_filter); ?>&violation_type=<?php echo urlencode($violation_type_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                                   class="px-3 py-1 border border-slate-300 rounded text-slate-600 hover:bg-slate-50">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
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
                        <select name="operator_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
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
                        <label class="block text-sm font-medium text-gray-700">Vehicle</label>
                        <select name="vehicle_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
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
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Violation Type</label>
                        <select name="violation_type" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">Select Type</option>
                            <option value="Speeding">Speeding</option>
                            <option value="Overloading">Overloading</option>
                            <option value="Route Deviation">Route Deviation</option>
                            <option value="No Franchise">No Franchise</option>
                            <option value="Reckless Driving">Reckless Driving</option>
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

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Setup search functionality
        setupSearchListeners();

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
                    closeModal('addModal');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to add violation');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });

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
                    closeModal('editModal');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update violation');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        function deleteViolation(violationId) {
            if (confirm('Are you sure you want to delete this violation?')) {
                window.location.href = `delete.php?id=${violationId}`;
            }
        }
        
        function searchViolations() {
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
        
        // Analytics function
        function showAnalytics() {
            fetch('analytics_ajax.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'analytics'})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAnalytics(data.data);
                } else {
                    alert('Failed to generate analytics: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Analytics error:', error);
                alert('Error generating analytics: ' + error.message);
            });
        }
        
        function displayAnalytics(stats) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white rounded-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Violation Analytics</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold mb-3">Top Violation Types</h4>
                            <div class="space-y-2">
                                ${stats.top_violations.map(item => `
                                    <div class="flex justify-between p-2 bg-slate-50 rounded">
                                        <span>${item.violation_type}</span>
                                        <span class="font-medium">${item.count}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-3">Monthly Trends</h4>
                            <div class="space-y-2">
                                ${stats.monthly_trends.map(item => `
                                    <div class="flex justify-between p-2 bg-slate-50 rounded">
                                        <span>${item.month}</span>
                                        <span class="font-medium">${item.violations} violations</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            lucide.createIcons();
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