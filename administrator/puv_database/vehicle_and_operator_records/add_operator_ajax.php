<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if ($_POST) {
    try {
        $operator_id = generateOperatorId($conn);
        $data = [
            'operator_id' => $operator_id,
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'address' => $_POST['address'],
            'contact_number' => $_POST['contact_number'],
            'license_number' => $_POST['license_number'],
            'license_expiry' => $_POST['license_expiry']
        ];
        
        if (addOperator($conn, $data)) {
            echo json_encode(['success' => true, 'message' => 'Operator added successfully', 'operator_id' => $operator_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add operator']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>