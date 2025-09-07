<?php
require_once '../../../config/database.php';
require_once '../../../config/config.php';

$database = new Database();
$db = $database->getConnection();

// Get public terminal information
function getPublicTerminalInfo($db) {
    $query = "SELECT t.*, COUNT(ta.assignment_id) as active_operators,
                     ROUND((t.current_occupancy / t.capacity) * 100, 2) as utilization_rate
              FROM terminals t
              LEFT JOIN terminal_assignments ta ON t.terminal_id = ta.terminal_id AND ta.status = 'active'
              WHERE t.status = 'active'
              GROUP BY t.terminal_id
              ORDER BY t.terminal_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get TODA rosters for public display
function getTODARosters($db) {
    $query = "SELECT t.terminal_name, t.location, 
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     v.plate_number, v.vehicle_type, ta.assignment_type,
                     fr.route_assigned, rs.departure_time, rs.arrival_time
              FROM terminal_assignments ta
              JOIN terminals t ON ta.terminal_id = t.terminal_id
              JOIN operators o ON ta.operator_id = o.operator_id
              JOIN vehicles v ON ta.vehicle_id = v.vehicle_id
              LEFT JOIN franchise_records fr ON ta.franchise_id = fr.franchise_id
              LEFT JOIN route_schedules rs ON o.operator_id = rs.operator_id
              WHERE ta.status = 'active' AND ta.assignment_type = 'toda'
              ORDER BY t.terminal_name, o.last_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get public routes information
function getPublicRoutes($db) {
    $query = "SELECT r.route_name, r.route_code, r.origin, r.destination,
                     r.fare_amount, rs.departure_time, rs.arrival_time, rs.frequency_minutes,
                     COUNT(rs.schedule_id) as active_schedules
              FROM official_routes r
              LEFT JOIN route_schedules rs ON r.route_id = rs.route_id AND rs.status = 'active'
              WHERE r.status = 'active' AND rs.published_to_citizen = TRUE
              GROUP BY r.route_id
              ORDER BY r.route_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get repeat offender flags for public awareness
function getRepeatOffenders($db) {
    $query = "SELECT CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     v.plate_number, va.total_violations, va.risk_level,
                     vh.violation_type, vh.violation_date
              FROM violation_analytics va
              JOIN operators o ON va.operator_id = o.operator_id
              JOIN vehicles v ON va.vehicle_id = v.vehicle_id
              LEFT JOIN violation_history vh ON va.operator_id = vh.operator_id
              WHERE va.repeat_offender_flag = TRUE AND va.risk_level = 'high'
              ORDER BY va.total_violations DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$terminals = getPublicTerminalInfo($db);
$toda_rosters = getTODARosters($db);
$public_routes = getPublicRoutes($db);
$repeat_offenders = getRepeatOffenders($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Transparency - TPRS</title>
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
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                        <img src="../../../upload/Caloocan_City.png" alt="Caloocan City Logo" class="w-10 h-10 rounded-xl">
                </div>
                <div>
                    <h1 class="text-xl font-bold dark:text-white">TPRS</h1>
                    <p class="text-xs text-slate-500">TPRS Portal</p>
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
                    <button onclick="toggleDropdown('terminal-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-orange-600 bg-orange-50 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="map-pin" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Terminal Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="terminal-mgmt-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="terminal-mgmt-menu" class="ml-8 space-y-1">
                        <a href="../terminal_assignment_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Terminal Assignment</a>
                        <a href="../roster_and_delivery/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Roster & Directory</a>
                        <a href="../public_transparency/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Public Transparency</a>
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
                            <h1 class="text-md font-bold dark:text-white">PUBLIC TRANSPARENCY</h1>
                            <span class="text-xs text-slate-500 font-bold">Provide public terminal information and enable queries</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="globalSearch" placeholder="Search terminals..." class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <button onclick="performGlobalSearch()" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </button>
                        <button class="p-2 rounded-xl text-slate-600 hover:bg-slate-200">
                            <i data-lucide="bell" class="w-6 h-6"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 p-6 overflow-auto">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Public Terminals</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($terminals) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="map-pin" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">TODA Operators</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($toda_rosters) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Public Routes</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($public_routes) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="route" class="w-6 h-6 text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">High Risk Operators</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($repeat_offenders) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="border-b border-slate-200 dark:border-slate-700">
                        <nav class="-mb-px flex space-x-8 px-6">
                            <button onclick="switchTab('terminals')" id="terminals-tab" class="py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600">
                                Terminal Information
                            </button>
                            <button onclick="switchTab('toda')" id="toda-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300">
                                TODA Rosters
                            </button>
                            <button onclick="switchTab('routes')" id="routes-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300">
                                Public Routes
                            </button>
                            <button onclick="switchTab('alerts')" id="alerts-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300">
                                Public Alerts
                            </button>
                        </nav>
                    </div>

                    <!-- Terminal Information Tab -->
                    <div id="terminals-content" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Public Terminal Information</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($terminals as $terminal): ?>
                            <div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-lg font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($terminal['terminal_name']) ?></h4>
                                    <span class="px-2 py-1 text-xs font-medium <?= getStatusBadge($terminal['status']) ?> rounded-full">
                                        <?= ucfirst($terminal['status']) ?>
                                    </span>
                                </div>
                                <div class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                    <div class="flex items-center">
                                        <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                                        <?= htmlspecialchars($terminal['location']) ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="clock" class="w-4 h-4 mr-2"></i>
                                        <?= htmlspecialchars($terminal['operating_hours']) ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="users" class="w-4 h-4 mr-2"></i>
                                        <?= $terminal['active_operators'] ?> operators
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="activity" class="w-4 h-4 mr-2"></i>
                                        <?= $terminal['utilization_rate'] ?>% utilization
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="phone" class="w-4 h-4 mr-2"></i>
                                        <?= htmlspecialchars($terminal['contact_number']) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TODA Rosters Tab -->
                    <div id="toda-content" class="p-6 hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-slate-900 dark:text-white">TODA Rosters</h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Terminal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Route</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Schedule</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                    <?php foreach ($toda_rosters as $roster): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($roster['terminal_name']) ?></div>
                                            <div class="text-sm text-slate-500"><?= htmlspecialchars($roster['location']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($roster['operator_name']) ?></td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($roster['plate_number']) ?></div>
                                            <div class="text-sm text-slate-500"><?= htmlspecialchars($roster['vehicle_type']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($roster['route_assigned'] ?? 'N/A') ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($roster['departure_time'] && $roster['arrival_time']): ?>
                                            <div class="text-sm text-slate-900 dark:text-white"><?= date('g:i A', strtotime($roster['departure_time'])) ?> - <?= date('g:i A', strtotime($roster['arrival_time'])) ?></div>
                                            <?php else: ?>
                                            <span class="text-sm text-slate-500">No schedule</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Public Routes Tab -->
                    <div id="routes-content" class="p-6 hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Public Routes</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($public_routes as $route): ?>
                            <div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-lg font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($route['route_name']) ?></h4>
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                        <?= htmlspecialchars($route['route_code']) ?>
                                    </span>
                                </div>
                                <div class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                    <div class="flex items-center">
                                        <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                                        <?= htmlspecialchars($route['origin']) ?> → <?= htmlspecialchars($route['destination']) ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="clock" class="w-4 h-4 mr-2"></i>
                                        <?= date('g:i A', strtotime($route['departure_time'])) ?> - <?= date('g:i A', strtotime($route['arrival_time'])) ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="repeat" class="w-4 h-4 mr-2"></i>
                                        Every <?= $route['frequency_minutes'] ?> minutes
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="dollar-sign" class="w-4 h-4 mr-2"></i>
                                        ₱<?= number_format($route['fare_amount'], 2) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Public Alerts Tab -->
                    <div id="alerts-content" class="p-6 hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Public Safety Alerts</h3>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <h4 class="text-lg font-semibold text-red-800 mb-2">High Risk Operators</h4>
                                <p class="text-sm text-red-600 mb-4">The following operators have been flagged for multiple violations. Exercise caution when using their services.</p>
                                
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-red-100">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-red-800 uppercase">Operator</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-red-800 uppercase">Plate Number</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-red-800 uppercase">Violations</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-red-800 uppercase">Risk Level</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-red-800 uppercase">Last Violation</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-red-200">
                                            <?php foreach ($repeat_offenders as $offender): ?>
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-red-900"><?= htmlspecialchars($offender['operator_name']) ?></td>
                                                <td class="px-4 py-2 text-sm text-red-900"><?= htmlspecialchars($offender['plate_number']) ?></td>
                                                <td class="px-4 py-2 text-sm text-red-900"><?= $offender['total_violations'] ?></td>
                                                <td class="px-4 py-2">
                                                    <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                                        <?= ucfirst($offender['risk_level']) ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 text-sm text-red-900"><?= date('M j, Y', strtotime($offender['violation_date'])) ?></td>
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

        function switchTab(tab) {
            // Hide all content
            document.getElementById('terminals-content').classList.add('hidden');
            document.getElementById('toda-content').classList.add('hidden');
            document.getElementById('routes-content').classList.add('hidden');
            document.getElementById('alerts-content').classList.add('hidden');
            
            // Reset all tabs
            document.getElementById('terminals-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300';
            document.getElementById('toda-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300';
            document.getElementById('routes-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300';
            document.getElementById('alerts-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300';
            
            // Show selected content and activate tab
            document.getElementById(tab + '-content').classList.remove('hidden');
            document.getElementById(tab + '-tab').className = 'py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600';
        }

        function performGlobalSearch() {
            const searchTerm = document.getElementById('globalSearch').value.toLowerCase();
            const cards = document.querySelectorAll('.bg-slate-50, .hover\\:bg-slate-50');
            const tableRows = document.querySelectorAll('tbody tr');
            
            // Search in terminal cards
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm) || searchTerm === '') {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Search in table rows
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm) || searchTerm === '') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Add enter key support for search
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('globalSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function(event) {
                    if (event.key === 'Enter') {
                        performGlobalSearch();
                    } else {
                        // Real-time search as user types
                        performGlobalSearch();
                    }
                });
            }
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