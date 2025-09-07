<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    $document_id = $_POST['document_id'] ?? '';
    $document_name = $_POST['document_name'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? null;
    
    if (empty($document_id) || empty($document_name)) {
        echo json_encode(['success' => false, 'message' => 'Document ID and name are required']);
        exit;
    }
    
    // Handle file upload if new file is provided
    $file_path = null;
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/uploads/documents/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
        $new_filename = $document_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $new_filename;
        
        if (!move_uploaded_file($_FILES['document_file']['tmp_name'], $file_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            exit;
        }
        
        // Store relative path
        $file_path = '/transport_and_mobility_management_system/uploads/documents/' . $new_filename;
    }
    
    // Update document in database
    if ($file_path) {
        // Update with new file
        $stmt = $conn->prepare("UPDATE document_repository SET document_name = ?, file_path = ?, expiry_date = ? WHERE document_id = ?");
        $stmt->execute([$document_name, $file_path, $expiry_date, $document_id]);
    } else {
        // Update without changing file
        $stmt = $conn->prepare("UPDATE document_repository SET document_name = ?, expiry_date = ? WHERE document_id = ?");
        $stmt->execute([$document_name, $expiry_date, $document_id]);
    }
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Document updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or document not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>