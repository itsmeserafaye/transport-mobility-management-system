<?php
header('Content-Type: application/json');

try {
    require_once '../../../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'filter') {
        $query = "SELECT cs.*, 
                         CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                         v.plate_number
                  FROM compliance_status cs
                  JOIN operators o ON cs.operator_id = o.operator_id
                  JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($_POST['franchise_status'])) {
            $query .= " AND cs.franchise_status = ?";
            $params[] = $_POST['franchise_status'];
        }
        
        if (!empty($_POST['inspection_status'])) {
            $query .= " AND cs.inspection_status = ?";
            $params[] = $_POST['inspection_status'];
        }
        
        if (!empty($_POST['search'])) {
            $query .= " AND (CONCAT(o.first_name, ' ', o.last_name) LIKE ? OR v.plate_number LIKE ?)";
            $params[] = '%' . $_POST['search'] . '%';
            $params[] = '%' . $_POST['search'] . '%';
        }
        
        $query .= " ORDER BY cs.updated_at DESC LIMIT 50";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $results]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Simple filter ready']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>