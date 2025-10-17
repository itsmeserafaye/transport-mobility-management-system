<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';
require_once 'functions.php';

$database = new Database();
$conn = $database->getConnection();

$compliance_id = $_GET['id'] ?? '';

if ($compliance_id) {
    $compliance = getComplianceById($conn, $compliance_id);
    
    if ($compliance) {
        $franchise_class = $compliance['franchise_status'] == 'valid' ? 'bg-green-100 text-green-800' : 
                          ($compliance['franchise_status'] == 'expired' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
        $inspection_class = $compliance['inspection_status'] == 'passed' ? 'bg-green-100 text-green-800' : 
                           ($compliance['inspection_status'] == 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
        $score = $compliance['compliance_score'] ?? 0;
        $score_color = $score >= 80 ? 'green' : ($score >= 60 ? 'yellow' : 'red');
        
        echo '<div class="space-y-4">';
        echo '<div class="grid grid-cols-2 gap-4">';
        echo '<div><label class="text-sm font-medium text-slate-600">Compliance ID</label><p class="text-slate-900">' . $compliance['compliance_id'] . '</p></div>';
        echo '<div><label class="text-sm font-medium text-slate-600">Operator</label><p class="text-slate-900">' . $compliance['first_name'] . ' ' . $compliance['last_name'] . '</p></div>';
        echo '<div><label class="text-sm font-medium text-slate-600">Vehicle</label><p class="text-slate-900">' . $compliance['plate_number'] . ' - ' . ucfirst($compliance['vehicle_type']) . '</p></div>';
        echo '<div><label class="text-sm font-medium text-slate-600">Make/Model</label><p class="text-slate-900">' . $compliance['make'] . ' ' . $compliance['model'] . '</p></div>';
        echo '</div>';
        echo '<div class="grid grid-cols-2 gap-4">';
        echo '<div><label class="text-sm font-medium text-slate-600">Franchise Status</label><br><span class="px-2 py-1 text-xs font-medium ' . $franchise_class . ' rounded-full">' . ucfirst($compliance['franchise_status']) . '</span></div>';
        echo '<div><label class="text-sm font-medium text-slate-600">Inspection Status</label><br><span class="px-2 py-1 text-xs font-medium ' . $inspection_class . ' rounded-full">' . ucfirst($compliance['inspection_status']) . '</span></div>';
        echo '</div>';
        echo '<div class="grid grid-cols-2 gap-4">';
        echo '<div><label class="text-sm font-medium text-slate-600">Compliance Score</label><div class="flex items-center mt-1"><div class="w-20 bg-gray-200 rounded-full h-2 mr-2"><div class="bg-' . $score_color . '-500 h-2 rounded-full" style="width: ' . $score . '%"></div></div><span class="text-sm font-medium text-' . $score_color . '-600">' . number_format($score, 1) . '%</span></div></div>';
        echo '<div><label class="text-sm font-medium text-slate-600">Violation Count</label><p class="text-slate-900">' . ($compliance['violation_count'] ?? 0) . ' violations</p></div>';
        echo '</div>';
        echo '<div class="grid grid-cols-2 gap-4">';
        echo '<div><label class="text-sm font-medium text-slate-600">Last Inspection</label><p class="text-slate-900">' . ($compliance['last_inspection_date'] ? date('M d, Y', strtotime($compliance['last_inspection_date'])) : 'N/A') . '</p></div>';
        echo '<div><label class="text-sm font-medium text-slate-600">Next Due</label><p class="text-slate-900">' . ($compliance['next_inspection_due'] ? date('M d, Y', strtotime($compliance['next_inspection_due'])) : 'N/A') . '</p></div>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<p class="text-red-600">Compliance record not found.</p>';
    }
} else {
    echo '<p class="text-red-600">Invalid compliance ID.</p>';
}
?>