<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $compliance_id = $_POST['compliance_id'];
    $data = [
        'franchise_status' => $_POST['franchise_status'],
        'inspection_status' => $_POST['inspection_status'],
        'next_inspection_due' => $_POST['next_inspection_due']
    ];
    
    // Compliance score is auto-generated based on compliance rules, not manually editable
    
    if (updateComplianceStatus($conn, $compliance_id, $data)) {
        echo json_encode(['success' => true, 'message' => 'Compliance status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update compliance status']);
    }
    exit;
}

$compliance_id = $_GET['id'] ?? '';
$compliance = getComplianceById($conn, $compliance_id);

if ($compliance) {
    echo '<input type="hidden" name="compliance_id" value="' . $compliance_id . '">';
    echo '<div class="mb-4 p-3 bg-slate-50 rounded-lg">';
    echo '<p class="text-sm"><strong>Operator:</strong> ' . $compliance['first_name'] . ' ' . $compliance['last_name'] . '</p>';
    echo '<p class="text-sm"><strong>Vehicle:</strong> ' . $compliance['plate_number'] . ' - ' . ucfirst($compliance['vehicle_type']) . '</p>';
    echo '</div>';
    
    echo '<div class="space-y-4">';
    echo '<div class="grid grid-cols-2 gap-4">';
    echo '<div>';
    echo '<label class="block text-sm font-medium text-slate-700 mb-1">Franchise Status</label>';
    echo '<select name="franchise_status" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">';
    echo '<option value="valid"' . ($compliance['franchise_status'] == 'valid' ? ' selected' : '') . '>Valid</option>';
    echo '<option value="expired"' . ($compliance['franchise_status'] == 'expired' ? ' selected' : '') . '>Expired</option>';
    echo '<option value="pending"' . ($compliance['franchise_status'] == 'pending' ? ' selected' : '') . '>Pending</option>';
    echo '<option value="revoked"' . ($compliance['franchise_status'] == 'revoked' ? ' selected' : '') . '>Revoked</option>';
    echo '</select>';
    echo '</div>';
    echo '<div>';
    echo '<label class="block text-sm font-medium text-slate-700 mb-1">Inspection Status</label>';
    echo '<select name="inspection_status" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">';
    echo '<option value="passed"' . ($compliance['inspection_status'] == 'passed' ? ' selected' : '') . '>Passed</option>';
    echo '<option value="failed"' . ($compliance['inspection_status'] == 'failed' ? ' selected' : '') . '>Failed</option>';
    echo '<option value="pending"' . ($compliance['inspection_status'] == 'pending' ? ' selected' : '') . '>Pending</option>';
    echo '<option value="overdue"' . ($compliance['inspection_status'] == 'overdue' ? ' selected' : '') . '>Overdue</option>';
    echo '</select>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="grid grid-cols-2 gap-4">';
    echo '<div>';
    echo '<label class="block text-sm font-medium text-slate-700 mb-1">Compliance Score (%)</label>';
    echo '<input type="number" name="compliance_score" min="0" max="100" step="0.1" value="' . $compliance['compliance_score'] . '" readonly class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-gray-100 cursor-not-allowed" title="Auto-generated based on compliance rules">';
    echo '</div>';
    echo '<div>';
    echo '<label class="block text-sm font-medium text-slate-700 mb-1">Next Inspection Due</label>';
    echo '<input type="date" name="next_inspection_due" value="' . $compliance['next_inspection_due'] . '" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-orange-300">';
    echo '</div>';
    echo '</div>';
    echo '</div>';
} else {
    echo '<p class="text-red-600">Compliance record not found.</p>';
}
?>