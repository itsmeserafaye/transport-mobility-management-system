<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $violation_id = $_POST['violation_id'] ?? '';
    $operator_id = $_POST['operator_id'] ?? '';
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $violation_category = $_POST['violation_category'] ?? '';
    $violation_type = $_POST['violation_type'] ?? '';
    $violation_date = $_POST['violation_date'] ?? '';
    $location = $_POST['location'] ?? '';
    $fine_amount = $_POST['fine_amount'] ?? 0;
    $ticket_number = $_POST['ticket_number'] ?? '';
    
    if (empty($violation_id) || empty($operator_id) || empty($vehicle_id) || empty($violation_type) || empty($violation_date)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        exit;
    }
    
    $query = "UPDATE violation_history SET 
              operator_id = :operator_id, vehicle_id = :vehicle_id, violation_category = :violation_category,
              violation_type = :violation_type, violation_date = :violation_date, location = :location, 
              fine_amount = :fine_amount, ticket_number = :ticket_number
              WHERE violation_id = :violation_id";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        'operator_id' => $operator_id,
        'vehicle_id' => $vehicle_id,
        'violation_category' => $violation_category,
        'violation_type' => $violation_type,
        'violation_date' => $violation_date,
        'location' => $location,
        'fine_amount' => $fine_amount,
        'ticket_number' => $ticket_number,
        'violation_id' => $violation_id
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Violation record updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update violation record']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>