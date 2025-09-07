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
    $operator_id = $_POST['operator_id'] ?? '';
    $vehicle_id = $_POST['vehicle_id'] ?? null;
    $document_category = $_POST['document_category'] ?? '';
    $document_type = $_POST['document_type'] ?? '';
    $document_name = $_POST['document_name'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? null;
    
    if (empty($operator_id) || empty($document_category) || empty($document_type) || empty($document_name)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        exit;
    }
    
    if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit;
    }
    
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/uploads/documents/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $document_id = generateDocumentId($conn);
    $file_extension = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
    $new_filename = $document_id . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;
    
    if (!move_uploaded_file($_FILES['document_file']['tmp_name'], $file_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit;
    }
    
    $relative_path = '/transport_and_mobility_management_system/uploads/documents/' . $new_filename;
    
    $query = "INSERT INTO document_repository (document_id, operator_id, vehicle_id, document_category, document_type, document_name, file_path, file_size, file_type, expiry_date, uploaded_by, verification_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $document_id,
        $operator_id,
        $vehicle_id,
        $document_category,
        $document_type,
        $document_name,
        $relative_path,
        $_FILES['document_file']['size'],
        $file_extension,
        $expiry_date,
        'System Admin',
        'pending'
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Document uploaded successfully', 'document_id' => $document_id]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>