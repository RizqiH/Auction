<?php
$page_title = "Auction Details";
require_once 'includes/header.php';
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: auctions.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch auction details
$query = "SELECT a.*, c.name as category_name, u.username as seller_name, u.id as seller_id 
          FROM auctions a 
          LEFT JOIN categories c ON a.category_id = c.id 
          LEFT JOIN users u ON a.seller_id = u.id 
          WHERE a.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_GET['id']);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: auctions.php");
    exit();
}

$auction = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch bid history
$bid_query = "SELECT b.*, u.username 
              FROM bids b 
              JOIN users u ON b.user_id = u.id 
              WHERE b.auction_id = :auction_id 
              ORDER BY b.bid_amount DESC 
              LIMIT 10";
$bid_stmt = $db->prepare($bid_query);
$bid_stmt->bindParam(':auction_id', $_GET['id']);
$bid_stmt->execute();
$bids = $bid_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate time remaining
$end_time = strtotime($auction['end_date']);
$current_time = time();
$time_remaining = $end_time - $current_time;
?>

<div class="container">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php 
            echo $_SESSION['message']; 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="grid" style="grid-template-columns: 1fr 2fr; gap: 2rem;">
        <div>
            <img src="<?php echo $auction['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" 
                 alt="<?php echo htmlspecialchars($auction['title']); ?>" 
                 style="width: 100%; border-radius: var(--border-radius);">
        </div>
        
        <div>
            <h1><?php echo htmlspecialchars($auction['title']); ?></h1>
            <span class="badge badge-<?php echo $auction['status']; ?>">
                <?php echo ucfirst($auction['status']); ?>
            </span>
            
            <p style="margin: 1rem 0; color: #6b7280;">
                Category: <?php echo htmlspecialchars($auction['category_name']); ?> | 
                Seller: <?php echo htmlspecialchars($auction['seller_name']); ?>
            </p>
            
            <div style="margin: 2rem 0;">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($auction['description'])); ?></p>
            </div>
            
            <div class="card" style="background: #f3f4f6;">
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <p style="color: #6b7280;">Starting Price</p>
                            <p style="font-size: 1.5rem; font-weight: bold;">
                                $<?php echo number_format($auction['starting_price'], 2); ?>
                            </p>
                        </div>
                        <div>
                            <p style="color: #6b7280;">Current Price</p>
                            <p style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                $<?php echo number_format($auction['current_price'] ?: $auction['starting_price'], 2); ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($time_remaining > 0 && $auction['status'] == 'active'): ?>
                        <p style="margin-top: 1rem; color: var(--danger-color);">
                            Time Remaining: 
                            <?php 
                            $days = floor($time_remaining / 86400);
                            $hours = floor(($time_remaining % 86400) / 3600);
                            $minutes = floor(($time_remaining % 3600) / 60);
                            echo "$days days, $hours hours, $minutes minutes";
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isLoggedIn() && $auction['status'] == 'active' && $time_remaining > 0 && $_SESSION['user_id'] != $auction['seller_id']): ?>
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-body">
                        <h3>Place Your Bid</h3>
                        <form method="POST" action="place-bid.php">
                            <input type="hidden" name="auction_id" value="<?php echo $auction['id']; ?>">
                            <div class="form-group">
                                <label class="form-label">Your Bid Amount ($)</label>
                                <input type="number" name="bid_amount" class="form-control" 
                                       step="0.01" 
                                       min="<?php echo ($auction['current_price'] ?: $auction['starting_price']) + 0.01; ?>" 
                                       required>
                                <small style="color: #6b7280;">
                                    Minimum bid: $<?php echo number_format(($auction['current_price'] ?: $auction['starting_price']) + 0.01, 2); ?>
                                </small>
                            </div>
                            <button type="submit" class="btn btn-primary">Place Bid</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card" style="margin-top: 3rem;">
        <div class="card-header">
            <h3>Bid History</h3>
        </div>
        <div class="card-body">
            <?php if (count($bids) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Bidder</th>
                            <th>Amount</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bids as $bid): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bid['username']); ?></td>
                                <td>$<?php echo number_format($bid['bid_amount'], 2); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($bid['bid_time'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No bids placed yet. Be the first to bid!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>