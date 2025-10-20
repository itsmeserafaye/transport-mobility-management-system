<?php
// Simple database viewer - for development only
$db_file = 'gsm_system.db';

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle delete request
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header('Location: view_database.php');
        exit;
    }
    
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Viewer - GSM System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">GSM System - User Database</h1>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">ID</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Role</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Created</th>
                        <th class="px-4 py-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="border-t">
                        <td class="px-4 py-2"><?= $user['id'] ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?= $user['role'] === 'administrator' ? 'bg-red-100 text-red-800' : 
                                   ($user['role'] === 'operator' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded-full <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= ucfirst($user['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-600"><?= $user['created_at'] ?></td>
                        <td class="px-4 py-2">
                            <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Delete user <?= htmlspecialchars($user['email']) ?>?')" class="text-red-600 hover:text-red-800 text-sm">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            <a href="index.html" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Back to Login</a>
        </div>
    </div>
</body>
</html>