<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Get all operators for dropdown
$operators = getOperators($conn, 100, 0);

if ($_POST) {
    $vehicle_id = generateVehicleId($conn);
    $data = [
        'vehicle_id' => $vehicle_id,
        'operator_id' => $_POST['operator_id'],
        'plate_number' => $_POST['plate_number'],
        'vehicle_type' => $_POST['vehicle_type'],
        'make' => $_POST['make'],
        'model' => $_POST['model'],
        'year_manufactured' => $_POST['year_manufactured'],
        'engine_number' => $_POST['engine_number'],
        'chassis_number' => $_POST['chassis_number'],
        'seating_capacity' => $_POST['seating_capacity']
    ];
    
    if (addVehicle($conn, $data)) {
        header('Location: index.php?message=Vehicle registered successfully');
        exit;
    } else {
        $error = "Failed to register vehicle";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Vehicle</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-6">Register New Vehicle</h1>
        
        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Operator</label>
                <select name="operator_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="">Select Operator</option>
                    <?php foreach ($operators as $op): ?>
                    <option value="<?php echo $op['operator_id']; ?>"><?php echo $op['first_name'] . ' ' . $op['last_name'] . ' (' . $op['operator_id'] . ')'; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Plate Number</label>
                    <input type="text" name="plate_number" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Vehicle Type</label>
                    <select name="vehicle_type" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Select Type</option>
                        <option value="jeepney">Jeepney</option>
                        <option value="bus">Bus</option>
                        <option value="tricycle">Tricycle</option>
                        <option value="taxi">Taxi</option>
                        <option value="van">Van</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Make</label>
                    <input type="text" name="make" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Model</label>
                    <input type="text" name="model" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Year</label>
                    <input type="number" name="year_manufactured" min="1990" max="2025" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Engine Number</label>
                    <input type="text" name="engine_number" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Chassis Number</label>
                    <input type="text" name="chassis_number" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Seating Capacity</label>
                <input type="number" name="seating_capacity" min="1" max="100" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            
            <div class="flex justify-end space-x-3">
                <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Register Vehicle</button>
            </div>
        </form>
    </div>
</body>
</html>