<?php
require_once '../../../config/database.php';
require_once '../../../config/config.php';

$database = new Database();
$db = $database->getConnection();

// Get terminal assignments with operator and vehicle data
function getTerminalAssignments($db) {
    $query = "SELECT ta.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, 
                     t.terminal_name, fr.franchise_number, fr.status as franchise_status
              FROM terminal_assignments ta 
              LEFT JOIN operators o ON ta.operator_id = o.operator_id 
              LEFT JOIN vehicles v ON ta.vehicle_id = v.vehicle_id
              LEFT JOIN terminals t ON ta.terminal_id = t.terminal_id
              LEFT JOIN franchise_records fr ON v.vehicle_id = fr.vehicle_id
              ORDER BY ta.assignment_date DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get available terminals
function getAvailableTerminals($db) {
    $query = "SELECT * FROM terminals WHERE status = 'active' ORDER BY terminal_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get operators with valid franchises
function getOperatorsWithFranchises($db) {
    $query = "SELECT DISTINCT o.operator_id, o.first_name, o.last_name, v.vehicle_id, v.plate_number,
                     fr.franchise_id, fr.franchise_number, fr.route_assigned
              FROM operators o 
              LEFT JOIN vehicles v ON o.operator_id = v.operator_id 
              LEFT JOIN franchise_records fr ON v.vehicle_id = fr.vehicle_id 
              WHERE o.status = 'active'
              ORDER BY o.last_name, o.first_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {

        }
    }
}

$assignments = getTerminalAssignments($db);
$terminals = getAvailableTerminals($db);
$operators = getOperatorsWithFranchises($db);

// Helper function for status badges
function getStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'inactive': return 'bg-gray-100 text-gray-800';
        case 'suspended': return 'bg-red-100 text-red-800';
        case 'expired': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-blue-100 text-blue-800';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Assignment Management - PTSMD</title>
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
                        <a href="../terminal_assignment_management/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Terminal Assignment</a>
                        <a href="../roster_and_delivery/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Roster & Directory</a>
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
                            <h1 class="text-md font-bold dark:text-white">TERMINAL ASSIGNMENT MANAGEMENT</h1>
                            <span class="text-xs text-slate-500 font-bold">Assign vehicles to terminals and manage TODA assignments</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
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
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Total Assignments</p>
                                <p class="text-2xl font-bold text-slate-900"><?= count($assignments) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="map-pin" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Active Terminals</p>
                                <p class="text-2xl font-bold text-slate-900"><?= count($terminals) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="building" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Available Operators</p>
                                <p class="text-2xl font-bold text-slate-900"><?= count($operators) ?></p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6 text-purple-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Capacity Utilization</p>
                                <p class="text-2xl font-bold text-slate-900">78%</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="bar-chart" class="w-6 h-6 text-orange-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terminal Assignments Table -->
                <div class="bg-white rounded-xl border border-slate-200">
                    <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-slate-900">Terminal Assignments</h2>

                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Assignment ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Operator</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Terminal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Route</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php foreach ($assignments as $assignment): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 text-sm font-medium text-slate-900"><?= htmlspecialchars($assignment['assignment_id'] ?? 'N/A') ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars(($assignment['first_name'] ?? '') . ' ' . ($assignment['last_name'] ?? '')) ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($assignment['plate_number'] ?? 'N/A') ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($assignment['terminal_name'] ?? 'N/A') ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($assignment['route_assigned'] ?? 'N/A') ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= getStatusBadgeClass($assignment['status'] ?? 'active') ?>">
                                            <?= ucfirst($assignment['status'] ?? 'active') ?>
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



    <!-- View Assignment Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-lg font-semibold">Assignment Details</h3>
                </div>
                <div id="viewContent" class="p-6">
                    <!-- Content will be loaded here -->
                </div>
                <div class="p-6 border-t border-slate-200">
                    <button onclick="closeModal('viewModal')" class="px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50">Close</button>
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



        function viewAssignment(id) {
            // Simulate fetching assignment data
            const assignmentData = {
                assignment_id: id,
                operator_name: 'Juan Dela Cruz',
                vehicle: 'ABC-1234 (Jeepney)',
                terminal: 'Terminal A',
                route: 'Route 1 - Cubao to Fairview',
                assignment_type: 'Permanent',
                status: 'Active',
                start_date: '2024-01-15',
                end_date: 'N/A',
                remarks: 'Regular assignment for Route 1'
            };
            
            document.getElementById('viewContent').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Assignment ID</label>
                            <p class="text-sm text-slate-900">${assignmentData.assignment_id}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Status</label>
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">${assignmentData.status}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Operator</label>
                            <p class="text-sm text-slate-900">${assignmentData.operator_name}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Vehicle</label>
                            <p class="text-sm text-slate-900">${assignmentData.vehicle}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Terminal</label>
                            <p class="text-sm text-slate-900">${assignmentData.terminal}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Assignment Type</label>
                            <p class="text-sm text-slate-900">${assignmentData.assignment_type}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Route</label>
                        <p class="text-sm text-slate-900">${assignmentData.route}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Start Date</label>
                            <p class="text-sm text-slate-900">${assignmentData.start_date}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">End Date</label>
                            <p class="text-sm text-slate-900">${assignmentData.end_date}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Remarks</label>
                        <p class="text-sm text-slate-900">${assignmentData.remarks}</p>
                    </div>
                </div>
            `;
            document.getElementById('viewModal').classList.remove('hidden');
        }



        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }



        // Enhanced sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.flex-1.flex.flex-col');
            
            if (!sidebar || !mainContent) {
                console.error('Sidebar or main content element not found');
                return;
            }
            
            const isHidden = sidebar.classList.contains('-translate-x-full');
            
            if (isHidden) {
                // Show sidebar
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                mainContent.style.marginLeft = '0';
                mainContent.style.width = 'calc(100% - 16rem)';
            } else {
                // Hide sidebar
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                mainContent.style.marginLeft = '-16rem';
                mainContent.style.width = '100%';
            }
        }
    </script>
</body>
</html>