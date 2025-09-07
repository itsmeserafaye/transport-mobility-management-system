<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...<br>";

try {
    require_once '../../config/database.php';
    echo "Database file loaded successfully<br>";
    
    $database = new Database();
    $conn = $database->getConnection();
    echo "Database connection established<br>";
    
    // Test if functions exist
    if (function_exists('getViolationRecords')) {
        echo "getViolationRecords function exists<br>";
        $violations = getViolationRecords($conn);
        echo "Found " . count($violations) . " violations<br>";
    } else {
        echo "getViolationRecords function NOT found<br>";
    }
    
    if (function_exists('getStatistics')) {
        echo "getStatistics function exists<br>";
        $stats = getStatistics($conn);
        echo "Statistics loaded<br>";
    } else {
        echo "getStatistics function NOT found<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>