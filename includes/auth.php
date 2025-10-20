<?php
// Authentication and session management for TPRS

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
function checkAuth() {
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
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Check user access permissions
function checkAccess($required_permissions = []) {
    if (!isset($_SESSION['user_type'])) {
        header('Location: /transport_and_mobility_management_system/login.php');
        exit();
    }
    
    // TPRS users have specific access patterns
    if ($_SESSION['user_type'] === 'tprs') {
        // TPRS has read access to terminal management
        if (in_array('read', $required_permissions)) {
            return true;
        }
        // TPRS has write access to franchise management and vehicle inspection
        if (in_array('write', $required_permissions)) {
            return true;
        }
    }
    
    // Administrator has full access
    if ($_SESSION['user_type'] === 'administrator') {
        return true;
    }
    
    // Default deny
    header('Location: /transport_and_mobility_management_system/access_denied.php');
    exit();
}

// Get current user information
function getCurrentUser() {
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'user_type' => $_SESSION['user_type'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null
    ];
}

// Check if user has specific permission
function hasPermission($permission) {
    if (!isset($_SESSION['user_type'])) {
        return false;
    }
    
    $user_type = $_SESSION['user_type'];
    
    // Define permissions by user type
    $permissions = [
        'administrator' => ['read', 'write', 'delete', 'approve'],
        'tprs' => ['read', 'write', 'approve'],
        'ptsmd' => ['read', 'write'],
        'citizen' => ['read']
    ];
    
    return isset($permissions[$user_type]) && in_array($permission, $permissions[$user_type]);
}

// Log user activity
function logActivity($action, $module = '', $details = '') {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    try {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO user_activity_logs (user_id, action, module, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $action,
            $module,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the request
        error_log('Failed to log user activity: ' . $e->getMessage());
    }
}
?>