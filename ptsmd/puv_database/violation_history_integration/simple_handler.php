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
                // Link violations to operators and vehicles
                $query = "SELECT vh.*, 
                                 CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                                 v.plate_number, v.vehicle_type,
                                 va.total_violations, va.risk_level
                          FROM violation_history vh
                          JOIN operators o ON vh.operator_id = o.operator_id
                          JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
                          LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id
                          WHERE 1=1";
                
                $params = [];
                
                if (!empty($_POST['settlement_status']) && $_POST['settlement_status'] !== 'All Settlement Status') {
                    $query .= " AND vh.settlement_status = ?";
                    $params[] = strtolower($_POST['settlement_status']);
                }
                
                if (!empty($_POST['violation_type']) && $_POST['violation_type'] !== 'All Violation Types') {
                    $query .= " AND vh.violation_type = ?";
                    $params[] = $_POST['violation_type'];
                }
                
                if (!empty($_POST['date_from'])) {
                    $query .= " AND vh.violation_date >= ?";
                    $params[] = $_POST['date_from'];
                }
                
                if (!empty($_POST['date_to'])) {
                    $query .= " AND vh.violation_date <= ?";
                    $params[] = $_POST['date_to'];
                }
                
                $query .= " ORDER BY vh.violation_date DESC LIMIT 50";
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $results]);
                break;
                
            case 'link_violations':
                // Integrate violation records from ticketing system
                $query = "UPDATE violation_history vh 
                          JOIN operators o ON vh.operator_id = o.operator_id 
                          JOIN vehicles v ON vh.vehicle_id = v.vehicle_id 
                          SET vh.settlement_status = 'linked' 
                          WHERE vh.settlement_status = 'unlinked'";
                
                $stmt = $db->prepare($query);
                $success = $stmt->execute();
                $linked_count = $stmt->rowCount();
                
                echo json_encode(['success' => $success, 'linked' => $linked_count]);
                break;
                
            case 'analytics':
                // Generate violation analytics
                $query = "SELECT 
                            COUNT(*) as total_violations,
                            SUM(CASE WHEN settlement_status = 'paid' THEN 1 ELSE 0 END) as paid_violations,
                            SUM(CASE WHEN settlement_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_violations,
                            AVG(fine_amount) as avg_fine,
                            violation_type,
                            COUNT(*) as type_count
                          FROM violation_history 
                          GROUP BY violation_type
                          ORDER BY type_count DESC";
                
                $stmt = $db->prepare($query);
                $stmt->execute();
                $analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Track violation trends
                $trends_query = "SELECT DATE_FORMAT(violation_date, '%Y-%m') as month,
                                        COUNT(*) as violations,
                                        SUM(fine_amount) as total_fines
                                 FROM violation_history 
                                 WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                                 GROUP BY month 
                                 ORDER BY month";
                
                $trends_stmt = $db->prepare($trends_query);
                $trends_stmt->execute();
                $trends = $trends_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'analytics' => $analytics, 'trends' => $trends]);
                break;
                
            case 'update_settlement':
                $violation_id = $_POST['violation_id'] ?? '';
                $status = $_POST['status'] ?? '';
                $settlement_date = $_POST['settlement_date'] ?? null;
                
                $query = "UPDATE violation_history SET 
                          settlement_status = ?, 
                          settlement_date = ?
                          WHERE violation_id = ?";
                
                $stmt = $db->prepare($query);
                $success = $stmt->execute([$status, $settlement_date, $violation_id]);
                
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
                // Export violation data
                $format = $_GET['format'] ?? 'csv';
                
                $query = "SELECT vh.violation_id, vh.violation_type, vh.violation_date, vh.fine_amount, vh.settlement_status,
                                 CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                                 v.plate_number, v.vehicle_type
                          FROM violation_history vh
                          JOIN operators o ON vh.operator_id = o.operator_id
                          JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
                          ORDER BY vh.violation_date DESC";
                
                $stmt = $db->prepare($query);
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($format === 'csv') {
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="violation_history_' . date('Y-m-d') . '.csv"');
                    
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
                    header('Content-Disposition: attachment; filename="violation_history_' . date('Y-m-d') . '.json"');
                    echo json_encode($data, JSON_PRETTY_PRINT);
                }
                exit;
                
            case 'summary':
                // Generate violation summaries
                $operator_id = $_GET['operator_id'] ?? null;
                
                $query = "SELECT 
                            COUNT(*) as total_violations,
                            SUM(CASE WHEN settlement_status = 'paid' THEN 1 ELSE 0 END) as paid_violations,
                            SUM(fine_amount) as total_fines,
                            MAX(violation_date) as last_violation_date
                          FROM violation_history";
                
                $params = [];
                if ($operator_id) {
                    $query .= " WHERE operator_id = ?";
                    $params[] = $operator_id;
                }
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'summary' => $summary]);
                break;
                
            default:
                echo json_encode(['success' => true, 'message' => 'Violation history integration ready']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>