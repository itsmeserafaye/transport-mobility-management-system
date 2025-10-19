<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $operatorId = $_GET['id'] ?? '';
    
    if (empty($operatorId)) {
        throw new Exception('Operator ID is required');
    }
    
    $query = "SELECT * FROM operators WHERE operator_id = ? AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$operatorId]);
    $operator = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$operator) {
        throw new Exception('Operator not found');
    }
    
    echo json_encode(['success' => true, 'operator' => $operator]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>