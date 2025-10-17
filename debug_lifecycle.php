<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "<h2>Debug: Franchise Lifecycle Data</h2>";

// Check franchise_applications table
echo "<h3>Franchise Applications:</h3>";
$query = "SELECT * FROM franchise_applications ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($applications)) {
    echo "No applications found.<br>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Operator</th><th>Status</th><th>Stage</th><th>Created</th></tr>";
    foreach ($applications as $app) {
        echo "<tr>";
        echo "<td>" . $app['application_id'] . "</td>";
        echo "<td>" . $app['operator_id'] . "</td>";
        echo "<td>" . $app['status'] . "</td>";
        echo "<td>" . $app['workflow_stage'] . "</td>";
        echo "<td>" . $app['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check franchise_records table
echo "<h3>Franchise Records:</h3>";
$query = "SELECT * FROM franchise_records ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($records)) {
    echo "No franchise records found.<br>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Operator</th><th>Vehicle</th><th>Number</th><th>Status</th><th>Created</th></tr>";
    foreach ($records as $record) {
        echo "<tr>";
        echo "<td>" . $record['franchise_id'] . "</td>";
        echo "<td>" . $record['operator_id'] . "</td>";
        echo "<td>" . $record['vehicle_id'] . "</td>";
        echo "<td>" . $record['franchise_number'] . "</td>";
        echo "<td>" . $record['status'] . "</td>";
        echo "<td>" . $record['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check franchise_lifecycle table
echo "<h3>Franchise Lifecycle:</h3>";
$query = "SELECT * FROM franchise_lifecycle ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$lifecycle = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($lifecycle)) {
    echo "No lifecycle records found.<br>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Franchise ID</th><th>Operator</th><th>Stage</th><th>Created</th></tr>";
    foreach ($lifecycle as $lc) {
        echo "<tr>";
        echo "<td>" . $lc['lifecycle_id'] . "</td>";
        echo "<td>" . $lc['franchise_id'] . "</td>";
        echo "<td>" . $lc['operator_id'] . "</td>";
        echo "<td>" . $lc['lifecycle_stage'] . "</td>";
        echo "<td>" . $lc['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>