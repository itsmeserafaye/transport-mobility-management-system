<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $operatorName = $_GET['operator_name'] ?? '';
    
    if (empty($operatorName)) {
        throw new Exception('Operator name is required');
    }
    
    // Get operator details and their vehicles
    $query = "SELECT 
                lto_registration_id,
                CONCAT(owner_first_name, ' ', owner_last_name) as owner_name,
                license_number,
                owner_address,
                plate_number,
                make,
                model,
                year_model,
                vehicle_type,
                engine_number,
                chassis_number,
                or_number,
                cr_number
              FROM lto_registrations 
              WHERE CONCAT(owner_first_name, ' ', owner_last_name) = ? 
              AND status = 'active'
              ORDER BY plate_number";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$operatorName]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        throw new Exception('No vehicles found for this operator');
    }
    
    // First result contains operator info
    $operator = [
        'lto_registration_id' => $results[0]['lto_registration_id'],
        'owner_name' => $results[0]['owner_name'],
        'license_number' => $results[0]['license_number'],
        'owner_address' => $results[0]['owner_address']
    ];
    
    // All results are vehicles
    $vehicles = $results;
    
    echo json_encode([
        'success' => true,
        'operator' => $operator,
        'vehicles' => $vehicles
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>