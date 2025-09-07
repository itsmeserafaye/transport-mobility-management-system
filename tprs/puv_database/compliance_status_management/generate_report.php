<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$reportType = $_GET['type'] ?? 'summary';
$complianceId = $_GET['id'] ?? null;

header('Content-Type: application/json');

try {
    switch ($reportType) {
        case 'summary':
            $data = generateSummaryReport($conn);
            break;
        case 'detailed':
            $data = generateDetailedReport($conn);
            break;
        case 'overdue':
            $data = generateOverdueReport($conn);
            break;
        case 'violations':
            $data = generateViolationReport($conn);
            break;
        case 'compliance_trends':
            $data = generateTrendsReport($conn);
            break;
        case 'individual':
            $data = generateIndividualReport($conn, $complianceId);
            break;
        default:
            throw new Exception('Invalid report type');
    }
    
    echo json_encode(['success' => true, 'data' => $data, 'message' => 'Report generated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateSummaryReport($conn) {
    $stats = getStatistics($conn);
    return [
        'report_type' => 'Summary Report',
        'generated_at' => date('Y-m-d H:i:s'),
        'total_operators' => $stats['total_operators'],
        'active_vehicles' => $stats['active_vehicles'],
        'compliance_rate' => $stats['compliance_rate'] . '%',
        'pending_inspections' => $stats['pending_inspections'],
        'total_violations' => $stats['total_violations'],
        'unpaid_fines' => 'PHP ' . number_format($stats['unpaid_fines'], 2)
    ];
}

function generateDetailedReport($conn) {
    $compliance = getComplianceStatus($conn);
    return [
        'report_type' => 'Detailed Compliance Report',
        'generated_at' => date('Y-m-d H:i:s'),
        'total_records' => count($compliance),
        'records' => array_map(function($comp) {
            return [
                'compliance_id' => $comp['compliance_id'],
                'operator_name' => $comp['first_name'] . ' ' . $comp['last_name'],
                'plate_number' => $comp['plate_number'],
                'vehicle_type' => $comp['vehicle_type'],
                'franchise_status' => $comp['franchise_status'],
                'inspection_status' => $comp['inspection_status'],
                'compliance_score' => $comp['compliance_score'],
                'violation_count' => $comp['violation_count'] ?? 0,
                'last_violation' => $comp['last_violation_date'] ?? 'None'
            ];
        }, $compliance)
    ];
}

function generateOverdueReport($conn) {
    $query = "SELECT cs.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              WHERE cs.inspection_status = 'overdue' OR cs.next_inspection_due < CURDATE()
              ORDER BY cs.next_inspection_due ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'report_type' => 'Overdue Inspections Report',
        'generated_at' => date('Y-m-d H:i:s'),
        'total_overdue' => count($overdue),
        'records' => array_map(function($comp) {
            return [
                'compliance_id' => $comp['compliance_id'],
                'operator_name' => $comp['first_name'] . ' ' . $comp['last_name'],
                'plate_number' => $comp['plate_number'],
                'vehicle_type' => $comp['vehicle_type'],
                'inspection_due' => $comp['next_inspection_due'],
                'days_overdue' => max(0, (time() - strtotime($comp['next_inspection_due'])) / (60*60*24))
            ];
        }, $overdue)
    ];
}

function generateViolationReport($conn) {
    $query = "SELECT va.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM violation_analytics va
              JOIN operators o ON va.operator_id = o.operator_id
              JOIN vehicles v ON va.vehicle_id = v.vehicle_id
              ORDER BY va.total_violations DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'report_type' => 'Violation Analysis Report',
        'generated_at' => date('Y-m-d H:i:s'),
        'total_records' => count($violations),
        'repeat_offenders' => count(array_filter($violations, function($v) { return $v['repeat_offender_flag']; })),
        'records' => array_map(function($viol) {
            return [
                'operator_name' => $viol['first_name'] . ' ' . $viol['last_name'],
                'plate_number' => $viol['plate_number'],
                'vehicle_type' => $viol['vehicle_type'],
                'total_violations' => $viol['total_violations'],
                'risk_level' => $viol['risk_level'],
                'repeat_offender' => $viol['repeat_offender_flag'] ? 'Yes' : 'No',
                'last_violation' => $viol['last_violation_date']
            ];
        }, $violations)
    ];
}

function generateTrendsReport($conn) {
    $query = "SELECT 
                DATE_FORMAT(updated_at, '%Y-%m') as month,
                AVG(compliance_score) as avg_score,
                COUNT(*) as total_records,
                SUM(CASE WHEN compliance_score >= 80 THEN 1 ELSE 0 END) as compliant_count
              FROM compliance_status 
              WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
              ORDER BY month DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'report_type' => 'Compliance Trends Report',
        'generated_at' => date('Y-m-d H:i:s'),
        'period' => 'Last 12 months',
        'trends' => array_map(function($trend) {
            return [
                'month' => $trend['month'],
                'average_score' => round($trend['avg_score'], 2),
                'total_records' => $trend['total_records'],
                'compliance_rate' => round(($trend['compliant_count'] / $trend['total_records']) * 100, 2) . '%'
            ];
        }, $trends)
    ];
}

function generateIndividualReport($conn, $complianceId) {
    if (!$complianceId) {
        throw new Exception('Compliance ID required for individual report');
    }
    
    $comp = getComplianceById($conn, $complianceId);
    if (!$comp) {
        throw new Exception('Compliance record not found');
    }
    
    return [
        'report_type' => 'Individual Compliance Report',
        'generated_at' => date('Y-m-d H:i:s'),
        'compliance_id' => $comp['compliance_id'],
        'operator_name' => $comp['first_name'] . ' ' . $comp['last_name'],
        'plate_number' => $comp['plate_number'],
        'vehicle_info' => $comp['vehicle_type'] . ' - ' . $comp['make'] . ' ' . $comp['model'],
        'franchise_status' => $comp['franchise_status'],
        'inspection_status' => $comp['inspection_status'],
        'compliance_score' => $comp['compliance_score'],
        'next_inspection_due' => $comp['next_inspection_due'],
        'recommendations' => generateRecommendations($comp['compliance_score'])
    ];
}

function generateRecommendations($score) {
    if ($score >= 90) {
        return 'Excellent compliance. Continue current practices.';
    } elseif ($score >= 80) {
        return 'Good compliance. Minor improvements needed.';
    } elseif ($score >= 60) {
        return 'Fair compliance. Attention required.';
    } else {
        return 'Poor compliance. Immediate action required.';
    }
}
?>