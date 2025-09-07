<?php
// General access control system

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';

// Require TPRS access with specific permission level
function requireTPRSAccess($permission = 'read') {
    // Check if user is authenticated
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        header('Location: /transport_and_mobility_management_system/login.php');
        exit();
    }
    
    // Check if user is TPRS type
    if ($_SESSION['user_type'] !== 'tprs') {
        header('Location: /transport_and_mobility_management_system/access_denied.php');
        exit();
    }
    
    // TPRS permissions
    $tprs_permissions = ['read', 'write', 'approve'];
    
    if (!in_array($permission, $tprs_permissions)) {
        header('Location: /transport_and_mobility_management_system/access_denied.php');
        exit();
    }
    
    return true;
}

// Require Administrator access
function requireAdminAccess() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        header('Location: /transport_and_mobility_management_system/login.php');
        exit();
    }
    
    if ($_SESSION['user_type'] !== 'administrator') {
        header('Location: /transport_and_mobility_management_system/access_denied.php');
        exit();
    }
    
    return true;
}

// Require PTSMD access
function requirePTSMDAccess($permission = 'read') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        header('Location: /transport_and_mobility_management_system/login.php');
        exit();
    }
    
    if ($_SESSION['user_type'] !== 'ptsmd') {
        header('Location: /transport_and_mobility_management_system/access_denied.php');
        exit();
    }
    
    return true;
}

// Check if user has access to specific module
function hasModuleAccess($module, $action = 'read') {
    if (!isset($_SESSION['user_type'])) {
        return false;
    }
    
    $user_type = $_SESSION['user_type'];
    
    // Define module access by user type
    $module_access = [
        'administrator' => [
            'puv_database' => ['read', 'write', 'delete'],
            'franchise_management' => ['read', 'write', 'approve', 'delete'],
            'traffic_violation_ticketing' => ['read', 'write', 'approve'],
            'vehicle_inspection' => ['read', 'write', 'approve'],
            'terminal_management' => ['read', 'write', 'approve']
        ],
        'tprs' => [
            'puv_database' => ['read', 'write'],
            'franchise_management' => ['read', 'write', 'approve'],
            'traffic_violation_ticketing' => ['read'],
            'vehicle_inspection' => ['read', 'write', 'approve'],
            'terminal_management' => ['read']
        ],
        'ptsmd' => [
            'puv_database' => ['read'],
            'franchise_management' => ['read'],
            'traffic_violation_ticketing' => ['read', 'write', 'approve'],
            'vehicle_inspection' => ['read'],
            'terminal_management' => ['read']
        ],
        'citizen' => [
            'puv_database' => ['read'],
            'franchise_management' => ['read'],
            'traffic_violation_ticketing' => ['read'],
            'vehicle_inspection' => ['read'],
            'terminal_management' => ['read']
        ]
    ];
    
    return isset($module_access[$user_type][$module]) && 
           in_array($action, $module_access[$user_type][$module]);
}

// Get user's access level for a module
function getUserAccessLevel($module) {
    if (!isset($_SESSION['user_type'])) {
        return 'none';
    }
    
    if (!hasModuleAccess($module, 'read')) {
        return 'none';
    }
    
    if (hasModuleAccess($module, 'approve')) {
        return 'full';
    }
    
    if (hasModuleAccess($module, 'write')) {
        return 'read_write';
    }
    
    return 'read_only';
}

// Check role-based access
function checkRoleAccess($required_roles) {
    if (!isset($_SESSION['user_type'])) {
        return false;
    }
    
    if (is_string($required_roles)) {
        $required_roles = [$required_roles];
    }
    
    return in_array($_SESSION['user_type'], $required_roles);
}
?>