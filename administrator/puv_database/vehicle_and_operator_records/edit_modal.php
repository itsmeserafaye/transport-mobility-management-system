<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$operator_id = $_GET['id'] ?? '';
$operator = getOperatorById($conn, $operator_id);

if (!$operator) {
    echo "<p class='text-red-500'>Operator not found</p>";
    exit;
}
?>
<form id="editOperatorForm" class="space-y-4">
    <input type="hidden" name="operator_id" value="<?php echo $operator['operator_id']; ?>">
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">First Name</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($operator['first_name']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Last Name</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($operator['last_name']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
        </div>
    </div>
    
    <div>
        <label class="block text-sm font-medium text-gray-700">Address</label>
        <textarea name="address" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"><?php echo htmlspecialchars($operator['address']); ?></textarea>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Contact Number</label>
            <input type="text" name="contact_number" value="<?php echo htmlspecialchars($operator['contact_number']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">License Number</label>
            <input type="text" value="<?php echo htmlspecialchars($operator['license_number']); ?>" readonly class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-100">
        </div>
    </div>
    
    <div>
        <label class="block text-sm font-medium text-gray-700">License Expiry</label>
        <input type="date" name="license_expiry" value="<?php echo $operator['license_expiry']; ?>" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
    </div>
    
    <div class="flex justify-end space-x-3">
        <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">Update Operator</button>
    </div>
</form>

