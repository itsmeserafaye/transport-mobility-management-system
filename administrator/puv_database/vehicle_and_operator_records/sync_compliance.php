<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // Create compliance status records for operators/vehicles that don't have them
    $query = "INSERT INTO compliance_status (compliance_id, operator_id, vehicle_id, franchise_status, inspection_status, violation_count, compliance_score)
              SELECT 
                  CONCAT('CS-', YEAR(CURDATE()), '-', LPAD(ROW_NUMBER() OVER (ORDER BY o.operator_id), 3, '0')) as compliance_id,
                  o.operator_id,
                  v.vehicle_id,
                  'pending' as franchise_status,
                  'pending' as inspection_status,
                  0 as violation_count,
                  75.00 as compliance_score
              FROM operators o
              JOIN vehicles v ON o.operator_id = v.operator_id
              LEFT JOIN compliance_status cs ON o.operator_id = cs.operator_id AND v.vehicle_id = cs.vehicle_id
              WHERE cs.compliance_id IS NULL";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $created = $stmt->rowCount();
    echo "Sync completed. Created {$created} compliance status records.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>