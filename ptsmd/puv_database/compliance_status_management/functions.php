<?php
require_once '../../../config/database.php';

// Filter compliance status records
function filterComplianceStatus($db, $filters = []) {
    $query = "SELECT cs.*, 
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     v.plate_number, v.vehicle_type,
                     fr.franchise_number, fr.route_assigned
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              LEFT JOIN franchise_records fr ON cs.operator_id = fr.operator_id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['franchise_status'])) {
        $query .= " AND cs.franchise_status = ?";
        $params[] = $filters['franchise_status'];
    }
    
    if (!empty($filters['inspection_status'])) {
        $query .= " AND cs.inspection_status = ?";
        $params[] = $filters['inspection_status'];
    }
    
    if (!empty($filters['compliance_score_min'])) {
        $query .= " AND cs.compliance_score >= ?";
        $params[] = $filters['compliance_score_min'];
    }
    
    if (!empty($filters['compliance_score_max'])) {
        $query .= " AND cs.compliance_score <= ?";
        $params[] = $filters['compliance_score_max'];
    }
    
    if (!empty($filters['violation_count_min'])) {
        $query .= " AND cs.violation_count >= ?";
        $params[] = $filters['violation_count_min'];
    }
    
    if (!empty($filters['inspection_overdue'])) {
        $query .= " AND cs.next_inspection_due < CURDATE()";
    }
    
    if (!empty($filters['search'])) {
        $query .= " AND (CONCAT(o.first_name, ' ', o.last_name) LIKE ? OR v.plate_number LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }
    
    $query .= " ORDER BY cs.updated_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Update compliance status
function updateComplianceStatus($db, $compliance_id, $data) {
    $query = "UPDATE compliance_status SET 
              franchise_status = ?, 
              inspection_status = ?, 
              violation_count = ?, 
              last_inspection_date = ?, 
              next_inspection_due = ?, 
              compliance_score = ?,
              updated_at = CURRENT_TIMESTAMP
              WHERE compliance_id = ?";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([
        $data['franchise_status'],
        $data['inspection_status'],
        $data['violation_count'],
        $data['last_inspection_date'],
        $data['next_inspection_due'],
        $data['compliance_score'],
        $compliance_id
    ]);
}

// Generate compliance report
function generateComplianceReport($db, $report_type = 'summary') {
    switch ($report_type) {
        case 'summary':
            return generateSummaryReport($db);
        case 'detailed':
            return generateDetailedReport($db);
        case 'overdue':
            return generateOverdueReport($db);
        case 'violations':
            return generateViolationsReport($db);
        default:
            return generateSummaryReport($db);
    }
}

// Summary report
function generateSummaryReport($db) {
    $query = "SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN franchise_status = 'valid' THEN 1 ELSE 0 END) as valid_franchise,
                SUM(CASE WHEN franchise_status = 'expired' THEN 1 ELSE 0 END) as expired_franchise,
                SUM(CASE WHEN inspection_status = 'passed' THEN 1 ELSE 0 END) as passed_inspection,
                SUM(CASE WHEN inspection_status = 'overdue' THEN 1 ELSE 0 END) as overdue_inspection,
                AVG(compliance_score) as avg_compliance_score,
                SUM(violation_count) as total_violations
              FROM compliance_status";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Detailed report
function generateDetailedReport($db) {
    $query = "SELECT cs.*, 
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     v.plate_number, v.vehicle_type,
                     fr.franchise_number, fr.route_assigned,
                     CASE 
                         WHEN cs.next_inspection_due < CURDATE() THEN 'Overdue'
                         WHEN cs.next_inspection_due <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Due Soon'
                         ELSE 'Current'
                     END as inspection_urgency
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              LEFT JOIN franchise_records fr ON cs.operator_id = fr.operator_id
              ORDER BY cs.compliance_score ASC, cs.violation_count DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Overdue inspections report
function generateOverdueReport($db) {
    $query = "SELECT cs.*, 
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     v.plate_number, v.vehicle_type,
                     DATEDIFF(CURDATE(), cs.next_inspection_due) as days_overdue
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              WHERE cs.next_inspection_due < CURDATE()
              ORDER BY days_overdue DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Violations report
function generateViolationsReport($db) {
    $query = "SELECT cs.*, 
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     v.plate_number, v.vehicle_type,
                     vh.violation_type, vh.violation_date, vh.fine_amount, vh.settlement_status
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              LEFT JOIN violation_history vh ON cs.operator_id = vh.operator_id
              WHERE cs.violation_count > 0
              ORDER BY cs.violation_count DESC, vh.violation_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Export report to CSV
function exportReportToCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

// Bulk update compliance status
function bulkUpdateComplianceStatus($db, $updates) {
    $db->beginTransaction();
    
    try {
        foreach ($updates as $update) {
            updateComplianceStatus($db, $update['compliance_id'], $update['data']);
        }
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

// Calculate compliance score
function calculateComplianceScore($franchise_status, $inspection_status, $violation_count) {
    $score = 100;
    
    // Franchise status impact
    switch ($franchise_status) {
        case 'expired': $score -= 30; break;
        case 'revoked': $score -= 50; break;
        case 'pending': $score -= 10; break;
    }
    
    // Inspection status impact
    switch ($inspection_status) {
        case 'failed': $score -= 25; break;
        case 'overdue': $score -= 20; break;
        case 'pending': $score -= 5; break;
    }
    
    // Violation count impact
    $score -= ($violation_count * 5);
    
    return max(0, min(100, $score));
}

// Get statistics for dashboard and reports
function getStatistics($db) {
    $stats = [];
    
    // Total operators
    $query = "SELECT COUNT(DISTINCT operator_id) as total FROM compliance_status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_operators'] = $stmt->fetchColumn();
    
    // Active vehicles
    $query = "SELECT COUNT(DISTINCT vehicle_id) as total FROM compliance_status WHERE franchise_status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['active_vehicles'] = $stmt->fetchColumn();
    
    // Compliance rate
    $query = "SELECT AVG(compliance_score) as avg_score FROM compliance_status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['compliance_rate'] = round($stmt->fetchColumn(), 1);
    
    // Pending inspections
    $query = "SELECT COUNT(*) as total FROM compliance_status WHERE inspection_status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_inspections'] = $stmt->fetchColumn();
    
    // Total violations
    $query = "SELECT SUM(violation_count) as total FROM compliance_status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_violations'] = $stmt->fetchColumn() ?: 0;
    
    return $stats;
}

// Get compliance statistics
function getComplianceStatistics($db) {
    $stats = [];
    
    // Total records
    $query = "SELECT COUNT(*) as total FROM compliance_status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total'] = $stmt->fetchColumn();
    
    // Franchise status breakdown
    $query = "SELECT franchise_status, COUNT(*) as count FROM compliance_status GROUP BY franchise_status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['franchise_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Inspection status breakdown
    $query = "SELECT inspection_status, COUNT(*) as count FROM compliance_status GROUP BY inspection_status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['inspection_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compliance score ranges
    $query = "SELECT 
                SUM(CASE WHEN compliance_score >= 90 THEN 1 ELSE 0 END) as excellent,
                SUM(CASE WHEN compliance_score >= 70 AND compliance_score < 90 THEN 1 ELSE 0 END) as good,
                SUM(CASE WHEN compliance_score >= 50 AND compliance_score < 70 THEN 1 ELSE 0 END) as fair,
                SUM(CASE WHEN compliance_score < 50 THEN 1 ELSE 0 END) as poor
              FROM compliance_status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['score_ranges'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $stats;
}

// Get compliance status records for display
function getComplianceStatus($db) {
    $query = "SELECT cs.*, 
                     o.first_name, o.last_name,
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     v.plate_number, v.vehicle_type, v.make, v.model,
                     fr.franchise_number, fr.route_assigned,
                     va.last_violation_date
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              LEFT JOIN franchise_records fr ON cs.operator_id = fr.operator_id
              LEFT JOIN violation_analytics va ON cs.operator_id = va.operator_id AND cs.vehicle_id = va.vehicle_id
              ORDER BY cs.updated_at DESC
              LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get compliance record by ID
function getComplianceById($db, $compliance_id) {
    $query = "SELECT cs.*, 
                     o.first_name, o.last_name,
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     v.plate_number, v.vehicle_type, v.make, v.model,
                     fr.franchise_number, fr.route_assigned,
                     va.last_violation_date
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              LEFT JOIN franchise_records fr ON cs.operator_id = fr.operator_id
              LEFT JOIN violation_analytics va ON cs.operator_id = va.operator_id AND cs.vehicle_id = va.vehicle_id
              WHERE cs.compliance_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$compliance_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>