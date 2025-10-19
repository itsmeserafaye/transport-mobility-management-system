<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    $conn->beginTransaction();
    
    // Clear all mock data except terminal and parking data
    $tables_to_clear = [
        'compliance_status',
        'vehicles', 
        'operators',
        'violation_history',
        'inspection_records',
        'franchise_records',
        'franchise_applications',
        'document_repository',
        'franchise_lifecycle',
        'route_schedules',
        'official_routes',
        'lto_vehicle_registration',
        'revenue_integration'
    ];
    
    foreach ($tables_to_clear as $table) {
        try {
            $query = "DELETE FROM {$table}";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            echo "Cleared table: {$table}<br>";
        } catch (Exception $e) {
            echo "Table {$table} might not exist or error: " . $e->getMessage() . "<br>";
        }
    }
    
    $conn->commit();
    echo "<br><strong>Mock data cleared successfully!</strong><br>";
    echo "Terminal and parking data preserved.<br>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "Error clearing data: " . $e->getMessage();
}
?>