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
    $settlement_status = $_POST['settlement_status'] ?? 'unpaid';
    $description = $_POST['description'] ?? '';
    $ticket_number = $_POST['ticket_number'] ?? '';
    
    if (empty($violation_id) || empty($operator_id) || empty($violation_type) || empty($violation_date)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        exit;
    }
    
    // Get current settlement status to check if it's changing to 'paid'
    $check_query = "SELECT settlement_status FROM violation_records WHERE violation_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('s', $violation_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $current_violation = $result->fetch_assoc();
    $previous_status = $current_violation['settlement_status'] ?? 'unpaid';
    
    $query = "UPDATE violation_records SET 
              operator_id = ?, violation_type = ?, violation_date = ?, location = ?, 
              fine_amount = ?, status = ?, settlement_status = ?, description = ?, ticket_number = ?, updated_at = NOW() 
              WHERE violation_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isssdsssss', $operator_id, $violation_type, $violation_date, $location, $fine_amount, $status, $settlement_status, $description, $ticket_number, $violation_id);
    
    if ($stmt->execute()) {
        // If settlement status changed to 'paid', automatically record to revenue_integration
        if ($previous_status !== 'paid' && $settlement_status === 'paid') {
            $revenue_query = "INSERT INTO revenue_integration (violation_id, operator_id, collection_amount, collection_date, payment_method, collection_status, created_at) 
                             VALUES (?, ?, ?, NOW(), 'Manual Payment', 'completed', NOW())";
            $revenue_stmt = $conn->prepare($revenue_query);
            $revenue_stmt->bind_param('sid', $violation_id, $operator_id, $fine_amount);
            $revenue_stmt->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Violation record updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update violation record']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>