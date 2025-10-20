<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        try {
            // Generate announcement ID
            $stmt = $conn->query("SELECT COUNT(*) as count FROM announcements");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $announcement_id = 'ANN-' . date('Y') . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            
            $title = $_POST['title'];
            $content = $_POST['content'];
            $priority = $_POST['priority'];
            $target_audience = $_POST['target_audience'];
            $status = $_POST['status'] ?? 'draft';
            $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            $created_by = $_POST['created_by'] ?? 'Administrator';
            
            $image_path = null;
            
            // Handle file upload
            if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/announcements/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['announcement_image']['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $filename = $announcement_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['announcement_image']['tmp_name'], $upload_path)) {
                        $image_path = 'uploads/announcements/' . $filename;
                    }
                }
            }
            
            $query = "INSERT INTO announcements (announcement_id, title, content, image_path, priority, status, target_audience, publish_date, expiry_date, created_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$announcement_id, $title, $content, $image_path, $priority, $status, $target_audience, $publish_date, $expiry_date, $created_by]);
            
            echo json_encode(['success' => true, 'message' => 'Announcement created successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error creating announcement: ' . $e->getMessage()]);
        }
    }
    
    if ($action === 'update') {
        try {
            $announcement_id = $_POST['announcement_id'];
            $title = $_POST['title'];
            $content = $_POST['content'];
            $priority = $_POST['priority'];
            $target_audience = $_POST['target_audience'];
            $status = $_POST['status'];
            $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            
            $image_path = $_POST['existing_image'] ?? null;
            
            // Handle new file upload
            if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/announcements/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['announcement_image']['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $filename = $announcement_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['announcement_image']['tmp_name'], $upload_path)) {
                        // Delete old image if exists
                        if ($image_path && file_exists('../' . $image_path)) {
                            unlink('../' . $image_path);
                        }
                        $image_path = 'uploads/announcements/' . $filename;
                    }
                }
            }
            
            $query = "UPDATE announcements SET title = ?, content = ?, image_path = ?, priority = ?, status = ?, target_audience = ?, publish_date = ?, expiry_date = ? WHERE announcement_id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$title, $content, $image_path, $priority, $status, $target_audience, $publish_date, $expiry_date, $announcement_id]);
            
            echo json_encode(['success' => true, 'message' => 'Announcement updated successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating announcement: ' . $e->getMessage()]);
        }
    }
    
    if ($action === 'delete') {
        try {
            $announcement_id = $_POST['announcement_id'];
            
            // Get image path before deletion
            $stmt = $conn->prepare("SELECT image_path FROM announcements WHERE announcement_id = ?");
            $stmt->execute([$announcement_id]);
            $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete the announcement
            $stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ?");
            $stmt->execute([$announcement_id]);
            
            // Delete image file if exists
            if ($announcement && $announcement['image_path'] && file_exists('../' . $announcement['image_path'])) {
                unlink('../' . $announcement['image_path']);
            }
            
            echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting announcement: ' . $e->getMessage()]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_announcements') {
        try {
            $query = "SELECT * FROM announcements ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $announcements]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching announcements: ' . $e->getMessage()]);
        }
    }
    
    if ($action === 'get_announcement') {
        try {
            $announcement_id = $_GET['id'];
            $stmt = $conn->prepare("SELECT * FROM announcements WHERE announcement_id = ?");
            $stmt->execute([$announcement_id]);
            $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $announcement]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching announcement: ' . $e->getMessage()]);
        }
    }
}
?>