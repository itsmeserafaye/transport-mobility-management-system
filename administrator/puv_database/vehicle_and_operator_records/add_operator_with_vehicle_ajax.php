<?php
header('Content-Type: application/json');

try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $conn->beginTransaction();
    
    // Generate IDs
    $operator_id = generateOperatorId($conn);
    $vehicle_id = generateVehicleId($conn);
    
    // Insert operator
    $operator_query = "INSERT INTO operators (operator_id, first_name, last_name, address, contact_number, license_number, license_expiry) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $operator_stmt = $conn->prepare($operator_query);
    $operator_stmt->execute([
        $operator_id,
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['address'],
        $_POST['contact_number'],
        $_POST['license_number'],
        $_POST['license_expiry']
    ]);
    
    // Insert vehicle
    $vehicle_query = "INSERT INTO vehicles (vehicle_id, operator_id, plate_number, vehicle_type, make, model, year_manufactured, engine_number, chassis_number, color, seating_capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $vehicle_stmt = $conn->prepare($vehicle_query);
    $vehicle_stmt->execute([
        $vehicle_id,
        $operator_id,
        $_POST['plate_number'],
        $_POST['vehicle_type'],
        $_POST['make'],
        $_POST['model'],
        $_POST['year_manufactured'],
        $_POST['engine_number'],
        $_POST['chassis_number'],
        $_POST['color'],
        $_POST['seating_capacity']
    ]);
    
    // Create compliance status
    $compliance_id = 'CS-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $compliance_query = "INSERT INTO compliance_status (compliance_id, operator_id, vehicle_id, franchise_status, inspection_status, violation_count, compliance_score) VALUES (?, ?, ?, 'pending', 'pending', 0, 75.00)";
    $compliance_stmt = $conn->prepare($compliance_query);
    $compliance_stmt->execute([$compliance_id, $operator_id, $vehicle_id]);
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Operator and vehicle added successfully']);
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>