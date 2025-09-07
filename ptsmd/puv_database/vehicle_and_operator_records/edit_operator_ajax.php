<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    if ($_POST) {
        $operator_id = $_POST['operator_id'] ?? '';
        
        if (empty($operator_id)) {
            throw new Exception('Operator ID is required');
        }
        
        $data = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'address' => $_POST['address'] ?? '',
            'contact_number' => $_POST['contact_number'] ?? '',
            'license_expiry' => $_POST['license_expiry'] ?? ''
        ];
        
        // Validate required fields
        foreach ($data as $key => $value) {
            if (empty($value)) {
                throw new Exception("Field {$key} is required");
            }
        }
        
        if (updateOperator($conn, $operator_id, $data)) {
            echo json_encode(['success' => true, 'message' => 'Operator updated successfully']);
        } else {
            throw new Exception('Database update failed');
        }
    } else {
        throw new Exception('No POST data received');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>