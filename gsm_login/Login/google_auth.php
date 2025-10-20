<?php
session_start();
header('Content-Type: application/json');

require_once '../vendor/autoload.php';

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['credential'])) {
        sendResponse(false, 'No credential provided');
    }
    
    try {
        // Verify Google JWT token
        $client = new Google_Client(['client_id' => '1012831009601-hqvi6p3fobvapr8aks0ha32lfgo6rjqn.apps.googleusercontent.com']);
        $payload = $client->verifyIdToken($input['credential']);
        
        if ($payload) {
            $email = $payload['email'];
            $name = $payload['name'];
            $given_name = $payload['given_name'] ?? '';
            $family_name = $payload['family_name'] ?? '';
            
            // Connect to database
            $pdo = new PDO("sqlite:gsm_system.db");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // User exists, log them in
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                sendResponse(true, 'Login successful');
            } else {
                // User doesn't exist, need registration
                sendResponse(false, 'Registration required', [
                    'email' => $email,
                    'name' => $name,
                    'given_name' => $given_name,
                    'family_name' => $family_name,
                    'needs_registration' => true
                ]);
            }
        } else {
            sendResponse(false, 'Invalid Google token');
        }
    } catch (Exception $e) {
        sendResponse(false, 'Authentication failed: ' . $e->getMessage());
    }
} else {
    sendResponse(false, 'Method not allowed');
}
?>