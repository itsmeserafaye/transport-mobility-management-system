<?php
require_once '../../../config/database.php';
require_once '../../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

// Get statistics and data
$stats = getHistoryStats($conn);
$inspection_history = getInspectionHistory($conn);
$compliance_trends = getComplianceTrends($conn);
$renewal_recommendations = getRenewalRecommendations($conn);

function getHistoryStats($conn) {
    $stats = [];
    
    // Total inspection records
    $query = "SELECT COUNT(*) as total FROM inspection_records";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['total_records'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Average compliance score
    $query = "SELECT AVG(compliance_score) as avg_score FROM compliance_status";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['avg_compliance'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_score'], 1);
    
    // Trend analysis (improving/declining)
    $query = "SELECT 
                SUM(CASE WHEN ir1.result = 'passed' AND ir2.result = 'failed' THEN 1 ELSE 0 END) as improving,
                SUM(CASE WHEN ir1.result = 'failed' AND ir2.result = 'passed' THEN 1 ELSE 0 END) as declining
              FROM inspection_records ir1
              LEFT JOIN inspection_records ir2 ON ir1.vehicle_id = ir2.vehicle_id 
                AND ir2.inspection_date < ir1.inspection_date
              WHERE ir2.inspection_id IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $trend = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['trend_direction'] = ($trend['improving'] > $trend['declining']) ? 'Improving' : 'Declining';
    
    // Renewal due count
    $query = "SELECT COUNT(*) as due FROM compliance_status WHERE next_inspection_due <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['renewals_due'] = $stmt->fetch(PDO::FETCH_ASSOC)['due'];
    
    return $stats;
}

function getInspectionHistory($conn) {
    $query = "SELECT ir.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model,
              va.repeat_offender_flag, va.risk_level
              FROM inspection_records ir
              JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
              JOIN operators o ON v.operator_id = o.operator_id
              LEFT JOIN violation_analytics va ON v.operator_id = va.operator_id AND v.vehicle_id = va.vehicle_id
              ORDER BY ir.inspection_date DESC
              LIMIT 50";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getComplianceTrends($conn) {
    $query = "SELECT 
                DATE_FORMAT(ir.inspection_date, '%Y-%m') as month,
                COUNT(*) as total_inspections,
                SUM(CASE WHEN ir.result = 'passed' THEN 1 ELSE 0 END) as passed,
                ROUND((SUM(CASE WHEN ir.result = 'passed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as pass_rate
              FROM inspection_records ir
              WHERE ir.inspection_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(ir.inspection_date, '%Y-%m')
              ORDER BY month DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRenewalRecommendations($conn) {
    $query = "SELECT cs.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type,
              ir.result as last_result, ir.inspection_date as last_inspection,
              va.total_violations, va.risk_level,
              CASE 
                WHEN cs.compliance_score >= 90 AND va.total_violations <= 1 THEN 'Approve'
                WHEN cs.compliance_score >= 70 AND va.total_violations <= 3 THEN 'Conditional'
                ELSE 'Review Required'
              END as recommendation
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              LEFT JOIN inspection_records ir ON cs.vehicle_id = ir.vehicle_id
              LEFT JOIN violation_analytics va ON cs.operator_id = va.operator_id AND cs.vehicle_id = va.vehicle_id
              WHERE cs.next_inspection_due <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
              ORDER BY cs.next_inspection_due ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'generate_report':
                $result = generateHistoricalReport($conn, $_POST);
                echo json_encode(['success' => $result]);
                break;
                

                

        }
    }
    exit;
}

function generateHistoricalReport($conn, $filters) {
    $query = "SELECT ir.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM inspection_records ir
              JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
              JOIN operators o ON v.operator_id = o.operator_id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['date_from'])) {
        $query .= " AND ir.inspection_date >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $query .= " AND ir.inspection_date <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }
    
    if (!empty($filters['result'])) {
        $query .= " AND ir.result = :result";
        $params['result'] = $filters['result'];
    }
    
    $query .= " ORDER BY ir.inspection_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate CSV report
    $filename = 'inspection_history_report_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = 'reports/' . $filename;
    
    // Create reports directory if it doesn't exist
    if (!file_exists('reports')) {
        mkdir('reports', 0755, true);
    }
    
    $file = fopen($filepath, 'w');
    
    // Add CSV headers
    fputcsv($file, ['Inspection ID', 'Vehicle Plate', 'Vehicle Type', 'Operator Name', 'Inspection Date', 'Inspector', 'Type', 'Result', 'Remarks']);
    
    // Add data rows
    foreach ($data as $row) {
        fputcsv($file, [
            $row['inspection_id'],
            $row['plate_number'],
            $row['vehicle_type'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['inspection_date'],
            $row['inspector_name'],
            $row['inspection_type'],
            $row['result'],
            $row['remarks']
        ]);
    }
    
    fclose($file);
    
    // Log report generation
    error_log('Historical report generated with filters: ' . json_encode($filters));
    
    // Return download response
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    unlink($filepath); // Delete file after download
    exit;
}




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection History Tracking - Transport Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

                <div class="space-y-1">
                    <button onclick="toggleDropdown('vehicle-inspection')" class="w-full flex items-center justify-between p-2 rounded-xl text-orange-600 bg-orange-50 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Vehicle Inspection</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="vehicle-inspection-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="vehicle-inspection-menu" class="ml-8 space-y-1">
                        <a href="../../vehicle_inspection_and_registration/inspection_scheduling/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Inspection Scheduling</a>
                        <a href="../../vehicle_inspection_and_registration/inspection_result_recording/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Result Recording</a>
                        <a href="../../vehicle_inspection_and_registration/inspection_history_tracking/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">History Tracking</a>
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
                            <h1 class="text-md font-bold dark:text-white">INSPECTION HISTORY TRACKING</h1>
                            <span class="text-xs text-slate-500 font-bold">Vehicle Inspection & Registration Module</span>
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
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i data-lucide="archive" class="h-6 w-6 text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Records</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_records']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i data-lucide="trending-up" class="h-6 w-6 text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Avg Compliance</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['avg_compliance']; ?>%</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i data-lucide="activity" class="h-6 w-6 text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Trend</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['trend_direction']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i data-lucide="calendar-clock" class="h-6 w-6 text-orange-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Renewals Due</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['renewals_due']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-lg shadow">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6">
                            <button onclick="switchTab('history')" id="history-tab" class="py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600">
                                Inspection History
                            </button>
                            <button onclick="switchTab('trends')" id="trends-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Compliance Trends
                            </button>
                            <button onclick="switchTab('renewals')" id="renewals-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Renewal Recommendations
                            </button>
                        </nav>
                    </div>

                    <!-- Inspection History Tab -->
                    <div id="history-content" class="p-6">

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Risk Level</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Certificate</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($inspection_history as $record): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($record['inspection_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($record['vehicle_type'] . ' - ' . $record['make'] . ' ' . $record['model']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo ucfirst($record['inspection_type']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo getStatusBadge($record['result']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($record['risk_level']): ?>
                                                <?php echo getRiskBadge($record['risk_level']); ?>
                                            <?php else: ?>
                                                <span class="text-gray-400">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $record['certificate_number'] ?? 'Not Issued'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Compliance Trends Tab -->
                    <div id="trends-content" class="p-6 hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Monthly Compliance Trends</h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Inspections</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pass Rate</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trend</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($compliance_trends as $trend): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo date('M Y', strtotime($trend['month'] . '-01')); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $trend['total_inspections']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $trend['passed']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $trend['pass_rate']; ?>%
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($trend['pass_rate'] >= 80): ?>
                                                <i data-lucide="trending-up" class="h-4 w-4 text-green-600"></i>
                                            <?php elseif ($trend['pass_rate'] >= 60): ?>
                                                <i data-lucide="minus" class="h-4 w-4 text-yellow-600"></i>
                                            <?php else: ?>
                                                <i data-lucide="trending-down" class="h-4 w-4 text-red-600"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Renewal Recommendations Tab -->
                    <div id="renewals-content" class="p-6 hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance Score</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Violations</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recommendation</th>

                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($renewal_recommendations as $renewal): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($renewal['first_name'] . ' ' . $renewal['last_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($renewal['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($renewal['vehicle_type']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($renewal['next_inspection_due']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $renewal['compliance_score']; ?>%
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $renewal['total_violations'] ?? 0; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $rec_colors = [
                                                'Approve' => 'bg-green-100 text-green-800',
                                'Conditional' => 'bg-yellow-100 text-yellow-800',
                                'Review Required' => 'bg-red-100 text-red-800'
                                            ];
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium <?php echo $rec_colors[$renewal['recommendation']]; ?> rounded-full">
                                                <?php echo $renewal['recommendation']; ?>
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
    </div>




                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Result Filter</label>
                        <select name="result" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                            <option value="">All Results</option>
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                            <option value="conditional">Conditional</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('reportModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700">Generate</button>
                    </div>
                </form>
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
            document.getElementById('history-content').classList.add('hidden');
            document.getElementById('trends-content').classList.add('hidden');
            document.getElementById('renewals-content').classList.add('hidden');
            
            document.getElementById('history-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            document.getElementById('trends-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            document.getElementById('renewals-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            
            document.getElementById(tab + '-content').classList.remove('hidden');
            document.getElementById(tab + '-tab').className = 'py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600';
        }



        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
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