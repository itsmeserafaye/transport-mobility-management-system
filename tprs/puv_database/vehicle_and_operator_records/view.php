<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$operator_id = $_GET['id'] ?? '';
$operator = getOperatorById($conn, $operator_id);
$vehicles = getVehiclesByOperator($conn, $operator_id);

if (!$operator) {
    echo "Operator not found";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Operator - <?php echo $operator['first_name'] . ' ' . $operator['last_name']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-6">Operator Details</h1>
        
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="font-semibold text-gray-700 mb-2">Personal Information</h3>
                <p><strong>Name:</strong> <?php echo $operator['first_name'] . ' ' . $operator['last_name']; ?></p>
                <p><strong>Operator ID:</strong> <?php echo $operator['operator_id']; ?></p>
                <p><strong>Contact:</strong> <?php echo $operator['contact_number']; ?></p>
                <p><strong>Email:</strong> <?php echo $operator['email'] ?? 'N/A'; ?></p>
                <p><strong>Address:</strong> <?php echo $operator['address']; ?></p>
            </div>
            <div>
                <h3 class="font-semibold text-gray-700 mb-2">License Information</h3>
                <p><strong>License Number:</strong> <?php echo $operator['license_number']; ?></p>
                <p><strong>Expiry Date:</strong> <?php echo date('M d, Y', strtotime($operator['license_expiry'])); ?></p>
                <p><strong>Status:</strong> <span class="px-2 py-1 text-xs rounded-full <?php echo $operator['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo ucfirst($operator['status']); ?></span></p>
                <p><strong>Registered:</strong> <?php echo date('M d, Y', strtotime($operator['date_registered'])); ?></p>
            </div>
        </div>

        <h3 class="font-semibold text-gray-700 mb-4">Vehicles</h3>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="border border-gray-300 px-4 py-2 text-left">Plate Number</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Type</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Make/Model</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Year</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2"><?php echo $vehicle['plate_number']; ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo ucfirst($vehicle['vehicle_type']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo $vehicle['year_manufactured']; ?></td>
                        <td class="border border-gray-300 px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $vehicle['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($vehicle['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <button onclick="window.close()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Close</button>
        </div>
    </div>
</body>
</html>