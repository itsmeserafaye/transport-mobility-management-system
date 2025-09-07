<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();

    if ($_POST) {
        $vehicle_id = generateVehicleId($conn);
        $data = [
            'vehicle_id' => $vehicle_id,
            'operator_id' => $_POST['operator_id'],
            'plate_number' => $_POST['plate_number'],
            'vehicle_type' => $_POST['vehicle_type'],
            'make' => $_POST['make'],
            'model' => $_POST['model'],
            'year_manufactured' => $_POST['year_manufactured'],
            'engine_number' => $_POST['engine_number'],
            'chassis_number' => $_POST['chassis_number'],
            'seating_capacity' => $_POST['seating_capacity']
        ];
        
        if (addVehicle($conn, $data)) {
            echo json_encode(['success' => true, 'message' => 'Vehicle registered successfully']);
        } else {
            throw new Exception('Failed to register vehicle');
        }
    } else {
        throw new Exception('No POST data received');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>