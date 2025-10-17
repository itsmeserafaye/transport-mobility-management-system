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
                
            case 'assign_application_id':
                // Assign application ID and set processing timeline
                $operator_id = $_POST['operator_id'] ?? '';
                $vehicle_id = $_POST['vehicle_id'] ?? '';
                $application_type = $_POST['application_type'] ?? 'new';
                $route_requested = $_POST['route_requested'] ?? '';
                
                if (!$vehicle_id) {
                    echo json_encode(['success' => false, 'message' => 'Vehicle ID is required']);
                    break;
                }
                
                $application_id = 'FA-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                $query = "INSERT INTO franchise_applications 
                          (application_id, operator_id, vehicle_id, application_type, route_requested, 
                           application_date, status, workflow_stage, processing_timeline) 
                          VALUES (?, ?, ?, ?, ?, CURDATE(), 'submitted', 'initial_review', 30)";
                
                $stmt = $db->prepare($query);
                $success = $stmt->execute([$application_id, $operator_id, $vehicle_id, $application_type, $route_requested]);
                
                echo json_encode(['success' => $success, 'application_id' => $application_id]);
                break;
                
            case 'route_workflow':
                // Route to appropriate workflow and update status
                $application_id = $_POST['application_id'] ?? '';
                $workflow_stage = $_POST['workflow_stage'] ?? '';
                $assigned_to = $_POST['assigned_to'] ?? '';
                
                $query = "UPDATE franchise_applications SET 
                          workflow_stage = ?, 
                          assigned_to = ?,
                          updated_at = CURRENT_TIMESTAMP
                          WHERE application_id = ?";
                
                $stmt = $db->prepare($query);
                $success = $stmt->execute([$workflow_stage, $assigned_to, $application_id]);
                
                // Log workflow history
                if ($success) {
                    $history_query = "INSERT INTO workflow_history 
                                      (history_id, application_id, stage_to, action_taken, processed_by, processing_date) 
                                      VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
                    
                    $history_id = 'WH-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                    $history_stmt = $db->prepare($history_query);
                    $history_stmt->execute([$history_id, $application_id, $workflow_stage, 'Workflow routed', $assigned_to]);
                }
                
                echo json_encode(['success' => $success]);
                break;
                
            case 'update_timeline':
                // Set processing timeline
                $application_id = $_POST['application_id'] ?? '';
                $timeline = $_POST['timeline'] ?? 30;
                
                $query = "UPDATE franchise_applications SET 
                          processing_timeline = ?
                          WHERE application_id = ?";
                
                $stmt = $db->prepare($query);
                $success = $stmt->execute([$timeline, $application_id]);
                
                echo json_encode(['success' => $success]);
                break;
                
            case 'approve_application':
                // Approve application and create franchise
                $application_id = $_POST['application_id'] ?? '';
                
                $db->beginTransaction();
                
                try {
                    // Get application details
                    $app_query = "SELECT * FROM franchise_applications WHERE application_id = ?";
                    $app_stmt = $db->prepare($app_query);
                    $app_stmt->execute([$application_id]);
                    $app = $app_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$app) {
                        throw new Exception('Application not found');
                    }
                    
                    // Update application status
                    $update_query = "UPDATE franchise_applications SET 
                                     status = 'approved',
                                     workflow_stage = 'completed',
                                     updated_at = CURRENT_TIMESTAMP
                                     WHERE application_id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->execute([$application_id]);
                    
                    // Create franchise record
                    $franchise_id = 'FR-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                    $franchise_number = 'FN-' . strtoupper(substr($app['route_requested'], 0, 3)) . '-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                    
                    $franchise_query = "INSERT INTO franchise_records 
                                        (franchise_id, operator_id, vehicle_id, franchise_number, 
                                         issue_date, expiry_date, route_assigned, status) 
                                        VALUES (?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?, 'valid')";
                    $franchise_stmt = $db->prepare($franchise_query);
                    $franchise_stmt->execute([$franchise_id, $app['operator_id'], $app['vehicle_id'], $franchise_number, $app['route_requested']]);
                    
                    // Create lifecycle record
                    $lifecycle_id = 'LC-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                    $lifecycle_query = "INSERT INTO franchise_lifecycle 
                                        (lifecycle_id, franchise_id, operator_id, vehicle_id, 
                                         lifecycle_stage, stage_date, expiry_date, renewal_due_date, 
                                         action_required, processed_by) 
                                        VALUES (?, ?, ?, ?, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 
                                                DATE_SUB(DATE_ADD(CURDATE(), INTERVAL 1 YEAR), INTERVAL 3 MONTH), 'none', 'System')";
                    $lifecycle_stmt = $db->prepare($lifecycle_query);
                    $lifecycle_stmt->execute([$lifecycle_id, $franchise_id, $app['operator_id'], $app['vehicle_id']]);
                    
                    $db->commit();
                    echo json_encode(['success' => true, 'franchise_id' => $franchise_id]);
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
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