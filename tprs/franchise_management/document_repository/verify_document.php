<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$document_id = $input['document_id'] ?? '';

if (empty($document_id)) {
    echo json_encode(['success' => false, 'message' => 'Document ID required']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    $result = updateDocumentStatus($conn, $document_id, 'verified', 'System Admin', 'Document verified through admin panel');
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Document verified successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to verify document']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>