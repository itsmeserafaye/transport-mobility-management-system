<?php
// Database connection configuration

// Database configuration
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'transport_and_mobility_management_system';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_username, $db_password);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Function to get database connection
function getDBConnection() {
    global $pdo;
    return $pdo;
}

// Function to execute query with error handling
function executeQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw new Exception("Database query failed");
    }
}

// Function to fetch single row
function fetchSingle($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

// Function to fetch all rows
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

// Function to get last insert ID
function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

// Function to begin transaction
function beginTransaction() {
    global $pdo;
    return $pdo->beginTransaction();
}

// Function to commit transaction
function commitTransaction() {
    global $pdo;
    return $pdo->commit();
}

// Function to rollback transaction
function rollbackTransaction() {
    global $pdo;
    return $pdo->rollBack();
}

// Function to check if table exists
function tableExists($tableName) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to escape string for SQL LIKE queries
function escapeLike($string) {
    return str_replace(['%', '_'], ['\\%', '\\_'], $string);
}

// Function to sanitize input
function sanitizeInput($input) {
    if (is_string($input)) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
    return $input;
}

// Function to validate and sanitize array of inputs
function sanitizeArray($array) {
    $sanitized = [];
    foreach ($array as $key => $value) {
        $sanitized[sanitizeInput($key)] = sanitizeInput($value);
    }
    return $sanitized;
}
?>