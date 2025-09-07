<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "<h3>Database Test Results</h3>";

// Test violation_history table
echo "<h4>Violation History Table:</h4>";
try {
    $query = "SELECT COUNT(*) as count FROM violation_history";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "Total violations: " . $count . "<br>";
    
    if ($count > 0) {
        $query = "SELECT * FROM violation_history LIMIT 3";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($violations, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test operators table
echo "<h4>Operators Table:</h4>";
try {
    $query = "SELECT COUNT(*) as count FROM operators";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "Total operators: " . $count . "<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test vehicles table
echo "<h4>Vehicles Table:</h4>";
try {
    $query = "SELECT COUNT(*) as count FROM vehicles";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "Total vehicles: " . $count . "<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>