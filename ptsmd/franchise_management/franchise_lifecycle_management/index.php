<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$stats = getStatistics($conn);
$lifecycle_stats = getLifecycleStatistics($conn);
$lifecycle = getFranchiseLifecycle($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Franchise Lifecycle Management - Transport Management</title>
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
                        <a href="../franchise_lifecycle_management/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Franchise Lifecycle Management</a>
                        <a href="../route_and_schedule_publication/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Route & Schedule Publication</a>
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
                            <span class="text-xs text-slate-500 font-bold">Franchise Management > Franchise Lifecycle</span>
                        </div>
                    </div>
                    <div class="flex-1 max-w-md mx-8">
                        <div class="relative">
                            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                            <input type="text" id="searchInput" placeholder="Search franchises..." 
                                   class="w-full pl-10 pr-4 py-2 bg-slate-100 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-300"
                                   onkeyup="searchLifecycle()">
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
                                <p class="text-slate-500 text-sm">Active Franchises</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo $lifecycle_stats['active_franchises']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Due for Renewal</p>
                                <p class="text-2xl font-bold text-yellow-600"><?php echo $lifecycle_stats['due_for_renewal']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="clock" class="w-6 h-6 text-yellow-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Expired</p>
                                <p class="text-2xl font-bold text-red-600"><?php echo $lifecycle_stats['expired']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="x-circle" class="w-6 h-6 text-red-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Revoked</p>
                                <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $lifecycle_stats['revoked']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="ban" class="w-6 h-6 text-slate-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Franchise Lifecycle Management</h2>
                    <div class="flex space-x-3">
                        <div class="relative">
                            <button onclick="toggleExportDropdown()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 flex items-center space-x-2">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>Export</span>
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </button>
                            <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-slate-200">
                                <div class="py-1">
                                    <a href="#" onclick="exportData('csv')" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">Export as CSV</a>
                                    <a href="#" onclick="exportData('json')" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">Export as JSON</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 mb-6 dark:bg-slate-800 dark:border-slate-700">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <select id="stageFilter" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">All Stages</option>
                            <option value="active">Active</option>
                            <option value="renewal">Renewal</option>
                            <option value="amendment">Amendment</option>
                            <option value="expired">Expired</option>
                            <option value="revoked">Revoked</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        <select id="actionFilter" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option value="">Action Required</option>
                            <option value="none">None</option>
                            <option value="renewal">Renewal</option>
                            <option value="inspection">Inspection</option>
                            <option value="compliance_check">Compliance Check</option>
                            <option value="document_update">Document Update</option>
                        </select>
                        <input type="date" id="expiryFilter" placeholder="Expiry Date" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                        <input type="date" id="renewalFilter" placeholder="Renewal Due" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                        <button onclick="applyFilters()" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 flex items-center justify-center space-x-2">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                            <span>Apply Filters</span>
                        </button>
                    </div>
                </div>

                <!-- Lifecycle Table -->
                <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Franchise</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Operator/Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Route</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Stage</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Expiry/Renewal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                <?php foreach ($lifecycle as $lc): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $lc['franchise_number']; ?></div>
                                            <div class="text-sm text-slate-500"><?php echo $lc['franchise_id']; ?></div>
                                            <div class="text-xs text-slate-400">Stage Date: <?php echo date('M d, Y', strtotime($lc['stage_date'])); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-blue-600 font-medium"><?php echo strtoupper(substr($lc['first_name'], 0, 1) . substr($lc['last_name'], 0, 1)); ?></span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $lc['first_name'] . ' ' . $lc['last_name']; ?></div>
                                                <div class="text-sm text-slate-500"><?php echo $lc['plate_number']; ?> - <?php echo ucfirst($lc['vehicle_type']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo $lc['route_assigned']; ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $stage_colors = [
                                            'active' => 'bg-green-100 text-green-800',
                                            'renewal' => 'bg-yellow-100 text-yellow-800',
                                            'expired' => 'bg-red-100 text-red-800',
                                            'revocation' => 'bg-gray-100 text-gray-800',
                                            'amendment' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $stage_class = $stage_colors[$lc['lifecycle_stage']] ?? 'bg-slate-100 text-slate-800';
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $stage_class; ?> rounded-full"><?php echo ucfirst($lc['lifecycle_stage']); ?></span>
                                        <?php if ($lc['action_required'] != 'none'): ?>
                                        <div class="text-xs text-orange-600 mt-1">Action: <?php echo ucfirst(str_replace('_', ' ', $lc['action_required'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($lc['expiry_date']): ?>
                                        <div class="text-sm text-slate-900 dark:text-white">Expires: <?php echo date('M d, Y', strtotime($lc['expiry_date'])); ?></div>
                                        <?php endif; ?>
                                        <?php if ($lc['renewal_due_date']): ?>
                                        <div class="text-xs text-slate-500">Renewal Due: <?php echo date('M d, Y', strtotime($lc['renewal_due_date'])); ?></div>
                                        <?php 
                                        $days_to_renewal = (strtotime($lc['renewal_due_date']) - time()) / (60 * 60 * 24);
                                        if ($days_to_renewal <= 30 && $days_to_renewal > 0): ?>
                                        <div class="text-xs text-orange-500">Due in <?php echo round($days_to_renewal); ?> days</div>
                                        <?php elseif ($days_to_renewal <= 0): ?>
                                        <div class="text-xs text-red-600 font-medium">Overdue</div>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button onclick="viewLifecycle('<?php echo $lc['lifecycle_id']; ?>')" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="View Details">
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
        
        function viewLifecycle(lifecycleId) {
            fetch(`view_lifecycle.php?id=${lifecycleId}`)
                .then(response => response.text())
                .then(html => {
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
                    modal.innerHTML = `
                        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-xl font-bold">Franchise Lifecycle Details</h2>
                                <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                                    <i data-lucide="x" class="w-6 h-6"></i>
                                </button>
                            </div>
                            <div>${html}</div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    lucide.createIcons();
                })
                .catch(error => {
                    alert('Error loading lifecycle details');
                });
        }
        

        

        
        function applyFilters() {
            const filters = {
                stage: document.getElementById('stageFilter').value,
                action_required: document.getElementById('actionFilter').value,
                expiry_date: document.getElementById('expiryFilter').value,
                renewal_due: document.getElementById('renewalFilter').value
            };
            
            fetch('handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'filter', ...filters})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateTable(data.data);
                } else {
                    alert('Filter failed: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error applying filters');
            });
        }
        
        function toggleExportDropdown() {
            const dropdown = document.getElementById('exportDropdown');
            dropdown.classList.toggle('hidden');
        }
        
        function exportData(format) {
            const filters = {
                stage: document.getElementById('stageFilter').value,
                action_required: document.getElementById('actionFilter').value,
                expiry_date: document.getElementById('expiryFilter').value,
                renewal_due: document.getElementById('renewalFilter').value
            };
            
            const params = new URLSearchParams({action: 'export', format: format, ...filters});
            window.open(`handler.php?${params.toString()}`, '_blank');
            document.getElementById('exportDropdown').classList.add('hidden');
        }
        
        function searchLifecycle() {
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
        
        function updateTable(data) {
            const tbody = document.querySelector('tbody');
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-slate-500">No lifecycle records found</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.map(row => {
                const stageColors = {
                    'active': 'bg-green-100 text-green-800',
                    'renewal': 'bg-yellow-100 text-yellow-800',
                    'expired': 'bg-red-100 text-red-800',
                    'revocation': 'bg-gray-100 text-gray-800',
                    'amendment': 'bg-blue-100 text-blue-800',
                    'suspended': 'bg-orange-100 text-orange-800'
                };
                const stageClass = stageColors[row.lifecycle_stage] || 'bg-slate-100 text-slate-800';
                
                return `
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div>
                                <div class="text-sm font-medium text-slate-900">${row.franchise_number || 'N/A'}</div>
                                <div class="text-sm text-slate-500">${row.lifecycle_id || 'N/A'}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-medium">${(row.first_name && row.last_name) ? (row.first_name.charAt(0) + row.last_name.charAt(0)).toUpperCase() : 'N/A'}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-slate-900">${(row.first_name && row.last_name) ? (row.first_name + ' ' + row.last_name) : 'N/A'}</div>
                                    <div class="text-sm text-slate-500">${row.plate_number || 'N/A'} - ${row.vehicle_type ? row.vehicle_type.charAt(0).toUpperCase() + row.vehicle_type.slice(1) : 'N/A'}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-900">${row.route_assigned || 'N/A'}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium ${stageClass} rounded-full">${row.lifecycle_stage ? row.lifecycle_stage.charAt(0).toUpperCase() + row.lifecycle_stage.slice(1) : 'N/A'}</span>
                            ${row.action_required && row.action_required !== 'none' ? `<div class="text-xs text-orange-600 mt-1">Action: ${row.action_required.replace('_', ' ').charAt(0).toUpperCase() + row.action_required.replace('_', ' ').slice(1)}</div>` : ''}
                        </td>
                        <td class="px-6 py-4">
                            ${row.expiry_date ? `<div class="text-sm text-slate-900">Expires: ${new Date(row.expiry_date).toLocaleDateString()}</div>` : ''}
                            ${row.renewal_due_date ? `<div class="text-xs text-slate-500">Renewal Due: ${new Date(row.renewal_due_date).toLocaleDateString()}</div>` : ''}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <button onclick="viewLifecycle('${row.lifecycle_id}')" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="View Details">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                                ${(row.lifecycle_stage === 'renewal' || row.action_required === 'renewal') ? `
                                <button onclick="processRenewal('${row.lifecycle_id}')" class="p-1 text-green-600 hover:bg-green-100 rounded" title="Process Renewal">
                                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                </button>` : ''}
                                ${row.lifecycle_stage === 'active' ? `
                                <button onclick="amendFranchise('${row.lifecycle_id}')" class="p-1 text-orange-600 hover:bg-orange-100 rounded" title="Amend">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                                <button onclick="suspendFranchise('${row.lifecycle_id}')" class="p-1 text-yellow-600 hover:bg-yellow-100 rounded" title="Suspend">
                                    <i data-lucide="pause" class="w-4 h-4"></i>
                                </button>
                                <button onclick="revokeFranchise('${row.lifecycle_id}')" class="p-1 text-red-600 hover:bg-red-100 rounded" title="Revoke">
                                    <i data-lucide="ban" class="w-4 h-4"></i>
                                </button>` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
            lucide.createIcons();
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('exportDropdown');
            const button = event.target.closest('button');
            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleExportDropdown') === -1) {
                dropdown.classList.add('hidden');
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