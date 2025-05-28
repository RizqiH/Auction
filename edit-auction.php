<?php
$page_title = "Edit Auction";
require_once 'config/session.php';
requireLogin();
require_once 'includes/header.php';
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: my-auctions.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check if user owns this auction and it's editable
$check_query = "SELECT * FROM auctions WHERE id = :id AND seller_id = :user_id AND status = 'pending'";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':id', $_GET['id']);
$check_stmt->bindParam(':user_id', $_SESSION['user_id']);
$check_stmt->execute();

if ($check_stmt->rowCount() == 0) {
    header("Location: my-auctions.php");
    exit();
}

$auction = $check_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch categories
$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->query($cat_query);
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $starting_price = $_POST['starting_price'];
    $category_id = $_POST['category_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $image_url = $_POST['image_url'];
    
    $query = "UPDATE auctions SET title = :title, description = :description, 
              starting_price = :starting_price, category_id = :category_id, 
              start_date = :start_date, end_date = :end_date, image_url = :image_url 
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':starting_price', $starting_price);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':image_url', $image_url);
    $stmt->bindParam(':id', $_GET['id']);
    
    if ($stmt->execute()) {
        $success = "Auction updated successfully!";
        // Refresh auction data
        $check_stmt->execute();
        $auction = $check_stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Failed to update auction. Please try again.";
    }
}
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Edit Auction</h2>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" 
                           value="<?php echo htmlspecialchars($auction['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4" required><?php 
                        echo htmlspecialchars($auction['description']); 
                    ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $auction['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Starting Price ($)</label>
                    <input type="number" name="starting_price" class="form-control" 
                           step="0.01" min="0.01" 
                           value="<?php echo $auction['starting_price']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Start Date & Time</label>
                    <input type="datetime-local" name="start_date" class="form-control" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($auction['start_date'])); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">End Date & Time</label>
                    <input type="datetime-local" name="end_date" class="form-control" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($auction['end_date'])); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Image URL</label>
                    <input type="url" name="image_url" class="form-control" 
                           value="<?php echo htmlspecialchars($auction['image_url']); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Update Auction</button>
                <a href="my-auctions.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>