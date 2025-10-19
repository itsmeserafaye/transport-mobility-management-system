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
    // Validate license number format
    $license_pattern = '/^(?:[A-Z]\d{2}-\d{2}-\d{6}|\d{1,2}-\d{9})$/';
    if (!preg_match($license_pattern, $data['license_number'])) {
        throw new Exception('Invalid license number format. Use old format (D12-34-567890) or new format (N-123456789 or 02-123456789)');
    }
    
    $query = "INSERT INTO operators (operator_id, first_name, last_name, address, contact_number, license_number, license_expiry) 
              VALUES (:operator_id, :first_name, :last_name, :address, :contact_number, :license_number, :license_expiry)";
    $stmt = $conn->prepare($query);
    return $stmt->execute($data);
}

function updateOperator($conn, $operator_id, $data) {
    try {
        // Validate license number format if provided
        if (isset($data['license_number'])) {
            $license_pattern = '/^(?:[A-Z]\d{2}-\d{2}-\d{6}|\d{1,2}-\d{9})$/';
            if (!preg_match($license_pattern, $data['license_number'])) {
                throw new Exception('Invalid license number format. Use old format (D12-34-567890) or new format (N-123456789 or 02-123456789)');
            }
            $query = "UPDATE operators SET first_name = :first_name, last_name = :last_name, 
                      address = :address, contact_number = :contact_number, license_number = :license_number, license_expiry = :license_expiry 
                      WHERE operator_id = :operator_id";
        } else {
            $query = "UPDATE operators SET first_name = :first_name, last_name = :last_name, 
                      address = :address, contact_number = :contact_number, license_expiry = :license_expiry 
                      WHERE operator_id = :operator_id";
        }
        
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
                  year_manufactured, engine_number, chassis_number, color, seating_capacity) 
                  VALUES (:vehicle_id, :operator_id, :plate_number, :vehicle_type, :make, :model, 
                  :year_manufactured, :engine_number, :chassis_number, :color, :seating_capacity)";
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
    
    $sql = "SELECT COUNT(*) as total FROM lto_registrations";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $sql = "SELECT COUNT(*) as active FROM lto_registrations WHERE status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
    
    $sql = "SELECT COUNT(*) as pending FROM lto_registrations WHERE status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
    
    $sql = "SELECT COUNT(*) as expired FROM lto_registrations WHERE status = 'expired' OR expiry_date < CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stats['expired'] = $stmt->fetch(PDO::FETCH_ASSOC)['expired'];
    
    $sql = "SELECT COUNT(*) as expiring_soon FROM lto_registrations WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stats['expiring_soon'] = $stmt->fetch(PDO::FETCH_ASSOC)['expiring_soon'];
    
    return $stats;
}

function getLTORegistrations($conn) {
    $sql = "SELECT * FROM lto_registrations ORDER BY registration_date DESC";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function approveLTORegistrationWithPlate($conn, $lto_id, $plate_number) {
    // Check if plate number already exists
    $check_query = "SELECT COUNT(*) FROM lto_registrations WHERE plate_number = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$plate_number]);
    
    if ($check_stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'Plate number already exists'];
    }
    
    $query = "UPDATE lto_registrations SET 
              plate_number = :plate_number, 
              status = 'active',
              remarks = 'Registration approved and plate number assigned'
              WHERE lto_registration_id = :lto_id AND status = 'pending' AND registration_type = 'new' AND plate_number IS NULL";
    $stmt = $conn->prepare($query);
    $success = $stmt->execute(['plate_number' => $plate_number, 'lto_id' => $lto_id]);
    
    if ($success && $stmt->rowCount() > 0) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => 'Failed to approve registration or registration not eligible'];
    }
}

function approveLTORegistration($conn, $lto_id) {
    // Generate plate number for new registrations
    $plate_number = generatePlateNumber($conn);
    
    $query = "UPDATE lto_registrations SET 
              plate_number = :plate_number, 
              status = 'active',
              remarks = 'Registration approved and plate number assigned'
              WHERE lto_registration_id = :lto_id AND status = 'pending' AND registration_type = 'new' AND plate_number IS NULL";
    $stmt = $conn->prepare($query);
    $success = $stmt->execute(['plate_number' => $plate_number, 'lto_id' => $lto_id]);
    
    if ($success && $stmt->rowCount() > 0) {
        return ['success' => true, 'plate_number' => $plate_number];
    } else {
        return ['success' => false, 'plate_number' => null];
    }
}

function getApprovedLTORegistrations($conn) {
    $query = "SELECT lto_registration_id, owner_first_name, owner_last_name, license_number, license_expiry,
                     make, model, year_model, plate_number, engine_number, chassis_number, body_type,
                     owner_address, classification, registration_date, expiry_date
              FROM lto_registrations 
              WHERE status = 'active' AND plate_number IS NOT NULL
              ORDER BY owner_last_name, owner_first_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generatePlateNumber($conn) {
    // Generate Philippine plate number format: ABC 1234
    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    
    do {
        $plate = $letters[array_rand($letters)] . $letters[array_rand($letters)] . $letters[array_rand($letters)] . ' ' . rand(1000, 9999);
        
        // Check if plate already exists
        $query = "SELECT COUNT(*) FROM lto_registrations WHERE plate_number = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$plate]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);
    
    return $plate;
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
    $query = "SELECT rs.*, r.route_name, r.route_code, o.first_name, o.last_name, v.plate_number, v.vehicle_type,
              COALESCE(rs.service_type, 'regular') as service_type,
              COALESCE(rs.service_frequency_minutes, 30) as service_frequency_minutes,
              COALESCE(rs.trips_per_day, 20) as trips_per_day,
              COALESCE(rs.service_start_time, '05:00:00') as service_start_time,
              COALESCE(rs.service_end_time, '22:00:00') as service_end_time
              FROM route_schedules rs
              LEFT JOIN official_routes r ON rs.route_id = r.route_id
              LEFT JOIN operators o ON rs.operator_id = o.operator_id
              LEFT JOIN vehicles v ON rs.vehicle_id = v.vehicle_id
              ORDER BY rs.service_start_time";
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