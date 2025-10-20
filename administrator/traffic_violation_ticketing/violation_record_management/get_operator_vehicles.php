<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

$operator_id = $_GET['operator_id'] ?? '';

if ($operator_id) {
    $query = "SELECT DISTINCT v.vehicle_id, v.plate_number, v.vehicle_type 
              FROM vehicles v
              JOIN franchise_records fr ON v.vehicle_id = fr.vehicle_id AND v.operator_id = fr.operator_id
              WHERE v.operator_id = ? AND fr.status = 'valid'
              ORDER BY v.plate_number";
    $stmt = $conn->prepare($query);
    $stmt->execute([$operator_id]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'vehicles' => $vehicles]);
} else {
    echo json_encode(['success' => false, 'message' => 'Operator ID required']);
}
?>