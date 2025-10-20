<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "=== Database Data Check ===\n";

// Check violation_history table
echo "\n1. Violation History Data:\n";
$stmt = $conn->query("SELECT COUNT(*) as total_violations, SUM(fine_amount) as total_revenue, COUNT(CASE WHEN settlement_status = 'paid' THEN 1 END) as paid_violations FROM violation_history");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total Violations: " . $result['total_violations'] . "\n";
echo "Total Revenue: ₱" . number_format($result['total_revenue'] ?? 0, 2) . "\n";
echo "Paid Violations: " . $result['paid_violations'] . "\n";

// Check repeat offenders
echo "\n2. Repeat Offenders:\n";
$stmt = $conn->query("SELECT COUNT(*) as repeat_offenders FROM (SELECT operator_id FROM violation_history GROUP BY operator_id HAVING COUNT(*) > 1) as repeat_count");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Repeat Offenders: " . $result['repeat_offenders'] . "\n";

// Check violation hotspots
echo "\n3. Violation Hotspots:\n";
$stmt = $conn->query("SELECT COUNT(*) as hotspot_count FROM (SELECT location, COUNT(*) as violation_count FROM violation_history WHERE location IS NOT NULL AND location != '' GROUP BY location HAVING COUNT(*) >= 3) as hotspots");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Hotspot Locations: " . $result['hotspot_count'] . "\n";

// Check operators and vehicles
echo "\n4. Operators and Vehicles:\n";
$stmt = $conn->query("SELECT COUNT(*) as total FROM operators WHERE status = 'active'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Active Operators: " . $result['total'] . "\n";

$stmt = $conn->query("SELECT COUNT(*) as total FROM vehicles WHERE status = 'active'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Active Vehicles: " . $result['total'] . "\n";

// Check monthly revenue
echo "\n5. Monthly Revenue:\n";
$stmt = $conn->query("SELECT SUM(fine_amount) as monthly_revenue FROM violation_history WHERE settlement_status = 'paid' AND MONTH(settlement_date) = MONTH(CURRENT_DATE()) AND YEAR(settlement_date) = YEAR(CURRENT_DATE())");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "This Month's Revenue: ₱" . number_format($result['monthly_revenue'] ?? 0, 2) . "\n";

// Check all revenue (paid violations)
echo "\n6. All Time Revenue:\n";
$stmt = $conn->query("SELECT SUM(fine_amount) as all_revenue FROM violation_history WHERE settlement_status = 'paid'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "All Time Revenue: ₱" . number_format($result['all_revenue'] ?? 0, 2) . "\n";

echo "\n=== End Check ===\n";
?>