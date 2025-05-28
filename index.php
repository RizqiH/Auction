<?php
$page_title = "Home - Auction System";
require_once 'includes/header.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch active auctions
$query = "SELECT a.*, c.name as category_name, u.username as seller_name 
          FROM auctions a 
          LEFT JOIN categories c ON a.category_id = c.id 
          LEFT JOIN users u ON a.seller_id = u.id 
          WHERE a.status = 'active' AND a.end_date > NOW() 
          ORDER BY a.created_at DESC 
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="hero">
    <div class="container">
        <h1>Welcome to AuctionHub</h1>
        <p>Discover amazing deals and bid on unique items</p>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-secondary" style="margin-top: 1rem;">Get Started</a>
        <?php else: ?>
            <a href="create-auction.php" class="btn btn-secondary" style="margin-top: 1rem;">Create Auction</a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <h2 style="margin-bottom: 2rem;">Active Auctions</h2>
    
    <div class="grid grid-3">
        <?php foreach ($auctions as $auction): ?>
            <div class="card">
                <img src="<?php echo $auction['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($auction['title']); ?>" 
                     class="card-image">
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($auction['title']); ?></h3>
                    <p style="color: #6b7280; margin: 0.5rem 0;">
                        <?php echo htmlspecialchars($auction['category_name']); ?>
                    </p>
                    <p style="margin: 1rem 0;">
                        <?php echo substr(htmlspecialchars($auction['description']), 0, 100); ?>...
                    </p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <p style="color: #6b7280; font-size: 0.875rem;">Current Price</p>
                            <p style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                $<?php echo number_format($auction['current_price'] ?: $auction['starting_price'], 2); ?>
                            </p>
                        </div>
                        <a href="auction-detail.php?id=<?php echo $auction['id']; ?>" 
                           class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div style="text-align: center; margin-top: 3rem;">
        <a href="auctions.php" class="btn btn-primary">View All Auctions</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>