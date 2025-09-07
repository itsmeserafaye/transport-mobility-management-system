<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=Invalid operator ID');
    exit;
}

$operator_id = intval($_GET['id']);

$success = deleteOperator($conn, $operator_id);

if ($success) {
    header('Location: index.php?message=Operator deleted successfully');
} else {
    header('Location: index.php?error=Failed to delete operator');
}

function deleteOperator($conn, $operator_id) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Delete related records first
        $queries = [
            "DELETE FROM violation_history WHERE operator_id = ?",
            "DELETE FROM vehicle_registrations WHERE operator_id = ?",
            "DELETE FROM inspection_records WHERE operator_id = ?",
            "DELETE FROM operators WHERE operator_id = ?"
        ];
        
        foreach ($queries as $query) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $operator_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute query: ' . $query);
            }
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}
?>