<?php
$page_title = "Create Auction";
require_once 'config/session.php';
requireLogin();
require_once 'includes/header.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch categories
$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
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
    $seller_id = $_SESSION['user_id'];
    
    $query = "INSERT INTO auctions (title, description, starting_price, category_id, seller_id, 
              start_date, end_date, image_url, status) 
              VALUES (:title, :description, :starting_price, :category_id, :seller_id, 
              :start_date, :end_date, :image_url, 'pending')";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':starting_price', $starting_price);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':seller_id', $seller_id);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':image_url', $image_url);
    
    if ($stmt->execute()) {
        $success = "Auction created successfully! It will be reviewed by our team.";
    } else {
        $error = "Failed to create auction. Please try again.";
    }
}
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Create New Auction</h2>
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
                    <input type="text" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Starting Price ($)</label>
                    <input type="number" name="starting_price" class="form-control" 
                           step="0.01" min="0.01" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Start Date & Time</label>
                    <input type="datetime-local" name="start_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">End Date & Time</label>
                    <input type="datetime-local" name="end_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Image URL</label>
                    <input type="url" name="image_url" class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary">Create Auction</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>