<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$lifecycleId = $_GET['id'] ?? '';

if (!$lifecycleId) {
    echo "<p>Lifecycle ID not provided.</p>";
    exit;
}

// Get lifecycle details
$query = "SELECT fl.*, fr.franchise_number, fr.franchise_id, fr.route_assigned, fr.status as franchise_status,
                 o.first_name, o.last_name, o.address, o.contact_number, o.license_number,
                 v.plate_number, v.vehicle_type, v.make, v.model, v.year_manufactured
          FROM franchise_lifecycle fl
          JOIN franchise_records fr ON fl.franchise_id = fr.franchise_id
          JOIN operators o ON fl.operator_id = o.operator_id
          JOIN vehicles v ON fl.vehicle_id = v.vehicle_id
          WHERE fl.lifecycle_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$lifecycleId]);
$lifecycle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lifecycle) {
    echo "<p>Lifecycle record not found.</p>";
    exit;
}

// Get lifecycle actions history
$query = "SELECT * FROM lifecycle_actions WHERE lifecycle_id = ? ORDER BY action_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$lifecycleId]);
$actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <!-- Franchise Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Franchise Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Franchise Number</label>
                <p class="text-sm"><?php echo htmlspecialchars($lifecycle['franchise_number']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Franchise ID</label>
                <p class="text-sm"><?php echo htmlspecialchars($lifecycle['franchise_id']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Route Assigned</label>
                <p class="text-sm"><?php echo htmlspecialchars($lifecycle['route_assigned']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Current Status</label>
                <?php 
                $statusClass = $lifecycle['franchise_status'] == 'valid' ? 'bg-green-100 text-green-800' : 
                              ($lifecycle['franchise_status'] == 'expired' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                ?>
                <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded-full"><?php echo ucfirst($lifecycle['franchise_status']); ?></span>
            </div>
        </div>
    </div>

    <!-- Operator Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Operator Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Name</label>
                <p class="text-sm"><?php echo htmlspecialchars($lifecycle['first_name'] . ' ' . $lifecycle['last_name']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Contact Number</label>
                <p class="text-sm"><?php echo htmlspecialchars($lifecycle['contact_number']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Address</label>
                <p class="text-sm"><?php echo htmlspecialchars($lifecycle['address']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">License Number</label>
                <p class="text-sm"><?php echo htmlspecialchars($lifecycle['license_number']); ?></p>
            </div>
        </div>
    </div>

    <!-- Vehicle Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Vehicle Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Plate Number</label>
                <p class="text-sm"><?php echo htmlspecialchars($lifecycle['plate_number']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Vehicle Type</label>
                <p class="text-sm"><?php echo ucfirst($lifecycle['vehicle_type']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Make & Model</label>
                <p class="text-sm"><?php echo htmlspecialchars($lifecycle['make'] . ' ' . $lifecycle['model']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Year Manufactured</label>
                <p class="text-sm"><?php echo $lifecycle['year_manufactured']; ?></p>
            </div>
        </div>
    </div>

    <!-- Lifecycle Status -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Lifecycle Status</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Current Stage</label>
                <?php 
                $stageColors = [
                    'active' => 'bg-green-100 text-green-800',
                    'renewal' => 'bg-yellow-100 text-yellow-800',
                    'expired' => 'bg-red-100 text-red-800',
                    'revocation' => 'bg-gray-100 text-gray-800',
                    'amendment' => 'bg-blue-100 text-blue-800',
                    'suspended' => 'bg-orange-100 text-orange-800'
                ];
                $stageClass = $stageColors[$lifecycle['lifecycle_stage']] ?? 'bg-slate-100 text-slate-800';
                ?>
                <span class="px-2 py-1 text-xs font-medium <?php echo $stageClass; ?> rounded-full"><?php echo ucfirst($lifecycle['lifecycle_stage']); ?></span>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Action Required</label>
                <p class="text-sm"><?php echo ucfirst(str_replace('_', ' ', $lifecycle['action_required'])); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Expiry Date</label>
                <p class="text-sm"><?php echo $lifecycle['expiry_date'] ? date('M d, Y', strtotime($lifecycle['expiry_date'])) : 'N/A'; ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Renewal Due Date</label>
                <p class="text-sm"><?php echo $lifecycle['renewal_due_date'] ? date('M d, Y', strtotime($lifecycle['renewal_due_date'])) : 'N/A'; ?></p>
            </div>
        </div>
    </div>

    <!-- Lifecycle Actions History -->
    <?php if (!empty($actions)): ?>
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Action History</h3>
        <div class="space-y-3">
            <?php foreach ($actions as $action): ?>
            <div class="border-l-4 border-blue-500 pl-4 py-2 bg-white rounded">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-medium text-sm"><?php echo ucfirst($action['action_type']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($action['reason']); ?></p>
                        <p class="text-xs text-gray-500">Processed by: <?php echo htmlspecialchars($action['processed_by']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($action['action_date'])); ?></p>
                        <?php if ($action['effective_date']): ?>
                        <p class="text-xs text-gray-500">Effective: <?php echo date('M d, Y', strtotime($action['effective_date'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>