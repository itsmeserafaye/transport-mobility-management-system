<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$format = $_GET['format'] ?? 'csv';
$applications = getFranchiseApplications($conn);

switch ($format) {
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="franchise_applications.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Application ID', 'Operator Name', 'Application Type', 'Route', 'Status', 'Workflow Stage', 'Date Applied']);
        
        foreach ($applications as $app) {
            fputcsv($output, [
                $app['application_id'],
                $app['first_name'] . ' ' . $app['last_name'],
                ucfirst($app['application_type']),
                $app['route_requested'],
                ucfirst(str_replace('_', ' ', $app['status'])),
                ucfirst(str_replace('_', ' ', $app['workflow_stage'])),
                date('M d, Y', strtotime($app['application_date']))
            ]);
        }
        fclose($output);
        break;
        
    case 'excel':
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="franchise_applications.xls"');
        
        echo "<table border='1'>";
        echo "<tr><th>Application ID</th><th>Operator Name</th><th>Application Type</th><th>Route</th><th>Status</th><th>Workflow Stage</th><th>Date Applied</th></tr>";
        
        foreach ($applications as $app) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($app['application_id']) . "</td>";
            echo "<td>" . htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars(ucfirst($app['application_type'])) . "</td>";
            echo "<td>" . htmlspecialchars($app['route_requested']) . "</td>";
            echo "<td>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $app['status']))) . "</td>";
            echo "<td>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $app['workflow_stage']))) . "</td>";
            echo "<td>" . htmlspecialchars(date('M d, Y', strtotime($app['application_date']))) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        break;
        
    case 'pdf':
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="franchise_applications.pdf"');
        
        echo "PDF export functionality requires additional libraries (TCPDF/FPDF)";
        break;
        
    case 'word':
        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename="franchise_applications.doc"');
        
        echo "<html><body>";
        echo "<h1>Franchise Applications Report</h1>";
        echo "<table border='1'>";
        echo "<tr><th>Application ID</th><th>Operator Name</th><th>Application Type</th><th>Route</th><th>Status</th><th>Workflow Stage</th><th>Date Applied</th></tr>";
        
        foreach ($applications as $app) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($app['application_id']) . "</td>";
            echo "<td>" . htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars(ucfirst($app['application_type'])) . "</td>";
            echo "<td>" . htmlspecialchars($app['route_requested']) . "</td>";
            echo "<td>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $app['status']))) . "</td>";
            echo "<td>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $app['workflow_stage']))) . "</td>";
            echo "<td>" . htmlspecialchars(date('M d, Y', strtotime($app['application_date']))) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</body></html>";
        break;
}
?>