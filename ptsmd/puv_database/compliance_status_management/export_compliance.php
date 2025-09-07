<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();
$format = $_GET['format'] ?? 'csv';

exportComplianceData($conn, $format);
?>