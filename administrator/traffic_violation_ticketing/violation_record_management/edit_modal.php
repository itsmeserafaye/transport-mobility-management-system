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
            <select name="operator_id" id="editOperatorSelect" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" onchange="loadEditOperatorVehicles()">
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
            <select name="vehicle_id" id="editVehicleSelect" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Select Vehicle</option>
                <?php 
                $v_query = "SELECT v.vehicle_id, v.plate_number, v.vehicle_type, v.operator_id 
                           FROM vehicles v 
                           JOIN franchise_records fr ON v.vehicle_id = fr.vehicle_id 
                           WHERE fr.status = 'valid' 
                           ORDER BY v.plate_number";
                $v_stmt = $conn->prepare($v_query);
                $v_stmt->execute();
                $vehicles = $v_stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($vehicles as $v): ?>
                <option value="<?php echo $v['vehicle_id']; ?>" data-operator="<?php echo $v['operator_id']; ?>" <?php echo $violation['vehicle_id'] == $v['vehicle_id'] ? 'selected' : ''; ?>><?php echo $v['plate_number'] . ' - ' . $v['vehicle_type']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Violation Category</label>
            <select name="violation_category" id="editCategorySelect" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" onchange="loadEditViolationTypes()">
                <option value="">Select Category</option>
                <?php 
                $cat_query = "SELECT * FROM violation_categories ORDER BY category_name";
                $cat_stmt = $conn->prepare($cat_query);
                $cat_stmt->execute();
                $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo $violation['violation_category'] == $cat['category_id'] ? 'selected' : ''; ?>><?php echo $cat['category_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Violation Type</label>
            <select name="violation_type" id="editTypeSelect" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Select Category First</option>
                <?php if ($violation['violation_type']): ?>
                <option value="<?php echo $violation['violation_type']; ?>" selected><?php echo $violation['violation_type']; ?></option>
                <?php endif; ?>
            </select>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Fine Amount</label>
            <input type="number" name="fine_amount" step="0.01" value="<?php echo htmlspecialchars($violation['fine_amount']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Violation Date</label>
            <input type="datetime-local" name="violation_date" value="<?php echo date('Y-m-d\TH:i', strtotime($violation['violation_date'])); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
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

<script>
// Load vehicles based on selected operator in edit form
function loadEditOperatorVehicles() {
    const operatorId = document.getElementById('editOperatorSelect').value;
    const vehicleSelect = document.getElementById('editVehicleSelect');
    
    if (!operatorId) {
        vehicleSelect.innerHTML = '<option value="">Select Operator First</option>';
        return;
    }
    
    vehicleSelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch(`get_operator_vehicles.php?operator_id=${operatorId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.vehicles.length > 0) {
                vehicleSelect.innerHTML = '<option value="">Select Vehicle</option>' +
                    data.vehicles.map(vehicle => 
                        `<option value="${vehicle.vehicle_id}">${vehicle.plate_number} - ${vehicle.vehicle_type}</option>`
                    ).join('');
            } else {
                vehicleSelect.innerHTML = '<option value="">No vehicles found</option>';
            }
        })
        .catch(error => {
            vehicleSelect.innerHTML = '<option value="">Error loading vehicles</option>';
        });
}

// Load violation types based on selected category in edit form
function loadEditViolationTypes() {
    const categoryId = document.getElementById('editCategorySelect').value;
    const typeSelect = document.getElementById('editTypeSelect');
    
    if (!categoryId) {
        typeSelect.innerHTML = '<option value="">Select Category First</option>';
        return;
    }
    
    typeSelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch(`get_violation_types.php?category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.types.length > 0) {
                typeSelect.innerHTML = '<option value="">Select Violation Type</option>' +
                    data.types.map(type => 
                        `<option value="${type.type_name}">${type.type_name}</option>`
                    ).join('');
            } else {
                typeSelect.innerHTML = '<option value="">No types found</option>';
            }
        })
        .catch(error => {
            typeSelect.innerHTML = '<option value="">Error loading types</option>';
        });
}
</script>