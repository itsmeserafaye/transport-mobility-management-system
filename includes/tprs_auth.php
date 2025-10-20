<?php
// TPRS-specific authentication and access control

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';

// Check TPRS access with specific permission level
function checkTPRSAccess($permission_level = 'read') {
    // First check if user is authenticated
    checkAuth();
    
    // Check if user is TPRS type
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'tprs') {
        header('Location: /transport_and_mobility_management_system/access_denied.php');
        exit();
    }
    
    // TPRS permission mapping
    $tprs_permissions = [
        'puv_database' => [
            'vehicle_operator_records' => 'read_write',
            'compliance_status' => 'update',
            'violation_history' => 'read_only'
        ],
        'franchise_management' => 'full_access',
        'traffic_violation_ticketing' => 'read_only',
        'vehicle_inspection' => 'full_access',
        'terminal_management' => 'read_only'
    ];
    
    // Check permission level
    switch ($permission_level) {
        case 'read':
            return true; // TPRS has read access to all modules
        case 'write':
            // TPRS has write access to franchise management and vehicle inspection
            return true;
        case 'full':
            // TPRS has full access to franchise management and vehicle inspection
            return true;
        default:
            return false;
    }
}

// Check TPRS module-specific access
function checkTPRSModuleAccess($module, $action = 'read') {
    checkAuth();
    
    if ($_SESSION['user_type'] !== 'tprs') {
        header('Location: /transport_and_mobility_management_system/access_denied.php');
        exit();
    }
    
    // Module-specific permissions for TPRS
    $module_permissions = [
        'puv_database' => ['read', 'write'],
        'franchise_management' => ['read', 'write', 'approve'],
        'traffic_violation_ticketing' => ['read'],
        'vehicle_inspection' => ['read', 'write', 'approve'],
        'terminal_management' => ['read']
    ];
    
    if (!isset($module_permissions[$module]) || !in_array($action, $module_permissions[$module])) {
        header('Location: /transport_and_mobility_management_system/access_denied.php');
        exit();
    }
    
    return true;
}

// Get TPRS user permissions
function getTPRSPermissions() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'tprs') {
        return [];
    }
    
    return [
        'puv_database' => [
            'vehicle_operator_records' => 'read_write',
            'compliance_status' => 'update',
            'violation_history' => 'read_only'
        ],
        'franchise_management' => 'full_access',
        'traffic_violation_ticketing' => 'read_only',
        'vehicle_inspection' => 'full_access',
        'terminal_management' => 'read_only'
    ];
}

// Log TPRS-specific activity
function logTPRSActivity($action, $module, $details = '') {
    logActivity($action, "TPRS - $module", $details);
}
?>