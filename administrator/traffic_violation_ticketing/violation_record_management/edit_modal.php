<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$violationId = $_GET['id'] ?? '';

if (!$violationId) {
    echo "<p>Violation ID not provided.</p>";
    exit;
}

// Get violation details
$violation = getViolationById($conn, $violationId);

if (!$violation) {
    echo "<p>Violation not found.</p>";
    exit;
}
?>

<form id="editViolationForm" class="space-y-4">
    <input type="hidden" name="violation_id" value="<?php echo htmlspecialchars($violation['violation_id']); ?>">
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Operator</label>
            <select name="operator_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Select Operator</option>
                <?php 
                $operators = getOperators($conn);
                foreach ($operators as $op): ?>
                <option value="<?php echo $op['operator_id']; ?>" <?php echo $violation['operator_id'] == $op['operator_id'] ? 'selected' : ''; ?>><?php echo $op['first_name'] . ' ' . $op['last_name'] . ' (' . $op['operator_id'] . ')'; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Vehicle</label>
            <select name="vehicle_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Select Vehicle</option>
                <?php 
                $v_query = "SELECT vehicle_id, plate_number, operator_id FROM vehicles ORDER BY plate_number";
                $v_stmt = $conn->prepare($v_query);
                $v_stmt->execute();
                $vehicles = $v_stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($vehicles as $v): ?>
                <option value="<?php echo $v['vehicle_id']; ?>" data-operator="<?php echo $v['operator_id']; ?>" <?php echo $violation['vehicle_id'] == $v['vehicle_id'] ? 'selected' : ''; ?>><?php echo $v['plate_number'] . ' (' . $v['vehicle_id'] . ')'; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Violation Type</label>
            <select name="violation_type" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="Speeding" <?php echo $violation['violation_type'] == 'Speeding' ? 'selected' : ''; ?>>Speeding</option>
                <option value="Overloading" <?php echo $violation['violation_type'] == 'Overloading' ? 'selected' : ''; ?>>Overloading</option>
                <option value="Route Deviation" <?php echo $violation['violation_type'] == 'Route Deviation' ? 'selected' : ''; ?>>Route Deviation</option>
                <option value="No Franchise" <?php echo $violation['violation_type'] == 'No Franchise' ? 'selected' : ''; ?>>No Franchise</option>
                <option value="Reckless Driving" <?php echo $violation['violation_type'] == 'Reckless Driving' ? 'selected' : ''; ?>>Reckless Driving</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Fine Amount</label>
            <input type="number" name="fine_amount" step="0.01" value="<?php echo htmlspecialchars($violation['fine_amount']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Violation Date</label>
            <input type="datetime-local" name="violation_date" value="<?php echo date('Y-m-d\TH:i', strtotime($violation['violation_date'])); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Settlement Status</label>
            <select name="settlement_status" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="unpaid" <?php echo $violation['settlement_status'] == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                <option value="paid" <?php echo $violation['settlement_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
            </select>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Location</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($violation['location'] ?? ''); ?>" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Ticket Number</label>
            <input type="text" name="ticket_number" value="<?php echo htmlspecialchars($violation['ticket_number'] ?? ''); ?>" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
        </div>
    </div>
    
    <div class="flex justify-end space-x-3">
        <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">Update Violation</button>
    </div>
</form>