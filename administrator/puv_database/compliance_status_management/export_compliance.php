<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();
$format = $_GET['format'] ?? 'csv';

exportComplianceData($conn, $format);
?>