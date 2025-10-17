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
    $query = "SELECT o.*, v.plate_number, v.vehicle_type, v.make, v.model, v.vehicle_id,
              cs.compliance_score, cs.franchise_status, cs.inspection_status
              FROM operators o 
              LEFT JOIN vehicles v ON o.operator_id = v.operator_id
              LEFT JOIN compliance_status cs ON o.operator_id = cs.operator_id AND v.vehicle_id = cs.vehicle_id
              ORDER BY o.date_registered DESC, v.vehicle_id
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

function searchOperators($conn, $search_term, $status = null, $vehicle_type = null, $compliance_filter = null, $date_filter = null) {
    $query = "SELECT o.*, v.plate_number, v.vehicle_type, v.make, v.model, v.vehicle_id,
              cs.compliance_score, cs.franchise_status, cs.inspection_status
              FROM operators o 
              LEFT JOIN vehicles v ON o.operator_id = v.operator_id
              LEFT JOIN compliance_status cs ON o.operator_id = cs.operator_id AND v.vehicle_id = cs.vehicle_id
              WHERE 1=1";
    
    $params = [];
    
    if ($search_term) {
        $query .= " AND (o.first_name LIKE :search OR o.last_name LIKE :search 
                     OR o.operator_id LIKE :search OR v.plate_number LIKE :search)";
        $params['search'] = '%' . $search_term . '%';
    }
    
    if ($status) {
        $query .= " AND o.status = :status";
        $params['status'] = $status;
    }
    
    if ($vehicle_type) {
        $query .= " AND v.vehicle_type = :vehicle_type";
        $params['vehicle_type'] = $vehicle_type;
    }
    
    if ($compliance_filter) {
        if ($compliance_filter == 'compliant') {
            $query .= " AND (COALESCE(cs.compliance_score, 0) >= 80 AND COALESCE(cs.franchise_status, 'valid') = 'valid' AND COALESCE(cs.inspection_status, 'passed') = 'passed')";
        } elseif ($compliance_filter == 'non-compliant') {
            $query .= " AND NOT (COALESCE(cs.compliance_score, 0) >= 80 AND COALESCE(cs.franchise_status, 'valid') = 'valid' AND COALESCE(cs.inspection_status, 'passed') = 'passed') AND NOT (COALESCE(cs.compliance_score, 0) >= 60 AND (COALESCE(cs.franchise_status, 'pending') = 'pending' OR COALESCE(cs.inspection_status, 'pending') = 'pending'))";
        } elseif ($compliance_filter == 'pending') {
            $query .= " AND (COALESCE(cs.compliance_score, 0) >= 60 AND (COALESCE(cs.franchise_status, 'pending') = 'pending' OR COALESCE(cs.inspection_status, 'pending') = 'pending'))";
        }
    }
    
    if ($date_filter) {
        $query .= " AND DATE(o.date_registered) = :date_filter";
        $params['date_filter'] = $date_filter;
    }
    
    $query .= " ORDER BY o.date_registered DESC, v.vehicle_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalOperators($conn) {
    $query = "SELECT COUNT(*) as total FROM operators";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
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

function addVehicle($conn, $data) {
    try {
        $conn->beginTransaction();
        
        $query = "INSERT INTO vehicles (vehicle_id, operator_id, plate_number, vehicle_type, make, model, 
                  year_manufactured, engine_number, chassis_number, seating_capacity) 
                  VALUES (:vehicle_id, :operator_id, :plate_number, :vehicle_type, :make, :model, 
                  :year_manufactured, :engine_number, :chassis_number, :seating_capacity)";
        $stmt = $conn->prepare($query);
        $stmt->execute($data);
        
        $compliance_id = generateComplianceId($conn);
        $compliance_query = "INSERT INTO compliance_status (compliance_id, operator_id, vehicle_id, franchise_status, inspection_status, violation_count, compliance_score) 
                            VALUES (:compliance_id, :operator_id, :vehicle_id, 'pending', 'pending', 0, 75.00)";
        $compliance_stmt = $conn->prepare($compliance_query);
        $compliance_stmt->execute([
            'compliance_id' => $compliance_id,
            'operator_id' => $data['operator_id'],
            'vehicle_id' => $data['vehicle_id']
        ]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
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

function generateComplianceId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM compliance_status WHERE compliance_id LIKE 'CS-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "CS-{$year}-{$next_id}";
}

// LTO Vehicle Registration Functions (Government Compliance)
function addLTORegistration($conn, $data) {
    $sql = "INSERT INTO lto_vehicle_registration (vehicle_id, operator_id, or_number, cr_number, plate_number, registration_type, registration_date, expiry_date, lto_office, fees_paid, status, document_path) 
            VALUES (:vehicle_id, :operator_id, :or_number, :cr_number, :plate_number, :registration_type, :registration_date, :expiry_date, :lto_office, :fees_paid, :status, :document_path)";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        return $conn->lastInsertId();
    } catch(PDOException $e) {
        return false;
    }
}

function getLTORegistrations($conn) {
    $sql = "SELECT lr.*, v.make, v.model, v.year, v.body_number, CONCAT(o.first_name, ' ', o.last_name) as operator_name 
            FROM lto_vehicle_registration lr
            LEFT JOIN vehicles v ON lr.vehicle_id = v.vehicle_id
            LEFT JOIN operators o ON lr.operator_id = o.operator_id
            ORDER BY lr.registration_date DESC";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function updateLTORegistration($conn, $id, $data) {
    $sql = "UPDATE lto_vehicle_registration SET 
            vehicle_id = :vehicle_id,
            operator_id = :operator_id,
            or_number = :or_number,
            cr_number = :cr_number,
            plate_number = :plate_number,
            registration_type = :registration_type,
            registration_date = :registration_date,
            expiry_date = :expiry_date,
            lto_office = :lto_office,
            fees_paid = :fees_paid,
            status = :status,
            document_path = :document_path
            WHERE lto_registration_id = :id";
    
    try {
        $stmt = $conn->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    } catch(PDOException $e) {
        return false;
    }
}

function deleteLTORegistration($conn, $id) {
    $sql = "DELETE FROM lto_vehicle_registration WHERE lto_registration_id = :id";
    
    try {
        $stmt = $conn->prepare($sql);
        return $stmt->execute(['id' => $id]);
    } catch(PDOException $e) {
        return false;
    }
}

function getLTORegistrationStats($conn) {
    $stats = [];
    
    $sql = "SELECT COUNT(*) as total FROM lto_vehicle_registration";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $sql = "SELECT COUNT(*) as active FROM lto_vehicle_registration WHERE status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
    
    $sql = "SELECT COUNT(*) as expired FROM lto_vehicle_registration WHERE status = 'expired' OR expiry_date < CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stats['expired'] = $stmt->fetch(PDO::FETCH_ASSOC)['expired'];
    
    $sql = "SELECT COUNT(*) as expiring_soon FROM lto_vehicle_registration WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stats['expiring_soon'] = $stmt->fetch(PDO::FETCH_ASSOC)['expiring_soon'];
    
    return $stats;
}

function generateLTORegistrationId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM lto_vehicle_registration WHERE lto_registration_id LIKE 'LTO-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 4, '0', STR_PAD_LEFT);
    return "LTO-{$year}-{$next_id}";
}

// Franchise Application Functions
function getFranchiseApplications($conn) {
    $query = "SELECT fa.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM franchise_applications fa
              LEFT JOIN operators o ON fa.operator_id = o.operator_id
              LEFT JOIN vehicles v ON fa.vehicle_id = v.vehicle_id
              ORDER BY fa.application_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFilteredApplications($conn, $status = '', $type = '', $stage = '', $date = '') {
    $query = "SELECT fa.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM franchise_applications fa
              LEFT JOIN operators o ON fa.operator_id = o.operator_id
              LEFT JOIN vehicles v ON fa.vehicle_id = v.vehicle_id
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

// Document Repository Functions
function getDocuments($conn) {
    $query = "SELECT dr.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM document_repository dr
              LEFT JOIN operators o ON dr.operator_id = o.operator_id
              LEFT JOIN vehicles v ON dr.vehicle_id = v.vehicle_id
              ORDER BY dr.upload_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Franchise Lifecycle Functions
function getLifecycleStatistics($conn) {
    $stats = [];
    
    $query = "SELECT COUNT(*) as active_franchises FROM franchise_lifecycle WHERE lifecycle_stage = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['active_franchises'] = $stmt->fetchColumn();
    
    $query = "SELECT COUNT(*) as due_for_renewal FROM franchise_lifecycle WHERE action_required = 'renewal'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['due_for_renewal'] = $stmt->fetchColumn();
    
    $query = "SELECT COUNT(*) as expired FROM franchise_lifecycle WHERE lifecycle_stage = 'expired'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['expired'] = $stmt->fetchColumn();
    
    $query = "SELECT COUNT(*) as revoked FROM franchise_lifecycle WHERE lifecycle_stage = 'revocation'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['revoked'] = $stmt->fetchColumn();
    
    return $stats;
}

function getFranchiseLifecycle($conn) {
    $query = "SELECT fl.*, 
                     COALESCE(fr.franchise_number, 'N/A') as franchise_number,
                     COALESCE(fr.route_assigned, 'N/A') as route_assigned,
                     o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM franchise_lifecycle fl
              LEFT JOIN operators o ON fl.operator_id = o.operator_id
              LEFT JOIN vehicles v ON fl.vehicle_id = v.vehicle_id
              LEFT JOIN franchise_records fr ON fl.franchise_id = fr.franchise_id
              ORDER BY fl.stage_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Route and Schedule Functions
function getRoutes($conn) {
    $query = "SELECT r.*, 
              (SELECT COUNT(*) FROM route_schedules rs WHERE rs.route_id = r.route_id) as schedule_count,
              (SELECT COUNT(*) FROM route_schedules rs WHERE rs.route_id = r.route_id AND rs.published_to_citizen = 1) as published_schedules
              FROM official_routes r
              ORDER BY r.route_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRouteSchedules($conn) {
    $query = "SELECT rs.*, r.route_name, r.route_code, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM route_schedules rs
              LEFT JOIN official_routes r ON rs.route_id = r.route_id
              LEFT JOIN operators o ON rs.operator_id = o.operator_id
              LEFT JOIN vehicles v ON rs.vehicle_id = v.vehicle_id
              ORDER BY rs.departure_time";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Traffic Violation Functions
function getViolationRecords($conn) {
    $query = "SELECT vh.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type
              FROM violation_history vh
              LEFT JOIN operators o ON vh.operator_id = o.operator_id
              LEFT JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
              ORDER BY vh.violation_date DESC
              LIMIT 50";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createRevenueIntegrationTable($conn) {
    try {
        $query = "CREATE TABLE IF NOT EXISTS revenue_integration (
                    revenue_id VARCHAR(20) PRIMARY KEY,
                    violation_id VARCHAR(20),
                    operator_id VARCHAR(20),
                    collection_amount DECIMAL(10,2),
                    collection_date DATETIME,
                    collection_status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
                    payment_method VARCHAR(50),
                    collected_by VARCHAR(100),
                    remarks TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                  )";
        $stmt = $conn->prepare($query);
        $stmt->execute();
    } catch (Exception $e) {
        // Table might already exist, continue
    }
}

function generateInspectionId($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) + 1 as next_id FROM inspection_records WHERE inspection_id LIKE 'INS-{$year}-%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $next_id = str_pad($stmt->fetch(PDO::FETCH_ASSOC)['next_id'], 3, '0', STR_PAD_LEFT);
    return "INS-{$year}-{$next_id}";
}

function getScheduledInspectionsFromDB($conn) {
    try {
        $query = "SELECT ir.*, 
                         COALESCE(o.first_name, 'Unknown') as first_name, 
                         COALESCE(o.last_name, 'Operator') as last_name, 
                         COALESCE(v.plate_number, ir.vehicle_id) as plate_number, 
                         COALESCE(v.vehicle_type, 'Unknown') as vehicle_type, 
                         COALESCE(v.make, '') as make, 
                         COALESCE(v.model, '') as model
                  FROM inspection_records ir
                  LEFT JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
                  LEFT JOIN operators o ON v.operator_id = o.operator_id
                  WHERE ir.result = 'scheduled'
                  ORDER BY ir.inspection_date ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getTodaysDueInspections($conn) {
    try {
        $query = "SELECT ir.*, 
                         COALESCE(o.first_name, 'Unknown') as first_name, 
                         COALESCE(o.last_name, 'Operator') as last_name, 
                         COALESCE(v.plate_number, ir.vehicle_id) as plate_number, 
                         COALESCE(v.vehicle_type, 'Unknown') as vehicle_type, 
                         COALESCE(v.make, '') as make, 
                         COALESCE(v.model, '') as model
                  FROM inspection_records ir
                  LEFT JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
                  LEFT JOIN operators o ON v.operator_id = o.operator_id
                  WHERE ir.result = 'scheduled' AND DATE(ir.inspection_date) = CURDATE()
                  ORDER BY ir.inspection_date ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}





?>