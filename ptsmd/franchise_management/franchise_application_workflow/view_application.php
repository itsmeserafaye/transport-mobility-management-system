<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$applicationId = $_GET['id'] ?? '';

if (!$applicationId) {
    echo "<p>Application ID not provided.</p>";
    exit;
}

// Get application details
$stmt = $conn->prepare("
    SELECT fa.*, o.first_name, o.last_name, o.contact_number, o.address, o.license_number,
           v.plate_number, v.make, v.model, v.vehicle_type
    FROM franchise_applications fa
    LEFT JOIN operators o ON fa.operator_id = o.operator_id
    LEFT JOIN vehicles v ON fa.vehicle_id = v.vehicle_id
    WHERE fa.application_id = ?
");
$stmt->execute([$applicationId]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    echo "<p>Application not found.</p>";
    exit;
}
?>

<div class="space-y-6">
    <!-- Application Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Application Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Application ID</label>
                <p class="text-sm"><?php echo htmlspecialchars($app['application_id']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Application Type</label>
                <p class="text-sm"><?php echo ucfirst($app['application_type']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Date Applied</label>
                <p class="text-sm"><?php echo date('M d, Y', strtotime($app['application_date'])); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <span class="px-2 py-1 text-xs font-medium <?php 
                    echo $app['status'] == 'approved' ? 'bg-green-100 text-green-800' : 
                        ($app['status'] == 'rejected' ? 'bg-red-100 text-red-800' : 
                        ($app['status'] == 'under_review' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'));
                ?> rounded-full"><?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?></span>
            </div>
        </div>
    </div>

    <!-- Operator Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Operator Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Name</label>
                <p class="text-sm"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Contact Number</label>
                <p class="text-sm"><?php echo htmlspecialchars($app['contact_number']); ?></p>
            </div>
            <div class="col-span-2">
                <label class="text-sm font-medium text-gray-600">Address</label>
                <p class="text-sm"><?php echo htmlspecialchars($app['address']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">License Number</label>
                <p class="text-sm"><?php echo htmlspecialchars($app['license_number']); ?></p>
            </div>
        </div>
    </div>

    <!-- Vehicle Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Vehicle Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Plate Number</label>
                <p class="text-sm"><?php echo htmlspecialchars($app['plate_number']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Vehicle Type</label>
                <p class="text-sm"><?php echo ucfirst($app['vehicle_type']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Make</label>
                <p class="text-sm"><?php echo htmlspecialchars($app['make']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Model</label>
                <p class="text-sm"><?php echo htmlspecialchars($app['model']); ?></p>
            </div>
        </div>
    </div>

    <!-- Workflow Info -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-semibold text-lg mb-3">Workflow Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Current Stage</label>
                <p class="text-sm"><?php echo ucfirst(str_replace('_', ' ', $app['workflow_stage'])); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Assigned To</label>
                <p class="text-sm"><?php echo htmlspecialchars($app['assigned_to'] ?: 'Not assigned'); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Route Requested</label>
                <p class="text-sm"><?php echo htmlspecialchars($app['route_requested']); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Processing Timeline</label>
                <p class="text-sm"><?php echo $app['processing_timeline'] ? $app['processing_timeline'] . ' days' : 'Not set'; ?></p>
            </div>
        </div>
    </div>
</div>