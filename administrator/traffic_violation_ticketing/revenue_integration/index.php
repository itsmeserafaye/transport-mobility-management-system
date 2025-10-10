<?php
require_once __DIR__ . '/../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_report') {
        $period_start = $_POST['period_start'] ?? date('Y-m-01');
        $period_end = $_POST['period_end'] ?? date('Y-m-t');
        
        $report_data = generateRevenueReport($conn, $period_start, $period_end);
        echo json_encode(['success' => true, 'data' => $report_data]);
        exit;
    }
    
    if ($action === 'get_chart_data') {
        $api_url = 'http://localhost:3001/api/traffic-violation/revenue?period=month';
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json\r\n'
            ]
        ]);
        
        $response = file_get_contents($api_url, false, $context);
        if ($response !== false) {
            echo $response;
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to fetch data from API']);
        }
        exit;
    }
}

// Get data for display
$collections = getRevenueCollections($conn);
$stats = getRevenueStatistics($conn);

// Monthly data will be loaded via API
$monthly_data = [];

// Function to get revenue collections
function getRevenueCollections($conn) {
    $query = "SELECT rc.*, tv.ticket_number, tv.violation_type, tv.fine_amount,
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     v.plate_number
              FROM revenue_collections rc
              LEFT JOIN violation_history tv ON rc.violation_id = tv.violation_id
              LEFT JOIN operators o ON tv.operator_id = o.operator_id
              LEFT JOIN vehicles v ON tv.vehicle_id = v.vehicle_id
              ORDER BY rc.collection_date DESC
              LIMIT 50";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get revenue statistics
function getRevenueStatistics($conn) {
    $stats = [];
    
    // Total revenue collected
    $query = "SELECT SUM(amount_collected) as total FROM revenue_collections";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
    
    // Monthly revenue
    $query = "SELECT SUM(amount_collected) as monthly FROM revenue_collections 
              WHERE MONTH(collection_date) = MONTH(CURDATE()) 
              AND YEAR(collection_date) = YEAR(CURDATE())";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['monthly_revenue'] = $stmt->fetchColumn() ?: 0;
    
    // Outstanding fines
    $query = "SELECT SUM(fine_amount) as outstanding FROM violation_history 
              WHERE settlement_status = 'unpaid'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['outstanding_fines'] = $stmt->fetchColumn() ?: 0;
    
    // Collection rate
    $query = "SELECT 
                (SUM(CASE WHEN settlement_status = 'paid' THEN fine_amount ELSE 0 END) / 
                 SUM(fine_amount)) * 100 as rate
              FROM violation_history";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['collection_rate'] = round($stmt->fetchColumn() ?: 0, 1);
    
    // Pending revenue (collections with pending status)
    $query = "SELECT SUM(amount_collected) as pending FROM revenue_collections 
              WHERE status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['pending_revenue'] = $stmt->fetchColumn() ?: 0;
    
    return $stats;
}

// Function to generate revenue report
function generateRevenueReport($conn, $period_start, $period_end) {
    $report = [];
    
    // Report header
    $report['report_type'] = 'Revenue Report';
    $report['period_start'] = $period_start;
    $report['period_end'] = $period_end;
    $report['generated_at'] = date('Y-m-d H:i:s');
    
    // Revenue summary for the period
    $query = "SELECT 
                COUNT(*) as total_collections,
                SUM(amount_collected) as total_amount,
                AVG(amount_collected) as average_amount,
                MIN(amount_collected) as min_amount,
                MAX(amount_collected) as max_amount
              FROM revenue_collections 
              WHERE collection_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$period_start, $period_end]);
    $report['summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Daily collections
    $query = "SELECT 
                DATE(collection_date) as collection_day,
                COUNT(*) as daily_count,
                SUM(amount_collected) as daily_amount
              FROM revenue_collections 
              WHERE collection_date BETWEEN ? AND ?
              GROUP BY DATE(collection_date)
              ORDER BY collection_day";
    $stmt = $conn->prepare($query);
    $stmt->execute([$period_start, $period_end]);
    $report['daily_collections'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payment method breakdown
    $query = "SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(amount_collected) as amount
              FROM revenue_collections 
              WHERE collection_date BETWEEN ? AND ?
              GROUP BY payment_method";
    $stmt = $conn->prepare($query);
    $stmt->execute([$period_start, $period_end]);
    $report['payment_methods'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top violation types by revenue
    $query = "SELECT 
                tv.violation_type,
                COUNT(*) as count,
                SUM(rc.amount_collected) as total_revenue
              FROM revenue_collections rc
              JOIN violation_history tv ON rc.violation_id = tv.violation_id
              WHERE rc.collection_date BETWEEN ? AND ?
              GROUP BY tv.violation_type
              ORDER BY total_revenue DESC
              LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->execute([$period_start, $period_end]);
    $report['top_violations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $report;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Integration - Transport Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body style="background-color: #FBFBFB;" class="dark:bg-slate-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-white border-r border-gray-200 dark:bg-slate-900 dark:border-slate-700 transform transition-transform duration-300 ease-in-out translate-x-0">
            <div class="p-6">
                <div class="flex items-center space-x-3">
                    <img src="../../../upload/Caloocan_City.png" alt="Caloocan City Logo" class="w-10 h-10 rounded-xl">
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
                    <button onclick="toggleDropdown('violation-ticketing')" class="w-full flex items-center justify-between p-2 rounded-xl transition-all" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.1);">
                        <div class="flex items-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Traffic Violation Ticketing</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="violation-ticketing-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="violation-ticketing-menu" class="ml-8 space-y-1">
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

                <div class="space-y-1">
                    <button onclick="toggleDropdown('user-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="users" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">User Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="user-mgmt-icon"></i>
                    </button>
                    <div id="user-mgmt-menu" class="hidden ml-8 space-y-1">
                        <a href="../../user_management/account_registry/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Account Registry</a>
                        <a href="../../user_management/verification_queue/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Verification Queue</a>
                        <a href="../../user_management/account_maintenance/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Account Maintenance</a>
                        <a href="../../user_management/roles_and_permissions/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Roles & Permissions</a>
                        <a href="../../user_management/audit_logs/" class="block p-2 text-sm text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">Audit Logs</a>
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
                            <h1 class="text-md font-bold dark:text-white">REVENUE INTEGRATION</h1>
                            <span class="text-xs text-slate-500 font-bold">Traffic Violation Ticketing Module</span>
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
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Total Revenue</p>
                                <p class="text-2xl font-bold text-green-600">₱<?php echo number_format($stats['total_revenue'], 2); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="dollar-sign" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Monthly Revenue</p>
                                <p class="text-2xl font-bold text-blue-600">₱<?php echo number_format($stats['monthly_revenue'], 2); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="calendar" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Pending Collections</p>
                                <p class="text-2xl font-bold text-orange-600">₱<?php echo number_format($stats['pending_revenue'], 2); ?></p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="clock" class="w-6 h-6 text-orange-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Collection Rate</p>
                                <p class="text-2xl font-bold text-purple-600"><?php echo $stats['collection_rate']; ?>%</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-6 h-6 text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Monthly Collections Chart -->
                <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Monthly Collections</h3>
                    <div id="monthlyChart" class="h-64 flex items-center justify-center">
                        <div class="text-center text-slate-500">
                            <i data-lucide="bar-chart" class="w-12 h-12 mx-auto mb-2"></i>
                            <p>Chart data will be available when API is configured</p>
                        </div>
                    </div>
                </div>

                <!-- Collections Table -->
                <div class="bg-white rounded-xl border border-slate-200">
                    <div class="p-6 border-b border-slate-200">
                        <h3 class="text-lg font-semibold">Recent Collections</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Collection ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Operator</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Violation</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php foreach ($collections as $collection): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 text-sm font-medium text-slate-900"><?php echo $collection['collection_id']; ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-900">
                                        <?php echo $collection['operator_name'] ?? 'N/A'; ?>
                                        <div class="text-xs text-slate-500"><?php echo $collection['plate_number']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-900">
                                        <?php echo $collection['violation_type']; ?>
                                        <div class="text-xs text-slate-500">Fine: ₱<?php echo number_format($collection['fine_amount'], 2); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-green-600">₱<?php echo number_format($collection['amount_collected'], 2); ?></td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $statusClass = $collection['status'] == 'deposited' ? 'bg-green-100 text-green-800' : 
                                                      ($collection['status'] == 'verified' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800');
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo ucfirst($collection['status']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-900"><?php echo date('M d, Y', strtotime($collection['collection_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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



        // Show Traffic Violation menu and highlight current page
        document.addEventListener('DOMContentLoaded', function() {
            const violationMenu = document.getElementById('violation-ticketing-menu');
            const violationIcon = document.getElementById('violation-ticketing-icon');
            if (violationMenu && violationIcon) {
                violationMenu.classList.remove('hidden');
                violationIcon.style.transform = 'rotate(180deg)';
                
                // Highlight current page
                const currentLink = violationMenu.querySelector('a[href*="revenue_integration"]');
                if (currentLink) {
                    currentLink.style.color = '#4CAF50';
                    currentLink.style.backgroundColor = 'rgba(76, 175, 80, 0.2)';
                    currentLink.classList.add('font-medium');
                }
            }
        });

    </script>
</body>
</html>