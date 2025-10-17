<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "<h3>All Vehicles:</h3>";
$query = "SELECT * FROM vehicles ORDER BY operator_id, vehicle_id";
$stmt = $conn->prepare($query);
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($vehicles as $vehicle) {
    echo "Vehicle ID: {$vehicle['vehicle_id']}, Operator: {$vehicle['operator_id']}, Plate: {$vehicle['plate_number']}<br>";
}

echo "<h3>All Compliance Records:</h3>";
$query = "SELECT * FROM compliance_status ORDER BY operator_id, vehicle_id";
$stmt = $conn->prepare($query);
$stmt->execute();
$compliance = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($compliance as $comp) {
    echo "Compliance ID: {$comp['compliance_id']}, Operator: {$comp['operator_id']}, Vehicle: {$comp['vehicle_id']}<br>";
}

echo "<h3>Vehicles WITHOUT Compliance Records:</h3>";
$query = "SELECT v.* FROM vehicles v 
          LEFT JOIN compliance_status cs ON v.vehicle_id = cs.vehicle_id 
          WHERE cs.compliance_id IS NULL";
$stmt = $conn->prepare($query);
$stmt->execute();
$missing = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($missing as $vehicle) {
    echo "Missing compliance for Vehicle ID: {$vehicle['vehicle_id']}, Operator: {$vehicle['operator_id']}, Plate: {$vehicle['plate_number']}<br>";
}
?>