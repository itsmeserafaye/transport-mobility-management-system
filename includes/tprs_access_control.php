<?php
// TPRS Access Control System

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';

// Check TPRS access for specific module and action
function checkTPRSAccess($module, $action = 'read') {
    // First check if user is authenticated
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        // For demo purposes, create a TPRS user session
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'tprs_user';
        $_SESSION['user_type'] = 'tprs';
        $_SESSION['full_name'] = 'TPRS User';
        $_SESSION['first_name'] = 'TPRS';
        $_SESSION['last_name'] = 'User';
        $_SESSION['role'] = 'tprs';
    }
    
    // Check if user is TPRS type
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'tprs') {
        http_response_code(403);
        die('Access denied: TPRS access required');
    }
    
    // TPRS access matrix
    $access_matrix = [
        'franchise_management' => [
            'read' => true,
            'write' => true,
            'approve' => true,
            'delete' => true
        ],
        'puv_database' => [
            'read' => true,
            'write' => true,
            'update' => true,
            'delete' => false // Limited delete access
        ],
        'vehicle_inspection' => [
            'read' => true,
            'write' => true,
            'approve' => true,
            'schedule' => true
        ],
        'traffic_violation_ticketing' => [
            'read' => true,
            'write' => false,
            'approve' => false
        ],
        'terminal_management' => [
            'read' => true,
            'write' => false,
            'approve' => false
        ]
    ];
    
    // Check if module exists in access matrix
    if (!isset($access_matrix[$module])) {
        http_response_code(403);
        die('Access denied: Invalid module');
    }
    
    // Check if action is allowed for the module
    if (!isset($access_matrix[$module][$action]) || !$access_matrix[$module][$action]) {
        http_response_code(403);
        die("Access denied: $action not allowed for $module");
    }
    
    return true;
}

// Require TPRS access (simplified version)
function requireTPRSAccess($action = 'read') {
    checkAuth();
    
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'tprs') {
        header('Location: /transport_and_mobility_management_system/access_denied.php');
        exit();
    }
    
    return true;
}

// Check if TPRS user has specific permission
function hasTPRSPermission($module, $action) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'tprs') {
        return false;
    }
    
    $access_matrix = [
        'franchise_management' => ['read', 'write', 'approve', 'delete'],
        'puv_database' => ['read', 'write', 'update'],
        'vehicle_inspection' => ['read', 'write', 'approve', 'schedule'],
        'traffic_violation_ticketing' => ['read'],
        'terminal_management' => ['read']
    ];
    
    return isset($access_matrix[$module]) && in_array($action, $access_matrix[$module]);
}

// Get TPRS access level for a module
function getTPRSAccessLevel($module) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'tprs') {
        return 'none';
    }
    
    $access_levels = [
        'franchise_management' => 'full',
        'puv_database' => 'read_write',
        'vehicle_inspection' => 'full',
        'traffic_violation_ticketing' => 'read_only',
        'terminal_management' => 'read_only'
    ];
    
    return $access_levels[$module] ?? 'none';
}

// Log TPRS access attempt
function logTPRSAccess($module, $action, $success = true) {
    $status = $success ? 'SUCCESS' : 'DENIED';
    $details = "Module: $module, Action: $action, Status: $status";
    logActivity('ACCESS_ATTEMPT', 'TPRS_ACCESS_CONTROL', $details);
}
?>