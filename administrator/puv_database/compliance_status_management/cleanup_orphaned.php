<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // Delete compliance records where operator doesn't exist
    $query1 = "DELETE cs FROM compliance_status cs 
               LEFT JOIN operators o ON cs.operator_id = o.operator_id 
               WHERE o.operator_id IS NULL";
    $stmt1 = $conn->prepare($query1);
    $stmt1->execute();
    $deleted_operators = $stmt1->rowCount();
    
    // Delete compliance records where vehicle doesn't exist
    $query2 = "DELETE cs FROM compliance_status cs 
               LEFT JOIN vehicles v ON cs.vehicle_id = v.vehicle_id 
               WHERE v.vehicle_id IS NULL";
    $stmt2 = $conn->prepare($query2);
    $stmt2->execute();
    $deleted_vehicles = $stmt2->rowCount();
    
    echo "Cleanup completed.<br>";
    echo "Removed {$deleted_operators} records with missing operators.<br>";
    echo "Removed {$deleted_vehicles} records with missing vehicles.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>