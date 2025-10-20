<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'link_violation':
            $result = linkViolationToDatabase($conn, $_POST['digitized_id'], $_POST['operator_id'], $_POST['vehicle_id']);
            echo json_encode(['success' => $result]);
            exit;
            

            
        case 'update_enforcement':
            $result = updateEnforcementRecommendation($conn, $_POST['operator_id'], $_POST['recommendation']);
            echo json_encode(['success' => $result]);
            exit;
    }
}

// Get data for display using existing tables
$analytics_data = getViolationAnalytics($conn);
$repeat_offenders = getRepeatOffenders($conn);
$enforcement_recommendations = getEnforcementRecommendations($conn);
$stats = getLinkingStats($conn);

function getViolationAnalytics($conn) {
    $query = "SELECT vh.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type,
                     COUNT(vh.violation_id) as total_violations,
                     AVG(CASE WHEN vh.settlement_status = 'paid' THEN 100 ELSE 0 END) as compliance_score,
                     CASE 
                         WHEN COUNT(vh.violation_id) >= 5 THEN 'high'
                         WHEN COUNT(vh.violation_id) >= 3 THEN 'medium'
                         ELSE 'low'
                     END as risk_level
              FROM violation_history vh 
              JOIN operators o ON vh.operator_id = o.operator_id 
              JOIN vehicles v ON vh.vehicle_id = v.vehicle_id 
              GROUP BY vh.operator_id, vh.vehicle_id
              ORDER BY total_violations DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRepeatOffenders($conn) {
    $query = "SELECT vh.operator_id, vh.vehicle_id, o.first_name, o.last_name, v.plate_number,
                     COUNT(vh.violation_id) as total_violations,
                     MAX(vh.violation_date) as last_violation_date,
                     'high' as risk_level
              FROM violation_history vh 
              JOIN operators o ON vh.operator_id = o.operator_id 
              JOIN vehicles v ON vh.vehicle_id = v.vehicle_id 
              GROUP BY vh.operator_id, vh.vehicle_id
              HAVING COUNT(vh.violation_id) >= 3
              ORDER BY total_violations DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getEnforcementRecommendations($conn) {
    $query = "SELECT vh.operator_id, vh.vehicle_id, o.first_name, o.last_name, v.plate_number,
                     cs.franchise_status,
                     COUNT(vh.violation_id) as total_violations,
                     CASE 
                         WHEN COUNT(vh.violation_id) >= 5 THEN 'high'
                         WHEN COUNT(vh.violation_id) >= 3 THEN 'medium'
                         ELSE 'low'
                     END as risk_level
              FROM violation_history vh 
              JOIN operators o ON vh.operator_id = o.operator_id 
              JOIN vehicles v ON vh.vehicle_id = v.vehicle_id 
              LEFT JOIN compliance_status cs ON vh.operator_id = cs.operator_id 
              GROUP BY vh.operator_id, vh.vehicle_id
              HAVING COUNT(vh.violation_id) >= 2
              ORDER BY total_violations DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLinkingStats($conn) {
    $stats = [];
    
    // Total violations as "unlinked tickets"
    $query = "SELECT COUNT(*) as count FROM violation_history WHERE settlement_status = 'unpaid'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['unlinked_tickets'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Repeat offenders count
    $query = "SELECT COUNT(DISTINCT operator_id) as count FROM (
                SELECT operator_id FROM violation_history 
                GROUP BY operator_id HAVING COUNT(*) >= 3
              ) as repeat_ops";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['repeat_offenders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // High risk operators
    $query = "SELECT COUNT(DISTINCT operator_id) as count FROM (
                SELECT operator_id FROM violation_history 
                GROUP BY operator_id HAVING COUNT(*) >= 5
              ) as high_risk_ops";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['high_risk'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Settlement rate as confidence
    $query = "SELECT ROUND(AVG(CASE WHEN settlement_status = 'paid' THEN 100 ELSE 0 END), 1) as rate
              FROM violation_history";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['avg_confidence'] = $stmt->fetch(PDO::FETCH_ASSOC)['rate'] ?? 0;
    
    return $stats;
}

function linkViolationToDatabase($conn, $digitized_id, $operator_id, $vehicle_id) {
    try {
        $conn->beginTransaction();
        
        // Update digitized ticket
        $query = "UPDATE digitized_tickets SET operator_id = ?, vehicle_id = ?, linking_status = 'linked', linking_confidence = 95.0 WHERE digitized_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$operator_id, $vehicle_id, $digitized_id]);
        
        // Create violation record
        $query = "INSERT INTO violation_history (violation_id, operator_id, vehicle_id, violation_type, violation_date, fine_amount, location, ticket_number) 
                  SELECT CONCAT('VIO-', DATE_FORMAT(NOW(), '%Y-%m'), '-', LPAD(FLOOR(RAND() * 9999), 4, '0')), 
                         ?, ?, violation_type, violation_date, fine_amount, location, ticket_number 
                  FROM digitized_tickets WHERE digitized_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$operator_id, $vehicle_id, $digitized_id]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}



function updateEnforcementRecommendation($conn, $operator_id, $recommendation) {
    try {
        $query = "UPDATE violation_analytics SET enforcement_recommendation = ?, updated_at = NOW() WHERE operator_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$recommendation, $operator_id]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linking & Analytics - Transport Management</title>
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
                        <a href="../violation_record_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Violation Record Management</a>
                        <a href="../linking_and_analytics/" class="block p-2 text-sm rounded-lg font-medium" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.2);">TVT Analytics</a>
                        <a href="../revenue_integration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Revenue Integration</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('vehicle-inspection')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Vehicle Inspection & Registration</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="vehicle-inspection-icon"></i>
                    </button>
                    <div id="vehicle-inspection-menu" class="hidden ml-8 space-y-1">
                        <a href="../../vehicle_inspection_and_registration/inspection_scheduling/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Inspection Scheduling</a>
                        <a href="../../vehicle_inspection_and_registration/inspection_result_recording/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Result Recording</a>
                        <a href="../../vehicle_inspection_and_registration/inspection_history_tracking/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">History Tracking</a>
                        <a href="../../vehicle_inspection_and_registration/vehicle_registration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">LTO Registration</a>
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
                        <a href="../../user_management/account_registry/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Account Registry</a>
                        <a href="../../user_management/verification_queue/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Verification Queue</a>
                        <a href="../../user_management/account_maintenance/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Account Maintenance</a>
                        <a href="../../user_management/roles_and_permissions/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Roles & Permissions</a>
                        <a href="../../user_management/audit_logs/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Audit Logs</a>
                    </div>
                </div>

                <div class="space-y-1">
                    <button onclick="toggleDropdown('settings')" class="w-full flex items-center justify-between p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="settings" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Settings</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="settings-icon"></i>
                    </button>
                    <div id="settings-menu" class="hidden ml-8 space-y-1">
                        <a href="../../settings/system_configuration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">System Configuration</a>
                        <a href="../../settings/backup_and_restore/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Backup & Restore</a>
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
                            <h1 class="text-md font-bold dark:text-white">TVT ANALYTICS</h1>
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

            <!-- Dashboard Content -->
            <div class="flex-1 p-6 overflow-auto">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <i data-lucide="users" class="h-6 w-6 text-red-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Repeat Offenders</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['repeat_offenders'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i data-lucide="alert-triangle" class="h-6 w-6 text-orange-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">High Risk</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['high_risk'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i data-lucide="target" class="h-6 w-6 text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Avg Confidence</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['avg_confidence'] ?>%</p>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Tabs -->
                <div class="bg-white rounded-lg shadow">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6">

                            <button onclick="switchTab('analytics')" id="analytics-tab" class="tab-button active py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600">
                                Violation Analytics
                            </button>
                            <button onclick="switchTab('offenders')" id="offenders-tab" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Repeat Offenders
                            </button>
                            <button onclick="switchTab('enforcement')" id="enforcement-tab" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Enforcement Recommendations
                            </button>
                        </nav>
                    </div>



                    <!-- Analytics Tab -->
                    <div id="analytics-content" class="tab-content p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Violations</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Level</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance Score</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($analytics_data as $data): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= $data['first_name'] . ' ' . $data['last_name'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $data['plate_number'] . ' - ' . ucfirst($data['vehicle_type']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $data['total_violations'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $risk_colors = [
                                                'low' => 'bg-green-100 text-green-800',
                                                'medium' => 'bg-yellow-100 text-yellow-800',
                                                'high' => 'bg-red-100 text-red-800'
                                            ];
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $risk_colors[$data['risk_level']] ?>">
                                                <?= ucfirst($data['risk_level']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= number_format($data['compliance_score'], 1) ?>%
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Repeat Offenders Tab -->
                    <div id="offenders-content" class="tab-content p-6 hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Violations</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Violation</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Level</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($repeat_offenders as $offender): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= $offender['first_name'] . ' ' . $offender['last_name'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $offender['plate_number'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $offender['total_violations'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $offender['last_violation_date'] ? date('M d, Y', strtotime($offender['last_violation_date'])) : 'N/A' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                <?= ucfirst($offender['risk_level']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Enforcement Recommendations Tab -->
                    <div id="enforcement-content" class="tab-content p-6 hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Franchise Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Level</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recommendation</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($enforcement_recommendations as $rec): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= $rec['first_name'] . ' ' . $rec['last_name'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $rec['plate_number'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $franchise_colors = [
                                                'valid' => 'bg-green-100 text-green-800',
                                                'expired' => 'bg-red-100 text-red-800',
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'revoked' => 'bg-gray-100 text-gray-800'
                                            ];
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $franchise_colors[$rec['franchise_status']] ?>">
                                                <?= ucfirst($rec['franchise_status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $rec['risk_level'] == 'high' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                <?= ucfirst($rec['risk_level']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php
                                            if ($rec['risk_level'] == 'high' && $rec['franchise_status'] == 'valid') {
                                                echo 'Suspend Franchise';
                                            } elseif ($rec['risk_level'] == 'high') {
                                                echo 'Revoke License';
                                            } else {
                                                echo 'Increase Monitoring';
                                            }
                                            ?>
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
        
        // Show Traffic Violation menu and highlight current page
        document.addEventListener('DOMContentLoaded', function() {
            const violationMenu = document.getElementById('violation-ticketing-menu');
            const violationIcon = document.getElementById('violation-ticketing-icon');
            if (violationMenu && violationIcon) {
                violationMenu.classList.remove('hidden');
                violationIcon.style.transform = 'rotate(180deg)';
                
                // Highlight current page
                const currentLink = violationMenu.querySelector('a[href*="linking_and_analytics"]');
                if (currentLink) {
                    currentLink.style.color = '#4CAF50';
                    currentLink.style.backgroundColor = 'rgba(76, 175, 80, 0.2)';
                    currentLink.classList.add('font-medium');
                }
            }
        });

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
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active', 'border-orange-500', 'text-orange-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            document.getElementById(tab + '-tab').classList.add('active', 'border-orange-500', 'text-orange-600');
            document.getElementById(tab + '-content').classList.remove('hidden');
        }

        function linkTicket(digitizedId) {
            const operatorId = prompt('Enter Operator ID:');
            const vehicleId = prompt('Enter Vehicle ID:');
            
            if (operatorId && vehicleId) {
                const formData = new FormData();
                formData.append('action', 'link_violation');
                formData.append('digitized_id', digitizedId);
                formData.append('operator_id', operatorId);
                formData.append('vehicle_id', vehicleId);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Ticket linked successfully');
                        location.reload();
                    } else {
                        alert('Error linking ticket');
                    }
                });
            }
        }



        function linkAllTickets() {
            if (confirm('Auto-link all unlinked tickets? This may take a few minutes.')) {
                alert('Auto-linking feature - Coming soon!');
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
    </script>
</body>
</html>