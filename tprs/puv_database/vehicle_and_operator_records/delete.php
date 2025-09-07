<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$operator_id = $_GET['id'] ?? '';

if ($operator_id) {
    $success = deleteOperator($conn, $operator_id);
    
    if ($success) {
        header('Location: index.php?message=Operator deleted successfully');
    } else {
        header('Location: index.php?error=Failed to delete operator');
    }
} else {
    header('Location: index.php?error=Invalid operator ID');
}
exit;
?>