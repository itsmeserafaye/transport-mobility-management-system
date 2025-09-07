<?php
require_once '../../../config/database.php';
require_once '../../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Get statistics
$stats = getInspectionStats($conn);
$pending_inspections = getPendingInspections($conn, $search, $status, $date_from, $date_to);
$scheduled_inspections = getScheduledInspections($conn, $search, $status, $date_from, $date_to);
$overdue_inspections = getOverdueInspections($conn, $search, $status, $date_from, $date_to);

function getInspectionStats($conn) {
    $stats = [];
    
    // Total vehicles requiring inspection
    $query = "SELECT COUNT(*) as total FROM vehicles WHERE status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['total_vehicles'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending inspections
    $query = "SELECT COUNT(*) as total FROM compliance_status WHERE inspection_status IN ('pending', 'overdue')";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['pending_inspections'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Scheduled this month
    $query = "SELECT COUNT(*) as total FROM inspection_records WHERE MONTH(inspection_date) = MONTH(CURDATE()) AND YEAR(inspection_date) = YEAR(CURDATE())";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['scheduled_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Compliance rate
    $query = "SELECT COUNT(*) as total FROM compliance_status WHERE inspection_status = 'passed'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $passed = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stats['compliance_rate'] = $stats['total_vehicles'] > 0 ? round(($passed / $stats['total_vehicles']) * 100) : 0;
    
    return $stats;
}

function getPendingInspections($conn, $search = '', $status = '', $date_from = '', $date_to = '') {
    $conditions = ["cs.inspection_status IN ('pending', 'overdue')"];
    $params = [];
    
    if (!empty($search)) {
        $conditions[] = "(o.first_name LIKE ? OR o.last_name LIKE ? OR v.plate_number LIKE ? OR v.vehicle_type LIKE ? OR v.make LIKE ? OR v.model LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if (!empty($status) && $status !== 'pending') {
        $conditions[0] = "cs.inspection_status = ?";
        array_unshift($params, $status);
    }
    
    if (!empty($date_from)) {
        $conditions[] = "cs.next_inspection_due >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $conditions[] = "cs.next_inspection_due <= ?";
        $params[] = $date_to;
    }
    
    $query = "SELECT cs.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model,
              fa.application_id, fa.application_type
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              LEFT JOIN franchise_applications fa ON cs.operator_id = fa.operator_id AND cs.vehicle_id = fa.vehicle_id
              WHERE " . implode(' AND ', $conditions) . "
              ORDER BY cs.next_inspection_due ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getScheduledInspections($conn, $search = '', $status = '', $date_from = '', $date_to = '') {
    $conditions = ["ir.inspection_date >= CURDATE()"];
    $params = [];
    
    if (!empty($search)) {
        $conditions[] = "(o.first_name LIKE ? OR o.last_name LIKE ? OR v.plate_number LIKE ? OR v.vehicle_type LIKE ? OR v.make LIKE ? OR v.model LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if (!empty($status) && $status === 'scheduled') {
        // Already filtered by scheduled status in base condition
    }
    
    if (!empty($date_from)) {
        $conditions[] = "ir.inspection_date >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $conditions[] = "ir.inspection_date <= ?";
        $params[] = $date_to;
    }
    
    $query = "SELECT ir.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model
              FROM inspection_records ir
              JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
              JOIN operators o ON v.operator_id = o.operator_id
              WHERE " . implode(' AND ', $conditions) . "
              ORDER BY ir.inspection_date ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOverdueInspections($conn, $search = '', $status = '', $date_from = '', $date_to = '') {
    $conditions = ["cs.inspection_status = 'overdue'", "cs.next_inspection_due < CURDATE()"];
    $params = [];
    
    if (!empty($search)) {
        $conditions[] = "(o.first_name LIKE ? OR o.last_name LIKE ? OR v.plate_number LIKE ? OR v.vehicle_type LIKE ? OR v.make LIKE ? OR v.model LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if (!empty($status) && $status === 'overdue') {
        // Already filtered by overdue status in base condition
    }
    
    if (!empty($date_from)) {
        $conditions[] = "cs.next_inspection_due >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $conditions[] = "cs.next_inspection_due <= ?";
        $params[] = $date_to;
    }
    
    $query = "SELECT cs.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model,
              DATEDIFF(CURDATE(), cs.next_inspection_due) as days_overdue
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              WHERE " . implode(' AND ', $conditions) . "
              ORDER BY days_overdue DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {

        }
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Scheduling - Transport Management</title>
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
                        <h1 class="text-xl font-bold dark:text-white">PTSMD</h1>
                        <p class="text-xs text-slate-500">PTSMD Portal</p>
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
                    <button onclick="toggleDropdown('vehicle-inspection')" class="w-full flex items-center justify-between p-2 rounded-xl text-orange-600 bg-orange-50 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Vehicle Inspection</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="vehicle-inspection-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="vehicle-inspection-menu" class="ml-8 space-y-1">
                        <a href="../../vehicle_inspection_and_registration/inspection_scheduling/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Inspection Scheduling</a>
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
                            <h1 class="text-md font-bold dark:text-white">INSPECTION SCHEDULING</h1>
                            <span class="text-xs text-slate-500 font-bold">Vehicle Inspection & Registration Module</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="p-2 rounded-xl text-slate-600 hover:bg-slate-200">
                            <i data-lucide="bell" class="w-6 h-6"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="flex-1 p-6 overflow-auto">
            <div class="max-w-7xl mx-auto">
                <!-- Page Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">Inspection Scheduling</h2>
                    <p class="mt-2 text-gray-600">Schedule vehicle inspections, manage appointments, and track compliance</p>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i data-lucide="car" class="h-6 w-6 text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Vehicles</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_vehicles']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i data-lucide="clock" class="h-6 w-6 text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending Inspections</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_inspections']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i data-lucide="calendar" class="h-6 w-6 text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Scheduled This Month</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['scheduled_this_month']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i data-lucide="check-circle" class="h-6 w-6 text-orange-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Compliance Rate</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['compliance_rate']; ?>%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-lg shadow">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6">
                            <button onclick="switchTab('pending')" id="pending-tab" class="py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600">
                                Pending Inspections
                            </button>
                            <button onclick="switchTab('scheduled')" id="scheduled-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Scheduled Inspections
                            </button>
                            <button onclick="switchTab('overdue')" id="overdue-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Overdue Inspections
                            </button>
                        </nav>
                    </div>

                    <!-- Search and Filter Form -->
                    <div class="p-6 border-b border-gray-200">
                        <form method="GET" action="" id="filterForm" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <!-- Search Input -->
                                <div>
                                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                    <input type="text" id="searchInput" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                                           placeholder="Search by operator, vehicle, plate number..." 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="hidden" id="hiddenSearch" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>">
                                </div>
                                
                                <!-- Status Filter -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?php echo ($status ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="scheduled" <?php echo ($status ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                        <option value="completed" <?php echo ($status ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="overdue" <?php echo ($status ?? '') === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                    </select>
                                </div>
                                
                                <!-- Date From -->
                                <div>
                                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                    <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from ?? ''); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <!-- Date To -->
                                <div>
                                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                    <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to ?? ''); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="flex">
                                <button type="button" onclick="clearFilters()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Clear Filters
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Pending Inspections Tab -->
                    <div id="pending-content" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Pending Inspections</h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>

                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($pending_inspections as $inspection): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['first_name'] . ' ' . $inspection['last_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['operator_id']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['vehicle_type'] . ' - ' . $inspection['make'] . ' ' . $inspection['model']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo getStatusBadge($inspection['inspection_status']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $inspection['next_inspection_due'] ? formatDate($inspection['next_inspection_due']) : 'Not Set'; ?>
                                        </td>

                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Scheduled Inspections Tab -->
                    <div id="scheduled-content" class="p-6 hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Scheduled Inspections</h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspection ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>

                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($scheduled_inspections as $inspection): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($inspection['inspection_id']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['first_name'] . ' ' . $inspection['last_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['vehicle_type']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($inspection['inspection_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($inspection['inspector_name']); ?>
                                        </td>

                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Overdue Inspections Tab -->
                    <div id="overdue-content" class="p-6 hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Overdue Inspections</h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Overdue</th>

                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($overdue_inspections as $inspection): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['first_name'] . ' ' . $inspection['last_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['operator_id']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['vehicle_type']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($inspection['next_inspection_due']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                                <?php echo $inspection['days_overdue']; ?> days
                                            </span>
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
        </div>
    </div>



    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Tab switching
        function switchTab(tab) {
            // Hide all content
            document.getElementById('pending-content').classList.add('hidden');
            document.getElementById('scheduled-content').classList.add('hidden');
            document.getElementById('overdue-content').classList.add('hidden');
            
            // Reset all tabs
            document.getElementById('pending-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            document.getElementById('scheduled-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            document.getElementById('overdue-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            
            // Show selected content and activate tab
            document.getElementById(tab + '-content').classList.remove('hidden');
            document.getElementById(tab + '-tab').className = 'py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600';
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

        // Search and Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const hiddenSearch = document.getElementById('hiddenSearch');
            const filterForm = document.getElementById('filterForm');
            const statusSelect = document.getElementById('status');
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');

            // Handle search input
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    hiddenSearch.value = this.value;
                    filterForm.submit();
                });
            }

            // Handle filter changes
            [statusSelect, dateFromInput, dateToInput].forEach(element => {
                if (element) {
                    element.addEventListener('change', function() {
                        if (searchInput) {
                            hiddenSearch.value = searchInput.value;
                        }
                        filterForm.submit();
                    });
                }
            });
        });

        // Add automatic filtering on input changes
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for automatic filtering
            const statusSelect = document.getElementById('status');
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');
            const filterForm = document.getElementById('filterForm');
            
            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    filterForm.submit();
                });
            }
            
            if (dateFromInput) {
                dateFromInput.addEventListener('change', function() {
                    filterForm.submit();
                });
            }
            
            if (dateToInput) {
                dateToInput.addEventListener('change', function() {
                    filterForm.submit();
                });
            }
        });

        // Clear filters function
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('hiddenSearch').value = '';
            document.getElementById('status').value = '';
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            document.getElementById('filterForm').submit();
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