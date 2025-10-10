<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    $conn->beginTransaction();
    
    // Insert operator
    $stmt = $conn->prepare("INSERT INTO operators (first_name, last_name, address, contact_number, license_number, license_expiry, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['address'],
        $_POST['contact_number'],
        $_POST['license_number'],
        $_POST['license_expiry']
    ]);
    
    $operator_id = $conn->lastInsertId();
    
    // Insert vehicle
    $stmt = $conn->prepare("INSERT INTO vehicles (operator_id, plate_number, vehicle_type, make, model, year_manufactured, engine_number, chassis_number, seating_capacity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        $operator_id,
        $_POST['plate_number'],
        $_POST['vehicle_type'],
        $_POST['make'],
        $_POST['model'],
        $_POST['year_manufactured'],
        $_POST['engine_number'],
        $_POST['chassis_number'],
        $_POST['seating_capacity']
    ]);
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Operator and vehicle added successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>