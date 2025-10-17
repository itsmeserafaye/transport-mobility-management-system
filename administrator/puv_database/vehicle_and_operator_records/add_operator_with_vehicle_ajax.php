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
    
    // Generate IDs
    $operator_id = generateOperatorId($conn);
    $vehicle_id = generateVehicleId($conn);
    
    // Insert operator
    $operator_data = [
        'operator_id' => $operator_id,
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'address' => $_POST['address'],
        'contact_number' => $_POST['contact_number'],
        'license_number' => $_POST['license_number'],
        'license_expiry' => $_POST['license_expiry']
    ];
    
    if (!addOperator($conn, $operator_data)) {
        throw new Exception('Failed to add operator');
    }
    
    // Insert vehicle
    $vehicle_data = [
        'vehicle_id' => $vehicle_id,
        'operator_id' => $operator_id,
        'plate_number' => $_POST['plate_number'],
        'vehicle_type' => $_POST['vehicle_type'],
        'make' => $_POST['make'],
        'model' => $_POST['model'],
        'year_manufactured' => $_POST['year_manufactured'],
        'engine_number' => $_POST['engine_number'],
        'chassis_number' => $_POST['chassis_number'],
        'seating_capacity' => $_POST['seating_capacity']
    ];
    
    if (!addVehicle($conn, $vehicle_data)) {
        throw new Exception('Failed to add vehicle');
    }
    
    // Create compliance status record
    $compliance_id = 'CS-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $compliance_query = "INSERT INTO compliance_status (compliance_id, operator_id, vehicle_id, franchise_status, inspection_status, violation_count, compliance_score) VALUES (?, ?, ?, 'pending', 'pending', 0, 75.00)";
    $compliance_stmt = $conn->prepare($compliance_query);
    if (!$compliance_stmt->execute([$compliance_id, $operator_id, $vehicle_id])) {
        throw new Exception('Failed to create compliance status');
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Operator, vehicle, and compliance status added successfully', 'operator_id' => $operator_id, 'vehicle_id' => $vehicle_id]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>