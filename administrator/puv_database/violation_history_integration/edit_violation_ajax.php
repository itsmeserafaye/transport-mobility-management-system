<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $violation_id = $_POST['violation_id'] ?? '';
    $operator_id = $_POST['operator_id'] ?? '';
    $violation_type = $_POST['violation_type'] ?? '';
    $violation_date = $_POST['violation_date'] ?? '';
    $location = $_POST['location'] ?? '';
    $fine_amount = $_POST['fine_amount'] ?? 0;
    $status = $_POST['status'] ?? 'pending';
    $description = $_POST['description'] ?? '';
    
    if (empty($violation_id) || empty($operator_id) || empty($violation_type) || empty($violation_date)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        exit;
    }
    
    $query = "UPDATE violation_history SET 
              operator_id = ?, violation_type = ?, violation_date = ?, location = ?, 
              fine_amount = ?, status = ?, description = ?, updated_at = NOW() 
              WHERE violation_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isssdssi', $operator_id, $violation_type, $violation_date, $location, $fine_amount, $status, $description, $violation_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Violation updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update violation']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>