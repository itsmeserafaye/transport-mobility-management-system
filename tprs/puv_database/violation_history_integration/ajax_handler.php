<?php
header('Content-Type: application/json');

try {
    require_once '../../../config/database.php';
    require_once 'functions.php';
    
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Setup failed: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'filter':
            try {
                $filters = [
                    'operator_id' => $_POST['operator_id'] ?? '',
                    'settlement_status' => $_POST['settlement_status'] ?? '',
                    'date_from' => $_POST['date_from'] ?? '',
                    'date_to' => $_POST['date_to'] ?? '',
                    'violation_type' => $_POST['violation_type'] ?? ''
                ];
                
                $results = linkViolationsToOperators($db, $filters);
                echo json_encode(['success' => true, 'data' => $results]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Filter error: ' . $e->getMessage()]);
            }
            break;
            
        case 'update_settlement':
            try {
                $violation_id = $_POST['violation_id'] ?? '';
                $status = $_POST['status'] ?? '';
                $settlement_date = $_POST['settlement_date'] ?? null;
                
                $success = updateSettlementStatus($db, $violation_id, $status, $settlement_date);
                echo json_encode(['success' => $success]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Update error: ' . $e->getMessage()]);
            }
            break;
            
        case 'sync_external':
            try {
                $external_data = json_decode($_POST['external_data'], true);
                $result = syncWithTicketingSystem($db, $external_data);
                echo json_encode(['success' => true, 'data' => $result]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Sync error: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid POST action: ' . $action]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'statistics':
            try {
                $stats = getViolationStatistics($db);
                echo json_encode(['success' => true, 'data' => $stats]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Statistics error: ' . $e->getMessage()]);
            }
            break;
            
        case 'trends':
            try {
                $period = $_GET['period'] ?? 'monthly';
                $trends = trackViolationTrends($db, $period);
                echo json_encode(['success' => true, 'data' => $trends]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Trends error: ' . $e->getMessage()]);
            }
            break;
            
        case 'summary':
            try {
                $operator_id = $_GET['operator_id'] ?? null;
                $summary = generateViolationSummary($db, $operator_id);
                echo json_encode(['success' => true, 'data' => $summary]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Summary error: ' . $e->getMessage()]);
            }
            break;
            
        case 'export':
            try {
                $format = $_GET['format'] ?? 'csv';
                $filters = $_GET;
                unset($filters['action'], $filters['format']);
                exportViolationData($db, $format, $filters);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Export error: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid GET action: ' . $action]);
    }
}
?>