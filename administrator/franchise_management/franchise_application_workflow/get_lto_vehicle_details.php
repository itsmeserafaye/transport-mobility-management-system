<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $ltoId = $_GET['id'] ?? '';
    
    if (empty($ltoId)) {
        throw new Exception('LTO Registration ID is required');
    }
    
    $query = "SELECT * FROM lto_registrations WHERE lto_registration_id = ? AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$ltoId]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        throw new Exception('LTO registration not found');
    }
    
    echo json_encode(['success' => true, 'vehicle' => $vehicle]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>