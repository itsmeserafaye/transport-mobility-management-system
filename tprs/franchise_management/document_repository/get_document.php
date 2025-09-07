<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Document ID required']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    $stmt = $conn->prepare("SELECT * FROM document_repository WHERE document_id = ?");
    $stmt->execute([$_GET['id']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($document) {
        echo json_encode(['success' => true, 'document' => $document]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>