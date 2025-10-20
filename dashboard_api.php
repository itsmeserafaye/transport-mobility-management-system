<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

try {
    $dashboardData = [];
    
    // Total violations
    $stmt = $conn->query("SELECT COUNT(*) as total_violations FROM violation_history");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dashboardData['total_violations'] = (int)$result['total_violations'];
    
    // Repeat offenders
    $stmt = $conn->query("SELECT COUNT(*) as repeat_offenders FROM (SELECT operator_id FROM violation_history GROUP BY operator_id HAVING COUNT(*) > 1) as repeat_count");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dashboardData['repeat_offenders'] = (int)$result['repeat_offenders'];
    
    // Violation hotspots
    $stmt = $conn->query("SELECT COUNT(*) as hotspot_count FROM (SELECT location, COUNT(*) as violation_count FROM violation_history WHERE location IS NOT NULL AND location != '' GROUP BY location HAVING COUNT(*) >= 3) as hotspots");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dashboardData['violation_hotspots'] = (int)$result['hotspot_count'];
    
    // All time revenue (paid violations)
    $stmt = $conn->query("SELECT SUM(fine_amount) as all_revenue FROM violation_history WHERE settlement_status = 'paid'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dashboardData['revenue_collected'] = (float)($result['all_revenue'] ?? 0);
    
    // Monthly revenue
    $stmt = $conn->query("SELECT SUM(fine_amount) as monthly_revenue FROM violation_history WHERE settlement_status = 'paid' AND MONTH(settlement_date) = MONTH(CURRENT_DATE()) AND YEAR(settlement_date) = YEAR(CURRENT_DATE())");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dashboardData['monthly_revenue'] = (float)($result['monthly_revenue'] ?? 0);
    
    // Unpaid violations
    $stmt = $conn->query("SELECT COUNT(*) as unpaid_violations FROM violation_history WHERE settlement_status = 'unpaid'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dashboardData['unpaid_violations'] = (int)$result['unpaid_violations'];
    
    // Pending applications
    $stmt = $conn->query("SELECT COUNT(*) as pending_applications FROM franchise_applications WHERE status IN ('submitted', 'under_review', 'pending_documents')");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dashboardData['pending_applications'] = (int)$result['pending_applications'];
    
    // Overdue inspections
    $stmt = $conn->query("SELECT COUNT(*) as overdue_inspections FROM compliance_status WHERE inspection_status = 'overdue'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dashboardData['overdue_inspections'] = (int)$result['overdue_inspections'];
    
    echo json_encode([
        'success' => true,
        'data' => $dashboardData,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>