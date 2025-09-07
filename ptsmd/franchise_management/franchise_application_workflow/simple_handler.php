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
                // Receive franchise applications and route to appropriate workflow
                $query = "SELECT fa.*, 
                                 CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                                 v.plate_number, v.vehicle_type
                          FROM franchise_applications fa
                          JOIN operators o ON fa.operator_id = o.operator_id
                          JOIN vehicles v ON fa.vehicle_id = v.vehicle_id
                          WHERE 1=1";
                
                $params = [];
                
                if (!empty($_POST['status']) && $_POST['status'] !== 'All Status') {
                    $query .= " AND fa.status = ?";
                    $params[] = strtolower($_POST['status']);
                }
                
                if (!empty($_POST['workflow_stage']) && $_POST['workflow_stage'] !== 'All Stages') {
                    $query .= " AND fa.workflow_stage = ?";
                    $params[] = str_replace(' ', '_', strtolower($_POST['workflow_stage']));
                }
                
                if (!empty($_POST['application_type']) && $_POST['application_type'] !== 'All Types') {
                    $query .= " AND fa.application_type = ?";
                    $params[] = strtolower($_POST['application_type']);
                }
                
                if (!empty($_POST['date_from'])) {
                    $query .= " AND fa.application_date >= ?";
                    $params[] = $_POST['date_from'];
                }
                
                $query .= " ORDER BY fa.application_date DESC LIMIT 50";
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $results]);
                break;
                

                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'workflow_status':
                // Get workflow status for dashboards
                $query = "SELECT workflow_stage, COUNT(*) as count, status
                          FROM franchise_applications 
                          GROUP BY workflow_stage, status
                          ORDER BY workflow_stage";
                
                $stmt = $db->prepare($query);
                $stmt->execute();
                $workflow_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'workflow_status' => $workflow_status]);
                break;
                
            case 'processing_timeline':
                // Get processing timeline data
                $query = "SELECT application_id, processing_timeline, 
                                 DATEDIFF(CURDATE(), application_date) as days_elapsed,
                                 (processing_timeline - DATEDIFF(CURDATE(), application_date)) as days_remaining
                          FROM franchise_applications 
                          WHERE status IN ('submitted', 'under_review', 'pending_documents')
                          ORDER BY days_remaining ASC";
                
                $stmt = $db->prepare($query);
                $stmt->execute();
                $timeline_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'timeline_data' => $timeline_data]);
                break;
                
            case 'statistics':
                // Get application statistics
                $stats = [];
                
                // Total applications
                $query = "SELECT COUNT(*) as total FROM franchise_applications";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['total_applications'] = $stmt->fetchColumn();
                
                // Status breakdown
                $query = "SELECT status, COUNT(*) as count FROM franchise_applications GROUP BY status";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['status_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Workflow stage breakdown
                $query = "SELECT workflow_stage, COUNT(*) as count FROM franchise_applications GROUP BY workflow_stage";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $stats['workflow_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'statistics' => $stats]);
                break;
                
            case 'export':
                // Export application data
                $format = $_GET['format'] ?? 'csv';
                
                $query = "SELECT fa.application_id, fa.application_type, fa.route_requested, fa.application_date,
                                 fa.status, fa.workflow_stage, fa.processing_timeline,
                                 CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                                 v.plate_number, v.vehicle_type
                          FROM franchise_applications fa
                          JOIN operators o ON fa.operator_id = o.operator_id
                          JOIN vehicles v ON fa.vehicle_id = v.vehicle_id
                          ORDER BY fa.application_date DESC";
                
                $stmt = $db->prepare($query);
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($format === 'csv') {
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="franchise_applications_' . date('Y-m-d') . '.csv"');
                    
                    $output = fopen('php://output', 'w');
                    if (!empty($data)) {
                        fputcsv($output, array_keys($data[0]));
                        foreach ($data as $row) {
                            fputcsv($output, $row);
                        }
                    }
                    fclose($output);
                } else {
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="franchise_applications_' . date('Y-m-d') . '.json"');
                    echo json_encode($data, JSON_PRETTY_PRINT);
                }
                exit;
                
            default:
                echo json_encode(['success' => true, 'message' => 'Franchise application workflow ready']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>