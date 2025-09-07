<?php
require_once '../../../config/database.php';

if (!isset($_GET['id'])) {
    echo "Document ID required";
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    $document = getDocumentById($conn, $_GET['id']);
    
    if (!$document) {
        echo "Document not found";
        exit;
    }
    
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $document['file_path'];
    
    if (file_exists($file_path)) {
        $file_type = $document['file_type'] ?? 'application/pdf';
        
        header('Content-Type: ' . $file_type);
        header('Content-Disposition: inline; filename="' . basename($document['document_name']) . '"');
        readfile($file_path);
    } else {
        echo "File not found on server";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>