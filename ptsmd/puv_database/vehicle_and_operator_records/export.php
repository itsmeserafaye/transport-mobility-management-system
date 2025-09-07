<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$format = $_GET['format'] ?? 'csv';
$operators = getOperators($conn, 1000, 0);

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="operators_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, [
        'Operator ID', 'First Name', 'Last Name', 'Contact Number', 
        'License Number', 'License Expiry', 'Plate Number', 'Vehicle Type', 
        'Make', 'Model', 'Compliance Score', 'Status'
    ]);
    
    foreach ($operators as $operator) {
        fputcsv($output, [
            $operator['operator_id'],
            $operator['first_name'],
            $operator['last_name'],
            $operator['contact_number'],
            $operator['license_number'],
            $operator['license_expiry'],
            $operator['plate_number'] ?? 'N/A',
            $operator['vehicle_type'] ?? 'N/A',
            $operator['make'] ?? 'N/A',
            $operator['model'] ?? 'N/A',
            $operator['compliance_score'] ?? '0',
            $operator['status']
        ]);
    }
    
    fclose($output);
    exit;
}

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="operators_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th>Operator ID</th><th>First Name</th><th>Last Name</th><th>Contact Number</th><th>License Number</th><th>License Expiry</th><th>Plate Number</th><th>Vehicle Type</th><th>Make</th><th>Model</th><th>Compliance Score</th><th>Status</th></tr>";
    
    foreach ($operators as $operator) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($operator['operator_id']) . "</td>";
        echo "<td>" . htmlspecialchars($operator['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($operator['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($operator['contact_number']) . "</td>";
        echo "<td>" . htmlspecialchars($operator['license_number']) . "</td>";
        echo "<td>" . htmlspecialchars($operator['license_expiry']) . "</td>";
        echo "<td>" . htmlspecialchars($operator['plate_number'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($operator['vehicle_type'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($operator['make'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($operator['model'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($operator['compliance_score'] ?? '0') . "</td>";
        echo "<td>" . htmlspecialchars($operator['status']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    exit;
}

if ($format === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="operators_' . date('Y-m-d') . '.pdf"');
    
    $html = '<h1>Vehicle & Operator Records</h1>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<tr><th>Operator ID</th><th>Name</th><th>Contact</th><th>License</th><th>Vehicle</th><th>Status</th></tr>';
    
    foreach ($operators as $operator) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($operator['operator_id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($operator['first_name'] . ' ' . $operator['last_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($operator['contact_number']) . '</td>';
        $html .= '<td>' . htmlspecialchars($operator['license_number']) . '</td>';
        $html .= '<td>' . htmlspecialchars(($operator['plate_number'] ?? 'N/A') . ' - ' . ($operator['vehicle_type'] ?? 'N/A')) . '</td>';
        $html .= '<td>' . htmlspecialchars($operator['status']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    // Simple PDF generation using HTML
    echo "<html><head><style>table{width:100%;border-collapse:collapse;}th,td{border:1px solid #000;padding:5px;}</style></head><body>$html</body></html>";
    exit;
}

if ($format === 'word') {
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="operators_' . date('Y-m-d') . '.doc"');
    
    $html = '<h1>Vehicle & Operator Records</h1>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<tr><th>Operator ID</th><th>Name</th><th>Contact</th><th>License</th><th>Vehicle</th><th>Status</th></tr>';
    
    foreach ($operators as $operator) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($operator['operator_id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($operator['first_name'] . ' ' . $operator['last_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($operator['contact_number']) . '</td>';
        $html .= '<td>' . htmlspecialchars($operator['license_number']) . '</td>';
        $html .= '<td>' . htmlspecialchars(($operator['plate_number'] ?? 'N/A') . ' - ' . ($operator['vehicle_type'] ?? 'N/A')) . '</td>';
        $html .= '<td>' . htmlspecialchars($operator['status']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    echo "<html><head><meta charset='UTF-8'></head><body>$html</body></html>";
    exit;
}
?>