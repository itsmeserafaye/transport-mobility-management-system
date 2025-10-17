<?php
require_once '../../../config/database.php';
require_once '../../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

// Fix existing records with empty result field
try {
    $fixQuery = "UPDATE inspection_records SET result = 'scheduled' WHERE result IS NULL OR result = '' OR result = '&#39;&#39;'";
    $conn->prepare($fixQuery)->execute();
    
    // Also check for any records that might have HTML entities
    $fixQuery2 = "UPDATE inspection_records SET result = 'scheduled' WHERE result LIKE '%&#39;%' OR result = ''";
    $conn->prepare($fixQuery2)->execute();
} catch (Exception $e) {
    // Continue if update fails
}

// Get statistics
$stats = getInspectionStats($conn);
$pending_inspections = getPendingInspections($conn);
// Show only future and today's scheduled inspections (not overdue, not completed)
try {
    $query = "SELECT ir.*, 
                     COALESCE(o.first_name, 'Unknown') as first_name, 
                     COALESCE(o.last_name, 'Operator') as last_name, 
                     COALESCE(v.plate_number, ir.vehicle_id) as plate_number, 
                     COALESCE(v.vehicle_type, 'Unknown') as vehicle_type, 
                     COALESCE(v.make, '') as make, 
                     COALESCE(v.model, '') as model
              FROM inspection_records ir
              LEFT JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
              LEFT JOIN operators o ON v.operator_id = o.operator_id
              WHERE (ir.result IS NULL OR ir.result = '' OR ir.result = 'scheduled')
                AND ir.inspection_date >= CURDATE()
              ORDER BY ir.inspection_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $scheduled_inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $scheduled_inspections = [];
}
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
              WHERE cs.inspection_status IN ('pending', 'overdue') AND cs.inspection_status != 'scheduled'
              ORDER BY cs.next_inspection_due ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getScheduledInspections($conn) {
    try {
        // First try with JOINs
        $query = "SELECT ir.*, 
                         COALESCE(o.first_name, 'Unknown') as first_name, 
                         COALESCE(o.last_name, 'Operator') as last_name, 
                         COALESCE(v.plate_number, ir.vehicle_id) as plate_number, 
                         COALESCE(v.vehicle_type, 'Unknown') as vehicle_type, 
                         COALESCE(v.make, '') as make, 
                         COALESCE(v.model, '') as model
                  FROM inspection_records ir
                  LEFT JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
                  LEFT JOIN operators o ON v.operator_id = o.operator_id
                  WHERE (ir.result IS NULL OR ir.result = '' OR ir.result = 'scheduled')
                  ORDER BY ir.inspection_date ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If JOIN fails, return basic inspection records
        try {
            $query = "SELECT *, vehicle_id as plate_number, 'Unknown' as first_name, 'Operator' as last_name, 'Unknown' as vehicle_type, '' as make, '' as model
                      FROM inspection_records 
                      WHERE (result IS NULL OR result = '' OR result = 'scheduled')
                      ORDER BY inspection_date ASC";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            return [];
        }
    }
}

function getOverdueInspections($conn) {
    // Show overdue inspections from inspection_records table
    $query = "SELECT ir.*, 
                     COALESCE(o.first_name, 'Unknown') as first_name, 
                     COALESCE(o.last_name, 'Operator') as last_name, 
                     COALESCE(v.plate_number, ir.vehicle_id) as plate_number, 
                     COALESCE(v.vehicle_type, 'Unknown') as vehicle_type, 
                     COALESCE(v.make, '') as make, 
                     COALESCE(v.model, '') as model,
                     DATEDIFF(CURDATE(), ir.inspection_date) as days_overdue
              FROM inspection_records ir
              LEFT JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
              LEFT JOIN operators o ON v.operator_id = o.operator_id
              WHERE ir.inspection_date < CURDATE() 
                AND (ir.result IS NULL OR ir.result = '' OR ir.result = 'scheduled')
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
                try {
                    $inspection_id = generateInspectionId($conn);
                    $data = [
                        'inspection_id' => $inspection_id,
                        'vehicle_id' => $_POST['vehicle_id'],
                        'inspection_date' => $_POST['inspection_date'],
                        'inspector_name' => $_POST['inspector_name'],
                        'inspection_type' => $_POST['inspection_type']
                    ];
                    
                    error_log('Scheduling inspection with data: ' . print_r($data, true));
                    
                    if (scheduleInspection($conn, $data)) {
                        echo json_encode(['success' => true, 'message' => 'Inspection scheduled successfully', 'inspection_id' => $inspection_id]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to schedule inspection']);
                    }
                } catch (Exception $e) {
                    error_log('Schedule inspection error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
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
        // Create inspection_records table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS inspection_records (
            inspection_id VARCHAR(20) PRIMARY KEY,
            vehicle_id VARCHAR(20),
            inspection_date DATE,
            inspector_name VARCHAR(100),
            inspection_type VARCHAR(50),
            result VARCHAR(20) DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->prepare($createTable)->execute();
        
        $conn->beginTransaction();
        
        // Insert inspection record
        $query = "INSERT INTO inspection_records (inspection_id, vehicle_id, inspection_date, inspector_name, inspection_type, result) 
                  VALUES (:inspection_id, :vehicle_id, :inspection_date, :inspector_name, :inspection_type, :result)";
        $stmt = $conn->prepare($query);
        $data['result'] = 'scheduled';
        $stmt->execute($data);
        
        // Update compliance status to scheduled
        $query = "UPDATE compliance_status SET inspection_status = 'scheduled', next_inspection_due = :inspection_date 
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
                    <button onclick="toggleDropdown('vehicle-inspection')" class="w-full flex items-center justify-between p-2 rounded-xl transition-all" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.1);">
                        <div class="flex items-center">
                            <i data-lucide="clipboard-check" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Vehicle Inspection & Registration</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="vehicle-inspection-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="vehicle-inspection-menu" class="ml-8 space-y-1">
                        <a href="../../vehicle_inspection_and_registration/inspection_scheduling/" class="block p-2 text-sm rounded-lg font-medium" style="color: #4CAF50; background-color: rgba(76, 175, 80, 0.2);">Inspection Scheduling</a>
                        <a href="../../vehicle_inspection_and_registration/inspection_result_recording/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Result Recording</a>
                        <a href="../../vehicle_inspection_and_registration/inspection_history_tracking/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">History Tracking</a>
                        <a href="../../vehicle_inspection_and_registration/vehicle_registration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Vehicle Registration</a>
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
                            <button onclick="switchTab('scheduled')" id="scheduled-tab" class="py-4 px-1 border-b-2 border-orange-500 font-medium text-sm text-orange-600">
                                Scheduled Inspections
                            </button>
                            <button onclick="switchTab('overdue')" id="overdue-tab" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Overdue Inspections
                            </button>
                        </nav>
                    </div>

                    <!-- Scheduled Inspections Tab -->
                    <div id="scheduled-content" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Scheduled Inspections</h3>
                            <button onclick="openNewScheduleModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                Schedule Inspection
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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
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
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                            $today = date('Y-m-d');
                                            $inspection_date = $inspection['inspection_date'];
                                            if ($inspection_date > $today) {
                                                echo '<span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Pending</span>';
                                            } elseif ($inspection_date == $today) {
                                                echo '<span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Due Today</span>';
                                            } else {
                                                echo '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Overdue</span>';
                                            }
                                            ?>
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
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['vehicle_id']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['plate_number']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['vehicle_type']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatDate($inspection['inspection_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                                <?php echo $inspection['days_overdue']; ?> days overdue
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="rescheduleModal('<?php echo $inspection['inspection_id']; ?>')" class="text-orange-600 hover:text-orange-900 mr-3">Reschedule</button>
                                            <button onclick="sendNotificationModal('<?php echo $inspection['vehicle_id']; ?>')" class="text-red-600 hover:text-red-900">Urgent Notify</button>
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
                    
                    <div id="selected_info" class="hidden p-3 bg-gray-50 rounded-md">
                        <div class="text-sm">
                            <div class="font-medium text-gray-700">Operator: <span id="operator_display" class="text-gray-900"></span></div>
                            <div class="font-medium text-gray-700 mt-1">Vehicle: <span id="vehicle_display" class="text-gray-900"></span></div>
                        </div>
                    </div>
                    
                    <div id="operator_selection">
                        <label class="block text-sm font-medium text-gray-700">Operator</label>
                        <select id="operator_select" name="operator_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
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
                    
                    <div id="vehicle_selection">
                        <label class="block text-sm font-medium text-gray-700">Vehicle</label>
                        <select id="vehicle_select" name="vehicle_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
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
                        <input type="text" name="inspector_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Enter inspector name">
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

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Reschedule Inspection</h3>
                </div>
                <form id="rescheduleForm" class="p-6 space-y-4">
                    <input type="hidden" id="reschedule_inspection_id" name="inspection_id">
                    
                    <div id="reschedule_info" class="p-3 bg-gray-50 rounded-md">
                        <div class="text-sm font-medium text-gray-700">Inspection ID: <span id="reschedule_display_id" class="text-gray-900"></span></div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Inspection Date</label>
                        <input type="date" name="new_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('rescheduleModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700">Reschedule</button>
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
            document.getElementById('scheduled-content').classList.add('hidden');
            document.getElementById('overdue-content').classList.add('hidden');
            
            // Reset all tabs
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
        function openNewScheduleModal() {
            // Reset form for new scheduling
            document.getElementById('operator_select').style.display = 'block';
            document.getElementById('vehicle_select').style.display = 'block';
            document.getElementById('selected_info').classList.add('hidden');
            
            // Reset form values
            document.getElementById('scheduleForm').reset();
            document.getElementById('schedule_vehicle_id').value = '';
            document.getElementById('schedule_operator_id').value = '';
            
            document.getElementById('scheduleModal').classList.remove('hidden');
        }

        function scheduleInspectionModal(vehicleId, operatorId) {
            // Set the selected vehicle and operator IDs
            document.getElementById('schedule_vehicle_id').value = vehicleId;
            document.getElementById('schedule_operator_id').value = operatorId;
            
            // Find and display operator and vehicle info from the table row
            const rows = document.querySelectorAll('#pending-content tbody tr');
            rows.forEach(row => {
                const scheduleBtn = row.querySelector(`button[onclick*="${vehicleId}"]`);
                if (scheduleBtn) {
                    const operatorCell = row.cells[0];
                    const vehicleCell = row.cells[1];
                    
                    const operatorName = operatorCell.querySelector('.text-sm.font-medium').textContent;
                    const operatorId = operatorCell.querySelector('.text-sm.text-gray-500').textContent;
                    const vehiclePlate = vehicleCell.querySelector('.text-sm.font-medium').textContent;
                    const vehicleInfo = vehicleCell.querySelector('.text-sm.text-gray-500').textContent;
                    
                    document.getElementById('operator_display').textContent = `${operatorName} (${operatorId})`;
                    document.getElementById('vehicle_display').textContent = `${vehiclePlate} - ${vehicleInfo}`;
                }
            });
            
            // Show selected info and open modal
            document.getElementById('selected_info').classList.remove('hidden');
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
                console.log('Response:', data);
                if (data.success) {
                    alert('Inspection scheduled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error occurred');
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
            document.getElementById('reschedule_inspection_id').value = inspectionId;
            document.getElementById('reschedule_display_id').textContent = inspectionId;
            document.getElementById('rescheduleModal').classList.remove('hidden');
        }

        // Reschedule form submission
        document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'reschedule_inspection');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Inspection rescheduled successfully!');
                    closeModal('rescheduleModal');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
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