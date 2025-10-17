<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=Invalid operator ID');
    exit;
}

$operator_id = intval($_GET['id']);

$result = deleteOperator($conn, $operator_id);

if ($result['success']) {
    header('Location: index.php?message=Operator deleted successfully');
} else {
    header('Location: index.php?error=' . urlencode($result['error']));
}

function deleteOperator($conn, $operator_id) {
    try {
        // Check if operator exists
        $checkStmt = $conn->prepare("SELECT operator_id FROM operators WHERE operator_id = ?");
        $checkStmt->bindParam(1, $operator_id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() == 0) {
            return ['success' => false, 'error' => "Operator ID $operator_id not found"];
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        // Get vehicle IDs for this operator
        $vehicleStmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE operator_id = ?");
        $vehicleStmt->bindParam(1, $operator_id, PDO::PARAM_INT);
        $vehicleStmt->execute();
        $vehicleIds = $vehicleStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Delete maintenance predictions for vehicles owned by this operator
        if (!empty($vehicleIds)) {
            $placeholders = str_repeat('?,', count($vehicleIds) - 1) . '?';
            $stmt = $conn->prepare("DELETE FROM maintenance_predictions WHERE vehicle_id IN ($placeholders)");
            $stmt->execute($vehicleIds);
        }
        
        // Delete vehicles
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE operator_id = ?");
        $stmt->bindParam(1, $operator_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Delete operator
        $stmt = $conn->prepare("DELETE FROM operators WHERE operator_id = ?");
        $stmt->bindParam(1, $operator_id, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Delete failed: ' . $errorInfo[2]);
        }
        
        $conn->commit();
        return ['success' => true, 'error' => ''];
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>