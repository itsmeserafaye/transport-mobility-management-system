<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get unique operators from LTO registrations
    $query = "SELECT DISTINCT 
                CONCAT(owner_first_name, ' ', owner_last_name) as owner_name,
                license_number,
                owner_address,
                lto_registration_id
              FROM lto_registrations 
              WHERE status = 'active' 
              AND owner_first_name IS NOT NULL 
              AND owner_last_name IS NOT NULL
              ORDER BY owner_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $operators = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'operators' => $operators
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading operators: ' . $e->getMessage()
    ]);
}
?>