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
    <link rel="stylesheet" href="assets/css/dark-mode.css">
</head>
<body style="background-color: #FBFBFB;" class="dark:bg-slate-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-white border-r border-gray-200 dark:bg-slate-900 dark:border-slate-700 transform transition-transform duration-300 ease-in-out translate-x-0">
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <img src="../upload/Caloocan_City.png?v=<?php echo time(); ?>" alt="Caloocan City Logo" class="w-10 h-10 rounded-xl">
                    <div>
                        <h1 class="text-xl font-bold dark:text-white">TMM</h1>
                        <p class="text-xs text-slate-500">Admin Dashboard</p>
                    </div>
                </div>
            </div>
            <hr class="border-gray-200 dark:border-slate-700 mx-2">
            
            <!-- Navigation -->
            <nav class="p-4 space-y-2">
                <a href="#" class="w-full flex items-center p-2 rounded-xl" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.1);">
                    <i data-lucide="home" class="w-5 h-5 mr-3"></i>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('puv-database')" class="w-full flex items-center justify-between p-2 rounded-xl text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="database" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">PUV Database</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="puv-database-icon"></i>
                    </button>
                    <div id="puv-database-menu" class="hidden ml-8 space-y-1">
                        <a href="puv_database/vehicle_and_operator_records/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Vehicle & Operator Records</a>
                        <a href="puv_database/compliance_status_management/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Compliance Status Management</a>
                        <a href="puv_database/violation_history_integration/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Violation History Integration</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('franchise-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="file-text" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Franchise Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="franchise-mgmt-icon"></i>
                    </button>
                    <div id="franchise-mgmt-menu" class="hidden ml-8 space-y-1">
                        <a href="franchise_management/franchise_application_workflow/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Application & Workflow</a>
                        <a href="franchise_management/document_repository/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Document Repository</a>
                        <a href="franchise_management/franchise_lifecycle_management/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Lifecycle Management</a>
                        <a href="franchise_management/route_and_schedule_publication/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Route & Schedule Publication</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('violation-ticketing')" class="w-full flex items-center justify-between p-2 rounded-xl text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Traffic Violation Ticketing</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="violation-ticketing-icon"></i>
                    </button>
                    <div id="violation-ticketing-menu" class="hidden ml-8 space-y-1">
                        <a href="traffic_violation_ticketing/violation_record_management/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Violation Record Management</a>
                        <a href="traffic_violation_ticketing/linking_and_analytics/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">TVT Analytics</a>
                        <a href="traffic_violation_ticketing/revenue_integration/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Revenue Integration</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('vehicle-inspection')" class="w-full flex items-center justify-between p-2 rounded-xl text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Vehicle Inspection</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="vehicle-inspection-icon"></i>
                    </button>
                    <div id="vehicle-inspection-menu" class="hidden ml-8 space-y-1">
                        <a href="vehicle_inspection_and_registration/inspection_scheduling/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Inspection Scheduling</a>
                        <a href="vehicle_inspection_and_registration/inspection_result_recording/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Result Recording</a>
                        <a href="vehicle_inspection_and_registration/inspection_history_tracking/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">History Tracking</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('terminal-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="map-pin" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Terminal Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="terminal-mgmt-icon"></i>
                    </button>
                    <div id="terminal-mgmt-menu" class="hidden ml-8 space-y-1">
                        <a href="parking_and_terminal_management/terminal_assignment_management/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Terminal Assignment</a>
                        <a href="parking_and_terminal_management/roster_and_delivery/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Roster & Directory</a>
                        <a href="parking_and_terminal_management/public_transparency/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Public Transparency</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('user-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="users" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">User Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="user-mgmt-icon"></i>
                    </button>
                    <div id="user-mgmt-menu" class="hidden ml-8 space-y-1">
                        <a href="user_management/account_registry/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Account Registry</a>
                        <a href="user_management/verification_queue/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Verification Queue</a>
                        <a href="user_management/account_maintenance/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Account Maintenance</a>
                        <a href="user_management/roles_and_permissions/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Roles & Permissions</a>
                        <a href="user_management/audit_logs/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Audit Logs</a>
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
                            <h1 class="text-md font-bold dark:text-white">ADMINISTRATOR DASHBOARD</h1>
                            <span class="text-xs text-gray-500 font-bold">Transport & Mobility Management System</span>
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

        <!-- Dashboard Content -->
        <main class="p-6 flex-1 overflow-y-auto" style="background-color: #FBFBFB;">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Operators</p>
                            <p class="text-3xl font-bold" style="color: #4A90E2;"><?php echo number_format($kpis['total_operators']); ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(74, 144, 226, 0.1);">
                            <i data-lucide="users" class="w-6 h-6" style="color: #4A90E2;"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Vehicles</p>
                            <p class="text-3xl font-bold" style="color: #4CAF50;"><?php echo number_format($kpis['total_vehicles']); ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(76, 175, 80, 0.1);">
                            <i data-lucide="car" class="w-6 h-6" style="color: #4CAF50;"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Valid Franchises</p>
                            <p class="text-3xl font-bold" style="color: #4A90E2;"><?php echo number_format($kpis['active_franchises']); ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(74, 144, 226, 0.1);">
                            <i data-lucide="award" class="w-6 h-6" style="color: #4A90E2;"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Avg Compliance</p>
                            <p class="text-3xl font-bold" style="color: #FDA811;"><?php echo $kpis['avg_compliance']; ?>%</p>
                        </div>
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(253, 168, 17, 0.1);">
                            <i data-lucide="trending-up" class="w-6 h-6" style="color: #FDA811;"></i>
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
                            <p class="text-2xl font-bold" style="color: #FDA811;"><?php echo number_format($kpis['pending_applications']); ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: rgba(253, 168, 17, 0.1);">
                            <i data-lucide="clock" class="w-5 h-5" style="color: #FDA811;"></i>
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
                            <p class="text-2xl font-bold" style="color: #4CAF50;">₱<?php echo number_format($kpis['monthly_revenue'], 2); ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: rgba(76, 175, 80, 0.1);">
                            <i data-lucide="dollar-sign" class="w-5 h-5" style="color: #4CAF50;"></i>
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
                        <i data-lucide="database" class="w-6 h-6" style="color: #4A90E2;"></i>
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
                        <i data-lucide="file-text" class="w-6 h-6" style="color: #4CAF50;"></i>
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
                            <span class="font-semibold" style="color: #4CAF50;">₱<?php echo number_format($moduleStats['traffic_violations']['collected_revenue'], 2); ?></span>
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
                        <i data-lucide="clipboard-check" class="w-6 h-6" style="color: #4A90E2;"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Inspections</span>
                            <span class="font-semibold"><?php echo number_format($moduleStats['vehicle_inspection']['total_inspections']); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Passed</span>
                            <span class="font-semibold" style="color: #4CAF50;"><?php echo number_format($moduleStats['vehicle_inspection']['passed']); ?></span>
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
                    <button class="text-sm font-medium" style="color: #4A90E2;" onmouseover="this.style.color='#357ABD'" onmouseout="this.style.color='#4A90E2'">View All</button>
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

            <!-- Announcements Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">System Announcements</h3>
                    <button onclick="openAnnouncementModal()" class="text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors" style="background-color: #FDA811;" onmouseover="this.style.backgroundColor='#E8970F'" onmouseout="this.style.backgroundColor='#FDA811'">
                        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
                        New Announcement
                    </button>
                </div>
                <div id="announcements-container" class="space-y-4">
                    <!-- Announcements will be loaded here -->
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">System Status & Data Flow</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background-color: rgba(76, 175, 80, 0.1);">
                            <i data-lucide="database" class="w-8 h-8" style="color: #4CAF50;"></i>
                        </div>
                        <h4 class="font-semibold text-gray-800">Database</h4>
                        <p class="text-sm" style="color: #4CAF50;">Connected</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background-color: rgba(74, 144, 226, 0.1);">
                            <i data-lucide="refresh-cw" class="w-8 h-8" style="color: #4A90E2;"></i>
                        </div>
                        <h4 class="font-semibold text-gray-800">Data Sync</h4>
                        <p class="text-sm" style="color: #4A90E2;">Active</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3" style="background-color: rgba(74, 144, 226, 0.1);">
                            <i data-lucide="shield" class="w-8 h-8" style="color: #4A90E2;"></i>
                        </div>
                        <h4 class="font-semibold text-gray-800">Security</h4>
                        <p class="text-sm" style="color: #4A90E2;">Secure</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Announcement Modal -->
    <div id="announcementModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Create New Announcement</h3>
                        <button onclick="closeAnnouncementModal()" class="text-gray-400 hover:text-gray-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    
                    <form id="announcementForm" enctype="multipart/form-data">
                        <input type="hidden" id="announcementId" name="announcement_id">
                        <input type="hidden" id="existingImage" name="existing_image">
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                                <input type="text" id="announcementTitle" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Content *</label>
                                <textarea id="announcementContent" name="content" required rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                                    <select id="announcementPriority" name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
                                    <select id="announcementAudience" name="target_audience" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                        <option value="all" selected>All Users</option>
                                        <option value="operators">Operators</option>
                                        <option value="citizens">Citizens</option>
                                        <option value="staff">Staff</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select id="announcementStatus" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                        <option value="draft">Draft</option>
                                        <option value="published" selected>Published</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Publish Date</label>
                                    <input type="datetime-local" id="announcementPublishDate" name="publish_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date (Optional)</label>
                                <input type="datetime-local" id="announcementExpiryDate" name="expiry_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Announcement Image (Optional)</label>
                                <input type="file" id="announcementImage" name="announcement_image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <p class="text-xs text-gray-500 mt-1">Supported formats: JPG, JPEG, PNG, GIF (Max 5MB)</p>
                                <div id="imagePreview" class="mt-2 hidden">
                                    <img id="previewImg" src="" alt="Preview" class="max-w-full h-32 object-cover rounded-lg">
                                </div>
                            </div>
                            
                            <input type="hidden" name="created_by" value="Administrator">
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="closeAnnouncementModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                                <span id="submitText">Create Announcement</span>
                            </button>
                        </div>
                    </form>
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

        // Announcement Functions
        function loadAnnouncements() {
            fetch('announcements_handler.php?action=get_announcements')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayAnnouncements(data.data);
                    }
                })
                .catch(error => console.error('Error loading announcements:', error));
        }

        function displayAnnouncements(announcements) {
            const container = document.getElementById('announcements-container');
            
            if (announcements.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-4">No announcements found.</p>';
                return;
            }
            
            container.innerHTML = announcements.slice(0, 3).map(announcement => {
                const priorityColors = {
                    'low': 'bg-gray-100 text-gray-800',
                    'medium': 'bg-blue-100 text-blue-800',
                    'high': 'bg-yellow-100 text-yellow-800',
                    'urgent': 'bg-red-100 text-red-800'
                };
                
                const statusColors = {
                    'draft': 'bg-gray-100 text-gray-800',
                    'published': 'bg-green-100 text-green-800',
                    'archived': 'bg-red-100 text-red-800'
                };
                
                return `
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <h4 class="font-semibold text-gray-800">${announcement.title}</h4>
                                    <span class="px-2 py-1 text-xs rounded-full ${priorityColors[announcement.priority]}">${announcement.priority.toUpperCase()}</span>
                                    <span class="px-2 py-1 text-xs rounded-full ${statusColors[announcement.status]}">${announcement.status.toUpperCase()}</span>
                                </div>
                                <p class="text-gray-600 text-sm mb-2">${announcement.content.substring(0, 150)}${announcement.content.length > 150 ? '...' : ''}</p>
                                ${announcement.image_path ? `<img src="../${announcement.image_path}" alt="Announcement" class="w-20 h-20 object-cover rounded-lg mb-2">` : ''}
                                <div class="flex items-center text-xs text-gray-500 space-x-4">
                                    <span>Target: ${announcement.target_audience}</span>
                                    <span>Created: ${new Date(announcement.created_at).toLocaleDateString()}</span>
                                    <span>By: ${announcement.created_by}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <button onclick="editAnnouncement('${announcement.announcement_id}')" class="text-blue-600 hover:text-blue-800">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                                <button onclick="deleteAnnouncement('${announcement.announcement_id}')" class="text-red-600 hover:text-red-800">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            lucide.createIcons();
        }

        function openAnnouncementModal(isEdit = false) {
            document.getElementById('announcementModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = isEdit ? 'Edit Announcement' : 'Create New Announcement';
            document.getElementById('submitText').textContent = isEdit ? 'Update Announcement' : 'Create Announcement';
            
            if (!isEdit) {
                document.getElementById('announcementForm').reset();
                document.getElementById('imagePreview').classList.add('hidden');
            }
        }

        function closeAnnouncementModal() {
            document.getElementById('announcementModal').classList.add('hidden');
            document.getElementById('announcementForm').reset();
            document.getElementById('imagePreview').classList.add('hidden');
        }

        function editAnnouncement(announcementId) {
            fetch(`announcements_handler.php?action=get_announcement&id=${announcementId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const announcement = data.data;
                        document.getElementById('announcementId').value = announcement.announcement_id;
                        document.getElementById('announcementTitle').value = announcement.title;
                        document.getElementById('announcementContent').value = announcement.content;
                        document.getElementById('announcementPriority').value = announcement.priority;
                        document.getElementById('announcementAudience').value = announcement.target_audience;
                        document.getElementById('announcementStatus').value = announcement.status;
                        document.getElementById('existingImage').value = announcement.image_path || '';
                        
                        if (announcement.publish_date) {
                            document.getElementById('announcementPublishDate').value = new Date(announcement.publish_date).toISOString().slice(0, 16);
                        }
                        if (announcement.expiry_date) {
                            document.getElementById('announcementExpiryDate').value = new Date(announcement.expiry_date).toISOString().slice(0, 16);
                        }
                        
                        if (announcement.image_path) {
                            document.getElementById('imagePreview').classList.remove('hidden');
                            document.getElementById('previewImg').src = '../' + announcement.image_path;
                        }
                        
                        openAnnouncementModal(true);
                    }
                })
                .catch(error => console.error('Error fetching announcement:', error));
        }

        function deleteAnnouncement(announcementId) {
            if (confirm('Are you sure you want to delete this announcement?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('announcement_id', announcementId);
                
                fetch('announcements_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadAnnouncements();
                    } else {
                        alert('Error deleting announcement: ' + data.message);
                    }
                })
                .catch(error => console.error('Error deleting announcement:', error));
            }
        }

        // Image preview functionality
        document.getElementById('announcementImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').classList.remove('hidden');
                    document.getElementById('previewImg').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Form submission
        document.getElementById('announcementForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const isEdit = document.getElementById('announcementId').value !== '';
            formData.append('action', isEdit ? 'update' : 'create');
            
            fetch('announcements_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeAnnouncementModal();
                    loadAnnouncements();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error submitting form:', error);
                alert('An error occurred while saving the announcement.');
            });
        });

        // Initialize tooltips and other interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dark mode
            initializeDarkMode();
            
            // Load announcements
            loadAnnouncements();
            
            // Add any other initialization code here
            console.log('Transport & Mobility Management System - Administrator Dashboard Loaded');
        });
    </script>
</body>
</html>