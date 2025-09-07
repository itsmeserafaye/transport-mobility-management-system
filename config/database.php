<?php
// Database Configuration for Transport and Mobility Management System

// Create necessary directories if they don't exist
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/uploads')) {
    mkdir($_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/uploads', 0755, true);
}
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/uploads/documents')) {
    mkdir($_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/uploads/documents', 0755, true);
}
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/uploads/tickets')) {
    mkdir($_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/uploads/tickets', 0755, true);
}

class Database {
    private $host = 'localhost';
    private $db_name = 'transport_mobility_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Database helper functions
function getOperators($conn, $limit = 10, $offset = 0) {
    $query = "SELECT o.*, v.plate_number, v.vehicle_type, v.make, v.model, 
              cs.compliance_score, cs.franchise_status, cs.inspection_status
              FROM operators o 
              LEFT JOIN vehicles v ON o.operator_id = v.operator_id
              LEFT JOIN compliance_status cs ON o.operator_id = cs.operator_id
              ORDER BY o.date_registered DESC
              LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOperatorById($conn, $operator_id) {
    $query = "SELECT o.*, v.plate_number, v.vehicle_type, v.make, v.model, 
              cs.compliance_score, cs.franchise_status, cs.inspection_status
              FROM operators o 
              LEFT JOIN vehicles v ON o.operator_id = v.operator_id
              LEFT JOIN compliance_status cs ON o.operator_id = cs.operator_id
              WHERE o.operator_id = :operator_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':operator_id', $operator_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getVehiclesByOperator($conn, $operator_id) {
    $query = "SELECT v.*, cs.compliance_score, cs.franchise_status, cs.inspection_status
              FROM vehicles v
              LEFT JOIN compliance_status cs ON v.vehicle_id = cs.vehicle_id
              WHERE v.operator_id = :operator_id
              ORDER BY v.date_registered DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':operator_id', $operator_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchOperators($conn, $search_term, $status = null, $vehicle_type = null) {
    $query = "SELECT o.*, v.plate_number, v.vehicle_type, v.make, v.model, 
              cs.compliance_score, cs.franchise_status, cs.inspection_status
              FROM operators o 
              LEFT JOIN vehicles v ON o.operator_id = v.operator_id
              LEFT JOIN compliance_status cs ON o.operator_id = cs.operator_id
              WHERE (o.first_name LIKE :search OR o.last_name LIKE :search 
                     OR o.operator_id LIKE :search OR v.plate_number LIKE :search)";
    
    if ($status) {
        $query .= " AND o.status = :status";
    }
    if ($vehicle_type) {
        $query .= " AND v.vehicle_type = :vehicle_type";
    }
    
    $query .= " ORDER BY o.date_registered DESC";
    
    $stmt = $conn->prepare($query);
    $search_param = '%' . $search_term . '%';
    $stmt->bindParam(':search', $search_param);
    
    if ($status) {
        $stmt->bindParam(':status', $status);
    }
    if ($vehicle_type) {
        $stmt->bindParam(':vehicle_type', $vehicle_type);
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalOperators($conn) {
    $query = "SELECT COUNT(*) as total FROM operators";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

function getComplianceStatus($conn) {
    // First, create missing compliance records
    $create_missing = "INSERT IGNORE INTO compliance_status (compliance_id, operator_id, vehicle_id, franchise_status, inspection_status, violation_count, compliance_score)
                       SELECT 
                           CONCAT('CS-', YEAR(CURDATE()), '-', LPAD((SELECT COUNT(*) FROM compliance_status) + ROW_NUMBER() OVER (ORDER BY o.operator_id), 3, '0')) as compliance_id,
                           o.operator_id,
                           v.vehicle_id,
                           'pending' as franchise_status,
                           'pending' as inspection_status,
                           0 as violation_count,
                           75.00 as compliance_score
                       FROM operators o
                       JOIN vehicles v ON o.operator_id = v.operator_id
                       LEFT JOIN compliance_status cs ON o.operator_id = cs.operator_id AND v.vehicle_id = cs.vehicle_id
                       WHERE cs.compliance_id IS NULL";
    $conn->exec($create_missing);
    
    // Update compliance scores based on current status
    $update_scores = "UPDATE compliance_status cs
                      LEFT JOIN franchise_records fr ON cs.operator_id = fr.operator_id AND cs.vehicle_id = fr.vehicle_id
                      LEFT JOIN (
                          SELECT operator_id, vehicle_id, COUNT(*) as violation_count
                          FROM violation_history 
                          WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                          GROUP BY operator_id, vehicle_id
                      ) vh ON cs.operator_id = vh.operator_id AND cs.vehicle_id = vh.vehicle_id
                      SET cs.compliance_score = (
                          CASE 
                              WHEN cs.franchise_status = 'valid' THEN 40
                              WHEN cs.franchise_status = 'pending' THEN 20
                              ELSE 0
                          END +
                          CASE 
                              WHEN cs.inspection_status = 'passed' THEN 40
                              WHEN cs.inspection_status = 'pending' THEN 20
                              ELSE 0
                          END +
                          CASE 
                              WHEN COALESCE(vh.violation_count, 0) = 0 THEN 20
                              WHEN COALESCE(vh.violation_count, 0) <= 2 THEN 10
                              ELSE 0
                          END
                      ),
                      cs.violation_count = COALESCE(vh.violation_count, 0)";
    $conn->exec($update_scores);
    
    // Now get all compliance records
    $query = "SELECT cs.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model,
              vh.violation_count, vh.last_violation_date
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              LEFT JOIN (
                  SELECT operator_id, vehicle_id, COUNT(*) as violation_count, MAX(violation_date) as last_violation_date
                  FROM violation_history 
                  GROUP BY operator_id, vehicle_id
              ) vh ON cs.operator_id = vh.operator_id AND cs.vehicle_id = vh.vehicle_id
              ORDER BY cs.updated_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getViolationHistory($conn) {
    $query = "SELECT vh.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model,
              va.total_violations, va.risk_level, va.repeat_offender_flag
              FROM violation_history vh
              JOIN operators o ON vh.operator_id = o.operator_id
              JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
              LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id AND vh.vehicle_id = va.vehicle_id
              ORDER BY vh.violation_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStatistics($conn) {
    $stats = [];
    
    // Total operators
    $query = "SELECT COUNT(*) as total FROM operators";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['total_operators'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active vehicles
    $query = "SELECT COUNT(*) as total FROM vehicles WHERE status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['active_vehicles'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Compliant vehicles
    $query = "SELECT COUNT(*) as total FROM compliance_status WHERE compliance_score >= 80";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $compliant = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stats['compliance_rate'] = $stats['active_vehicles'] > 0 ? round(($compliant / $stats['active_vehicles']) * 100) : 0;
    
    // Pending inspections
    $query = "SELECT COUNT(*) as total FROM compliance_status WHERE inspection_status = 'pending' OR inspection_status = 'overdue'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['pending_inspections'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Violation statistics
    $query = "SELECT COUNT(*) as total FROM violation_history";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['total_violations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Unpaid fines
    $query = "SELECT SUM(fine_amount) as total FROM violation_history WHERE settlement_status IN ('unpaid', 'partial')";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['unpaid_fines'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Repeat offenders
    $query = "SELECT COUNT(*) as total FROM violation_analytics WHERE repeat_offender_flag = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['repeat_offenders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Settlement rate
    $query = "SELECT COUNT(*) as paid FROM violation_history WHERE settlement_status = 'paid'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $paid = $stmt->fetch(PDO::FETCH_ASSOC)['paid'];
    $stats['settlement_rate'] = $stats['total_violations'] > 0 ? round(($paid / $stats['total_violations']) * 100) : 0;
    
    return $stats;
}

// Additional utility functions for vehicle and operator records
function addOperator($conn, $data) {
    $query = "INSERT INTO operators (operator_id, first_name, last_name, address, contact_number, license_number, license_expiry) 
              VALUES (:operator_id, :first_name, :last_name, :address, :contact_number, :license_number, :license_expiry)";
    $stmt = $conn->prepare($query);
    return $stmt->execute($data);
}

function updateOperator($conn, $operator_id, $data) {
    try {
        $query = "UPDATE operators SET first_name = :first_name, last_name = :last_name, 
                  address = :address, contact_number = :contact_number, license_expiry = :license_expiry 
                  WHERE operator_id = :operator_id";
        $stmt = $conn->prepare($query);
        $data['operator_id'] = $operator_id;
        $result = $stmt->execute($data);
        
        // Check if any rows were affected
        if ($result && $stmt->rowCount() > 0) {
            return true;
        } else {
            error_log("No rows updated for operator_id: " . $operator_id);
            return false;
        }
    } catch (PDOException $e) {
        error_log("Update operator error: " . $e->getMessage());
        return false;
    }
}

function deleteOperator($conn, $operator_id) {
    $query = "DELETE FROM operators WHERE operator_id = :operator_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':operator_id', $operator_id);
    return $stmt->execute();
}

function addVehicle($conn, $data) {
    $query = "INSERT INTO vehicles (vehicle_id, operator_id, plate_number, vehicle_type, make, model, 
              year_manufactured, engine_number, chassis_number, seating_capacity) 
              VALUES (:vehicle_id, :operator_id, :plate_number, :vehicle_type, :make, :model, 
              :year_manufactured, :engine_number, :chassis_number, :seating_capacity)";
    $stmt = $conn->prepare($query);
    return $stmt->execute($data);
}

function generateOperatorId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM operators WHERE operator_id LIKE 'OP-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "OP-{$year}-{$next_id}";
}

function generateVehicleId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM vehicles WHERE vehicle_id LIKE 'VH-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "VH-{$year}-{$next_id}";
}

// Compliance Status Management Functions
function updateComplianceStatus($conn, $compliance_id, $data) {
    try {
        $query = "UPDATE compliance_status SET franchise_status = :franchise_status, 
                  inspection_status = :inspection_status, compliance_score = :compliance_score,
                  next_inspection_due = :next_inspection_due WHERE compliance_id = :compliance_id";
        $stmt = $conn->prepare($query);
        $data['compliance_id'] = $compliance_id;
        return $stmt->execute($data);
    } catch (PDOException $e) {
        error_log("Update compliance error: " . $e->getMessage());
        return false;
    }
}

function generateComplianceReport($conn, $compliance_id) {
    try {
        // Get compliance data
        $compliance = getComplianceById($conn, $compliance_id);
        if (!$compliance) {
            return false;
        }
        
        // Generate report data (simulate report generation)
        $report_data = [
            'compliance_id' => $compliance_id,
            'operator_name' => $compliance['first_name'] . ' ' . $compliance['last_name'],
            'vehicle_info' => $compliance['plate_number'] . ' - ' . $compliance['vehicle_type'],
            'franchise_status' => $compliance['franchise_status'],
            'inspection_status' => $compliance['inspection_status'],
            'compliance_score' => $compliance['compliance_score'],
            'report_date' => date('Y-m-d H:i:s'),
            'recommendations' => $compliance['compliance_score'] >= 90 ? 'Excellent compliance. Continue current practices.' :
                               ($compliance['compliance_score'] >= 80 ? 'Good compliance. Minor improvements needed.' :
                               ($compliance['compliance_score'] >= 60 ? 'Fair compliance. Attention required.' : 'Poor compliance. Immediate action required.'))
        ];
        
        // Log the report generation (you can save to file or database)
        error_log('Compliance report generated for: ' . $compliance_id . ' at ' . date('Y-m-d H:i:s'));
        
        return true;
    } catch (Exception $e) {
        error_log('Error generating compliance report: ' . $e->getMessage());
        return false;
    }
}

function getComplianceById($conn, $compliance_id) {
    $query = "SELECT cs.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model
              FROM compliance_status cs
              JOIN operators o ON cs.operator_id = o.operator_id
              JOIN vehicles v ON cs.vehicle_id = v.vehicle_id
              WHERE cs.compliance_id = :compliance_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':compliance_id', $compliance_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function exportComplianceData($conn, $format) {
    $compliance_data = getComplianceStatus($conn);
    
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="compliance_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Compliance ID', 'Operator', 'Vehicle', 'Franchise Status', 'Inspection Status', 'Compliance Score', 'Last Inspection', 'Next Due']);
        
        foreach ($compliance_data as $record) {
            fputcsv($output, [
                $record['compliance_id'],
                $record['first_name'] . ' ' . $record['last_name'],
                $record['plate_number'] . ' - ' . $record['vehicle_type'],
                $record['franchise_status'],
                $record['inspection_status'],
                $record['compliance_score'],
                $record['last_inspection_date'] ?? 'N/A',
                $record['next_inspection_due'] ?? 'N/A'
            ]);
        }
        fclose($output);
    }
    
    return $compliance_data;
}

// Franchise Application Functions
function getFranchiseApplications($conn) {
    $query = "SELECT fa.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model
              FROM franchise_applications fa
              JOIN operators o ON fa.operator_id = o.operator_id
              JOIN vehicles v ON fa.vehicle_id = v.vehicle_id
              ORDER BY fa.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getApplicationById($conn, $application_id) {
    $query = "SELECT fa.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model
              FROM franchise_applications fa
              JOIN operators o ON fa.operator_id = o.operator_id
              JOIN vehicles v ON fa.vehicle_id = v.vehicle_id
              WHERE fa.application_id = :application_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':application_id', $application_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateApplicationStatus($conn, $application_id, $status, $workflow_stage, $assigned_to = null, $remarks = null) {
    $query = "UPDATE franchise_applications SET status = :status, workflow_stage = :workflow_stage";
    $params = ['status' => $status, 'workflow_stage' => $workflow_stage, 'application_id' => $application_id];
    
    if ($assigned_to) {
        $query .= ", assigned_to = :assigned_to";
        $params['assigned_to'] = $assigned_to;
    }
    if ($remarks) {
        $query .= ", remarks = :remarks";
        $params['remarks'] = $remarks;
    }
    
    $query .= " WHERE application_id = :application_id";
    $stmt = $conn->prepare($query);
    return $stmt->execute($params);
}

function generateApplicationId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM franchise_applications WHERE application_id LIKE 'FA-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "FA-{$year}-{$next_id}";
}

// Document Repository Functions
function getDocuments($conn) {
    $query = "SELECT dr.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM document_repository dr
              JOIN operators o ON dr.operator_id = o.operator_id
              LEFT JOIN vehicles v ON dr.vehicle_id = v.vehicle_id
              ORDER BY dr.upload_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDocumentById($conn, $document_id) {
    $query = "SELECT dr.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM document_repository dr
              JOIN operators o ON dr.operator_id = o.operator_id
              LEFT JOIN vehicles v ON dr.vehicle_id = v.vehicle_id
              WHERE dr.document_id = :document_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':document_id', $document_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateDocumentStatus($conn, $document_id, $verification_status, $verified_by = null, $remarks = null) {
    $query = "UPDATE document_repository SET verification_status = :verification_status";
    $params = ['verification_status' => $verification_status, 'document_id' => $document_id];
    
    if ($verified_by) {
        $query .= ", verified_by = :verified_by, verification_date = NOW()";
        $params['verified_by'] = $verified_by;
    }
    if ($remarks) {
        $query .= ", remarks = :remarks";
        $params['remarks'] = $remarks;
    }
    
    $query .= " WHERE document_id = :document_id";
    $stmt = $conn->prepare($query);
    return $stmt->execute($params);
}

function generateDocumentId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM document_repository WHERE document_id LIKE 'DOC-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "DOC-{$year}-{$next_id}";
}

// Franchise Lifecycle Functions
function getFranchiseLifecycle($conn) {
    $query = "SELECT fl.*, fr.franchise_number, fr.route_assigned, fr.status as franchise_status,
                     o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM franchise_lifecycle fl
              JOIN franchise_records fr ON fl.franchise_id = fr.franchise_id
              JOIN operators o ON fl.operator_id = o.operator_id
              JOIN vehicles v ON fl.vehicle_id = v.vehicle_id
              ORDER BY fl.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateFranchiseStatus($conn, $franchise_id, $status, $lifecycle_stage, $processed_by, $remarks = null) {
    try {
        $conn->beginTransaction();
        
        // Update franchise record status
        $query1 = "UPDATE franchise_records SET status = :status WHERE franchise_id = :franchise_id";
        $stmt1 = $conn->prepare($query1);
        $stmt1->execute(['status' => $status, 'franchise_id' => $franchise_id]);
        
        // Update lifecycle stage
        $query2 = "UPDATE franchise_lifecycle SET lifecycle_stage = :lifecycle_stage, processed_by = :processed_by, remarks = :remarks WHERE franchise_id = :franchise_id";
        $stmt2 = $conn->prepare($query2);
        $stmt2->execute([
            'lifecycle_stage' => $lifecycle_stage,
            'processed_by' => $processed_by,
            'remarks' => $remarks,
            'franchise_id' => $franchise_id
        ]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function addLifecycleAction($conn, $lifecycle_id, $action_type, $reason, $processed_by, $effective_date = null) {
    $action_id = generateActionId($conn);
    $query = "INSERT INTO lifecycle_actions (action_id, lifecycle_id, action_type, action_date, reason, processed_by, effective_date) 
              VALUES (:action_id, :lifecycle_id, :action_type, CURDATE(), :reason, :processed_by, :effective_date)";
    $stmt = $conn->prepare($query);
    return $stmt->execute([
        'action_id' => $action_id,
        'lifecycle_id' => $lifecycle_id,
        'action_type' => $action_type,
        'reason' => $reason,
        'processed_by' => $processed_by,
        'effective_date' => $effective_date
    ]);
}

function generateActionId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM lifecycle_actions WHERE action_id LIKE 'LA-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "LA-{$year}-{$next_id}";
}

// Route & Schedule Publication Functions
function getRoutes($conn) {
    $query = "SELECT r.*, COUNT(s.schedule_id) as schedule_count,
              COUNT(CASE WHEN s.published_to_citizen = 1 THEN 1 END) as published_schedules
              FROM official_routes r
              LEFT JOIN route_schedules s ON r.route_id = s.route_id
              GROUP BY r.route_id
              ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRouteSchedules($conn) {
    $query = "SELECT rs.*, r.route_name, r.route_code, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM route_schedules rs
              JOIN official_routes r ON rs.route_id = r.route_id
              JOIN operators o ON rs.operator_id = o.operator_id
              JOIN vehicles v ON rs.vehicle_id = v.vehicle_id
              ORDER BY rs.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function publishToCitizenPortal($conn, $schedule_id, $published_by) {
    try {
        $conn->beginTransaction();
        
        // Update schedule as published
        $query1 = "UPDATE route_schedules SET published_to_citizen = 1, published_date = NOW(), published_by = :published_by WHERE schedule_id = :schedule_id";
        $stmt1 = $conn->prepare($query1);
        $stmt1->execute(['published_by' => $published_by, 'schedule_id' => $schedule_id]);
        
        // Get route and schedule info
        $query2 = "SELECT route_id FROM route_schedules WHERE schedule_id = :schedule_id";
        $stmt2 = $conn->prepare($query2);
        $stmt2->execute(['schedule_id' => $schedule_id]);
        $route_id = $stmt2->fetch(PDO::FETCH_ASSOC)['route_id'];
        
        // Create publication record
        $pub_id = generatePublicationId($conn);
        $query3 = "INSERT INTO citizen_portal_publications (publication_id, route_id, schedule_id, publication_type, published_by) VALUES (:pub_id, :route_id, :schedule_id, 'both', :published_by)";
        $stmt3 = $conn->prepare($query3);
        $stmt3->execute([
            'pub_id' => $pub_id,
            'route_id' => $route_id,
            'schedule_id' => $schedule_id,
            'published_by' => $published_by
        ]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function generatePublicationId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM citizen_portal_publications WHERE publication_id LIKE 'PUB-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "PUB-{$year}-{$next_id}";
}

// Enhanced Franchise Lifecycle Functions
function getLifecycleStatistics($conn) {
    $stats = [];
    
    // Active franchises
    $query = "SELECT COUNT(*) as total FROM franchise_lifecycle WHERE lifecycle_stage = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['active_franchises'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Due for renewal
    $query = "SELECT COUNT(*) as total FROM franchise_lifecycle WHERE action_required = 'renewal' OR lifecycle_stage = 'renewal'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['due_for_renewal'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Expired
    $query = "SELECT COUNT(*) as total FROM franchise_lifecycle WHERE lifecycle_stage = 'expired'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['expired'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Revoked
    $query = "SELECT COUNT(*) as total FROM franchise_lifecycle WHERE lifecycle_stage = 'revocation'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['revoked'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $stats;
}

function generateLifecycleId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM franchise_lifecycle WHERE lifecycle_id LIKE 'FL-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "FL-{$year}-{$next_id}";
}

// OCR Ticket Digitization Functions
function getOCRScans($conn) {
    $query = "SELECT os.*, COUNT(dt.digitized_id) as digitized_count
              FROM ocr_ticket_scans os
              LEFT JOIN digitized_tickets dt ON os.scan_id = dt.scan_id
              GROUP BY os.scan_id
              ORDER BY os.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDigitizedTickets($conn) {
    $query = "SELECT dt.*, os.ticket_image_path, os.ocr_confidence, o.first_name, o.last_name, v.vehicle_type
              FROM digitized_tickets dt
              JOIN ocr_ticket_scans os ON dt.scan_id = os.scan_id
              LEFT JOIN operators o ON dt.operator_id = o.operator_id
              LEFT JOIN vehicles v ON dt.vehicle_id = v.vehicle_id
              ORDER BY dt.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getValidationQueue($conn) {
    $query = "SELECT vq.*, os.ticket_image_path, dt.ticket_number, dt.plate_number
              FROM ocr_validation_queue vq
              JOIN ocr_ticket_scans os ON vq.scan_id = os.scan_id
              LEFT JOIN digitized_tickets dt ON vq.digitized_id = dt.digitized_id
              ORDER BY vq.priority DESC, vq.queue_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function processOCRScan($conn, $scan_id, $processed_by) {
    $query = "UPDATE ocr_ticket_scans SET ocr_status = 'processing', scanned_by = :processed_by WHERE scan_id = :scan_id";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['processed_by' => $processed_by, 'scan_id' => $scan_id]);
}

function validateTicket($conn, $digitized_id, $validated_by, $status) {
    $query = "UPDATE digitized_tickets SET status = :status, reviewed_by = :validated_by, review_date = NOW() WHERE digitized_id = :digitized_id";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['status' => $status, 'validated_by' => $validated_by, 'digitized_id' => $digitized_id]);
}

function generateScanId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM ocr_ticket_scans WHERE scan_id LIKE 'OCR-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "OCR-{$year}-{$next_id}";
}

// Violation Record Management Functions
function getViolationRecords($conn) {
    $query = "SELECT vh.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model,
              va.total_violations, va.risk_level, va.repeat_offender_flag
              FROM violation_history vh
              JOIN operators o ON vh.operator_id = o.operator_id
              JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
              LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id AND vh.vehicle_id = va.vehicle_id
              ORDER BY vh.violation_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getViolationById($conn, $violation_id) {
    $query = "SELECT vh.*, o.first_name, o.last_name, o.status as operator_status,
              v.plate_number, v.vehicle_type, v.make, v.model, v.status as vehicle_status,
              va.total_violations, va.risk_level, va.repeat_offender_flag
              FROM violation_history vh
              JOIN operators o ON vh.operator_id = o.operator_id
              JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
              LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id AND vh.vehicle_id = va.vehicle_id
              WHERE vh.violation_id = :violation_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':violation_id', $violation_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function addViolationRecord($conn, $data) {
    try {
        $conn->beginTransaction();
        
        // Insert violation record
        $query = "INSERT INTO violation_history (violation_id, operator_id, vehicle_id, violation_type, 
                  violation_date, fine_amount, settlement_status, location, ticket_number) 
                  VALUES (:violation_id, :operator_id, :vehicle_id, :violation_type, 
                  :violation_date, :fine_amount, :settlement_status, :location, :ticket_number)";
        $stmt = $conn->prepare($query);
        $stmt->execute($data);
        
        // Update violation analytics
        updateViolationAnalytics($conn, $data['operator_id'], $data['vehicle_id']);
        
        // Update compliance status
        updateComplianceViolationCount($conn, $data['operator_id'], $data['vehicle_id']);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function updateViolationRecord($conn, $violation_id, $data) {
    $query = "UPDATE violation_history SET violation_type = :violation_type, violation_date = :violation_date,
              fine_amount = :fine_amount, settlement_status = :settlement_status, location = :location
              WHERE violation_id = :violation_id";
    $data['violation_id'] = $violation_id;
    $stmt = $conn->prepare($query);
    return $stmt->execute($data);
}

function updateSettlementStatus($conn, $violation_id, $settlement_status, $settlement_date = null) {
    $query = "UPDATE violation_history SET settlement_status = :settlement_status";
    $params = ['settlement_status' => $settlement_status, 'violation_id' => $violation_id];
    
    if ($settlement_date) {
        $query .= ", settlement_date = :settlement_date";
        $params['settlement_date'] = $settlement_date;
    }
    
    $query .= " WHERE violation_id = :violation_id";
    $stmt = $conn->prepare($query);
    return $stmt->execute($params);
}

function updateViolationAnalytics($conn, $operator_id, $vehicle_id) {
    // Get total violations for this operator/vehicle
    $query = "SELECT COUNT(*) as total, MAX(violation_date) as last_date FROM violation_history 
              WHERE operator_id = :operator_id AND vehicle_id = :vehicle_id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['operator_id' => $operator_id, 'vehicle_id' => $vehicle_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_violations = $result['total'];
    $last_violation_date = $result['last_date'];
    $repeat_offender = $total_violations >= 3;
    $risk_level = $total_violations >= 5 ? 'high' : ($total_violations >= 3 ? 'medium' : 'low');
    $compliance_score = max(0, 100 - ($total_violations * 10));
    
    // Update or insert analytics record
    $query = "INSERT INTO violation_analytics (analytics_id, operator_id, vehicle_id, total_violations, 
              last_violation_date, repeat_offender_flag, risk_level, compliance_score) 
              VALUES (:analytics_id, :operator_id, :vehicle_id, :total_violations, 
              :last_violation_date, :repeat_offender_flag, :risk_level, :compliance_score)
              ON DUPLICATE KEY UPDATE 
              total_violations = :total_violations, last_violation_date = :last_violation_date,
              repeat_offender_flag = :repeat_offender_flag, risk_level = :risk_level, compliance_score = :compliance_score";
    
    $analytics_id = generateAnalyticsId($conn);
    $stmt = $conn->prepare($query);
    return $stmt->execute([
        'analytics_id' => $analytics_id,
        'operator_id' => $operator_id,
        'vehicle_id' => $vehicle_id,
        'total_violations' => $total_violations,
        'last_violation_date' => $last_violation_date,
        'repeat_offender_flag' => $repeat_offender,
        'risk_level' => $risk_level,
        'compliance_score' => $compliance_score
    ]);
}

function updateComplianceViolationCount($conn, $operator_id, $vehicle_id) {
    $query = "UPDATE compliance_status SET violation_count = (
                SELECT COUNT(*) FROM violation_history 
                WHERE operator_id = :operator_id AND vehicle_id = :vehicle_id
              ) WHERE operator_id = :operator_id AND vehicle_id = :vehicle_id";
    $stmt = $conn->prepare($query);
    return $stmt->execute(['operator_id' => $operator_id, 'vehicle_id' => $vehicle_id]);
}

function generateViolationId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM violation_history WHERE violation_id LIKE 'VIO-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 4, '0', STR_PAD_LEFT);
    return "VIO-{$year}-{$next_id}";
}

function generateAnalyticsId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM violation_analytics WHERE analytics_id LIKE 'VA-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "VA-{$year}-{$next_id}";
}

function generateViolationReport($conn, $filters = []) {
    $query = "SELECT vh.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type,
              va.risk_level, va.repeat_offender_flag
              FROM violation_history vh
              JOIN operators o ON vh.operator_id = o.operator_id
              JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
              LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id AND vh.vehicle_id = va.vehicle_id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['date_from'])) {
        $query .= " AND vh.violation_date >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $query .= " AND vh.violation_date <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }
    
    if (!empty($filters['settlement_status'])) {
        $query .= " AND vh.settlement_status = :settlement_status";
        $params['settlement_status'] = $filters['settlement_status'];
    }
    
    if (!empty($filters['violation_type'])) {
        $query .= " AND vh.violation_type = :violation_type";
        $params['violation_type'] = $filters['violation_type'];
    }
    
    $query .= " ORDER BY vh.violation_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function linkDigitizedTicket($conn, $digitized_id, $operator_id, $vehicle_id) {
    try {
        $conn->beginTransaction();
        
        // Get digitized ticket data
        $query = "SELECT * FROM digitized_tickets WHERE digitized_id = :digitized_id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['digitized_id' => $digitized_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket) {
            throw new Exception('Digitized ticket not found');
        }
        
        // Create violation record from digitized ticket
        $violation_id = generateViolationId($conn);
        $violation_data = [
            'violation_id' => $violation_id,
            'operator_id' => $operator_id,
            'vehicle_id' => $vehicle_id,
            'violation_type' => $ticket['violation_type'],
            'violation_date' => $ticket['violation_date'],
            'fine_amount' => $ticket['fine_amount'],
            'settlement_status' => 'unpaid',
            'location' => $ticket['location'],
            'ticket_number' => $ticket['ticket_number']
        ];
        
        addViolationRecord($conn, $violation_data);
        
        // Update digitized ticket linking status
        $query = "UPDATE digitized_tickets SET operator_id = :operator_id, vehicle_id = :vehicle_id, 
                  linking_status = 'linked', linking_confidence = 100.00, status = 'processed' 
                  WHERE digitized_id = :digitized_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            'operator_id' => $operator_id,
            'vehicle_id' => $vehicle_id,
            'digitized_id' => $digitized_id
        ]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function getFilteredApplications($conn, $status = '', $type = '', $stage = '', $date = '') {
    $query = "SELECT fa.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type, v.make, v.model
              FROM franchise_applications fa
              JOIN operators o ON fa.operator_id = o.operator_id
              JOIN vehicles v ON fa.vehicle_id = v.vehicle_id
              WHERE 1=1";
    
    $params = [];
    
    if ($status) {
        $query .= " AND fa.status = :status";
        $params['status'] = $status;
    }
    
    if ($type) {
        $query .= " AND fa.application_type = :type";
        $params['type'] = $type;
    }
    
    if ($stage) {
        $query .= " AND fa.workflow_stage = :stage";
        $params['stage'] = $stage;
    }
    
    if ($date) {
        $query .= " AND DATE(fa.application_date) >= :date";
        $params['date'] = $date;
    }
    
    $query .= " ORDER BY fa.application_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Violation Statistics Function for Analytics
function getViolationStatistics($conn) {
    $stats = [];
    
    // Total violations
    $query = "SELECT COUNT(*) as total FROM violation_history";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['total_violations'] = $stmt->fetchColumn();
    
    // Settlement breakdown
    $query = "SELECT settlement_status, COUNT(*) as count, SUM(fine_amount) as amount 
              FROM violation_history GROUP BY settlement_status";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['settlement_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top violation types
    $query = "SELECT violation_type, COUNT(*) as count 
              FROM violation_history 
              GROUP BY violation_type 
              ORDER BY count DESC LIMIT 5";
    $stmt = $conn->prepare($query);
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
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['monthly_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}


?>