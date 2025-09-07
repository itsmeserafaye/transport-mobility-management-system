<?php
require_once '../../../config/database.php';
require_once '../../../config/config.php';

$database = new Database();
$db = $database->getConnection();

// Get operator directory with terminal assignments
function getOperatorDirectory($db) {
    $query = "SELECT o.*, v.plate_number, v.vehicle_type, v.make, v.model,
                     t.terminal_name, t.location, ta.assignment_type, ta.status as assignment_status,
                     fr.franchise_number, fr.route_assigned, fr.status as franchise_status,
                     rs.departure_time, rs.arrival_time, rs.operating_days
              FROM operators o
              LEFT JOIN vehicles v ON o.operator_id = v.operator_id
              LEFT JOIN terminal_assignments ta ON o.operator_id = ta.operator_id AND ta.status = 'active'
              LEFT JOIN terminals t ON ta.terminal_id = t.terminal_id
              LEFT JOIN franchise_records fr ON o.operator_id = fr.operator_id
              LEFT JOIN route_schedules rs ON o.operator_id = rs.operator_id
              WHERE o.status = 'active'
              ORDER BY o.last_name, o.first_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get terminal locations for directory
function getTerminalLocations($db) {
    $query = "SELECT t.*, COUNT(ta.assignment_id) as assigned_operators
              FROM terminals t
              LEFT JOIN terminal_assignments ta ON t.terminal_id = ta.terminal_id AND ta.status = 'active'
              WHERE t.status = 'active'
              GROUP BY t.terminal_id
              ORDER BY t.terminal_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get public directory data
function getPublicDirectory($db) {
    $query = "SELECT o.operator_id, CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     v.plate_number, v.vehicle_type, t.terminal_name, t.location,
                     fr.route_assigned, rs.departure_time, rs.arrival_time, rs.operating_days
              FROM operators o
              JOIN vehicles v ON o.operator_id = v.operator_id
              JOIN terminal_assignments ta ON o.operator_id = ta.operator_id
              JOIN terminals t ON ta.terminal_id = t.terminal_id
              LEFT JOIN franchise_records fr ON o.operator_id = fr.operator_id
              LEFT JOIN route_schedules rs ON o.operator_id = rs.operator_id
              WHERE o.status = 'active' AND ta.status = 'active' AND fr.status = 'valid'
              ORDER BY t.terminal_name, o.last_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$operators = getOperatorDirectory($db);
$terminals = getTerminalLocations($db);
$public_directory = getPublicDirectory($db);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roster & Directory - PTSMD</title>
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
                        <a href="../roster_and_delivery/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Roster & Directory</a>
                        <a href="../public_transparency/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Public Transparency</a>
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
                            <h1 class="text-md font-bold dark:text-white">ROSTER & DIRECTORY</h1>
                            <span class="text-xs text-slate-500 font-bold">Maintain operator directories and track locations</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="globalSearch" placeholder="Search operators..." class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
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
                                <p class="text-slate-500 text-sm">Total Operators</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($operators) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Active Terminals</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($terminals) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="map-pin" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Public Directory</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($public_directory) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="book-open" class="w-6 h-6 text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Terminal Locations</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count(array_unique(array_column($terminals, 'location'))) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="navigation" class="w-6 h-6 text-orange-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="border-b border-slate-200 dark:border-slate-700">
                        <nav class="-mb-px flex space-x-8 px-6">
                            <button onclick="switchTab('directory')" id="directory-tab" class="py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600">
                                Operator Directory
                            </button>
                            <button onclick="switchTab('terminals')" id="terminals-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300">
                                Terminal Locations
                            </button>
                            <button onclick="switchTab('public')" id="public-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300">
                                Public Directory
                            </button>
                        </nav>
                    </div>

                    <!-- Operator Directory Tab -->
                    <div id="directory-content" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Operator Directory</h3>
                            <button onclick="exportDirectory()" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                                <i data-lucide="download" class="w-4 h-4 inline mr-2"></i>
                                Export Directory
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Terminal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Route</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Contact</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                    <?php foreach ($operators as $operator): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($operator['first_name'] . ' ' . $operator['last_name']) ?></div>
                                            <div class="text-sm text-slate-500"><?= htmlspecialchars($operator['operator_id']) ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($operator['plate_number'] ?? 'N/A') ?></div>
                                            <div class="text-sm text-slate-500"><?= htmlspecialchars(($operator['make'] ?? '') . ' ' . ($operator['model'] ?? '')) ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($operator['terminal_name'] ?? 'Not Assigned') ?></div>
                                            <div class="text-sm text-slate-500"><?= htmlspecialchars($operator['location'] ?? '') ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($operator['route_assigned'] ?? 'N/A') ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?= getStatusBadge($operator['franchise_status'] ?? 'pending') ?>">
                                                <?= ucfirst($operator['franchise_status'] ?? 'pending') ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($operator['contact_number']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Terminal Locations Tab -->
                    <div id="terminals-content" class="p-6 hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Terminal Locations</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($terminals as $terminal): ?>
                            <div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-lg font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($terminal['terminal_name']) ?></h4>
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                        <?= ucfirst($terminal['terminal_type']) ?>
                                    </span>
                                </div>
                                <div class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                    <div class="flex items-center">
                                        <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                                        <?= htmlspecialchars($terminal['location']) ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="users" class="w-4 h-4 mr-2"></i>
                                        <?= $terminal['assigned_operators'] ?> / <?= $terminal['capacity'] ?> operators
                                    </div>
                                    <div class="flex items-center">
                                        <i data-lucide="clock" class="w-4 h-4 mr-2"></i>
                                        <?= htmlspecialchars($terminal['operating_hours']) ?>
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

                    <!-- Public Directory Tab -->
                    <div id="public-content" class="p-6 hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Public Directory</h3>

                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Terminal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Route</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Schedule</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                    <?php foreach ($public_directory as $entry): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($entry['operator_name']) ?></td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($entry['plate_number']) ?></div>
                                            <div class="text-sm text-slate-500"><?= htmlspecialchars($entry['vehicle_type']) ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($entry['terminal_name']) ?></div>
                                            <div class="text-sm text-slate-500"><?= htmlspecialchars($entry['location']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($entry['route_assigned'] ?? 'N/A') ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($entry['departure_time'] && $entry['arrival_time']): ?>
                                            <div class="text-sm text-slate-900 dark:text-white"><?= date('g:i A', strtotime($entry['departure_time'])) ?> - <?= date('g:i A', strtotime($entry['arrival_time'])) ?></div>
                                            <div class="text-sm text-slate-500"><?= ucfirst($entry['operating_days']) ?></div>
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
            document.getElementById('directory-content').classList.add('hidden');
            document.getElementById('terminals-content').classList.add('hidden');
            document.getElementById('public-content').classList.add('hidden');
            
            // Reset all tabs
            document.getElementById('directory-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300';
            document.getElementById('terminals-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300';
            document.getElementById('public-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300';
            
            // Show selected content and activate tab
            document.getElementById(tab + '-content').classList.remove('hidden');
            document.getElementById(tab + '-tab').className = 'py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600';
        }

        function performGlobalSearch() {
            const searchTerm = document.getElementById('globalSearch').value.toLowerCase();
            const tables = document.querySelectorAll('tbody tr');
            
            tables.forEach(row => {
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

        function exportDirectory() {
            // Create CSV content
            let csvContent = "Operator Name,Vehicle,Plate Number,Terminal,Location,Route,Status,Contact\n";
            
            const operators = <?= json_encode($operators) ?>;
            operators.forEach(operator => {
                const row = [
                    `"${operator.first_name} ${operator.last_name}"`,
                    `"${operator.vehicle_type || 'N/A'}"`,
                    `"${operator.plate_number || 'N/A'}"`,
                    `"${operator.terminal_name || 'Not Assigned'}"`,
                    `"${operator.location || ''}"`,
                    `"${operator.route_assigned || 'N/A'}"`,
                    `"${operator.franchise_status || 'pending'}"`,
                    `"${operator.contact_number}"`
                ].join(',');
                csvContent += row + "\n";
            });
            
            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `operator_directory_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }



        // Enhanced sidebar toggle function with null checks and smooth animations
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.flex-1.flex.flex-col');
            
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