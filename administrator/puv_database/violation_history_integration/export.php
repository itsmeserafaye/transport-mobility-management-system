<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$format = $_GET['format'] ?? 'csv';

// Get violation data
$query = "SELECT vh.violation_id, vh.violation_type, vh.violation_date, vh.fine_amount, vh.settlement_status,
                 CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                 v.plate_number, v.vehicle_type, vh.location, vh.ticket_number
          FROM violation_history vh
          LEFT JOIN operators o ON vh.operator_id = o.operator_id
          LEFT JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
          ORDER BY vh.violation_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

switch ($format) {
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="violation_history_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        fclose($output);
        break;
        
    case 'json':
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="violation_history_' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        break;
        
    case 'excel':
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="violation_history_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        if (!empty($data)) {
            echo "<tr>";
            foreach (array_keys($data[0]) as $header) {
                echo "<th>" . htmlspecialchars($header) . "</th>";
            }
            echo "</tr>";
            
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $cell) {
                    echo "<td>" . htmlspecialchars($cell) . "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>";
        break;
        
    case 'pdf':
        // Simple HTML to PDF conversion
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="violation_history_' . date('Y-m-d') . '.pdf"');
        
        echo "<!DOCTYPE html><html><head><title>Violation History Report</title></head><body>";
        echo "<h1>Violation History Report</h1>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        
        if (!empty($data)) {
            echo "<tr style='background-color: #f2f2f2;'>";
            foreach (array_keys($data[0]) as $header) {
                echo "<th style='padding: 8px;'>" . htmlspecialchars($header) . "</th>";
            }
            echo "</tr>";
            
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $cell) {
                    echo "<td style='padding: 8px;'>" . htmlspecialchars($cell) . "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table></body></html>";
        break;
        
    case 'word':
        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename="violation_history_' . date('Y-m-d') . '.doc"');
        
        echo "<html><head><title>Violation History Report</title></head><body>";
        echo "<h1>Violation History Report</h1>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        
        if (!empty($data)) {
            echo "<tr style='background-color: #f2f2f2;'>";
            foreach (array_keys($data[0]) as $header) {
                echo "<th style='padding: 8px;'>" . htmlspecialchars($header) . "</th>";
            }
            echo "</tr>";
            
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $cell) {
                    echo "<td style='padding: 8px;'>" . htmlspecialchars($cell) . "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table></body></html>";
        break;
}
exit;
?>