<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

if ($_POST) {
    $operator_id = generateOperatorId($conn);
    $data = [
        'operator_id' => $operator_id,
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'address' => $_POST['address'],
        'contact_number' => $_POST['contact_number'],
        'license_number' => $_POST['license_number'],
        'license_expiry' => $_POST['license_expiry']
    ];
    
    if (addOperator($conn, $data)) {
        header('Location: index.php?message=Operator added successfully');
        exit;
    } else {
        $error = "Failed to add operator";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Operator</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-6">Add New Operator</h1>
        
        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" name="first_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" name="last_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <textarea name="address" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                    <input type="text" name="contact_number" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">License Number</label>
                    <input type="text" name="license_number" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">License Expiry</label>
                <input type="date" name="license_expiry" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            
            <div class="flex justify-end space-x-3">
                <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">Add Operator</button>
            </div>
        </form>
    </div>
</body>
</html>