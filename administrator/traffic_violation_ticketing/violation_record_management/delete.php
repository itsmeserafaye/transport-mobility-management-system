<?php
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=Invalid violation ID');
    exit;
}

$violation_id = intval($_GET['id']);

try {
    $query = "DELETE FROM violation_records WHERE violation_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $violation_id);
    
    if ($stmt->execute()) {
        header('Location: index.php?message=Violation deleted successfully');
    } else {
        header('Location: index.php?error=Failed to delete violation');
    }
    
} catch (Exception $e) {
    header('Location: index.php?error=Error: ' . urlencode($e->getMessage()));
}
?>