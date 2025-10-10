<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $admin_id = 'USR001'; // Should be from session
    
    try {
        switch ($action) {
            case 'update_profile':
                $first_name = $_POST['first_name'] ?? '';
                $last_name = $_POST['last_name'] ?? '';
                $email = $_POST['email'] ?? '';
                $phone_number = $_POST['phone_number'] ?? '';
                
                $stmt = $conn->prepare("CALL UpdateUserProfile(?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $first_name, $last_name, $email, $phone_number, $admin_id]);
                
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
                break;
                
            case 'reset_password':
                $new_password = 'TempPass123!'; // Generate random password
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("CALL ResetUserPassword(?, ?, ?)");
                $stmt->execute([$user_id, $password_hash, $admin_id]);
                
                echo json_encode(['success' => true, 'message' => 'Password reset successfully', 'temp_password' => $new_password]);
                break;
                
            case 'toggle_status':
                $new_status = $_POST['new_status'] ?? '';
                $reason = $_POST['reason'] ?? '';
                
                $stmt = $conn->prepare("CALL ToggleUserStatus(?, ?, ?, ?)");
                $stmt->execute([$user_id, $new_status, $reason, $admin_id]);
                
                echo json_encode(['success' => true, 'message' => "Status changed to $new_status"]);
                break;
                
            case 'unlock_account':
                $stmt = $conn->prepare("CALL UnlockUserAccount(?, ?)");
                $stmt->execute([$user_id, $admin_id]);
                
                echo json_encode(['success' => true, 'message' => 'Account unlocked successfully']);
                break;
                
            case 'bulk_unlock':
                $user_ids = $_POST['user_ids'] ?? [];
                if (empty($user_ids)) {
                    echo json_encode(['success' => false, 'message' => 'No users selected']);
                    break;
                }
                
                $success_count = 0;
                foreach ($user_ids as $uid) {
                    $stmt = $conn->prepare("CALL UnlockUserAccount(?, ?)");
                    if ($stmt->execute([$uid, $admin_id])) {
                        $success_count++;
                    }
                }
                
                echo json_encode(['success' => true, 'message' => "$success_count accounts unlocked successfully"]);
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