<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_lto_registration') {
            // Check for duplicate vehicle identifiers
            $chassis_number = $_POST['chassis_number'];
            $engine_number = $_POST['engine_number'];
            $or_number = $_POST['or_number'];
            $cr_number = $_POST['cr_number'];
            $insurance_policy = $_POST['insurance_policy'];
            
            $duplicate_check = "SELECT 
                CASE 
                    WHEN chassis_number = ? THEN 'Chassis Number'
                    WHEN engine_number = ? THEN 'Engine Number'
                    WHEN or_number = ? THEN 'OR Number'
                    WHEN cr_number = ? THEN 'CR Number'
                    WHEN insurance_policy = ? THEN 'Insurance Policy Number'
                END as duplicate_field
                FROM lto_registrations 
                WHERE chassis_number = ? OR engine_number = ? OR or_number = ? OR cr_number = ? OR insurance_policy = ?
                LIMIT 1";
            
            $check_stmt = $conn->prepare($duplicate_check);
            $check_stmt->execute([$chassis_number, $engine_number, $or_number, $cr_number, $insurance_policy,
                                 $chassis_number, $engine_number, $or_number, $cr_number, $insurance_policy]);
            $duplicate = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($duplicate) {
                echo json_encode(['success' => false, 'message' => $duplicate['duplicate_field'] . ' already exists in the database']);
                exit;
            }
            
            // Generate LTO registration ID
            $lto_id = 'LTO-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            // Handle Other options
            $make = $_POST['make'] === 'Other' ? $_POST['make_other'] : $_POST['make'];
            $model = $_POST['model'] === 'Other' ? $_POST['model_other'] : $_POST['model'];
            $insurance_provider = $_POST['insurance_provider'] === 'Other' ? $_POST['insurance_provider_other'] : $_POST['insurance_provider'];
            $lto_office = $_POST['lto_office'] === 'Other' ? $_POST['lto_office_other'] : $_POST['lto_office'];
            
            // Combine address fields
            $full_address = $_POST['house_street'] . ', ' . $_POST['barangay'] . ', ' . 
                           $_POST['city_municipality'] . ', ' . $_POST['province'] . ', ' . $_POST['zip_code'];
            
            // Handle file upload
            $document_path = null;
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../../uploads/lto_documents/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
                $filename = $lto_id . '_' . time() . '.' . $file_extension;
                $document_path = $upload_dir . $filename;
                
                if (!move_uploaded_file($_FILES['document_file']['tmp_name'], $document_path)) {
                    $document_path = null;
                }
            }
            
            $query = "INSERT INTO lto_registrations (
                lto_registration_id, owner_first_name, owner_last_name, owner_address, license_number, license_expiry,
                make, model, year_model, engine_number, chassis_number, body_type, color,
                plate_number, classification, or_number, cr_number, registration_type,
                registration_date, expiry_date, lto_office, fees_paid,
                insurance_policy, insurance_provider, document_path, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $conn->prepare($query);
            $success = $stmt->execute([
                $lto_id,
                $_POST['owner_first_name'],
                $_POST['owner_last_name'], 
                $full_address,
                $_POST['license_number'],
                $_POST['license_expiry'],
                $make,
                $model,
                $_POST['year_model'],
                $_POST['engine_number'],
                $_POST['chassis_number'],
                $_POST['body_type'],
                $_POST['color'],
                $_POST['plate_number'] ?: null,
                $_POST['classification'],
                $_POST['or_number'],
                $_POST['cr_number'],
                $_POST['registration_type'],
                $_POST['registration_date'],
                $_POST['expiry_date'],
                $lto_office,
                $_POST['fees_paid'] ?: 0,
                $_POST['insurance_policy'],
                $insurance_provider,
                $document_path
            ]);
            
            if ($success) {
                echo json_encode(['success' => true, 'lto_id' => $lto_id, 'message' => 'LTO registration added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add LTO registration']);
            }
            
        } elseif ($action === 'get_registration_details') {
            $lto_id = $_POST['lto_id'] ?? '';
            
            $query = "SELECT * FROM lto_registrations WHERE lto_registration_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$lto_id]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($registration) {
                $html = generateRegistrationDetailsHTML($registration);
                echo json_encode(['success' => true, 'html' => $html]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Registration not found']);
            }
            
        } elseif ($action === 'check_plate_exists') {
            $plate_number = $_POST['plate_number'] ?? '';
            
            $query = "SELECT COUNT(*) FROM lto_registrations WHERE plate_number = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$plate_number]);
            $exists = $stmt->fetchColumn() > 0;
            
            echo json_encode(['exists' => $exists]);
            
        } elseif ($action === 'check_duplicate_field') {
            $field_name = $_POST['field_name'] ?? '';
            $field_value = $_POST['field_value'] ?? '';
            
            $allowed_fields = ['chassis_number', 'engine_number', 'or_number', 'cr_number', 'insurance_policy', 'plate_number'];
            
            if (!in_array($field_name, $allowed_fields) || empty($field_value)) {
                echo json_encode(['exists' => false]);
                exit;
            }
            
            $query = "SELECT COUNT(*) FROM lto_registrations WHERE $field_name = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$field_value]);
            $exists = $stmt->fetchColumn() > 0;
            
            echo json_encode(['exists' => $exists]);
            
        } elseif ($action === 'approve_registration') {
            $lto_id = $_POST['lto_id'] ?? '';
            $plate_number = $_POST['plate_number'] ?? '';
            
            if (!$plate_number) {
                echo json_encode(['success' => false, 'message' => 'Plate number is required']);
                exit;
            }
            
            $result = approveLTORegistrationWithPlate($conn, $lto_id, $plate_number);
            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Registration approved and plate number assigned']);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
            
        } elseif ($action === 'reject_registration') {
            $lto_id = $_POST['lto_id'] ?? '';
            $reason = $_POST['reason'] ?? 'Registration rejected by LTO officer';
            
            $query = "UPDATE lto_registrations SET status = 'rejected', remarks = ? WHERE lto_registration_id = ?";
            $stmt = $conn->prepare($query);
            
            if ($stmt->execute([$reason, $lto_id])) {
                echo json_encode(['success' => true, 'message' => 'Registration rejected successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reject registration']);
            }
            
        } elseif ($action === 'renew_registration') {
            $lto_id = $_POST['lto_id'] ?? '';
            $renewal_period = (int)($_POST['renewal_period'] ?? 1);
            
            $conn->beginTransaction();
            
            try {
                // Get current registration
                $query = "SELECT * FROM lto_registrations WHERE lto_registration_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$lto_id]);
                $registration = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$registration) {
                    throw new Exception('Registration not found');
                }
                
                // Calculate new expiry date
                $current_expiry = $registration['expiry_date'];
                $new_expiry = date('Y-m-d', strtotime($current_expiry . ' + ' . $renewal_period . ' year'));
                
                // Update registration
                $update_query = "UPDATE lto_registrations SET 
                                 expiry_date = ?,
                                 registration_expiry = ?,
                                 renewal_count = COALESCE(renewal_count, 0) + 1,
                                 updated_at = CURRENT_TIMESTAMP
                                 WHERE lto_registration_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute([$new_expiry, $new_expiry, $lto_id]);
                
                // Log renewal history
                $history_query = "INSERT INTO lto_renewal_history 
                                  (lto_registration_id, previous_expiry, new_expiry, renewal_fee, processed_by)
                                  VALUES (?, ?, ?, 500.00, 'LTO Officer')";
                $history_stmt = $conn->prepare($history_query);
                $history_stmt->execute([$lto_id, $current_expiry, $new_expiry]);
                
                $conn->commit();
                echo json_encode(['success' => true, 'new_expiry' => date('M d, Y', strtotime($new_expiry))]);
                
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            
        } elseif ($action === 'renew_registration_full') {
            $lto_id = $_POST['lto_id'] ?? '';
            $renewal_period = (int)($_POST['renewal_period'] ?? 1);
            $renewal_fee = $_POST['renewal_fee'] ?? 500;
            $or_number = $_POST['or_number'] ?? '';
            $insurance_policy = $_POST['insurance_policy'] ?? '';
            $insurance_provider = $_POST['insurance_provider'] ?? '';
            $license_expiry = $_POST['license_expiry'] ?? '';
            $lto_office = $_POST['lto_office'] ?? '';
            $renewal_date = $_POST['renewal_date'] ?? date('Y-m-d');
            
            $conn->beginTransaction();
            
            try {
                // Get current registration
                $query = "SELECT * FROM lto_registrations WHERE lto_registration_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$lto_id]);
                $registration = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$registration) {
                    throw new Exception('Registration not found');
                }
                
                // Calculate new expiry date
                $current_expiry = $registration['expiry_date'];
                $new_expiry = date('Y-m-d', strtotime($current_expiry . ' + ' . $renewal_period . ' year'));
                
                // Handle document uploads
                $document_paths = [];
                if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
                    $upload_dir = '../../../uploads/lto_renewals/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    for ($i = 0; $i < count($_FILES['documents']['name']); $i++) {
                        if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                            $file_extension = pathinfo($_FILES['documents']['name'][$i], PATHINFO_EXTENSION);
                            $filename = $lto_id . '_renewal_' . time() . '_' . $i . '.' . $file_extension;
                            $file_path = $upload_dir . $filename;
                            
                            if (move_uploaded_file($_FILES['documents']['tmp_name'][$i], $file_path)) {
                                $document_paths[] = $file_path;
                            }
                        }
                    }
                }
                
                // Update registration with renewal information
                $update_query = "UPDATE lto_registrations SET 
                                 expiry_date = ?,
                                 registration_expiry = ?,
                                 renewal_count = COALESCE(renewal_count, 0) + 1,
                                 license_expiry = ?,
                                 insurance_policy = ?,
                                 insurance_provider = ?,
                                 lto_office = ?,
                                 updated_at = CURRENT_TIMESTAMP
                                 WHERE lto_registration_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute([$new_expiry, $new_expiry, $license_expiry, $insurance_policy, $insurance_provider, $lto_office, $lto_id]);
                
                // Log renewal history with detailed information
                $history_query = "INSERT INTO lto_renewal_history 
                                  (lto_registration_id, previous_expiry, new_expiry, renewal_fee, processed_by)
                                  VALUES (?, ?, ?, ?, 'LTO Officer')";
                $history_stmt = $conn->prepare($history_query);
                $history_stmt->execute([$lto_id, $current_expiry, $new_expiry, $renewal_fee]);
                
                $conn->commit();
                echo json_encode(['success' => true, 'new_expiry' => date('M d, Y', strtotime($new_expiry))]);
                
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            
        } elseif ($action === 'get_active_registrations') {
            $query = "SELECT lto_registration_id, 
                             CONCAT(owner_first_name, ' ', owner_last_name) as owner_name,
                             plate_number, make, model, expiry_date
                      FROM lto_registrations 
                      WHERE status = 'active' 
                      ORDER BY owner_first_name, owner_last_name";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'registrations' => $registrations]);
            
        } elseif ($action === 'get_existing_owners') {
            $query = "SELECT DISTINCT lto_registration_id,
                             CONCAT(owner_first_name, ' ', owner_last_name) as owner_name,
                             license_number, owner_address
                      FROM lto_registrations 
                      WHERE status = 'active'
                      GROUP BY owner_first_name, owner_last_name, license_number
                      ORDER BY owner_first_name, owner_last_name";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'owners' => $owners]);
            
        } elseif ($action === 'add_vehicle_to_owner') {
            $owner_lto_id = $_POST['owner_lto_id'] ?? '';
            
            // Check for duplicate vehicle identifiers
            $chassis_number = $_POST['chassis_number'];
            $engine_number = $_POST['engine_number'];
            $or_number = $_POST['or_number'];
            $cr_number = $_POST['cr_number'];
            $insurance_policy = $_POST['insurance_policy'];
            
            $duplicate_check = "SELECT 
                CASE 
                    WHEN chassis_number = ? THEN 'Chassis Number'
                    WHEN engine_number = ? THEN 'Engine Number'
                    WHEN or_number = ? THEN 'OR Number'
                    WHEN cr_number = ? THEN 'CR Number'
                    WHEN insurance_policy = ? THEN 'Insurance Policy Number'
                END as duplicate_field
                FROM lto_registrations 
                WHERE chassis_number = ? OR engine_number = ? OR or_number = ? OR cr_number = ? OR insurance_policy = ?
                LIMIT 1";
            
            $check_stmt = $conn->prepare($duplicate_check);
            $check_stmt->execute([$chassis_number, $engine_number, $or_number, $cr_number, $insurance_policy,
                                 $chassis_number, $engine_number, $or_number, $cr_number, $insurance_policy]);
            $duplicate = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($duplicate) {
                echo json_encode(['success' => false, 'message' => $duplicate['duplicate_field'] . ' already exists in the database']);
                exit;
            }
            
            // Get owner information from existing registration
            $owner_query = "SELECT owner_first_name, owner_last_name, owner_address, license_number, license_expiry 
                           FROM lto_registrations WHERE lto_registration_id = ?";
            $owner_stmt = $conn->prepare($owner_query);
            $owner_stmt->execute([$owner_lto_id]);
            $owner_info = $owner_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$owner_info) {
                echo json_encode(['success' => false, 'message' => 'Owner not found']);
                exit;
            }
            
            // Generate new LTO registration ID for the vehicle
            $new_lto_id = 'LTO-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            // Handle document uploads
            $document_paths = [];
            if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
                $upload_dir = '../../../uploads/lto_documents/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                for ($i = 0; $i < count($_FILES['documents']['name']); $i++) {
                    if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_extension = pathinfo($_FILES['documents']['name'][$i], PATHINFO_EXTENSION);
                        $filename = $new_lto_id . '_' . time() . '_' . $i . '.' . $file_extension;
                        $file_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['documents']['tmp_name'][$i], $file_path)) {
                            $document_paths[] = $file_path;
                        }
                    }
                }
            }
            
            $document_path = !empty($document_paths) ? implode(',', $document_paths) : null;
            
            // Insert new vehicle registration
            $query = "INSERT INTO lto_registrations (
                lto_registration_id, owner_first_name, owner_last_name, owner_address, license_number, license_expiry,
                make, model, year_model, body_type, color, engine_number, chassis_number,
                classification, or_number, cr_number, registration_type,
                registration_date, expiry_date, lto_office, fees_paid,
                insurance_policy, insurance_provider, document_path, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $conn->prepare($query);
            $success = $stmt->execute([
                $new_lto_id,
                $owner_info['owner_first_name'],
                $owner_info['owner_last_name'],
                $owner_info['owner_address'],
                $owner_info['license_number'],
                $owner_info['license_expiry'],
                $_POST['make'],
                $_POST['model'],
                $_POST['year_model'],
                $_POST['body_type'],
                $_POST['color'],
                $_POST['engine_number'],
                $_POST['chassis_number'],
                $_POST['classification'],
                $_POST['or_number'],
                $_POST['cr_number'],
                $_POST['registration_date'],
                $_POST['expiry_date'],
                $_POST['lto_office'],
                $_POST['fees_paid'],
                $_POST['insurance_policy'],
                $_POST['insurance_provider'],
                $document_path
            ]);
            
            if ($success) {
                echo json_encode(['success' => true, 'lto_id' => $new_lto_id, 'message' => 'Vehicle added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add vehicle']);
            }
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Helper function to generate registration details HTML
function generateRegistrationDetailsHTML($reg) {
    $status_color = $reg['status'] === 'active' ? 'text-green-600' : 
                   ($reg['status'] === 'rejected' ? 'text-red-600' : 
                   ($reg['status'] === 'pending' ? 'text-yellow-600' : 'text-gray-600'));
    
    return '
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">LTO Registration ID</label>
            <p class="text-sm text-gray-900 font-mono">' . htmlspecialchars($reg['lto_registration_id']) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <p class="text-sm font-semibold ' . $status_color . '">' . ucfirst($reg['status']) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Owner Name</label>
            <p class="text-sm text-gray-900">' . htmlspecialchars($reg['owner_first_name'] . ' ' . $reg['owner_last_name']) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Vehicle</label>
            <p class="text-sm text-gray-900">' . htmlspecialchars($reg['make'] . ' ' . $reg['model'] . ' (' . $reg['year_model'] . ')') . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Plate Number</label>
            <p class="text-sm text-gray-900">' . htmlspecialchars($reg['plate_number'] ?: 'Not assigned') . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Classification</label>
            <p class="text-sm text-gray-900">' . ucfirst($reg['classification']) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">OR Number</label>
            <p class="text-sm text-gray-900 font-mono">' . htmlspecialchars($reg['or_number']) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">CR Number</label>
            <p class="text-sm text-gray-900 font-mono">' . htmlspecialchars($reg['cr_number']) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Engine Number</label>
            <p class="text-sm text-gray-900 font-mono">' . htmlspecialchars($reg['engine_number']) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Chassis Number</label>
            <p class="text-sm text-gray-900 font-mono">' . htmlspecialchars($reg['chassis_number']) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Registration Date</label>
            <p class="text-sm text-gray-900">' . date('M d, Y', strtotime($reg['registration_date'])) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Expiry Date</label>
            <p class="text-sm text-gray-900">' . date('M d, Y', strtotime($reg['expiry_date'])) . '</p>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Address</label>
            <p class="text-sm text-gray-900">' . htmlspecialchars($reg['owner_address']) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">LTO Office</label>
            <p class="text-sm text-gray-900">' . htmlspecialchars($reg['lto_office']) . '</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Insurance Policy</label>
            <p class="text-sm text-gray-900">' . htmlspecialchars($reg['insurance_policy']) . '</p>
        </div>' . 
        ($reg['remarks'] ? '
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Remarks</label>
            <p class="text-sm text-gray-900">' . htmlspecialchars($reg['remarks']) . '</p>
        </div>' : '') . '
    </div>';
}

// Helper function to format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}
?>