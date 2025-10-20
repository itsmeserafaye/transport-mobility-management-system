<?php
require_once '../../../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

$category_id = $_GET['category_id'] ?? '';

if ($category_id) {
    $query = "SELECT type_id, type_name FROM violation_types WHERE category_id = ? ORDER BY type_name";
    $stmt = $conn->prepare($query);
    $stmt->execute([$category_id]);
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'types' => $types]);
} else {
    echo json_encode(['success' => false, 'message' => 'Category ID required']);
}
?>