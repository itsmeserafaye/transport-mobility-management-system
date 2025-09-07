<?php
require_once '../../../config/database.php';

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
            
        case 'generate_analytics':
            $filters = [
                'date_from' => $_POST['date_from'] ?? null,
                'date_to' => $_POST['date_to'] ?? null,
                'risk_level' => $_POST['risk_level'] ?? null
            ];
            $analytics = generateViolationAnalytics($conn, $filters);
            echo json_encode(['success' => true, 'data' => $analytics]);
            exit;
            
        case 'update_enforcement':
            $result = updateEnforcementRecommendation($conn, $_POST['operator_id'], $_POST['recommendation']);
            echo json_encode(['success' => $result]);
            exit;
    }
}

// Get data for display
$unlinked_tickets = getUnlinkedTickets($conn);
$analytics_data = getViolationAnalytics($conn);
$repeat_offenders = getRepeatOffenders($conn);
$enforcement_recommendations = getEnforcementRecommendations($conn);
$stats = getLinkingStats($conn);

function getUnlinkedTickets($conn) {
    try {
        // Check if digitized_tickets table exists
        $check_query = "SHOW TABLES LIKE 'digitized_tickets'";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            // Table doesn't exist, return empty array
            return [];
        }
        
        $query = "SELECT dt.*, os.ocr_confidence 
                  FROM digitized_tickets dt 
                  JOIN ocr_ticket_scans os ON dt.scan_id = os.scan_id 
                  WHERE dt.linking_status = 'unlinked' 
                  ORDER BY dt.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If any error occurs, return empty array
        return [];
    }
}

function getViolationAnalytics($conn) {
    $query = "SELECT va.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type 
              FROM violation_analytics va 
              JOIN operators o ON va.operator_id = o.operator_id 
              JOIN vehicles v ON va.vehicle_id = v.vehicle_id 
              ORDER BY va.total_violations DESC, va.compliance_score ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRepeatOffenders($conn) {
    $query = "SELECT va.*, o.first_name, o.last_name, v.plate_number 
              FROM violation_analytics va 
              JOIN operators o ON va.operator_id = o.operator_id 
              JOIN vehicles v ON va.vehicle_id = v.vehicle_id 
              WHERE va.repeat_offender_flag = 1 
              ORDER BY va.total_violations DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getEnforcementRecommendations($conn) {
    $query = "SELECT va.*, o.first_name, o.last_name, v.plate_number, cs.franchise_status 
              FROM violation_analytics va 
              JOIN operators o ON va.operator_id = o.operator_id 
              JOIN vehicles v ON va.vehicle_id = v.vehicle_id 
              JOIN compliance_status cs ON va.operator_id = cs.operator_id 
              WHERE va.risk_level IN ('medium', 'high') 
              ORDER BY va.risk_level DESC, va.total_violations DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLinkingStats($conn) {
    $stats = [];
    
    try {
        // Check if digitized_tickets table exists
        $check_query = "SHOW TABLES LIKE 'digitized_tickets'";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Unlinked tickets count
            $query = "SELECT COUNT(*) as count FROM digitized_tickets WHERE linking_status = 'unlinked'";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $stats['unlinked_tickets'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Average linking confidence
            $query = "SELECT AVG(linking_confidence) as avg_confidence FROM digitized_tickets WHERE linking_status = 'linked'";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $stats['avg_confidence'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_confidence'] ?? 0, 1);
        } else {
            // Table doesn't exist, set default values
            $stats['unlinked_tickets'] = 0;
            $stats['avg_confidence'] = 0;
        }
    } catch (Exception $e) {
        // If any error occurs, set default values
        $stats['unlinked_tickets'] = 0;
        $stats['avg_confidence'] = 0;
    }
    
    try {
        // Repeat offenders count
        $query = "SELECT COUNT(*) as count FROM violation_analytics WHERE repeat_offender_flag = 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['repeat_offenders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        $stats['repeat_offenders'] = 0;
    }
    
    try {
        // High risk operators
        $query = "SELECT COUNT(*) as count FROM violation_analytics WHERE risk_level = 'high'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats['high_risk'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (Exception $e) {
        $stats['high_risk'] = 0;
    }
    
    return $stats;
}

function linkViolationToDatabase($conn, $digitized_id, $operator_id, $vehicle_id) {
    try {
        // Check if digitized_tickets table exists
        $check_query = "SHOW TABLES LIKE 'digitized_tickets'";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            // Table doesn't exist, return false
            return false;
        }
        
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

function generateViolationAnalytics($conn, $filters = []) {
    $analytics = [];
    
    // Base query for violation analytics
    $query = "SELECT 
                va.operator_id,
                va.vehicle_id,
                o.first_name,
                o.last_name,
                v.plate_number,
                v.vehicle_type,
                va.total_violations,
                va.compliance_score,
                va.risk_level,
                va.repeat_offender_flag,
                va.last_violation_date,
                COUNT(vh.violation_id) as recent_violations
              FROM violation_analytics va
              JOIN operators o ON va.operator_id = o.operator_id
              JOIN vehicles v ON va.vehicle_id = v.vehicle_id
              LEFT JOIN violation_history vh ON va.operator_id = vh.operator_id";
    
    $where_conditions = [];
    $params = [];
    
    // Apply date filters
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "vh.violation_date >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "vh.violation_date <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }
    
    // Apply risk level filter
    if (!empty($filters['risk_level'])) {
        $where_conditions[] = "va.risk_level = :risk_level";
        $params['risk_level'] = $filters['risk_level'];
    }
    
    if (!empty($where_conditions)) {
        $query .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $query .= " GROUP BY va.operator_id, va.vehicle_id, o.first_name, o.last_name, v.plate_number, v.vehicle_type, va.total_violations, va.compliance_score, va.risk_level, va.repeat_offender_flag, va.last_violation_date
                ORDER BY va.total_violations DESC, va.compliance_score ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $analytics['detailed_analytics'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate summary statistics
    $summary_query = "SELECT 
                        COUNT(DISTINCT va.operator_id) as total_operators,
                        AVG(va.compliance_score) as avg_compliance_score,
                        COUNT(CASE WHEN va.risk_level = 'high' THEN 1 END) as high_risk_count,
                        COUNT(CASE WHEN va.risk_level = 'medium' THEN 1 END) as medium_risk_count,
                        COUNT(CASE WHEN va.risk_level = 'low' THEN 1 END) as low_risk_count,
                        COUNT(CASE WHEN va.repeat_offender_flag = 1 THEN 1 END) as repeat_offenders
                      FROM violation_analytics va";
    
    if (!empty($filters['risk_level'])) {
        $summary_query .= " WHERE va.risk_level = :risk_level";
    }
    
    $stmt = $conn->prepare($summary_query);
    if (!empty($filters['risk_level'])) {
        $stmt->execute(['risk_level' => $filters['risk_level']]);
    } else {
        $stmt->execute();
    }
    $analytics['summary'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate violation trends by month
    $trends_query = "SELECT 
                        DATE_FORMAT(vh.violation_date, '%Y-%m') as month,
                        COUNT(*) as violation_count,
                        COUNT(DISTINCT vh.operator_id) as unique_operators
                     FROM violation_history vh
                     WHERE vh.violation_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
    
    if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
        $trends_query .= " AND vh.violation_date BETWEEN :date_from AND :date_to";
    }
    
    $trends_query .= " GROUP BY DATE_FORMAT(vh.violation_date, '%Y-%m')
                       ORDER BY month DESC";
    
    $stmt = $conn->prepare($trends_query);
    if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
        $stmt->execute(['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']]);
    } else {
        $stmt->execute();
    }
    $analytics['trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $analytics;
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
                    <button onclick="toggleDropdown('violation-ticketing')" class="w-full flex items-center justify-between p-2 rounded-xl text-orange-600 bg-orange-50 transition-all">
                        <div class="flex items-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 mr-3"></i>
                            <span class="text-sm font-medium">Traffic Violation Ticketing</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="violation-ticketing-icon" style="transform: rotate(180deg);"></i>
                    </button>
                    <div id="violation-ticketing-menu" class="ml-8 space-y-1">
                        <a href="../../traffic_violation_ticketing/violation_record_management/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Violation Record Management</a>
                        <a href="../../traffic_violation_ticketing/linking_and_analytics/" class="block p-2 text-sm text-orange-600 bg-orange-100 rounded-lg font-medium">TVT Analytics</a>
                        <a href="../../traffic_violation_ticketing/revenue_integration/" class="block p-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Revenue Integration</a>

                    </div>
                </div>

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
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i data-lucide="link-2" class="h-6 w-6 text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Unlinked Tickets</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['unlinked_tickets'] ?></p>
                            </div>
                        </div>
                    </div>

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

                <!-- Generate Analytics Button -->
                <div class="mb-6">
                    <button onclick="generateAnalytics()" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 flex items-center">
                        <i data-lucide="bar-chart" class="h-4 w-4 mr-2"></i>
                        Generate Analytics
                    </button>
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

        function generateAnalytics() {
            const formData = new FormData();
            formData.append('action', 'generate_analytics');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Analytics generated successfully! Data refreshed.');
                    location.reload();
                } else {
                    alert('Error generating analytics: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
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