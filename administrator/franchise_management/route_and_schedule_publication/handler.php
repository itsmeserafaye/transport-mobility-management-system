<?php
header('Content-Type: application/json');

try {
    require_once '../../../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'define_route':
                $route_data = [
                    'route_id' => generateRouteId($db),
                    'route_name' => $_POST['route_name'] ?? '',
                    'route_code' => $_POST['route_code'] ?? '',
                    'origin' => $_POST['origin'] ?? '',
                    'destination' => $_POST['destination'] ?? '',
                    'distance_km' => $_POST['distance_km'] ?? 0,
                    'estimated_travel_time' => $_POST['estimated_travel_time'] ?? 0,
                    'fare_amount' => $_POST['fare_amount'] ?? 0,
                    'waypoints' => $_POST['waypoints'] ?? '',
                    'status' => 'active'
                ];
                
                $success = defineRoute($db, $route_data);
                echo json_encode(['success' => $success]);
                break;
                
            case 'edit_route':
                $route_id = $_POST['route_id'] ?? '';
                $route_data = [
                    'route_name' => $_POST['route_name'] ?? '',
                    'route_code' => $_POST['route_code'] ?? '',
                    'origin' => $_POST['origin'] ?? '',
                    'destination' => $_POST['destination'] ?? '',
                    'distance_km' => $_POST['distance_km'] ?? 0,
                    'estimated_travel_time' => $_POST['estimated_travel_time'] ?? 0,
                    'fare_amount' => $_POST['fare_amount'] ?? 0,
                    'waypoints' => $_POST['waypoints'] ?? ''
                ];
                
                $success = updateRoute($db, $route_id, $route_data);
                echo json_encode(['success' => $success]);
                break;
                
            case 'add_schedule':
                $schedule_data = [
                    'schedule_id' => generateScheduleId($db),
                    'route_id' => $_POST['route_id'] ?? '',
                    'operator_id' => $_POST['operator_id'] ?? '',
                    'vehicle_id' => $_POST['vehicle_id'] ?? '',
                    'departure_time' => $_POST['departure_time'] ?? '',
                    'arrival_time' => $_POST['arrival_time'] ?? '',
                    'frequency_minutes' => $_POST['frequency_minutes'] ?? 30,
                    'operating_days' => $_POST['operating_days'] ?? 'daily',
                    'status' => 'active'
                ];
                
                $success = addSchedule($db, $schedule_data);
                echo json_encode(['success' => $success]);
                break;
                
            case 'edit_schedule':
                $schedule_id = $_POST['schedule_id'] ?? '';
                $schedule_data = [
                    'route_id' => $_POST['route_id'] ?? '',
                    'operator_id' => $_POST['operator_id'] ?? '',
                    'vehicle_id' => $_POST['vehicle_id'] ?? '',
                    'departure_time' => $_POST['departure_time'] ?? '',
                    'arrival_time' => $_POST['arrival_time'] ?? '',
                    'frequency_minutes' => $_POST['frequency_minutes'] ?? 30,
                    'operating_days' => $_POST['operating_days'] ?? 'daily'
                ];
                
                $success = updateSchedule($db, $schedule_id, $schedule_data);
                echo json_encode(['success' => $success]);
                break;
                
            case 'publish_schedule':
                $schedule_id = $_POST['schedule_id'] ?? '';
                $published_by = $_POST['published_by'] ?? 'Admin';
                
                $success = publishToCitizenPortal($db, $schedule_id, $published_by);
                echo json_encode(['success' => $success]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'export_routes':
                $format = $_GET['format'] ?? 'csv';
                exportRouteData($db, $format);
                break;
                
            case 'export_schedules':
                $format = $_GET['format'] ?? 'csv';
                exportScheduleData($db, $format);
                break;
                
            case 'view_route':
                $route_id = $_GET['route_id'] ?? '';
                $route = getRouteById($db, $route_id);
                echo json_encode(['success' => true, 'data' => $route]);
                break;
                
            case 'view_schedule':
                $schedule_id = $_GET['schedule_id'] ?? '';
                $schedule = getScheduleById($db, $schedule_id);
                echo json_encode(['success' => true, 'data' => $schedule]);
                break;
                
            case 'get_operators':
                $operators = getActiveOperators($db);
                echo json_encode(['success' => true, 'data' => $operators]);
                break;
                
            case 'get_vehicles':
                $operator_id = $_GET['operator_id'] ?? '';
                $vehicles = getVehiclesByOperator($db, $operator_id);
                echo json_encode(['success' => true, 'data' => $vehicles]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Route Management Functions
function defineRoute($db, $route_data) {
    $query = "INSERT INTO official_routes (route_id, route_name, route_code, origin, destination, 
              distance_km, estimated_travel_time, fare_amount, waypoints, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([
        $route_data['route_id'],
        $route_data['route_name'],
        $route_data['route_code'],
        $route_data['origin'],
        $route_data['destination'],
        $route_data['distance_km'],
        $route_data['estimated_travel_time'],
        $route_data['fare_amount'],
        $route_data['waypoints'],
        $route_data['status']
    ]);
}

function updateRoute($db, $route_id, $route_data) {
    $query = "UPDATE official_routes SET route_name = ?, route_code = ?, origin = ?, 
              destination = ?, distance_km = ?, estimated_travel_time = ?, fare_amount = ?, 
              waypoints = ? WHERE route_id = ?";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([
        $route_data['route_name'],
        $route_data['route_code'],
        $route_data['origin'],
        $route_data['destination'],
        $route_data['distance_km'],
        $route_data['estimated_travel_time'],
        $route_data['fare_amount'],
        $route_data['waypoints'],
        $route_id
    ]);
}

function getRouteById($db, $route_id) {
    $query = "SELECT * FROM official_routes WHERE route_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$route_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Schedule Management Functions
function addSchedule($db, $schedule_data) {
    $query = "INSERT INTO route_schedules (schedule_id, route_id, operator_id, vehicle_id, 
              departure_time, arrival_time, frequency_minutes, operating_days, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([
        $schedule_data['schedule_id'],
        $schedule_data['route_id'],
        $schedule_data['operator_id'],
        $schedule_data['vehicle_id'],
        $schedule_data['departure_time'],
        $schedule_data['arrival_time'],
        $schedule_data['frequency_minutes'],
        $schedule_data['operating_days'],
        $schedule_data['status']
    ]);
}

function updateSchedule($db, $schedule_id, $schedule_data) {
    $query = "UPDATE route_schedules SET route_id = ?, operator_id = ?, vehicle_id = ?, 
              departure_time = ?, arrival_time = ?, frequency_minutes = ?, operating_days = ? 
              WHERE schedule_id = ?";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([
        $schedule_data['route_id'],
        $schedule_data['operator_id'],
        $schedule_data['vehicle_id'],
        $schedule_data['departure_time'],
        $schedule_data['arrival_time'],
        $schedule_data['frequency_minutes'],
        $schedule_data['operating_days'],
        $schedule_id
    ]);
}

function getScheduleById($db, $schedule_id) {
    $query = "SELECT rs.*, r.route_name, r.route_code, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM route_schedules rs
              JOIN official_routes r ON rs.route_id = r.route_id
              JOIN operators o ON rs.operator_id = o.operator_id
              JOIN vehicles v ON rs.vehicle_id = v.vehicle_id
              WHERE rs.schedule_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$schedule_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper Functions
function getActiveOperators($db) {
    $query = "SELECT operator_id, CONCAT(first_name, ' ', last_name) as operator_name 
              FROM operators WHERE status = 'active' ORDER BY first_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateRouteId($db) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM official_routes WHERE route_id LIKE 'RT-{$year}-%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "RT-{$year}-{$next_id}";
}

function generateScheduleId($db) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM route_schedules WHERE schedule_id LIKE 'SCH-{$year}-%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "SCH-{$year}-{$next_id}";
}

// Export Functions
function exportRouteData($db, $format) {
    $routes = getRoutes($db);
    
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="routes_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            if (!empty($routes)) {
                fputcsv($output, ['Route ID', 'Route Name', 'Code', 'Origin', 'Destination', 'Distance (km)', 'Travel Time (min)', 'Fare', 'Status']);
                foreach ($routes as $route) {
                    fputcsv($output, [
                        $route['route_id'],
                        $route['route_name'],
                        $route['route_code'],
                        $route['origin'],
                        $route['destination'],
                        $route['distance_km'],
                        $route['estimated_travel_time'],
                        $route['fare_amount'],
                        $route['status']
                    ]);
                }
            }
            fclose($output);
            break;
            
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="routes_' . date('Y-m-d') . '.json"');
            echo json_encode($routes, JSON_PRETTY_PRINT);
            break;
    }
    exit;
}

function exportScheduleData($db, $format) {
    $schedules = getRouteSchedules($db);
    
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="schedules_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            if (!empty($schedules)) {
                fputcsv($output, ['Schedule ID', 'Route', 'Operator', 'Vehicle', 'Departure', 'Arrival', 'Frequency', 'Days', 'Status', 'Published']);
                foreach ($schedules as $schedule) {
                    fputcsv($output, [
                        $schedule['schedule_id'],
                        $schedule['route_name'],
                        $schedule['first_name'] . ' ' . $schedule['last_name'],
                        $schedule['plate_number'],
                        $schedule['departure_time'],
                        $schedule['arrival_time'],
                        $schedule['frequency_minutes'],
                        $schedule['operating_days'],
                        $schedule['status'],
                        $schedule['published_to_citizen'] ? 'Yes' : 'No'
                    ]);
                }
            }
            fclose($output);
            break;
            
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="schedules_' . date('Y-m-d') . '.json"');
            echo json_encode($schedules, JSON_PRETTY_PRINT);
            break;
    }
    exit;
}
?>