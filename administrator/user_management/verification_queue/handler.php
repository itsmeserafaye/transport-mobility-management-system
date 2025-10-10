<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    try {
        switch ($action) {
            case 'approve':
                $query = "UPDATE users SET 
                         verification_status = 'verified', 
                         verified_at = NOW(), 
                         verification_notes = :notes 
                         WHERE user_id = :user_id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':notes', $notes);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'User verified successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to verify user']);
                }
                break;
                
            case 'reject':
                $query = "UPDATE users SET 
                         verification_status = 'rejected', 
                         verified_at = NOW(), 
                         verification_notes = :notes 
                         WHERE user_id = :user_id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':notes', $notes);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'User verification rejected']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to reject verification']);
                }
                break;
                
            case 'bulk_approve':
                $user_ids = $_POST['user_ids'] ?? [];
                if (empty($user_ids)) {
                    echo json_encode(['success' => false, 'message' => 'No users selected']);
                    break;
                }
                
                $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
                $query = "UPDATE users SET 
                         verification_status = 'verified', 
                         verified_at = NOW(), 
                         verification_notes = 'Bulk approved' 
                         WHERE user_id IN ($placeholders)";
                
                $stmt = $conn->prepare($query);
                
                if ($stmt->execute($user_ids)) {
                    echo json_encode(['success' => true, 'message' => count($user_ids) . ' users verified successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to bulk approve users']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>