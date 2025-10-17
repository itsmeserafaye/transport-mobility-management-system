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
    
    $operator_id = $_POST['operator_id'] ?? '';
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $violation_category = $_POST['violation_category'] ?? '';
    $violation_type = $_POST['violation_type'] ?? '';
    $violation_date = $_POST['violation_date'] ?? '';
    $location = $_POST['location'] ?? '';
    $fine_amount = $_POST['fine_amount'] ?? 0;
    $ticket_number = $_POST['ticket_number'] ?? '';
    
    if (empty($operator_id) || empty($vehicle_id) || empty($violation_type) || empty($violation_date)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        exit;
    }
    
    // Generate violation ID
    $violation_id = generateViolationId($conn);
    
    $query = "INSERT INTO violation_history (violation_id, operator_id, vehicle_id, violation_type, violation_category, violation_date, fine_amount, settlement_status, location, ticket_number) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'unpaid', ?, ?)";
    
    $stmt = $conn->prepare($query);
    $success = $stmt->execute([
        $violation_id,
        $operator_id,
        $vehicle_id,
        $violation_type,
        $violation_category,
        $violation_date,
        $fine_amount,
        $location,
        $ticket_number
    ]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Violation record added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add violation record']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>