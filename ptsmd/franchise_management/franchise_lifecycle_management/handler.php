<?php
header('Content-Type: application/json');

try {
    require_once '../../../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'filter':
                $filters = [
                    'stage' => $_POST['stage'] ?? '',
                    'action_required' => $_POST['action_required'] ?? '',
                    'expiry_date' => $_POST['expiry_date'] ?? '',
                    'renewal_due' => $_POST['renewal_due'] ?? ''
                ];
                
                $results = getFilteredLifecycle($db, $filters);
                echo json_encode(['success' => true, 'data' => $results]);
                break;
                

                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'export':
                $format = $_GET['format'] ?? 'csv';
                $filters = $_GET;
                unset($filters['action'], $filters['format']);
                
                exportLifecycleData($db, $format, $filters);
                break;
                
            case 'view':
                $lifecycle_id = $_GET['lifecycle_id'] ?? '';
                $lifecycle = getLifecycleById($db, $lifecycle_id);
                echo json_encode(['success' => true, 'data' => $lifecycle]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Franchise Lifecycle Management Functions
function getFilteredLifecycle($db, $filters = []) {
    $query = "SELECT fl.*, fr.franchise_number, fr.route_assigned, fr.status as franchise_status,
                     o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM franchise_lifecycle fl
              JOIN franchise_records fr ON fl.franchise_id = fr.franchise_id
              JOIN operators o ON fl.operator_id = o.operator_id
              JOIN vehicles v ON fl.vehicle_id = v.vehicle_id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['stage']) && $filters['stage'] !== 'All Stages') {
        $query .= " AND fl.lifecycle_stage = ?";
        $params[] = $filters['stage'];
    }
    
    if (!empty($filters['action_required']) && $filters['action_required'] !== 'Action Required') {
        $query .= " AND fl.action_required = ?";
        $params[] = $filters['action_required'];
    }
    
    if (!empty($filters['expiry_date'])) {
        $query .= " AND fl.expiry_date <= ?";
        $params[] = $filters['expiry_date'];
    }
    
    if (!empty($filters['renewal_due'])) {
        $query .= " AND fl.renewal_due_date <= ?";
        $params[] = $filters['renewal_due'];
    }
    
    $query .= " ORDER BY fl.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



function getLifecycleById($db, $lifecycle_id) {
    $query = "SELECT fl.*, fr.franchise_number, fr.franchise_id, fr.route_assigned, fr.status as franchise_status,
                     o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM franchise_lifecycle fl
              JOIN franchise_records fr ON fl.franchise_id = fr.franchise_id
              JOIN operators o ON fl.operator_id = o.operator_id
              JOIN vehicles v ON fl.vehicle_id = v.vehicle_id
              WHERE fl.lifecycle_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$lifecycle_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function exportLifecycleData($db, $format, $filters = []) {
    $data = getFilteredLifecycle($db, $filters);
    
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="franchise_lifecycle_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            if (!empty($data)) {
                fputcsv($output, ['Lifecycle ID', 'Franchise Number', 'Operator', 'Vehicle', 'Route', 'Stage', 'Action Required', 'Expiry Date', 'Renewal Due']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['lifecycle_id'],
                        $row['franchise_number'],
                        $row['first_name'] . ' ' . $row['last_name'],
                        $row['plate_number'] . ' - ' . $row['vehicle_type'],
                        $row['route_assigned'],
                        $row['lifecycle_stage'],
                        $row['action_required'],
                        $row['expiry_date'],
                        $row['renewal_due_date']
                    ]);
                }
            }
            fclose($output);
            break;
            
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="franchise_lifecycle_' . date('Y-m-d') . '.json"');
            echo json_encode($data, JSON_PRETTY_PRINT);
            break;
    }
    exit;
}


?>