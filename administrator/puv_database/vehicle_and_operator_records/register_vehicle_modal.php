<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();
$operators = getOperators($conn, 100, 0);
?>
<form id="registerVehicleForm" class="space-y-4">
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
        <button type="button" onclick="closeModal('vehicleModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Register Vehicle</button>
    </div>
</form>