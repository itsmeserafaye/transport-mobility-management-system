<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$stats = getStatistics($conn);
$documents = getDocuments($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Repository - Transport Management</title>
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
                    <button onclick="toggleDropdown('franchise-mgmt')" class="w-full flex items-center justify-between p-2 rounded-xl text-orange-600 bg-orange-50 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="file-text" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Franchise Management</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="franchise-mgmt-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="franchise-mgmt-menu" class="ml-8 space-y-1">
                        <a href="../../franchise_management/franchise_application_workflow/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Application & Workflow</a>
                        <a href="../../franchise_management/document_repository/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Document Repository</a>
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
                            <span class="text-xs text-slate-500 font-bold">Franchise Management > Document Repository</span>
                        </div>
                    </div>
                    <div class="flex-1 max-w-md mx-8">
                        <div class="relative">
                            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500"></i>
                            <input type="text" id="searchInput" placeholder="Search documents..." 
                                   class="w-full pl-10 pr-4 py-2 bg-slate-100 border border-slate-200 rounded-lg focus:ring-2 focus:ring-orange-300"
                                   onkeyup="searchDocuments()">
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
                                <p class="text-slate-500 text-sm">Total Documents</p>
                                <p class="text-2xl font-bold text-blue-600">6</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="file" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Verified</p>
                                <p class="text-2xl font-bold text-green-600">5</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Pending</p>
                                <p class="text-2xl font-bold text-yellow-600">1</p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="clock" class="w-6 h-6 text-yellow-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-500 text-sm">Expiring Soon</p>
                                <p class="text-2xl font-bold text-red-600">2</p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Document Repository</h2>
                    <div class="flex space-x-3">
                        <button onclick="openUploadModal()" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 flex items-center space-x-2">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            <span>Upload Document</span>
                        </button>
                        <button class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 flex items-center space-x-2">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 mb-6 dark:bg-slate-800 dark:border-slate-700">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <select class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option>All Categories</option>
                            <option>Legal</option>
                            <option>Permit</option>
                            <option>License</option>
                            <option>Insurance</option>
                            <option>Certificate</option>
                            <option>Clearance</option>
                        </select>
                        <select class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option>All Status</option>
                            <option>Active</option>
                            <option>Expired</option>
                            <option>Pending</option>
                        </select>
                        <select class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                            <option>Verification Status</option>
                            <option>Verified</option>
                            <option>Pending</option>
                            <option>Rejected</option>
                        </select>
                        <input type="date" placeholder="Expiry Date" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                        <button class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 flex items-center justify-center space-x-2">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                            <span>Apply Filters</span>
                        </button>
                    </div>
                </div>

                <!-- Documents Table -->
                <div class="bg-white rounded-xl border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Document</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Owner</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Expiry</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                                <?php foreach ($documents as $doc): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i data-lucide="file-text" class="w-5 h-5 text-blue-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $doc['document_name']; ?></div>
                                                <div class="text-sm text-slate-500"><?php echo $doc['document_id']; ?> | <?php echo ucfirst($doc['document_type']); ?></div>
                                                <div class="text-xs text-slate-400">v<?php echo $doc['version_number']; ?> | <?php echo strtoupper($doc['file_type'] ?? 'PDF'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                <span class="text-purple-600 font-medium text-xs"><?php echo strtoupper(substr($doc['first_name'], 0, 1) . substr($doc['last_name'], 0, 1)); ?></span>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $doc['first_name'] . ' ' . $doc['last_name']; ?></div>
                                                <?php if ($doc['plate_number']): ?>
                                                <div class="text-sm text-slate-500"><?php echo $doc['plate_number']; ?> - <?php echo ucfirst($doc['vehicle_type']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $category_colors = [
                                            'legal' => 'bg-blue-100 text-blue-800',
                                            'permit' => 'bg-green-100 text-green-800',
                                            'license' => 'bg-purple-100 text-purple-800',
                                            'insurance' => 'bg-orange-100 text-orange-800',
                                            'certificate' => 'bg-yellow-100 text-yellow-800',
                                            'clearance' => 'bg-pink-100 text-pink-800'
                                        ];
                                        $category_class = $category_colors[$doc['document_category']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium <?php echo $category_class; ?> rounded-full"><?php echo ucfirst($doc['document_category']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col space-y-1">
                                            <?php 
                                            $verification_class = $doc['verification_status'] == 'verified' ? 'bg-green-100 text-green-800' : 
                                                                 ($doc['verification_status'] == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium <?php echo $verification_class; ?> rounded-full"><?php echo ucfirst($doc['verification_status']); ?></span>
                                            <?php 
                                            $status_class = $doc['status'] == 'active' ? 'bg-green-100 text-green-800' : 
                                                           ($doc['status'] == 'expired' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium <?php echo $status_class; ?> rounded-full"><?php echo ucfirst($doc['status']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($doc['expiry_date']): ?>
                                        <div class="text-sm text-slate-900 dark:text-white"><?php echo date('M d, Y', strtotime($doc['expiry_date'])); ?></div>
                                        <?php 
                                        $days_to_expiry = (strtotime($doc['expiry_date']) - time()) / (60 * 60 * 24);
                                        if ($days_to_expiry <= 30 && $days_to_expiry > 0): ?>
                                        <div class="text-xs text-red-500">Expires in <?php echo round($days_to_expiry); ?> days</div>
                                        <?php elseif ($days_to_expiry <= 0): ?>
                                        <div class="text-xs text-red-600 font-medium">Expired</div>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <div class="text-sm text-slate-500">No expiry</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button onclick="viewDocument('<?php echo $doc['document_id']; ?>')" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="View Document">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="editDocument('<?php echo $doc['document_id']; ?>')" class="p-1 text-purple-600 hover:bg-purple-100 rounded" title="Edit Document">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="downloadDocument('<?php echo $doc['document_id']; ?>')" class="p-1 text-green-600 hover:bg-green-100 rounded" title="Download">
                                                <i data-lucide="download" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="verifyDocument('<?php echo $doc['document_id']; ?>')" class="p-1 text-orange-600 hover:bg-orange-100 rounded" title="Verify">
                                                <i data-lucide="check" class="w-4 h-4"></i>
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
            const menu = document.getElementById(menuId + '-menu');
            const icon = document.getElementById(menuId + '-icon');
            
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                menu.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
        
        function viewDocument(documentId) {
            openViewModal(documentId);
        }
        
        function openViewModal(documentId) {
            fetch('get_document.php?id=' + documentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const doc = data.document;
                        const modalHTML = `
                            <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                                <div class="bg-white rounded-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold">Document Details</h3>
                                        <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                                            <i data-lucide="x" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Document ID</label>
                                                <p class="text-sm text-gray-900">${doc.document_id}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Document Name</label>
                                                <p class="text-sm text-gray-900">${doc.document_name}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Category</label>
                                                <p class="text-sm text-gray-900">${doc.document_category}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Type</label>
                                                <p class="text-sm text-gray-900">${doc.document_type}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                                <p class="text-sm text-gray-900">${doc.status}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Verification Status</label>
                                                <p class="text-sm text-gray-900">${doc.verification_status}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Upload Date</label>
                                                <p class="text-sm text-gray-900">${new Date(doc.upload_date).toLocaleDateString()}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Expiry Date</label>
                                                <p class="text-sm text-gray-900">${doc.expiry_date ? new Date(doc.expiry_date).toLocaleDateString() : 'No expiry'}</p>
                                            </div>
                                        </div>
                                        ${doc.remarks ? `
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Remarks</label>
                                                <p class="text-sm text-gray-900">${doc.remarks}</p>
                                            </div>
                                        ` : ''}
                                    </div>
                                    <div class="flex space-x-3 mt-6">
                                        <button onclick="downloadDocument('${doc.document_id}')" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 flex items-center space-x-2">
                                            <i data-lucide="download" class="w-4 h-4"></i>
                                            <span>Download</span>
                                        </button>
                                        <button onclick="closeViewModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.body.insertAdjacentHTML('beforeend', modalHTML);
                        lucide.createIcons();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading document details.');
                });
        }
        
        function closeViewModal() {
            const modal = document.getElementById('viewModal');
            if (modal) {
                modal.remove();
            }
        }
        
        function downloadDocument(documentId) {
            window.location.href = 'download_document.php?id=' + documentId;
        }
        
        function verifyDocument(documentId) {
            if (confirm('Mark this document as verified?')) {
                fetch('verify_document.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({document_id: documentId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Document verified successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while verifying the document.');
                });
            }
        }
        
        function editDocument(documentId) {
            // Open edit modal with document details
            openEditModal(documentId);
        }
        
        function openEditModal(documentId) {
            // Create modal HTML
            const modalHTML = `
                <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Edit Document</h3>
                            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                        <form id="editDocumentForm" enctype="multipart/form-data">
                            <input type="hidden" name="document_id" value="${documentId}">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Name</label>
                                    <input type="text" name="document_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Replace File (Optional)</label>
                                    <input type="file" name="document_file" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep current file</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                                </div>
                            </div>
                            <div class="flex space-x-3 mt-6">
                                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit" class="flex-1 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                                    Update Document
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            lucide.createIcons();
            
            // Load document details
            loadDocumentDetails(documentId);
            
            // Handle form submission
            document.getElementById('editDocumentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                updateDocument(this);
            });
        }
        
        function closeEditModal() {
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.remove();
            }
        }
        
        function loadDocumentDetails(documentId) {
            fetch('get_document.php?id=' + documentId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('input[name="document_name"]').value = data.document.document_name;
                        if (data.document.expiry_date) {
                            document.querySelector('input[name="expiry_date"]').value = data.document.expiry_date;
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function updateDocument(form) {
            const formData = new FormData(form);
            
            fetch('update_document.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Document updated successfully!');
                    closeEditModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the document.');
            });
        }
        
        function openUploadModal() {
            const modalHTML = `
                <div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Upload Document</h3>
                            <button onclick="closeUploadModal()" class="text-gray-500 hover:text-gray-700">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                        <form id="uploadDocumentForm" enctype="multipart/form-data">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Operator ID</label>
                                    <input type="text" name="operator_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle ID (Optional)</label>
                                    <input type="text" name="vehicle_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Category</label>
                                    <select name="document_category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300" required>
                                        <option value="">Select Category</option>
                                        <option value="legal">Legal</option>
                                        <option value="permit">Permit</option>
                                        <option value="license">License</option>
                                        <option value="insurance">Insurance</option>
                                        <option value="certificate">Certificate</option>
                                        <option value="clearance">Clearance</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                                    <input type="text" name="document_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Name</label>
                                    <input type="text" name="document_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Document File</label>
                                    <input type="file" name="document_file" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date (Optional)</label>
                                    <input type="date" name="expiry_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-300">
                                </div>
                            </div>
                            <div class="flex space-x-3 mt-6">
                                <button type="button" onclick="closeUploadModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit" class="flex-1 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                                    Upload Document
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            lucide.createIcons();
            
            document.getElementById('uploadDocumentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                uploadDocument(this);
            });
        }
        
        function closeUploadModal() {
            const modal = document.getElementById('uploadModal');
            if (modal) {
                modal.remove();
            }
        }
        
        function searchDocuments() {
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
        
        function uploadDocument(form) {
            const formData = new FormData(form);
            
            fetch('upload_document.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Document uploaded successfully!');
                    closeUploadModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while uploading the document.');
            });
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