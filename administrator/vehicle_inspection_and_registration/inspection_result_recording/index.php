<?php
require_once '../../../config/database.php';
require_once '../../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

// Get statistics and data
$stats = getInspectionResultStats($conn);
$scheduled_inspections = getScheduledInspections($conn);
$recent_results = getRecentResults($conn);
$pending_certificates = getPendingCertificates($conn);

function getInspectionResultStats($conn) {
    $stats = [];
    
    // Total inspections conducted
    $query = "SELECT COUNT(*) as total FROM inspection_records";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['total_inspections'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pass rate
    $query = "SELECT COUNT(*) as passed FROM inspection_records WHERE result = 'passed'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $passed = $stmt->fetch(PDO::FETCH_ASSOC)['passed'];
    $stats['pass_rate'] = $stats['total_inspections'] > 0 ? round(($passed / $stats['total_inspections']) * 100) : 0;
    
    // Pending results
    $query = "SELECT COUNT(*) as pending FROM inspection_records WHERE result = 'pending' OR certificate_number IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['pending_results'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
    
    // Certificates issued
    $query = "SELECT COUNT(*) as issued FROM inspection_records WHERE certificate_number IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['certificates_issued'] = $stmt->fetch(PDO::FETCH_ASSOC)['issued'];
    
    return $stats;
}

function getScheduledInspections($conn) {
    $query = "SELECT ir.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model
              FROM inspection_records ir
              JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
              JOIN operators o ON v.operator_id = o.operator_id
              WHERE ir.result = 'pending' OR ir.result = 'conditional'
              ORDER BY ir.inspection_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentResults($conn) {
    $query = "SELECT ir.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model
              FROM inspection_records ir
              JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
              JOIN operators o ON v.operator_id = o.operator_id
              WHERE ir.result != 'pending'
              ORDER BY ir.created_at DESC
              LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPendingCertificates($conn) {
    $query = "SELECT ir.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM inspection_records ir
              JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
              JOIN operators o ON v.operator_id = o.operator_id
              WHERE ir.result = 'passed' AND (ir.certificate_number IS NULL OR ir.certificate_number = '')
              ORDER BY ir.inspection_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'record_result':
                $result = recordInspectionResult($conn, $_POST);
                echo json_encode(['success' => $result]);
                break;
                
            case 'generate_certificate':
                $result = generateCertificate($conn, $_POST['inspection_id']);
                echo json_encode(['success' => $result]);
                break;
                
            case 'update_vehicle_status':
                $result = updateVehicleStatus($conn, $_POST['vehicle_id'], $_POST['status']);
                echo json_encode(['success' => $result]);
                break;
        }
    }
    exit;
}

function recordInspectionResult($conn, $data) {
    try {
        $conn->beginTransaction();
        
        // Generate inspection ID
        $inspection_id = 'INS-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert new inspection record
        $query = "INSERT INTO inspection_records (inspection_id, vehicle_id, inspection_date, inspector_name, inspection_type, result, remarks, next_inspection_due) 
                  VALUES (:inspection_id, :vehicle_id, :inspection_date, :inspector_name, :inspection_type, :result, :remarks, :next_due)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            'inspection_id' => $inspection_id,
            'vehicle_id' => $data['vehicle_id'],
            'inspection_date' => $data['inspection_date'],
            'inspector_name' => $data['inspector_name'],
            'inspection_type' => $data['inspection_type'],
            'result' => $data['result'],
            'remarks' => $data['remarks'],
            'next_due' => $data['next_inspection_due']
        ]);
        
        // Update compliance status
        $query = "UPDATE compliance_status SET inspection_status = :result, last_inspection_date = :inspection_date, next_inspection_due = :next_due WHERE vehicle_id = :vehicle_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            'result' => $data['result'],
            'inspection_date' => $data['inspection_date'],
            'next_due' => $data['next_inspection_due'],
            'vehicle_id' => $data['vehicle_id']
        ]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function generateCertificate($conn, $inspection_id) {
    // This function is for monitoring certificate input, not generating certificates
    $cert_number = $_POST['certificate_number'] ?? '';
    if (empty($cert_number)) {
        return false;
    }
    $query = "UPDATE inspection_records SET certificate_number = :cert_number WHERE inspection_id = :inspection_id";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['cert_number' => $cert_number, 'inspection_id' => $inspection_id]);
}

function updateVehicleStatus($conn, $vehicle_id, $status) {
    $query = "UPDATE vehicles SET status = :status WHERE vehicle_id = :vehicle_id";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['status' => $status, 'vehicle_id' => $vehicle_id]);
}

function generateCertificateNumber($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM inspection_records WHERE certificate_number LIKE 'CERT-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 4, '0', STR_PAD_LEFT);
    return "CERT-{$year}-{$next_id}";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Result Recording - Transport Management</title>
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
                        <h1 class="text-xl font-bold dark:text-white">TMM</h1>
                        <p class="text-xs text-slate-500">Admin Dashboard</p>
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
                        <a href="../../vehicle_inspection_and_registration/inspection_result_recording/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Result Recording</a>
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
                            <h1 class="text-md font-bold dark:text-white">INSPECTION RESULT RECORDING</h1>
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
                                <i data-lucide="clipboard-list" class="h-6 w-6 text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Inspections</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_inspections']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i data-lucide="check-circle" class="h-6 w-6 text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pass Rate</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pass_rate']; ?>%</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i data-lucide="clock" class="h-6 w-6 text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending Results</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_results']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i data-lucide="award" class="h-6 w-6 text-orange-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Certificates Issued</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['certificates_issued']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-lg shadow">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6">
                            <button onclick="switchTab('scheduled')" id="scheduled-tab" class="py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600">
                                Scheduled Inspections
                            </button>
                            <button onclick="switchTab('results')" id="results-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Recent Results
                            </button>
                            <button onclick="switchTab('certificates')" id="certificates-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Pending Certificates
                            </button>
                        </nav>
                    </div>

                    <!-- Scheduled Inspections Tab -->
                    <div id="scheduled-content" class="p-6">
                        <div class="flex justify-end mb-4">
                            <button onclick="openResultModal()" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 flex items-center">
                                <i data-lucide="clipboard-check" class="h-4 w-4 mr-2"></i>
                                Record Result
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspection ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($scheduled_inspections as $inspection): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($inspection['inspection_id']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['first_name'] . ' ' . $inspection['last_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['vehicle_type'] . ' - ' . $inspection['make'] . ' ' . $inspection['model']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($inspection['inspection_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($inspection['inspector_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="recordResultModal('<?php echo $inspection['inspection_id']; ?>', '<?php echo $inspection['vehicle_id']; ?>')" class="text-orange-600 hover:text-orange-900">Record Result</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Results Tab -->
                    <div id="results-content" class="p-6 hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspection ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Certificate</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($recent_results as $result): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($result['inspection_id']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($result['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo getStatusBadge($result['result']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($result['inspection_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $result['certificate_number'] ?? 'Not Issued'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if ($result['result'] === 'failed'): ?>
                                                <button onclick="updateVehicleStatusModal('<?php echo $result['vehicle_id']; ?>')" class="text-red-600 hover:text-red-900">Flag Vehicle</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pending Certificates Tab -->
                    <div id="certificates-content" class="p-6 hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspection ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspection Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($pending_certificates as $cert): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($cert['inspection_id']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cert['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($cert['vehicle_type']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($cert['inspection_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="inputCertificateModal('<?php echo $cert['inspection_id']; ?>')" class="text-blue-600 hover:text-blue-900">Input Certificate</button>
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

    <!-- Record Result Modal -->
    <div id="resultModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Record Inspection Result</h3>
                </div>
                <form id="resultForm" class="p-6 space-y-4">
                    <input type="hidden" id="result_inspection_id" name="inspection_id">
                    <input type="hidden" id="result_vehicle_id" name="vehicle_id">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Vehicle</label>
                            <select name="vehicle_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Select Vehicle</option>
                                <?php 
                                $v_query = "SELECT v.vehicle_id, v.plate_number, CONCAT(o.first_name, ' ', o.last_name) as operator_name FROM vehicles v JOIN operators o ON v.operator_id = o.operator_id ORDER BY v.plate_number";
                                $v_stmt = $conn->prepare($v_query);
                                $v_stmt->execute();
                                $vehicles = $v_stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($vehicles as $v): ?>
                                <option value="<?php echo $v['vehicle_id']; ?>"><?php echo $v['plate_number'] . ' - ' . $v['operator_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Inspection Date</label>
                            <input type="date" name="inspection_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Inspector Name</label>
                            <input type="text" name="inspector_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Enter inspector name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Inspection Type</label>
                            <select name="inspection_type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Select Type</option>
                                <option value="annual">Annual</option>
                                <option value="renewal">Renewal</option>
                                <option value="spot_check">Spot Check</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inspection Result</label>
                        <select name="result" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select Result</option>
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                            <option value="conditional">Conditional</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Remarks</label>
                        <textarea name="remarks" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Enter inspection remarks..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Next Inspection Due</label>
                        <input type="date" name="next_inspection_due" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('resultModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700">Record Result</button>
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
            document.getElementById('scheduled-content').classList.add('hidden');
            document.getElementById('results-content').classList.add('hidden');
            document.getElementById('certificates-content').classList.add('hidden');
            
            document.getElementById('scheduled-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            document.getElementById('results-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            document.getElementById('certificates-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            
            document.getElementById(tab + '-content').classList.remove('hidden');
            document.getElementById(tab + '-tab').className = 'py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600';
        }

        function openResultModal() {
            document.getElementById('resultModal').classList.remove('hidden');
        }

        function recordResultModal(inspectionId, vehicleId) {
            document.getElementById('result_inspection_id').value = inspectionId;
            document.getElementById('result_vehicle_id').value = vehicleId;
            document.getElementById('resultModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function inputCertificateModal(inspectionId) {
            const certNumber = prompt('Enter certificate number for monitoring:');
            if (certNumber) {
                const formData = new FormData();
                formData.append('action', 'generate_certificate');
                formData.append('inspection_id', inspectionId);
                formData.append('certificate_number', certNumber);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Certificate number recorded successfully!');
                        location.reload();
                    } else {
                        alert('Error recording certificate number');
                    }
                });
            }
        }

        function updateVehicleStatusModal(vehicleId) {
            const status = prompt('Enter new vehicle status (suspended/for_inspection):');
            if (status) {
                const formData = new FormData();
                formData.append('action', 'update_vehicle_status');
                formData.append('vehicle_id', vehicleId);
                formData.append('status', status);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Vehicle status updated successfully!');
                        location.reload();
                    } else {
                        alert('Error updating vehicle status');
                    }
                });
            }
        }

        document.getElementById('resultForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'record_result');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Inspection result recorded successfully!');
                    location.reload();
                } else {
                    alert('Error recording result');
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