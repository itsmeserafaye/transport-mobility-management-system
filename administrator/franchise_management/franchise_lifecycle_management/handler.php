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
                
            case 'process_renewal':
                $lifecycle_id = $_POST['lifecycle_id'] ?? '';
                $renewal_date = $_POST['renewal_date'] ?? '';
                $processed_by = $_POST['processed_by'] ?? 'Admin';
                $remarks = $_POST['remarks'] ?? '';
                
                $success = processRenewal($db, $lifecycle_id, $renewal_date, $processed_by, $remarks);
                echo json_encode(['success' => $success]);
                break;
                
            case 'amend_franchise':
                $lifecycle_id = $_POST['lifecycle_id'] ?? '';
                $amendment_type = $_POST['amendment_type'] ?? '';
                $reason = $_POST['reason'] ?? '';
                $processed_by = $_POST['processed_by'] ?? 'Admin';
                
                $success = amendFranchise($db, $lifecycle_id, $amendment_type, $reason, $processed_by);
                echo json_encode(['success' => $success]);
                break;
                
            case 'suspend_franchise':
                $lifecycle_id = $_POST['lifecycle_id'] ?? '';
                $reason = $_POST['reason'] ?? '';
                $processed_by = $_POST['processed_by'] ?? 'Admin';
                
                $success = suspendFranchise($db, $lifecycle_id, $reason, $processed_by);
                echo json_encode(['success' => $success]);
                break;
                
            case 'revoke_franchise':
                $lifecycle_id = $_POST['lifecycle_id'] ?? '';
                $reason = $_POST['reason'] ?? '';
                $processed_by = $_POST['processed_by'] ?? 'Admin';
                
                $success = revokeFranchise($db, $lifecycle_id, $reason, $processed_by);
                echo json_encode(['success' => $success]);
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

function processRenewal($db, $lifecycle_id, $renewal_date, $processed_by, $remarks = '') {
    try {
        $db->beginTransaction();
        
        // Get lifecycle record
        $lifecycle = getLifecycleById($db, $lifecycle_id);
        if (!$lifecycle) {
            throw new Exception('Lifecycle record not found');
        }
        
        // Calculate new expiry date (add 1 year from renewal date)
        $new_expiry = date('Y-m-d', strtotime($renewal_date . ' +1 year'));
        $new_renewal_due = date('Y-m-d', strtotime($new_expiry . ' -30 days'));
        
        // Update franchise record
        $query1 = "UPDATE franchise_records SET status = 'valid', expiry_date = ? WHERE franchise_id = ?";
        $stmt1 = $db->prepare($query1);
        $stmt1->execute([$new_expiry, $lifecycle['franchise_id']]);
        
        // Update lifecycle
        $query2 = "UPDATE franchise_lifecycle SET lifecycle_stage = 'active', action_required = 'none',
                   expiry_date = ?, renewal_due_date = ?, processed_by = ?
                   WHERE lifecycle_id = ?";
        $stmt2 = $db->prepare($query2);
        $stmt2->execute([$new_expiry, $new_renewal_due, $processed_by, $lifecycle_id]);
        
        // Add lifecycle action record - simplified without generateActionId
        $action_id = 'LA-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $action_reason = $remarks ? 'Franchise renewed: ' . $remarks : 'Franchise renewed';
        $query3 = "INSERT INTO lifecycle_actions (action_id, lifecycle_id, action_type, action_date, reason, processed_by, effective_date) 
                   VALUES (?, ?, 'renew', CURDATE(), ?, ?, ?)";
        $stmt3 = $db->prepare($query3);
        $stmt3->execute([$action_id, $lifecycle_id, $action_reason, $processed_by, $renewal_date]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        error_log('Renewal error: ' . $e->getMessage());
        $db->rollback();
        return false;
    }
}

function amendFranchise($db, $lifecycle_id, $amendment_type, $reason, $processed_by) {
    try {
        $db->beginTransaction();
        
        // Update lifecycle stage
        $query = "UPDATE franchise_lifecycle SET lifecycle_stage = 'amendment', 
                  action_required = 'document_update', processed_by = ?, updated_at = NOW()
                  WHERE lifecycle_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$processed_by, $lifecycle_id]);
        
        // Add lifecycle action record
        $action_id = 'LA-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $query2 = "INSERT INTO lifecycle_actions (action_id, lifecycle_id, action_type, action_date, reason, processed_by) 
                   VALUES (?, ?, 'amend', CURDATE(), ?, ?)";
        $stmt2 = $db->prepare($query2);
        $stmt2->execute([$action_id, $lifecycle_id, $reason, $processed_by]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

function suspendFranchise($db, $lifecycle_id, $reason, $processed_by) {
    try {
        $db->beginTransaction();
        
        $lifecycle = getLifecycleById($db, $lifecycle_id);
        
        // Update franchise status
        $query1 = "UPDATE franchise_records SET status = 'suspended' WHERE franchise_id = ?";
        $stmt1 = $db->prepare($query1);
        $stmt1->execute([$lifecycle['franchise_id']]);
        
        // Update lifecycle
        $query2 = "UPDATE franchise_lifecycle SET lifecycle_stage = 'suspended', 
                   action_required = 'compliance_check', processed_by = ?, updated_at = NOW()
                   WHERE lifecycle_id = ?";
        $stmt2 = $db->prepare($query2);
        $stmt2->execute([$processed_by, $lifecycle_id]);
        
        // Add lifecycle action record
        $action_id = 'LA-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $query3 = "INSERT INTO lifecycle_actions (action_id, lifecycle_id, action_type, action_date, reason, processed_by) 
                   VALUES (?, ?, 'suspend', CURDATE(), ?, ?)";
        $stmt3 = $db->prepare($query3);
        $stmt3->execute([$action_id, $lifecycle_id, $reason, $processed_by]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

function revokeFranchise($db, $lifecycle_id, $reason, $processed_by) {
    try {
        $db->beginTransaction();
        
        $lifecycle = getLifecycleById($db, $lifecycle_id);
        
        // Update franchise status
        $query1 = "UPDATE franchise_records SET status = 'revoked' WHERE franchise_id = ?";
        $stmt1 = $db->prepare($query1);
        $stmt1->execute([$lifecycle['franchise_id']]);
        
        // Update lifecycle
        $query2 = "UPDATE franchise_lifecycle SET lifecycle_stage = 'revocation', 
                   action_required = 'none', processed_by = ?, updated_at = NOW()
                   WHERE lifecycle_id = ?";
        $stmt2 = $db->prepare($query2);
        $stmt2->execute([$processed_by, $lifecycle_id]);
        
        // Add lifecycle action record
        $action_id = 'LA-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $query3 = "INSERT INTO lifecycle_actions (action_id, lifecycle_id, action_type, action_date, reason, processed_by) 
                   VALUES (?, ?, 'revoke', CURDATE(), ?, ?)";
        $stmt3 = $db->prepare($query3);
        $stmt3->execute([$action_id, $lifecycle_id, $reason, $processed_by]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
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