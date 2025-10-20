<?php
// Government Services Management System - SQLite Version
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// SQLite database file
$db_file = 'gsm_system.db';

// Initialize SQLite database
function initDatabase($db_file) {
    try {
        $pdo = new PDO("sqlite:$db_file");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create default admin user if not exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute(['admin@gsm.gov.ph']);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("
                INSERT INTO users (email, password_hash, first_name, last_name, role, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                'admin@gsm.gov.ph',
                password_hash('admin123', PASSWORD_DEFAULT),
                'System',
                'Administrator',
                'admin',
                'active'
            ]);
        }
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Response helper function
function sendResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}



// Input validation
function validateInput($data) {
    $errors = [];
    
    // Skip validation for registration
    if (isset($data['action']) && $data['action'] === 'register') {
        return $errors;
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($data['password']) && isset($data['password'])) {
        $errors[] = 'Password is required';
    }
    
    return $errors;
}

// Sanitize input
function sanitizeInput($data) {
    $sanitized = [];
    foreach ($data as $key => $value) {
        $sanitized[$key] = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    return $sanitized;
}

// User authentication
function authenticateUser($email, $password, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, email, password_hash, first_name, last_name, role, status 
            FROM users 
            WHERE email = ? AND status = 'active'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

// Main request handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendResponse(false, 'Invalid JSON input', null, 400);
    }
    
    // Debug: Log received data
    error_log('Received input: ' . json_encode($input));
    
    $sanitizedInput = sanitizeInput($input);
    $errors = validateInput($sanitizedInput);
    
    if (!empty($errors)) {
        sendResponse(false, 'Validation failed: ' . implode(', ', $errors), ['errors' => $errors], 400);
    }
    
    $pdo = initDatabase($db_file);
    if (!$pdo) {
        sendResponse(false, 'Database connection failed', null, 500);
    }
    
    $action = $sanitizedInput['action'] ?? 'login';
    
    switch ($action) {
        case 'login':
            if (empty($sanitizedInput['password'])) {
                sendResponse(false, 'Password is required for login', null, 400);
            }
            
            // Check if user has recent OTP verification (within 5 minutes) from cookie
            $hasRecentOTP = false;
            if (isset($_COOKIE['otp_grace'])) {
                $graceData = json_decode($_COOKIE['otp_grace'], true);
                if ($graceData && 
                    $graceData['email'] === $sanitizedInput['email'] && 
                    (time() - $graceData['time']) < 300) {
                    $hasRecentOTP = true;
                }
            }
            
            // First validate credentials
            $user = authenticateUser($sanitizedInput['email'], $sanitizedInput['password'], $pdo);
            
            if (!$user) {
                sendResponse(false, 'Invalid email or password', null, 401);
            }
            

            
            // Check OTP verification
            if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
                sendResponse(false, 'OTP verification required', null, 401);
            }
            
            if (!isset($_SESSION['verified_email']) || $_SESSION['verified_email'] !== $sanitizedInput['email']) {
                sendResponse(false, 'Email mismatch with verified OTP', null, 401);
            }
            
            // Set OTP grace period cookie (5 minutes)
            $graceData = json_encode([
                'email' => $user['email'],
                'time' => time()
            ]);
            setcookie('otp_grace', $graceData, time() + 300, '/', '', false, true);
            
            // Clear current session OTP flags
            unset($_SESSION['otp_verified'], $_SESSION['verified_email']);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            // Remove sensitive data
            unset($user['password_hash']);
            
            // Determine redirect URL based on user role
            $redirectUrl = '';
            switch ($user['role']) {
                case 'commuter':
                    $redirectUrl = '/transport-mobility-management-system-main/commuter/index.php';
                    break;
                case 'operator':
                    $redirectUrl = '/transport-mobility-management-system-main/operator/dashboard.php';
                    break;
                case 'administrator':
                case 'admin':
                    $redirectUrl = '/transport-mobility-management-system-main/administrator/index.php';
                    break;
                default:
                    $redirectUrl = '/transport-mobility-management-system-main/administrator/index.php';
            }
            
            if (empty($redirectUrl)) {
                sendResponse(false, 'Administrator dashboard not configured');
            } else {
                sendResponse(true, 'Login successful', [
                    'user' => $user,
                    'redirect' => $redirectUrl
                ]);
            }
            break;
            
        case 'register':
            // Debug: Log sanitized input for registration
            error_log('Registration attempt with data: ' . json_encode($sanitizedInput));
            
            // Only validate essential fields for basic registration
            if (empty($sanitizedInput['firstName']) || empty($sanitizedInput['lastName']) || 
                empty($sanitizedInput['regEmail']) || empty($sanitizedInput['regPassword']) || 
                empty($sanitizedInput['role'])) {
                sendResponse(false, 'Please fill in all required fields', null, 400);
            }
            
            // Validate role
            $validRoles = ['administrator', 'operator', 'commuter'];
            if (!in_array($sanitizedInput['role'], $validRoles)) {
                sendResponse(false, 'Invalid role selected', null, 400);
            }
            
            // Check if email already exists
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$sanitizedInput['regEmail']]);
                if ($stmt->fetch()) {
                    sendResponse(false, 'Email already registered', null, 400);
                }
                
                // Insert new user
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password_hash, first_name, last_name, role, status) 
                    VALUES (?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([
                    $sanitizedInput['regEmail'],
                    password_hash($sanitizedInput['regPassword'], PASSWORD_DEFAULT),
                    $sanitizedInput['firstName'],
                    $sanitizedInput['lastName'],
                    $sanitizedInput['role']
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                // No auto-login for Google users - they need to login manually with their credentials
                
                sendResponse(true, 'Registration successful');
            } catch (PDOException $e) {
                sendResponse(false, 'Registration failed', null, 500);
            }
            break;
            
        case 'check_credentials':
            if (empty($sanitizedInput['password'])) {
                sendResponse(false, 'Password is required', null, 400);
            }
            
            $user = authenticateUser($sanitizedInput['email'], $sanitizedInput['password'], $pdo);
            
            if ($user) {
                sendResponse(true, 'Credentials valid');
            } else {
                sendResponse(false, 'Invalid email or password', null, 401);
            }
            break;
            
        case 'check_email':
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND status = 'active'");
                $stmt->execute([$sanitizedInput['email']]);
                $user = $stmt->fetch();
                
                sendResponse(true, 'Email check completed', [
                    'exists' => (bool)$user
                ]);
            } catch (PDOException $e) {
                sendResponse(false, 'Database error', null, 500);
            }
            break;
            
        default:
            sendResponse(false, 'Invalid action', null, 400);
    }
} else {
    sendResponse(false, 'Method not allowed', null, 405);
}
?>
