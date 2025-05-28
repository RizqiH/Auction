<?php
/* ===== MY BIDS PAGE (my-bids.php) ===== */
$page_title = "My Bids";
require_once 'config/session.php';
requireLogin();
require_once 'includes/header.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch user's bids
$query = "SELECT b.*, a.title as auction_title, a.status as auction_status, 
          a.end_date, a.current_price, a.id as auction_id,
          (SELECT MAX(bid_amount) FROM bids WHERE auction_id = b.auction_id) as highest_bid
          FROM bids b 
          JOIN auctions a ON b.auction_id = a.id 
          WHERE b.user_id = :user_id 
          ORDER BY b.bid_time DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1>My Bids</h1>
    
    <?php if (count($bids) > 0): ?>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Auction</th>
                            <th>My Bid</th>
                            <th>Current Price</th>
                            <th>Status</th>
                            <th>End Date</th>
                            <th>Position</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bids as $bid): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bid['auction_title']); ?></td>
                                <td>$<?php echo number_format($bid['bid_amount'], 2); ?></td>
                                <td>$<?php echo number_format($bid['current_price'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $bid['auction_status']; ?>">
                                        <?php echo ucfirst($bid['auction_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($bid['end_date'])); ?></td>
                                <td>
                                    <?php if ($bid['bid_amount'] == $bid['highest_bid']): ?>
                                        <span style="color: var(--success-color); font-weight: bold;">Winning</span>
                                    <?php else: ?>
                                        <span style="color: var(--danger-color);">Outbid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="auction-detail.php?id=<?php echo $bid['auction_id']; ?>" 
                                       class="btn btn-primary" style="padding: 0.5rem 1rem;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <h3>You haven't placed any bids yet</h3>
                <p>Start bidding on auctions to see them here!</p>
                <a href="auctions.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Auctions</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>