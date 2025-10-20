<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/transport_and_mobility_management_system/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$operator_id = $_GET['id'] ?? '';
$operator = getOperatorById($conn, $operator_id);

if (!$operator) {
    header('Location: index.php?error=Operator not found');
    exit;
}

if ($_POST) {
    $data = [
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'address' => $_POST['address'],
        'contact_number' => $_POST['contact_number'],
        'license_expiry' => $_POST['license_expiry']
    ];
    
    if (updateOperator($conn, $operator_id, $data)) {
        header('Location: index.php?message=Operator updated successfully');
        exit;
    } else {
        $error = "Failed to update operator";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Operator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-6">Edit Operator</h1>
        
        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
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
                <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">Update Operator</button>
            </div>
        </form>
    </div>
</body>
</html>