<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$violationId = $_GET['id'] ?? '';

if (!$violationId) {
    echo "<p>Violation ID not provided.</p>";
    exit;
}

// Get violation details using existing function
$violation = getViolationById($conn, $violationId);

if (!$violation) {
    echo "<p>Violation not found.</p>";
    exit;
}
?>

<div class="space-y-6">
    <!-- Violation Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Violation Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Violation ID</label>
                <p class="text-sm"><?php echo htmlspecialchars($violation['violation_id']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Violation Type</label>
                <p class="text-sm"><?php echo htmlspecialchars($violation['violation_type']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Date & Time</label>
                <p class="text-sm"><?php echo date('M d, Y g:i A', strtotime($violation['violation_date'])); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Location</label>
                <p class="text-sm"><?php echo htmlspecialchars($violation['location'] ?? 'Not specified'); ?></p>
            </div>
        </div>
    </div>

    <!-- Operator Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Operator Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Name</label>
                <p class="text-sm"><?php echo htmlspecialchars($violation['first_name'] . ' ' . $violation['last_name']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Operator ID</label>
                <p class="text-sm"><?php echo htmlspecialchars($violation['operator_id']); ?></p>
            </div>
            <?php if (isset($violation['operator_status'])): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Operator Status</label>
                <?php 
                $statusClass = $violation['operator_status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                ?>
                <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo ucfirst($violation['operator_status']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Vehicle Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Vehicle Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Plate Number</label>
                <p class="text-sm"><?php echo htmlspecialchars($violation['plate_number']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Vehicle Type</label>
                <p class="text-sm"><?php echo ucfirst($violation['vehicle_type']); ?></p>
            </div>
            <?php if (isset($violation['make']) && isset($violation['model'])): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Make & Model</label>
                <p class="text-sm"><?php echo htmlspecialchars($violation['make'] . ' ' . $violation['model']); ?></p>
            </div>
            <?php endif; ?>
            <?php if (isset($violation['vehicle_status'])): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Vehicle Status</label>
                <?php 
                $statusClass = $violation['vehicle_status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                ?>
                <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo ucfirst($violation['vehicle_status']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fine & Settlement Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Fine & Settlement</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Fine Amount</label>
                <p class="text-sm font-bold text-red-600">₱<?php echo number_format($violation['fine_amount'], 2); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Settlement Status</label>
                <?php 
                $statusClass = $violation['settlement_status'] == 'paid' ? 'bg-green-100 text-green-800' : 
                              ($violation['settlement_status'] == 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                ?>
                <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo ucfirst($violation['settlement_status']); ?></span>
            </div>
            <?php if ($violation['settlement_date']): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Settlement Date</label>
                <p class="text-sm"><?php echo date('M d, Y', strtotime($violation['settlement_date'])); ?></p>
            </div>
            <?php endif; ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Ticket Number</label>
                <p class="text-sm"><?php echo htmlspecialchars($violation['ticket_number'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>

    <!-- Violation Analytics (if available) -->
    <?php if (isset($violation['total_violations']) || isset($violation['risk_level'])): ?>
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Violation History</h3>
        <div class="grid grid-cols-3 gap-4">
            <?php if (isset($violation['total_violations'])): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Total Violations</label>
                <p class="text-sm font-bold"><?php echo $violation['total_violations']; ?></p>
            </div>
            <?php endif; ?>
            <?php if (isset($violation['risk_level'])): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Risk Level</label>
                <?php 
                $riskColor = $violation['risk_level'] == 'high' ? 'red' : ($violation['risk_level'] == 'medium' ? 'yellow' : 'green');
                ?>
                <span class="px-2 py-1 text-xs font-medium bg-<?php echo $riskColor; ?>-100 text-<?php echo $riskColor; ?>-800 rounded-full"><?php echo ucfirst($violation['risk_level']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($violation['repeat_offender_flag'])): ?>
            <div>
                <label class="text-sm font-medium text-gray-600">Repeat Offender</label>
                <p class="text-sm"><?php echo $violation['repeat_offender_flag'] ? 'Yes' : 'No'; ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>