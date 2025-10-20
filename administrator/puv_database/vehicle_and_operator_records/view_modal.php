<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$operator_id = $_GET['id'] ?? '';
$operator = getOperatorById($conn, $operator_id);
$vehicles = getVehiclesByOperator($conn, $operator_id);

if (!$operator) {
    echo "<p class='text-red-500'>Operator not found</p>";
    exit;
}
?>
<div class="grid grid-cols-2 gap-6 mb-6">
    <div>
        <h3 class="font-semibold text-gray-700 mb-2">Personal Information</h3>
        <p><strong>Name:</strong> <?php echo $operator['first_name'] . ' ' . $operator['last_name']; ?></p>
        <p><strong>Operator ID:</strong> <?php echo $operator['operator_id']; ?></p>
        <p><strong>Contact:</strong> <?php echo $operator['contact_number']; ?></p>
        <p><strong>Email:</strong> <?php echo $operator['email'] ?? 'N/A'; ?></p>
        <p><strong>Address:</strong> <?php echo $operator['address']; ?></p>
        <p><strong>Status:</strong> <span class="px-2 py-1 text-xs rounded-full <?php echo $operator['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo ucfirst($operator['status']); ?></span></p>
    </div>
    <div>
        <h3 class="font-semibold text-gray-700 mb-2">License & Compliance</h3>
        <p><strong>License Number:</strong> <?php echo $operator['license_number']; ?></p>
        <p><strong>Expiry Date:</strong> <?php echo date('M d, Y', strtotime($operator['license_expiry'])); ?></p>
        <p><strong>Registered:</strong> <?php echo date('M d, Y', strtotime($operator['date_registered'])); ?></p>
        <?php if (isset($operator['compliance_score'])): ?>
        <p><strong>Compliance Score:</strong> <span class="font-medium <?php echo $operator['compliance_score'] >= 80 ? 'text-green-600' : ($operator['compliance_score'] >= 60 ? 'text-yellow-600' : 'text-red-600'); ?>"><?php echo $operator['compliance_score']; ?>%</span></p>
        <p><strong>Franchise Status:</strong> <span class="px-2 py-1 text-xs rounded-full <?php echo $operator['franchise_status'] == 'valid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo ucfirst($operator['franchise_status']); ?></span></p>
        <p><strong>Inspection Status:</strong> <span class="px-2 py-1 text-xs rounded-full <?php echo $operator['inspection_status'] == 'passed' ? 'bg-green-100 text-green-800' : ($operator['inspection_status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>"><?php echo ucfirst($operator['inspection_status']); ?></span></p>
        <?php endif; ?>
    </div>
</div>

<h3 class="font-semibold text-gray-700 mb-4">Registered Vehicles</h3>
<div class="overflow-x-auto">
    <table class="w-full border-collapse border border-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="border border-gray-300 px-4 py-2 text-left">Plate Number</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Type</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Make/Model</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Color</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Year</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                <th class="border border-gray-300 px-4 py-2 text-left">Compliance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vehicles)): ?>
            <tr>
                <td colspan="7" class="border border-gray-300 px-4 py-2 text-center text-gray-500">No vehicles registered</td>
            </tr>
            <?php else: ?>
            <?php foreach ($vehicles as $vehicle): ?>
            <tr>
                <td class="border border-gray-300 px-4 py-2"><?php echo $vehicle['plate_number']; ?></td>
                <td class="border border-gray-300 px-4 py-2"><?php echo ucfirst($vehicle['vehicle_type']); ?></td>
                <td class="border border-gray-300 px-4 py-2"><?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?></td>
                <td class="border border-gray-300 px-4 py-2"><?php echo $vehicle['color'] ?? 'N/A'; ?></td>
                <td class="border border-gray-300 px-4 py-2"><?php echo $vehicle['year_manufactured']; ?></td>
                <td class="border border-gray-300 px-4 py-2">
                    <span class="px-2 py-1 text-xs rounded-full <?php echo $vehicle['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($vehicle['status']); ?>
                    </span>
                </td>
                <td class="border border-gray-300 px-4 py-2">
                    <?php if (isset($vehicle['compliance_score'])): ?>
                    <span class="text-sm font-medium <?php echo $vehicle['compliance_score'] >= 80 ? 'text-green-600' : ($vehicle['compliance_score'] >= 60 ? 'text-yellow-600' : 'text-red-600'); ?>"><?php echo $vehicle['compliance_score']; ?>%</span>
                    <?php else: ?>
                    <span class="text-gray-500 text-sm">N/A</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>