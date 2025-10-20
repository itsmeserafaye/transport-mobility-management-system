<?php
// Debug script to check what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP is working<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Database file exists: " . (file_exists('gsm_system.db') ? 'YES' : 'NO') . "<br>";

// Test database connection
try {
    $pdo = new PDO("sqlite:gsm_system.db");
    echo "Database connection: SUCCESS<br>";
    
    // Check if users table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    $table_exists = $stmt->fetch();
    echo "Users table exists: " . ($table_exists ? 'YES' : 'NO') . "<br>";
    
    if ($table_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "Users in database: " . $count . "<br>";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Test JSON response
header('Content-Type: application/json');
echo json_encode(['test' => 'success', 'timestamp' => date('Y-m-d H:i:s')]);
?>