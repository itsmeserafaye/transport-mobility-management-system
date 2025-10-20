<?php
// Transport and Mobility Management System Configuration

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'transport_mobility_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'Transport & Mobility Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/transport_and_mobility_management_system/');

// Module Paths
define('ADMIN_PATH', BASE_URL . 'administrator/');
define('TPRS_PATH', BASE_URL . 'tprs/');
define('PTSMD_PATH', BASE_URL . 'ptsmd/');
define('CITIZEN_PATH', BASE_URL . 'citizen/');

// PUV Database Module Paths
define('PUV_DB_PATH', ADMIN_PATH . 'puv_database/');
define('VEHICLE_RECORDS_PATH', PUV_DB_PATH . 'vehicle_and_operator_records/');
define('COMPLIANCE_PATH', PUV_DB_PATH . 'compliance_status_management/');
define('VIOLATION_PATH', PUV_DB_PATH . 'violation_history_integration/');

// System Settings
define('RECORDS_PER_PAGE', 10);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('UPLOAD_MAX_SIZE', 5242880); // 5MB

// Status Constants
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_SUSPENDED', 'suspended');

// Compliance Status Constants
define('FRANCHISE_VALID', 'valid');
define('FRANCHISE_EXPIRED', 'expired');
define('FRANCHISE_PENDING', 'pending');
define('FRANCHISE_REVOKED', 'revoked');

define('INSPECTION_PASSED', 'passed');
define('INSPECTION_FAILED', 'failed');
define('INSPECTION_PENDING', 'pending');
define('INSPECTION_OVERDUE', 'overdue');

// Violation Status Constants
define('SETTLEMENT_PAID', 'paid');
define('SETTLEMENT_UNPAID', 'unpaid');
define('SETTLEMENT_PARTIAL', 'partial');

// Vehicle Types
define('VEHICLE_TYPES', [
    'jeepney' => 'Jeepney',
    'bus' => 'Bus', 
    'tricycle' => 'Tricycle',
    'taxi' => 'Taxi',
    'van' => 'Van'
]);

// Violation Types
define('VIOLATION_TYPES', [
    'speeding' => 'Speeding',
    'overloading' => 'Overloading',
    'route_deviation' => 'Route Deviation',
    'no_franchise' => 'No Franchise',
    'expired_registration' => 'Expired Registration',
    'reckless_driving' => 'Reckless Driving'
]);

// Risk Levels
define('RISK_LEVELS', [
    'low' => 'Low Risk',
    'medium' => 'Medium Risk', 
    'high' => 'High Risk'
]);

// Include database configuration
require_once 'database.php';

// Utility Functions
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function getStatusBadge($status, $type = 'default') {
    $badges = [
        'active' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-gray-100 text-gray-800',
        'suspended' => 'bg-red-100 text-red-800',
        'valid' => 'bg-green-100 text-green-800',
        'expired' => 'bg-red-100 text-red-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'revoked' => 'bg-red-100 text-red-800',
        'passed' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
        'overdue' => 'bg-orange-100 text-orange-800',
        'paid' => 'bg-green-100 text-green-800',
        'unpaid' => 'bg-red-100 text-red-800',
        'partial' => 'bg-yellow-100 text-yellow-800'
    ];
    
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-800';
    return "<span class='px-2 py-1 text-xs font-medium {$class} rounded-full'>" . ucfirst($status) . "</span>";
}

function getRiskBadge($risk_level) {
    $badges = [
        'low' => 'bg-green-100 text-green-800',
        'medium' => 'bg-yellow-100 text-yellow-800',
        'high' => 'bg-red-100 text-red-800'
    ];
    
    $class = $badges[$risk_level] ?? 'bg-gray-100 text-gray-800';
    $label = RISK_LEVELS[$risk_level] ?? ucfirst($risk_level);
    return "<span class='px-2 py-1 text-xs font-medium {$class} rounded-full'>{$label}</span>";
}
?>