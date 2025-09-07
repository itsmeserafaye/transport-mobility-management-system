<?php
// Transport and Mobility Management System - Administrator Dashboard
require_once '../config/config.php';
require_once '../config/database.php';

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Function to get dashboard KPIs
function getDashboardKPIs($conn) {
    $kpis = [];
    
    // Total Operators
    $stmt = $conn->query("SELECT COUNT(*) as total FROM operators WHERE status = 'active'");
    $kpis['total_operators'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Vehicles
    $stmt = $conn->query("SELECT COUNT(*) as total FROM vehicles WHERE status = 'active'");
    $kpis['total_vehicles'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active Franchises
    $stmt = $conn->query("SELECT COUNT(*) as total FROM franchise_records WHERE status = 'valid'");
    $kpis['active_franchises'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending Applications
    $stmt = $conn->query("SELECT COUNT(*) as total FROM franchise_applications WHERE status IN ('submitted', 'under_review', 'pending_documents')");
    $kpis['pending_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Compliance Score Average
    $stmt = $conn->query("SELECT AVG(compliance_score) as avg_score FROM compliance_status");
    $kpis['avg_compliance'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 2);
    
    // Unpaid Violations
    $stmt = $conn->query("SELECT COUNT(*) as total FROM violation_history WHERE settlement_status = 'unpaid'");
    $kpis['unpaid_violations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Overdue Inspections
    $stmt = $conn->query("SELECT COUNT(*) as total FROM compliance_status WHERE inspection_status = 'overdue'");
    $kpis['overdue_inspections'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Revenue This Month
    $stmt = $conn->query("SELECT SUM(fine_amount) as total FROM violation_history WHERE settlement_status = 'paid' AND MONTH(settlement_date) = MONTH(CURRENT_DATE()) AND YEAR(settlement_date) = YEAR(CURRENT_DATE())");
    $kpis['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    return $kpis;
}

// Function to get recent activities
function getRecentActivities($conn, $limit = 10) {
    $query = "SELECT 
        'franchise_application' as type,
        fa.application_id as id,
        CONCAT(o.first_name, ' ', o.last_name) as operator_name,
        fa.status,
        fa.created_at as activity_date,
        'Franchise Application' as activity_type
    FROM franchise_applications fa
    JOIN operators o ON fa.operator_id = o.operator_id
    UNION ALL
    SELECT 
        'violation' as type,
        vh.violation_id as id,
        CONCAT(o.first_name, ' ', o.last_name) as operator_name,
        vh.settlement_status as status,
        vh.created_at as activity_date,
        'Traffic Violation' as activity_type
    FROM violation_history vh
    JOIN operators o ON vh.operator_id = o.operator_id
    UNION ALL
    SELECT 
        'inspection' as type,
        ir.inspection_id as id,
        CONCAT(o.first_name, ' ', o.last_name) as operator_name,
        ir.result as status,
        ir.created_at as activity_date,
        'Vehicle Inspection' as activity_type
    FROM inspection_records ir
    JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
    JOIN operators o ON v.operator_id = o.operator_id
    ORDER BY activity_date DESC
    LIMIT :limit";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get module statistics
function getModuleStats($conn) {
    $stats = [];
    
    // PUV Database Stats
    $stmt = $conn->query("SELECT 
        COUNT(DISTINCT o.operator_id) as operators,
        COUNT(DISTINCT v.vehicle_id) as vehicles,
        COUNT(DISTINCT cs.compliance_id) as compliance_records
    FROM operators o
    LEFT JOIN vehicles v ON o.operator_id = v.operator_id
    LEFT JOIN compliance_status cs ON o.operator_id = cs.operator_id");
    $stats['puv_database'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Franchise Management Stats
    $stmt = $conn->query("SELECT 
        COUNT(DISTINCT fa.application_id) as applications,
        COUNT(DISTINCT dr.document_id) as documents,
        COUNT(DISTINCT fr.franchise_id) as franchises
    FROM franchise_applications fa
    LEFT JOIN document_repository dr ON fa.operator_id = dr.operator_id
    LEFT JOIN franchise_records fr ON fa.operator_id = fr.operator_id");
    $stats['franchise_management'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Traffic Violation Stats
    $stmt = $conn->query("SELECT 
        COUNT(*) as total_violations,
        SUM(CASE WHEN settlement_status = 'paid' THEN fine_amount ELSE 0 END) as collected_revenue,
        SUM(CASE WHEN settlement_status = 'unpaid' THEN fine_amount ELSE 0 END) as pending_revenue
    FROM violation_history");
    $stats['traffic_violations'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vehicle Inspection Stats
    $stmt = $conn->query("SELECT 
        COUNT(*) as total_inspections,
        COUNT(CASE WHEN result = 'passed' THEN 1 END) as passed,
        COUNT(CASE WHEN result = 'failed' THEN 1 END) as failed,
        COUNT(CASE WHEN result = 'pending' THEN 1 END) as pending
    FROM inspection_records");
    $stats['vehicle_inspection'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $stats;
}

// Get dashboard data
$kpis = getDashboardKPIs($conn);
$activities = getRecentActivities($conn);
$moduleStats = getModuleStats($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard - Transport Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
</head>
<body class="bg-slate-50 dark:bg-slate-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-white border-r border-slate-200 dark:bg-slate-900 dark:border-slate-700 transform transition-transform duration-300 ease-in-out translate-x-0">
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <img src="../upload/Caloocan_City.png" alt="Caloocan City Logo" class="w-10 h-10 rounded-xl">
                    <div>
                        <h1 class="text-xl font-bold dark:text-white">TMM</h1>
                        <p class="text-xs text-slate-500">Admin Dashboard</p>
                    </div>
                </div>
            </div>
            <hr class="border-slate-200 dark:border-slate-700 mx-2">
            
            <!-- Navigation -->
            <nav class="p-4 space-y-2">
                <a href="#" class="w-full flex items-center p-2 rounded-xl text-orange-600 bg-orange-50 transition-all">
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
                        <a href="puv_database/vehicle_and_operator_records/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Vehicle & Operator Records</a>
                        <a href="puv_database/compliance_status_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Compliance Status Management</a>
                        <a href="puv_database/violation_history_integration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Violation History Integration</a>
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
                        <a href="franchise_management/franchise_application_workflow/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Application & Workflow</a>
                        <a href="franchise_management/document_repository/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Document Repository</a>
                        <a href="franchise_management/franchise_lifecycle_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Lifecycle Management</a>
                        <a href="franchise_management/route_and_schedule_publication/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Route & Schedule Publication</a>
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
                        <a href="traffic_violation_ticketing/violation_record_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Violation Record Management</a>
                        <a href="traffic_violation_ticketing/linking_and_analytics/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">TVT Analytics</a>
                        <a href="traffic_violation_ticketing/revenue_integration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Revenue Integration</a>
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
                        <a href="vehicle_inspection_and_registration/inspection_scheduling/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Inspection Scheduling</a>
                        <a href="vehicle_inspection_and_registration/inspection_result_recording/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Result Recording</a>
                        <a href="vehicle_inspection_and_registration/inspection_history_tracking/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">History Tracking</a>
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
                        <a href="parking_and_terminal_management/terminal_assignment_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Terminal Assignment</a>
                        <a href="parking_and_terminal_management/roster_and_delivery/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Roster & Directory</a>
                        <a href="parking_and_terminal_management/public_transparency/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Public Transparency</a>
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
                            <h1 class="text-md font-bold dark:text-white">ADMINISTRATOR DASHBOARD</h1>
                            <span class="text-xs text-slate-500 font-bold">Transport & Mobility Management System</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="p-2 rounded-xl text-slate-600 hover:bg-slate-200">
                            <i data-lucide="bell" class="w-6 h-6"></i>
                        </button>
                        <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle Dark Mode">
                            <i class="fas fa-moon" id="darkModeIcon"></i>
                        </button>
                    </div>
                </div>
            </div>

        <!-- Dashboard Content -->
        <main class="p-6 bg-slate-50 dark:bg-slate-900 flex-1 overflow-y-auto">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Operators</p>
                            <p class="text-3xl font-bold text-blue-600"><?php echo number_format($kpis['total_operators']); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Vehicles</p>
                            <p class="text-3xl font-bold text-green-600"><?php echo number_format($kpis['total_vehicles']); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="car" class="w-6 h-6 text-green-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Valid Franchises</p>
                            <p class="text-3xl font-bold text-purple-600"><?php echo number_format($kpis['active_franchises']); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="award" class="w-6 h-6 text-purple-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Avg Compliance</p>
                            <p class="text-3xl font-bold text-orange-600"><?php echo $kpis['avg_compliance']; ?>%</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-6 h-6 text-orange-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pending Applications</p>
                            <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($kpis['pending_applications']); ?></p>
                        </div>
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="clock" class="w-5 h-5 text-yellow-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Unpaid Violations</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo number_format($kpis['unpaid_violations']); ?></p>
                        </div>
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Overdue Inspections</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo number_format($kpis['overdue_inspections']); ?></p>
                        </div>
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="search" class="w-5 h-5 text-red-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Monthly Revenue</p>
                            <p class="text-2xl font-bold text-green-600">₱<?php echo number_format($kpis['monthly_revenue'], 2); ?></p>
                        </div>
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="dollar-sign" class="w-5 h-5 text-green-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Overview Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- PUV Database Module -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">PUV Database Module</h3>
                        <i data-lucide="database" class="w-6 h-6 text-blue-600"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Registered Operators</span>
                            <span class="font-semibold"><?php echo number_format($moduleStats['puv_database']['operators']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Registered Vehicles</span>
                            <span class="font-semibold"><?php echo number_format($moduleStats['puv_database']['vehicles']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Compliance Records</span>
                            <span class="font-semibold"><?php echo number_format($moduleStats['puv_database']['compliance_records']); ?></span>
                        </div>
                    </div>

                </div>

                <!-- Franchise Management Module -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Franchise Management</h3>
                        <i data-lucide="file-text" class="w-6 h-6 text-green-600"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Applications</span>
                            <span class="font-semibold"><?php echo number_format($moduleStats['franchise_management']['applications']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Documents Stored</span>
                            <span class="font-semibold"><?php echo number_format($moduleStats['franchise_management']['documents']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Active Franchises</span>
                            <span class="font-semibold"><?php echo number_format($moduleStats['franchise_management']['franchises']); ?></span>
                        </div>
                    </div>

                </div>

                <!-- Traffic Violation Module -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Traffic Violations</h3>
                        <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Violations</span>
                            <span class="font-semibold"><?php echo number_format($moduleStats['traffic_violations']['total_violations']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Collected Revenue</span>
                            <span class="font-semibold text-green-600">₱<?php echo number_format($moduleStats['traffic_violations']['collected_revenue'], 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Pending Revenue</span>
                            <span class="font-semibold text-red-600">₱<?php echo number_format($moduleStats['traffic_violations']['pending_revenue'], 2); ?></span>
                        </div>
                    </div>

                </div>

                <!-- Vehicle Inspection Module -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Vehicle Inspection</h3>
                        <i data-lucide="clipboard-check" class="w-6 h-6 text-purple-600"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Inspections</span>
                            <span class="font-semibold"><?php echo number_format($moduleStats['vehicle_inspection']['total_inspections']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Passed</span>
                            <span class="font-semibold text-green-600"><?php echo number_format($moduleStats['vehicle_inspection']['passed']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Failed</span>
                            <span class="font-semibold text-red-600"><?php echo number_format($moduleStats['vehicle_inspection']['failed']); ?></span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Activities</h3>
                    <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</button>
                </div>
                <div class="space-y-4">
                    <?php foreach ($activities as $activity): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center mr-4
                                <?php 
                                    switch($activity['type']) {
                                        case 'franchise_application': echo 'bg-blue-100'; break;
                                        case 'violation': echo 'bg-red-100'; break;
                                        case 'inspection': echo 'bg-purple-100'; break;
                                        default: echo 'bg-gray-100';
                                    }
                                ?>">
                                <i data-lucide="<?php 
                                        switch($activity['type']) {
                                            case 'franchise_application': echo 'file-text'; break;
                                            case 'violation': echo 'alert-triangle'; break;
                                            case 'inspection': echo 'clipboard-check'; break;
                                            default: echo 'info';
                                        }
                                    ?>" class="w-5 h-5 <?php 
                                        switch($activity['type']) {
                                            case 'franchise_application': echo 'text-blue-600'; break;
                                            case 'violation': echo 'text-red-600'; break;
                                            case 'inspection': echo 'text-purple-600'; break;
                                            default: echo 'text-gray-600';
                                        }
                                    ?>"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($activity['operator_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($activity['activity_type']); ?> - <?php echo htmlspecialchars($activity['status']); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($activity['activity_date'])); ?></p>
                            <p class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($activity['activity_date'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">System Status & Data Flow</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="database" class="w-8 h-8 text-green-600"></i>
                        </div>
                        <h4 class="font-semibold text-gray-800">Database</h4>
                        <p class="text-sm text-green-600">Connected</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="refresh-cw" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <h4 class="font-semibold text-gray-800">Data Sync</h4>
                        <p class="text-sm text-blue-600">Active</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="shield" class="w-8 h-8 text-purple-600"></i>
                        </div>
                        <h4 class="font-semibold text-gray-800">Security</h4>
                        <p class="text-sm text-purple-600">Secure</p>
                    </div>
                </div>
            </div>
        </main>
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

        // Auto-refresh dashboard data every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);

        // Dark Mode Functionality
        function initializeDarkMode() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const darkModeIcon = document.getElementById('darkModeIcon');
            const body = document.body;
            
            // Check for saved theme preference or default to light mode
            const savedTheme = localStorage.getItem('theme') || 'light';
            
            // Apply saved theme
            if (savedTheme === 'dark') {
                body.setAttribute('data-theme', 'dark');
                darkModeIcon.className = 'fas fa-sun';
            } else {
                body.setAttribute('data-theme', 'light');
                darkModeIcon.className = 'fas fa-moon';
            }
            
            // Toggle dark mode
            darkModeToggle.addEventListener('click', function() {
                const currentTheme = body.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                // Add rotation animation
                darkModeToggle.classList.add('rotating');
                
                setTimeout(() => {
                    body.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    
                    // Update icon
                    if (newTheme === 'dark') {
                        darkModeIcon.className = 'fas fa-sun';
                    } else {
                        darkModeIcon.className = 'fas fa-moon';
                    }
                    
                    // Remove rotation animation
                    darkModeToggle.classList.remove('rotating');
                }, 150);
            });
        }

        // Initialize tooltips and other interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dark mode
            initializeDarkMode();
            
            // Add any other initialization code here
            console.log('Transport & Mobility Management System - Administrator Dashboard Loaded');
        });
    </script>
</body>
</html>