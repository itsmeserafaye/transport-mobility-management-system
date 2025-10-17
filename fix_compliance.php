<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Create missing compliance record for VH-2025-002
$compliance_id = 'CS-2025-002';
$query = "INSERT INTO compliance_status (compliance_id, operator_id, vehicle_id, franchise_status, inspection_status, violation_count, compliance_score) 
          VALUES (:compliance_id, :operator_id, :vehicle_id, 'pending', 'pending', 0, 75.00)";

$stmt = $conn->prepare($query);
$result = $stmt->execute([
    'compliance_id' => $compliance_id,
    'operator_id' => 'OP-2025-001',
    'vehicle_id' => 'VH-2025-002'
]);

if ($result) {
    echo "✅ Compliance record created successfully for VH-2025-002<br>";
} else {
    echo "❌ Failed to create compliance record<br>";
}

// Verify it was created
$query = "SELECT * FROM compliance_status WHERE vehicle_id = 'VH-2025-002'";
$stmt = $conn->prepare($query);
$stmt->execute();
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if ($record) {
    echo "✅ Verification: Compliance record exists - ID: {$record['compliance_id']}<br>";
    echo "Now both vehicles should appear in compliance status management!";
} else {
    echo "❌ Verification failed";
}
?>