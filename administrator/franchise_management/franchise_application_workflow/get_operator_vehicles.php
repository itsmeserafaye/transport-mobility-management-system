<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if (isset($_GET['operator_id'])) {
    $operator_id = $_GET['operator_id'];
    
    try {
        $query = "SELECT vehicle_id, plate_number, vehicle_type, make, model, status 
                  FROM vehicles 
                  WHERE operator_id = :operator_id 
                  ORDER BY plate_number";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':operator_id', $operator_id);
        $stmt->execute();
        
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'vehicles' => $vehicles
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Operator ID not provided'
    ]);
}
?>