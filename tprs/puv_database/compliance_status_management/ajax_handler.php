<?php
header('Content-Type: application/json');

try {
    require_once '../../../config/database.php';
    require_once 'functions.php';

    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Setup failed: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'filter':
            try {
                $filters = [
                    'franchise_status' => $_POST['franchise_status'] ?? '',
                    'inspection_status' => $_POST['inspection_status'] ?? '',
                    'compliance_score_min' => $_POST['compliance_score_min'] ?? '',
                    'compliance_score_max' => $_POST['compliance_score_max'] ?? '',
                    'violation_count_min' => $_POST['violation_count_min'] ?? '',
                    'inspection_overdue' => $_POST['inspection_overdue'] ?? '',
                    'search' => $_POST['search'] ?? ''
                ];
                
                $results = filterComplianceStatus($db, $filters);
                echo json_encode(['success' => true, 'data' => $results]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Filter error: ' . $e->getMessage()]);
            }
            break;
            
        case 'update_status':
            $compliance_id = $_POST['compliance_id'] ?? '';
            $data = [
                'franchise_status' => $_POST['franchise_status'] ?? '',
                'inspection_status' => $_POST['inspection_status'] ?? '',
                'violation_count' => $_POST['violation_count'] ?? 0,
                'last_inspection_date' => $_POST['last_inspection_date'] ?? null,
                'next_inspection_due' => $_POST['next_inspection_due'] ?? null,
                'compliance_score' => $_POST['compliance_score'] ?? 0
            ];
            
            $success = updateComplianceStatus($db, $compliance_id, $data);
            echo json_encode(['success' => $success]);
            break;
            
        case 'bulk_update':
            $updates = json_decode($_POST['updates'], true);
            $success = bulkUpdateComplianceStatus($db, $updates);
            echo json_encode(['success' => $success]);
            break;
            
        case 'calculate_score':
            $franchise_status = $_POST['franchise_status'] ?? 'valid';
            $inspection_status = $_POST['inspection_status'] ?? 'passed';
            $violation_count = intval($_POST['violation_count'] ?? 0);
            
            $score = calculateComplianceScore($franchise_status, $inspection_status, $violation_count);
            echo json_encode(['success' => true, 'score' => $score]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid POST action: ' . $action]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'generate_report':
            $report_type = $_GET['report_type'] ?? 'summary';
            $data = generateComplianceReport($db, $report_type);
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'export_csv':
            $report_type = $_GET['report_type'] ?? 'detailed';
            $data = generateComplianceReport($db, $report_type);
            $filename = 'compliance_report_' . $report_type . '_' . date('Y-m-d');
            exportReportToCSV($data, $filename);
            break;
            
        case 'statistics':
            $stats = getComplianceStatistics($db);
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'test':
            echo json_encode(['success' => true, 'message' => 'AJAX handler is working']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid GET action: ' . $action]);
    }
}
?>