<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $violation_id = $_POST['violation_id'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $remarks = $_POST['remarks'] ?? '';
    
    if (empty($violation_id)) {
        echo json_encode(['success' => false, 'message' => 'Violation ID is required']);
        exit;
    }
    
    // Get violation details
    $violation_query = "SELECT * FROM violation_history WHERE violation_id = :violation_id";
    $violation_stmt = $conn->prepare($violation_query);
    $violation_stmt->execute(['violation_id' => $violation_id]);
    $violation = $violation_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$violation) {
        echo json_encode(['success' => false, 'message' => 'Violation not found']);
        exit;
    }
    
    // Check if already paid
    if ($violation['settlement_status'] === 'paid') {
        echo json_encode(['success' => false, 'message' => 'Violation is already marked as paid']);
        exit;
    }
    
    $conn->beginTransaction();
    
    // Create revenue integration table if needed
    createRevenueIntegrationTable($conn);
    
    // Update settlement status
    $update_query = "UPDATE violation_history SET settlement_status = 'paid' WHERE violation_id = :violation_id";
    $update_stmt = $conn->prepare($update_query);
    $update_result = $update_stmt->execute(['violation_id' => $violation_id]);
    
    if (!$update_result) {
        throw new Exception('Failed to update settlement status');
    }
    
    // Create revenue integration record
    $revenue_id = generateRevenueId($conn);
    $revenue_query = "INSERT INTO revenue_integration 
                     (revenue_id, violation_id, operator_id, collection_amount, collection_date, 
                      payment_method, collection_status, remarks, created_at) 
                     VALUES (:revenue_id, :violation_id, :operator_id, :collection_amount, NOW(), 
                             :payment_method, 'completed', :remarks, NOW())";
    
    $revenue_stmt = $conn->prepare($revenue_query);
    $revenue_result = $revenue_stmt->execute([
        'revenue_id' => $revenue_id,
        'violation_id' => $violation_id,
        'operator_id' => $violation['operator_id'],
        'collection_amount' => $violation['fine_amount'],
        'payment_method' => $payment_method,
        'remarks' => $remarks
    ]);
    
    if (!$revenue_result) {
        throw new Exception('Failed to create revenue record');
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Settlement status updated and revenue recorded successfully']);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function generateRevenueId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM revenue_integration WHERE revenue_id LIKE 'REV-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 4, '0', STR_PAD_LEFT);
    return "REV-{$year}-{$next_id}";
}
?>