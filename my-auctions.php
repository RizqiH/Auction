<?php
$page_title = "My Auctions";
require_once 'config/session.php';
requireLogin();
require_once 'includes/header.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch user's auctions
$query = "SELECT a.*, c.name as category_name,
          (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
          FROM auctions a 
          LEFT JOIN categories c ON a.category_id = c.id 
          WHERE a.seller_id = :user_id 
          ORDER BY a.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>My Auctions</h1>
        <a href="create-auction.php" class="btn btn-primary">Create New Auction</a>
    </div>
    
    <?php if (count($auctions) > 0): ?>
        <div class="grid grid-3">
            <?php foreach ($auctions as $auction): ?>
                <div class="card">
                    <img src="<?php echo $auction['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($auction['title']); ?>" 
                         class="card-image">
                    <div class="card-body">
                        <h3><?php echo htmlspecialchars($auction['title']); ?></h3>
                        <span class="badge badge-<?php echo $auction['status']; ?>">
                            <?php echo ucfirst($auction['status']); ?>
                        </span>
                        
                        <p style="margin: 1rem 0;">
                            Category: <?php echo htmlspecialchars($auction['category_name']); ?><br>
                            Bids: <?php echo $auction['bid_count']; ?><br>
                            Current Price: $<?php echo number_format($auction['current_price'] ?: $auction['starting_price'], 2); ?>
                        </p>
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="auction-detail.php?id=<?php echo $auction['id']; ?>" 
                               class="btn btn-primary" style="padding: 0.5rem 1rem;">View</a>
                            <?php if ($auction['status'] == 'pending'): ?>
                                <a href="edit-auction.php?id=<?php echo $auction['id']; ?>" 
                                   class="btn btn-secondary" style="padding: 0.5rem 1rem;">Edit</a>
                                <a href="delete-auction.php?id=<?php echo $auction['id']; ?>" 
                                   class="btn btn-danger" style="padding: 0.5rem 1rem;"
                                   onclick="return confirm('Are you sure you want to delete this auction?');">Delete</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <h3>You haven't created any auctions yet</h3>
                <p>Start selling by creating your first auction!</p>
                <a href="create-auction.php" class="btn btn-primary" style="margin-top: 1rem;">Create Auction</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>