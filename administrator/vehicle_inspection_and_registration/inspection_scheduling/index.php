<?php
require_once '../../../config/database.php';
require_once '../../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

// Get statistics
$stats = getInspectionStats($conn);
$pending_inspections = getPendingInspections($conn);
$scheduled_inspections = getScheduledInspections($conn);
$overdue_inspections = getOverdueInspections($conn);

function getInspectionStats($conn) {
    $stats = [];
    
    // Total vehicles requiring inspection
    $query = "SELECT COUNT(*) as total FROM vehicles WHERE status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['total_vehicles'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending inspections
    $query = "SELECT COUNT(*) as total FROM compliance_status WHERE inspection_status IN ('pending', 'overdue')";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['pending_inspections'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Scheduled this month
    $query = "SELECT COUNT(*) as total FROM inspection_records WHERE MONTH(inspection_date) = MONTH(CURDATE()) AND YEAR(inspection_date) = YEAR(CURDATE())";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['scheduled_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Compliance rate
    $query = "SELECT COUNT(*) as total FROM compliance_status WHERE inspection_status = 'passed'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $passed = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stats['compliance_rate'] = $stats['total_vehicles'] > 0 ? round(($passed / $stats['total_vehicles']) * 100) : 0;
    
    return $stats;
}

function getPendingInspections($conn) {
    $query = "SELECT cs.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model,
              fa.application_id, fa.application_type
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              LEFT JOIN franchise_applications fa ON cs.operator_id = fa.operator_id AND cs.vehicle_id = fa.vehicle_id
              WHERE cs.inspection_status IN ('pending', 'overdue')
              ORDER BY cs.next_inspection_due ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getScheduledInspections($conn) {
    $query = "SELECT ir.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model
              FROM inspection_records ir
              JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
              JOIN operators o ON v.operator_id = o.operator_id
              WHERE ir.inspection_date >= CURDATE()
              ORDER BY ir.inspection_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOverdueInspections($conn) {
    $query = "SELECT cs.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model,
              DATEDIFF(CURDATE(), cs.next_inspection_due) as days_overdue
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              WHERE cs.inspection_status = 'overdue' AND cs.next_inspection_due < CURDATE()
              ORDER BY days_overdue DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'schedule_inspection':
                $inspection_id = generateInspectionId($conn);
                $data = [
                    'inspection_id' => $inspection_id,
                    'vehicle_id' => $_POST['vehicle_id'],
                    'inspection_date' => $_POST['inspection_date'],
                    'inspector_name' => $_POST['inspector_name'],
                    'inspection_type' => $_POST['inspection_type']
                ];
                
                if (scheduleInspection($conn, $data)) {
                    echo json_encode(['success' => true, 'message' => 'Inspection scheduled successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to schedule inspection']);
                }
                break;
                
            case 'send_notification':
                $operator_id = $_POST['operator_id'];
                $message = $_POST['message'];
                
                if (sendNotification($conn, $operator_id, $message)) {
                    echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send notification']);
                }
                break;
                
            case 'reschedule_inspection':
                $inspection_id = $_POST['inspection_id'];
                $new_date = $_POST['new_date'];
                
                if (rescheduleInspection($conn, $inspection_id, $new_date)) {
                    echo json_encode(['success' => true, 'message' => 'Inspection rescheduled successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to reschedule inspection']);
                }
                break;
        }
    }
    exit;
}

function scheduleInspection($conn, $data) {
    try {
        $conn->beginTransaction();
        
        // Insert inspection record
        $query = "INSERT INTO inspection_records (inspection_id, vehicle_id, inspection_date, inspector_name, inspection_type, result) 
                  VALUES (:inspection_id, :vehicle_id, :inspection_date, :inspector_name, :inspection_type, 'pending')";
        $stmt = $conn->prepare($query);
        $stmt->execute($data);
        
        // Update compliance status
        $query = "UPDATE compliance_status SET inspection_status = 'pending', next_inspection_due = :inspection_date 
                  WHERE vehicle_id = :vehicle_id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['inspection_date' => $data['inspection_date'], 'vehicle_id' => $data['vehicle_id']]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function sendNotification($conn, $operator_id, $message) {
    // Simulate notification sending (in real implementation, this would send SMS/email)
    error_log("Notification sent to operator {$operator_id}: {$message}");
    return true;
}

function rescheduleInspection($conn, $inspection_id, $new_date) {
    $query = "UPDATE inspection_records SET inspection_date = :new_date WHERE inspection_id = :inspection_id";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['new_date' => $new_date, 'inspection_id' => $inspection_id]);
}

function generateInspectionId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM inspection_records WHERE inspection_id LIKE 'INS-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "INS-{$year}-{$next_id}";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Scheduling - Transport Management</title>
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
                        <a href="../../vehicle_inspection_and_registration/inspection_scheduling/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">Inspection Scheduling</a>
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
                            <h1 class="text-md font-bold dark:text-white">INSPECTION SCHEDULING</h1>
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
            <div class="max-w-7xl mx-auto">
                <!-- Page Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">Inspection Scheduling</h2>
                    <p class="mt-2 text-gray-600">Schedule vehicle inspections, manage appointments, and track compliance</p>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i data-lucide="car" class="h-6 w-6 text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Vehicles</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_vehicles']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i data-lucide="clock" class="h-6 w-6 text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending Inspections</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_inspections']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i data-lucide="calendar" class="h-6 w-6 text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Scheduled This Month</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['scheduled_this_month']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i data-lucide="check-circle" class="h-6 w-6 text-orange-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Compliance Rate</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['compliance_rate']; ?>%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-lg shadow">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6">
                            <button onclick="switchTab('pending')" id="pending-tab" class="py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600">
                                Pending Inspections
                            </button>
                            <button onclick="switchTab('scheduled')" id="scheduled-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Scheduled Inspections
                            </button>
                            <button onclick="switchTab('overdue')" id="overdue-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Overdue Inspections
                            </button>
                        </nav>
                    </div>

                    <!-- Pending Inspections Tab -->
                    <div id="pending-content" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Pending Inspections</h3>
                            <button onclick="openScheduleModal()" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 flex items-center">
                                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                Schedule Inspection
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($pending_inspections as $inspection): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['first_name'] . ' ' . $inspection['last_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['operator_id']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['vehicle_type'] . ' - ' . $inspection['make'] . ' ' . $inspection['model']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo getStatusBadge($inspection['inspection_status']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $inspection['next_inspection_due'] ? formatDate($inspection['next_inspection_due']) : 'Not Set'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="scheduleInspectionModal('<?php echo $inspection['vehicle_id']; ?>', '<?php echo $inspection['operator_id']; ?>')" class="text-orange-600 hover:text-orange-900 mr-3">Schedule</button>
                                            <button onclick="sendNotificationModal('<?php echo $inspection['operator_id']; ?>')" class="text-blue-600 hover:text-blue-900">Notify</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Scheduled Inspections Tab -->
                    <div id="scheduled-content" class="p-6 hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Scheduled Inspections</h3>
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
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['vehicle_type']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($inspection['inspection_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($inspection['inspector_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="rescheduleModal('<?php echo $inspection['inspection_id']; ?>')" class="text-orange-600 hover:text-orange-900">Reschedule</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Overdue Inspections Tab -->
                    <div id="overdue-content" class="p-6 hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Overdue Inspections</h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operator</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Overdue</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($overdue_inspections as $inspection): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['first_name'] . ' ' . $inspection['last_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['operator_id']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['vehicle_type']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($inspection['next_inspection_due']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                                <?php echo $inspection['days_overdue']; ?> days
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="scheduleInspectionModal('<?php echo $inspection['vehicle_id']; ?>', '<?php echo $inspection['operator_id']; ?>')" class="text-orange-600 hover:text-orange-900 mr-3">Schedule</button>
                                            <button onclick="sendNotificationModal('<?php echo $inspection['operator_id']; ?>')" class="text-red-600 hover:text-red-900">Urgent Notify</button>
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
    </div>

    <!-- Schedule Inspection Modal -->
    <div id="scheduleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Schedule Inspection</h3>
                </div>
                <form id="scheduleForm" class="p-6 space-y-4">
                    <input type="hidden" id="schedule_vehicle_id" name="vehicle_id">
                    <input type="hidden" id="schedule_operator_id" name="operator_id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Operator</label>
                        <select name="operator_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select Operator</option>
                            <?php 
                            $op_query = "SELECT operator_id, first_name, last_name FROM operators ORDER BY first_name";
                            $op_stmt = $conn->prepare($op_query);
                            $op_stmt->execute();
                            $operators = $op_stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($operators as $op): ?>
                            <option value="<?php echo $op['operator_id']; ?>"><?php echo $op['first_name'] . ' ' . $op['last_name'] . ' (' . $op['operator_id'] . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
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
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inspector Name</label>
                        <input type="text" name="inspector_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inspection Type</label>
                        <select name="inspection_type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                            <option value="annual">Annual Inspection</option>
                            <option value="renewal">Renewal Inspection</option>
                            <option value="spot_check">Spot Check</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('scheduleModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700">Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div id="notificationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Send Notification</h3>
                </div>
                <form id="notificationForm" class="p-6 space-y-4">
                    <input type="hidden" id="notification_operator_id" name="operator_id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Message</label>
                        <textarea name="message" rows="4" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Enter notification message..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('notificationModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Tab switching
        function switchTab(tab) {
            // Hide all content
            document.getElementById('pending-content').classList.add('hidden');
            document.getElementById('scheduled-content').classList.add('hidden');
            document.getElementById('overdue-content').classList.add('hidden');
            
            // Reset all tabs
            document.getElementById('pending-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            document.getElementById('scheduled-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            document.getElementById('overdue-tab').className = 'py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300';
            
            // Show selected content and activate tab
            document.getElementById(tab + '-content').classList.remove('hidden');
            document.getElementById(tab + '-tab').className = 'py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600';
        }

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

        // Modal functions
        function openScheduleModal() {
            document.getElementById('scheduleModal').classList.remove('hidden');
        }

        function scheduleInspectionModal(vehicleId, operatorId) {
            document.getElementById('schedule_vehicle_id').value = vehicleId;
            document.getElementById('schedule_operator_id').value = operatorId;
            document.getElementById('scheduleModal').classList.remove('hidden');
        }

        function sendNotificationModal(operatorId) {
            document.getElementById('notification_operator_id').value = operatorId;
            document.getElementById('notificationModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Form submissions
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'schedule_inspection');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Inspection scheduled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        document.getElementById('notificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'send_notification');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Notification sent successfully!');
                    closeModal('notificationModal');
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        function rescheduleModal(inspectionId) {
            const newDate = prompt('Enter new inspection date (YYYY-MM-DD):');
            if (newDate) {
                const formData = new FormData();
                formData.append('action', 'reschedule_inspection');
                formData.append('inspection_id', inspectionId);
                formData.append('new_date', newDate);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Inspection rescheduled successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
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