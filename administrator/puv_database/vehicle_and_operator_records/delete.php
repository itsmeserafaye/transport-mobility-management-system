<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=Invalid operator ID');
    exit;
}

$operator_id = $_GET['id'];

$result = archiveOperator($conn, $operator_id, 'Administrator');

if ($result['success']) {
    header('Location: index.php?message=Operator archived successfully');
} else {
    header('Location: index.php?error=' . urlencode($result['message']));
}
?>