<?php
$page_title = "Manage Categories - Admin";
require_once '../config/session.php';
requireAdmin();
require_once '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $name = $_POST['name'];
            $description = $_POST['description'];
            
            // Check if category name already exists
            $check_query = "SELECT id FROM categories WHERE name = :name";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':name', $name);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error = "Category name already exists!";
            } else {
                $insert_query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(':name', $name);
                $insert_stmt->bindParam(':description', $description);
                
                if ($insert_stmt->execute()) {
                    $success = "Category added successfully!";
                } else {
                    $error = "Failed to add category!";
                }
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $description = $_POST['description'];
            
            // Check if category name already exists (excluding current category)
            $check_query = "SELECT id FROM categories WHERE name = :name AND id != :id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':name', $name);
            $check_stmt->bindParam(':id', $id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error = "Category name already exists!";
            } else {
                $update_query = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':name', $name);
                $update_stmt->bindParam(':description', $description);
                $update_stmt->bindParam(':id', $id);
                
                if ($update_stmt->execute()) {
                    $success = "Category updated successfully!";
                } else {
                    $error = "Failed to update category!";
                }
            }
        }
    }
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // Check if category is being used by any auctions
    $check_usage_query = "SELECT COUNT(*) as count FROM auctions WHERE category_id = :id";
    $check_usage_stmt = $db->prepare($check_usage_query);
    $check_usage_stmt->bindParam(':id', $category_id);
    $check_usage_stmt->execute();
    $usage_count = $check_usage_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($usage_count > 0) {
        $error = "Cannot delete category! It is being used by $usage_count auction(s).";
    } else {
        $delete_query = "DELETE FROM categories WHERE id = :id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':id', $category_id);
        
        if ($delete_stmt->execute()) {
            $success = "Category deleted successfully!";
        } else {
            $error = "Failed to delete category!";
        }
    }
}

// Fetch all categories with auction count
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM auctions WHERE category_id = c.id) as auction_count
          FROM categories c ORDER BY c.name";
$stmt = $db->query($query);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category for editing if requested
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_query = "SELECT * FROM categories WHERE id = :id";
    $edit_stmt = $db->prepare($edit_query);
    $edit_stmt->bindParam(':id', $_GET['edit']);
    $edit_stmt->execute();
    $edit_category = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container">
    <h1>Manage Categories</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Add/Edit Category Form -->
    <div class="card" style="margin: 2rem 0;">
        <div class="card-header">
            <h3><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $edit_category ? 'edit' : 'add'; ?>">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php 
                        echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; 
                    ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                </button>
                
                <?php if ($edit_category): ?>
                    <a href="manage-categories.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Categories List -->
    <div class="card">
        <div class="card-header">
            <h3>Existing Categories</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Auctions</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($category['description'], 0, 50)); ?>...</td>
                            <td><?php echo $category['auction_count']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($category['created_at'])); ?></td>
                            <td>
                                <a href="manage-categories.php?edit=<?php echo $category['id']; ?>" 
                                   class="btn btn-secondary" style="padding: 0.5rem 1rem;">Edit</a>
                                
                                <?php if ($category['auction_count'] == 0): ?>
                                    <a href="manage-categories.php?delete=<?php echo $category['id']; ?>" 
                                       class="btn btn-danger" style="padding: 0.5rem 1rem;"
                                       onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                                <?php else: ?>
                                    <span style="color: #6b7280; font-size: 0.875rem;">Cannot delete (has auctions)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div style="margin-top: 2rem;">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>