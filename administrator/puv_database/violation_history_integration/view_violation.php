<?php
try {
    // Secure file inclusion with absolute path
    $baseDir = realpath(dirname(__DIR__, 3));
    if ($baseDir === false) {
        throw new Exception('Invalid base directory.');
    }
    $configPath = $baseDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
    $realConfigPath = realpath($configPath);
    if ($realConfigPath === false || !str_starts_with($realConfigPath, $baseDir) || !is_readable($realConfigPath)) {
        throw new Exception('Database configuration file not accessible.');
    }
    require_once $realConfigPath;
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $violationId = $_GET['id'] ?? '';
    
    // Validate violation ID format (should be VIO-YYYY-NNNN)
    if ($violationId && !preg_match('/^VIO-\d{4}-\d{4}$/', $violationId)) {
        throw new InvalidArgumentException('Invalid violation ID format.');
    }
    
    if (!$violationId) {
        throw new InvalidArgumentException('Violation ID not provided.');
    }
    
    // Get violation details
    $query = "SELECT vh.*, o.first_name, o.last_name, o.status as operator_status,
              v.plate_number, v.vehicle_type, v.make, v.model, v.status as vehicle_status,
              va.total_violations, va.risk_level, va.repeat_offender_flag
              FROM violation_history vh
              JOIN operators o ON vh.operator_id = o.operator_id
              JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
              LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id
              WHERE vh.violation_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$violationId]);
    $violation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$violation) {
        throw new Exception('Violation not found.');
    }
    
} catch (Exception $e) {
    echo '<div class="bg-red-50 border border-red-200 rounded-lg p-4">';
    echo '<p class="text-red-800 font-medium">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
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
            <div>
                <label class="text-sm font-medium text-gray-600">Operator Status</label>
                <?php 
                $statusClass = $violation['operator_status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                ?>
                <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo htmlspecialchars(ucfirst($violation['operator_status'])); ?></span>
            </div>
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
                <p class="text-sm"><?php echo htmlspecialchars(ucfirst($violation['vehicle_type'])); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Make & Model</label>
                <p class="text-sm"><?php echo htmlspecialchars($violation['make'] . ' ' . $violation['model']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Vehicle Status</label>
                <?php 
                $statusClass = $violation['vehicle_status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                ?>
                <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo htmlspecialchars(ucfirst($violation['vehicle_status'])); ?></span>
            </div>
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

    <!-- Violation Analytics -->
    <?php if (isset($violation['total_violations'])): ?>
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Violation History</h3>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Total Violations</label>
                <p class="text-sm font-bold"><?php echo $violation['total_violations']; ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Risk Level</label>
                <?php 
                $riskColor = $violation['risk_level'] == 'high' ? 'red' : ($violation['risk_level'] == 'medium' ? 'yellow' : 'green');
                ?>
                <span class="px-2 py-1 text-xs font-medium bg-<?php echo $riskColor; ?>-100 text-<?php echo $riskColor; ?>-800 rounded-full"><?php echo ucfirst($violation['risk_level']); ?></span>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Repeat Offender</label>
                <p class="text-sm"><?php echo $violation['repeat_offender_flag'] ? 'Yes' : 'No'; ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>