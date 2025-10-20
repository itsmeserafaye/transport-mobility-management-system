<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/administrator/puv_database/compliance_status_management/functions.php';

$database = new Database();
$conn = $database->getConnection();

$format = $_GET['format'] ?? 'csv';
$date = date('Ymd');
$status = 'RAW'; // Default status

// Get all operators data
$operators = getOperators($conn, 1000, 0); // Get up to 1000 records

// Generate filename
$filename = "CLCN_VehicleAndOperatorRecords_{$date}_{$status}";

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Operator ID', 'First Name', 'Last Name', 'Address', 'Contact Number',
        'License Number', 'License Expiry', 'Plate Number', 'Vehicle Type',
        'Make', 'Model', 'Year', 'Engine Number', 'Chassis Number',
        'Seating Capacity', 'Compliance Score', 'Status'
    ]);
    
    // CSV Data
    foreach ($operators as $operator) {
        fputcsv($output, [
            $operator['operator_id'],
            $operator['first_name'],
            $operator['last_name'],
            $operator['address'],
            $operator['contact_number'],
            $operator['license_number'],
            $operator['license_expiry'],
            $operator['plate_number'] ?? 'N/A',
            $operator['vehicle_type'] ?? 'N/A',
            $operator['make'] ?? 'N/A',
            $operator['model'] ?? 'N/A',
            $operator['year_manufactured'] ?? 'N/A',
            $operator['engine_number'] ?? 'N/A',
            $operator['chassis_number'] ?? 'N/A',
            $operator['seating_capacity'] ?? 'N/A',
            $operator['compliance_score'] ?? '0',
            $operator['status'] ?? 'active'
        ]);
    }
    
    fclose($output);
    
} elseif ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>Operator ID</th><th>First Name</th><th>Last Name</th><th>Address</th>';
    echo '<th>Contact Number</th><th>License Number</th><th>License Expiry</th>';
    echo '<th>Plate Number</th><th>Vehicle Type</th><th>Make</th><th>Model</th>';
    echo '<th>Year</th><th>Engine Number</th><th>Chassis Number</th>';
    echo '<th>Seating Capacity</th><th>Compliance Score</th><th>Status</th>';
    echo '</tr>';
    
    foreach ($operators as $operator) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($operator['operator_id']) . '</td>';
        echo '<td>' . htmlspecialchars($operator['first_name']) . '</td>';
        echo '<td>' . htmlspecialchars($operator['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($operator['address']) . '</td>';
        echo '<td>' . htmlspecialchars($operator['contact_number']) . '</td>';
        echo '<td>' . htmlspecialchars($operator['license_number']) . '</td>';
        echo '<td>' . htmlspecialchars($operator['license_expiry']) . '</td>';
        echo '<td>' . htmlspecialchars($operator['plate_number'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($operator['vehicle_type'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($operator['make'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($operator['model'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($operator['year_manufactured'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($operator['engine_number'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($operator['chassis_number'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($operator['seating_capacity'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($operator['compliance_score'] ?? '0') . '</td>';
        echo '<td>' . htmlspecialchars($operator['status'] ?? 'active') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
} elseif ($format === 'pdf') {
    // Simple PDF generation using HTML to PDF conversion
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    
    // Start output buffering
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Vehicle and Operator Records</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .header { text-align: center; margin-bottom: 20px; }
            .header h1 { margin: 0; font-size: 16px; }
            .header p { margin: 5px 0; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>CALOOCAN CITY - TRANSPORT AND MOBILITY MANAGEMENT</h1>
            <p>Vehicle and Operator Records Report</p>
            <p>Generated on: <?php echo date('F d, Y'); ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>License</th>
                    <th>Vehicle</th>
                    <th>Plate</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($operators as $operator): ?>
                <tr>
                    <td><?php echo htmlspecialchars($operator['operator_id']); ?></td>
                    <td><?php echo htmlspecialchars($operator['first_name'] . ' ' . $operator['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($operator['contact_number']); ?></td>
                    <td><?php echo htmlspecialchars($operator['license_number']); ?></td>
                    <td><?php echo htmlspecialchars(($operator['make'] ?? 'N/A') . ' ' . ($operator['model'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($operator['plate_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($operator['vehicle_type'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($operator['status'] ?? 'active'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    
    $html = ob_get_clean();
    
    // Use DomPDF or similar library if available, otherwise use simple HTML output
    if (class_exists('Dompdf\Dompdf')) {
        require_once 'vendor/autoload.php';
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        echo $dompdf->output();
    } else {
        // Fallback: output HTML with PDF headers (browsers will handle PDF conversion)
        echo $html;
    }
}

exit;
?>