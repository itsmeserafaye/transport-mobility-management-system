<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$stats = getStatistics($conn);
$routes = getRoutes($conn);
$schedules = getRouteSchedules($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route & Schedule Publication - Transport Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../../administrator/assets/css/lucide.min.css" rel="stylesheet">
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
                        <a href="../franchise_application_workflow/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Application & Workflow</a>
                        <a href="../document_repository/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Document Repository</a>
                        <a href="../franchise_lifecycle_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Franchise Lifecycle Management</a>
                        <a href="../route_and_schedule_publication/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Route & Schedule Publication</a>
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
                            <span class="text-xs text-slate-500 font-bold">Franchise Management > Route & Schedule Publication</span>
                        </div>
                    </div>
                    <div class="flex-1 max-w-md mx-8">
                        <div class="relative">
                            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                            <input type="text" id="searchInput" placeholder="Search routes..." 
                                   class="w-full pl-10 pr-4 py-2 bg-slate-100 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-300"
                                   onkeyup="searchData()">
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
                                <p class="text-slate-500 text-sm">Official Routes</p>
                                <p class="text-2xl font-bold text-blue-600">5</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="map" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Active Schedules</p>
                                <p class="text-2xl font-bold text-green-600">5</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="clock" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Published to Citizen</p>
                                <p class="text-2xl font-bold text-orange-600">3</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="globe" class="w-6 h-6 text-orange-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Avg Travel Time</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white">31 min</p>
                            </div>
                            <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-6 h-6 text-slate-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mb-6">
                    <div class="border-b border-slate-200">
                        <nav class="-mb-px flex space-x-8">
                            <button onclick="switchTab('routes')" id="routes-tab" class="py-2 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600">Official Routes</button>
                            <button onclick="switchTab('schedules')" id="schedules-tab" class="py-2 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300">Route Schedules</button>
                        </nav>
                    </div>
                </div>

                <!-- Routes Tab -->
                <div id="routes-content" class="tab-content">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Official Routes</h2>
                        <div class="flex space-x-3">

                            <div class="relative">
                                <button onclick="toggleExportDropdown('routes')" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 flex items-center space-x-2">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                    <span>Export</span>
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </button>
                                <div id="routesExportDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-slate-200">
                                    <div class="py-1">
                                        <a href="#" onclick="exportData('routes', 'csv')" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">Export as CSV</a>
                                        <a href="#" onclick="exportData('routes', 'json')" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">Export as JSON</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Route</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Origin - Destination</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Distance/Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Fare</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Schedules</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                    <?php foreach ($routes as $route): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $route['route_name']; ?></div>
                                                <div class="text-sm text-slate-500"><?php echo $route['route_code']; ?> | <?php echo $route['route_id']; ?></div>
                                                <div class="text-xs text-slate-400"><?php echo ucfirst($route['status']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900 dark:text-white"><?php echo $route['origin']; ?></div>
                                            <div class="text-sm text-slate-500">to <?php echo $route['destination']; ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900 dark:text-white"><?php echo $route['distance_km']; ?> km</div>
                                            <div class="text-sm text-slate-500"><?php echo $route['estimated_travel_time']; ?> minutes</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">₱<?php echo number_format($route['fare_amount'], 2); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900 dark:text-white"><?php echo $route['schedule_count']; ?> total</div>
                                            <div class="text-sm text-green-600"><?php echo $route['published_schedules']; ?> published</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <button onclick="viewRoute('<?php echo $route['route_id']; ?>')" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="View Route">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
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

                <!-- Schedules Tab -->
                <div id="schedules-content" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Route Schedules</h2>
                        <div class="flex space-x-3">
                            <div class="relative">
                                <button onclick="toggleExportDropdown('schedules')" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 flex items-center space-x-2">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                    <span>Export</span>
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </button>
                                <div id="schedulesExportDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-slate-200">
                                    <div class="py-1">
                                        <a href="#" onclick="exportData('schedules', 'csv')" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">Export as CSV</a>
                                        <a href="#" onclick="exportData('schedules', 'json')" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">Export as JSON</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Schedule</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Route</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Operator/Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                    <?php foreach ($schedules as $schedule): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $schedule['schedule_id']; ?></div>
                                                <div class="text-sm text-slate-500">Every <?php echo $schedule['frequency_minutes']; ?> mins</div>
                                                <div class="text-xs text-slate-400"><?php echo ucfirst($schedule['operating_days']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $schedule['route_name']; ?></div>
                                            <div class="text-sm text-slate-500"><?php echo $schedule['route_code']; ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $schedule['first_name'] . ' ' . $schedule['last_name']; ?></div>
                                            <div class="text-sm text-slate-500"><?php echo $schedule['plate_number']; ?> - <?php echo ucfirst($schedule['vehicle_type']); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900 dark:text-white"><?php echo date('g:i A', strtotime($schedule['departure_time'])); ?></div>
                                            <div class="text-sm text-slate-500">to <?php echo date('g:i A', strtotime($schedule['arrival_time'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col space-y-1">
                                                <?php 
                                                $status_class = $schedule['status'] == 'active' ? 'bg-green-100 text-green-800' : 
                                                               ($schedule['status'] == 'suspended' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800');
                                                ?>
                                                <span class="px-2 py-1 text-xs font-medium <?php echo $status_class; ?> rounded-full"><?php echo ucfirst($schedule['status']); ?></span>
                                                <?php if ($schedule['published_to_citizen']): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Published</span>
                                                <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Draft</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <button onclick="viewSchedule('<?php echo $schedule['schedule_id']; ?>')" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="View Schedule">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>

                                                <?php if (!$schedule['published_to_citizen']): ?>
                                                <button onclick="publishSchedule('<?php echo $schedule['schedule_id']; ?>')" class="p-1 text-green-600 hover:bg-green-100 rounded" title="Publish">
                                                    <i data-lucide="globe" class="w-4 h-4"></i>
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
        
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('[id$="-tab"]').forEach(tab => {
                tab.classList.remove('border-orange-500', 'text-orange-600');
                tab.classList.add('border-transparent', 'text-slate-500');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-content').classList.remove('hidden');
            
            // Add active class to selected tab
            const activeTab = document.getElementById(tabName + '-tab');
            activeTab.classList.remove('border-transparent', 'text-slate-500');
            activeTab.classList.add('border-orange-500', 'text-orange-600');
        }
        
        function viewRoute(routeId) {
            alert('Route viewing functionality has been disabled.');
        }
        

        
        function viewSchedule(scheduleId) {
            alert('Schedule viewing functionality has been disabled.');
        }
        

        
        function publishSchedule(scheduleId) {
            if (confirm('Publish this schedule to citizen portal?')) {
                fetch('handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'publish_schedule',
                        schedule_id: scheduleId,
                        published_by: 'Admin'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Schedule published successfully!');
                        location.reload();
                    } else {
                        alert('Failed to publish schedule');
                    }
                })
                .catch(error => {
                    alert('Error publishing schedule');
                });
            }
        }
        

        

        

        

        
        function toggleExportDropdown(type) {
            const dropdown = document.getElementById(type + 'ExportDropdown');
            dropdown.classList.toggle('hidden');
        }
        
        function exportData(type, format) {
            window.open(`handler.php?action=export_${type}&format=${format}`, '_blank');
            document.getElementById(type + 'ExportDropdown').classList.add('hidden');
        }
        
        function searchData() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const activeTab = document.querySelector('[id$="-tab"].border-orange-500').id.replace('-tab', '');
            const rows = document.querySelectorAll(`#${activeTab}-content tbody tr`);
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const dropdowns = ['routesExportDropdown', 'schedulesExportDropdown'];
            dropdowns.forEach(dropdownId => {
                const dropdown = document.getElementById(dropdownId);
                const button = event.target.closest('button');
                if (!button || !button.onclick || button.onclick.toString().indexOf('toggleExportDropdown') === -1) {
                    dropdown.classList.add('hidden');
                }
            });
        });
        
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