<?php
if (!class_exists('Database')) {
    require_once '../../../config/database.php';
}

// Integrate violation records from ticketing system
function integrateViolationRecords($db, $violation_data) {
    $query = "INSERT INTO violation_history (violation_id, operator_id, vehicle_id, violation_type, 
              violation_date, fine_amount, settlement_status, location, ticket_number) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";


// Link violations to operators and vehicles
function linkViolationsToOperators($db, $filters = []) {
    $query = "SELECT vh.*, 
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     o.first_name, o.last_name,
                     v.plate_number, v.vehicle_type, v.make, v.model,
                     va.total_violations, va.risk_level
              FROM violation_history vh
              JOIN operators o ON vh.operator_id = o.operator_id
              JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
              LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['operator_id'])) {
        $query .= " AND vh.operator_id = ?";
        $params[] = $filters['operator_id'];
    }
    
    if (!empty($filters['settlement_status'])) {
        $query .= " AND vh.settlement_status = ?";
        $params[] = $filters['settlement_status'];
    }
    
    if (!empty($filters['date_from'])) {
        $query .= " AND vh.violation_date >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $query .= " AND vh.violation_date <= ?";
        $params[] = $filters['date_to'];
    }
    
    $query .= " ORDER BY vh.violation_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Track violation trends
function trackViolationTrends($db, $period = 'monthly') {
    switch ($period) {
        case 'daily':
            $date_format = '%Y-%m-%d';
            break;
        case 'weekly':
            $date_format = '%Y-%u';
            break;
        case 'yearly':
            $date_format = '%Y';
            break;
        default:
            $date_format = '%Y-%m';
    }
    
    $query = "SELECT DATE_FORMAT(violation_date, ?) as period,
                     COUNT(*) as total_violations,
                     SUM(fine_amount) as total_fines,
                     COUNT(DISTINCT operator_id) as unique_operators
              FROM violation_history 
              WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
              GROUP BY period
              ORDER BY period DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$date_format]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generate violation summaries
function generateViolationSummary($db, $operator_id = null) {
    $query = "SELECT 
                COUNT(*) as total_violations,
                SUM(CASE WHEN settlement_status = 'paid' THEN 1 ELSE 0 END) as paid_violations,
                SUM(CASE WHEN settlement_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_violations,
                SUM(fine_amount) as total_fines,
                SUM(CASE WHEN settlement_status = 'paid' THEN fine_amount ELSE 0 END) as paid_amount,
                AVG(fine_amount) as avg_fine,
                MAX(violation_date) as last_violation_date,
                COUNT(DISTINCT violation_type) as violation_types
              FROM violation_history";
    
    $params = [];
    if ($operator_id) {
        $query .= " WHERE operator_id = ?";
        $params[] = $operator_id;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Update settlement status
function updateSettlementStatus($db, $violation_id, $status, $settlement_date = null) {
    $query = "UPDATE violation_history SET 
              settlement_status = ?, 
              settlement_date = ?
              WHERE violation_id = ?";
    
    $stmt = $db->prepare($query);
    return $stmt->execute([$status, $settlement_date, $violation_id]);
}

// Get violation statistics
function getViolationStatistics($db) {
    $stats = [];
    
    // Total violations
    $query = "SELECT COUNT(*) as total FROM violation_history";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_violations'] = $stmt->fetchColumn();
    
    // Settlement breakdown
    $query = "SELECT settlement_status, COUNT(*) as count, SUM(fine_amount) as amount 
              FROM violation_history GROUP BY settlement_status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['settlement_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top violation types
    $query = "SELECT violation_type, COUNT(*) as count 
              FROM violation_history 
              GROUP BY violation_type 
              ORDER BY count DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['top_violations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly trends
    $query = "SELECT DATE_FORMAT(violation_date, '%Y-%m') as month, 
                     COUNT(*) as violations,
                     SUM(fine_amount) as fines
              FROM violation_history 
              WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              GROUP BY month 
              ORDER BY month";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['monthly_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

// Sync with external ticketing system
function syncWithTicketingSystem($db, $external_data) {
    $synced = 0;
    $errors = [];
    
    foreach ($external_data as $record) {
        try {
            // Check if violation already exists
            $check_query = "SELECT violation_id FROM violation_history WHERE ticket_number = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$record['ticket_number']]);
            
            if (!$check_stmt->fetch()) {
                // Insert new violation
                if (integrateViolationRecords($db, $record)) {
                    $synced++;
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error syncing ticket {$record['ticket_number']}: " . $e->getMessage();
        }
    }
    
    return ['synced' => $synced, 'errors' => $errors];
}

// Export violation data
function exportViolationData($db, $format = 'csv', $filters = []) {
    $data = linkViolationsToOperators($db, $filters);
    
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
    }
    exit;
}

// Search violations function
if (!function_exists('searchViolations')) {
function searchViolations($db, $search = '', $settlement_status = '', $violation_type = '', $date_from = '', $date_to = '') {
    $query = "SELECT vh.*, 
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     o.first_name, o.last_name,
                     v.plate_number, v.vehicle_type, v.make, v.model,
                     va.total_violations, va.risk_level
              FROM violation_history vh
              LEFT JOIN operators o ON vh.operator_id = o.operator_id
              LEFT JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
              LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (CONCAT(o.first_name, ' ', o.last_name) LIKE ? OR v.plate_number LIKE ? OR vh.violation_type LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($settlement_status)) {
        $query .= " AND vh.settlement_status = ?";
        $params[] = $settlement_status;
    }
    
    if (!empty($violation_type)) {
        $query .= " AND vh.violation_type = ?";
        $params[] = $violation_type;
    }
    
    if (!empty($date_from)) {
        $query .= " AND vh.violation_date >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $query .= " AND vh.violation_date <= ?";
        $params[] = $date_to;
    }
    
    $query .= " ORDER BY vh.violation_date DESC LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}

// Get total violations count
if (!function_exists('getTotalViolations')) {
function getTotalViolations($db) {
    $query = "SELECT COUNT(*) FROM violation_history";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchColumn();
}
}

// Get violation history with pagination
if (!function_exists('getViolationHistory')) {
function getViolationHistory($db, $limit = 10, $offset = 0) {
    $query = "SELECT vh.*, 
                     CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                     o.first_name, o.last_name,
                     v.plate_number, v.vehicle_type, v.make, v.model,
                     va.total_violations, va.risk_level
              FROM violation_history vh
              LEFT JOIN operators o ON vh.operator_id = o.operator_id
              LEFT JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
              LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id
              ORDER BY vh.violation_date DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}

// Get statistics for violation history
if (!function_exists('getStatistics')) {
function getStatistics($db) {
    $stats = [];
    
    // Total violations
    $query = "SELECT COUNT(*) as total FROM violation_history";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_violations'] = $stmt->fetchColumn();
    
    // Unpaid fines
    $query = "SELECT SUM(fine_amount) as unpaid FROM violation_history WHERE settlement_status = 'unpaid'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['unpaid_fines'] = $stmt->fetchColumn() ?? 0;
    
    // Settlement rate
    $query = "SELECT 
                COUNT(CASE WHEN settlement_status = 'paid' THEN 1 END) as paid,
                COUNT(*) as total
              FROM violation_history";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['settlement_rate'] = $result['total'] > 0 ? round(($result['paid'] / $result['total']) * 100) : 0;
    
    // Repeat offenders
    $query = "SELECT COUNT(*) as repeat_offenders FROM (
                SELECT operator_id FROM violation_history 
                GROUP BY operator_id 
                HAVING COUNT(*) > 1
              ) as repeat_count";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['repeat_offenders'] = $stmt->fetchColumn();
    
    return $stats;
}
}
?>