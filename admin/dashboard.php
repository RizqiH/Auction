<?php
$page_title = "Admin Dashboard";
require_once '../config/session.php';
requireAdmin();
require_once '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch statistics
$stats = [];

// Total users
$query = "SELECT COUNT(*) as count FROM users";
$stmt = $db->query($query);
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total auctions
$query = "SELECT COUNT(*) as count FROM auctions";
$stmt = $db->query($query);
$stats['total_auctions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Active auctions
$query = "SELECT COUNT(*) as count FROM auctions WHERE status = 'active'";
$stmt = $db->query($query);
$stats['active_auctions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total bids
$query = "SELECT COUNT(*) as count FROM bids";
$stmt = $db->query($query);
$stats['total_bids'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending auctions
$query = "SELECT a.*, u.username as seller_name 
          FROM auctions a 
          JOIN users u ON a.seller_id = u.id 
          WHERE a.status = 'pending' 
          ORDER BY a.created_at DESC";
$stmt = $db->query($query);
$pending_auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="../assets/css/style.css">

<div class="container">
    <h1>Admin Dashboard</h1>
    
    <div class="grid grid-3" style="margin: 2rem 0;">
        <div class="card">
            <div class="card-body">
                <h3>Total Users</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--primary-color);">
                    <?php echo $stats['total_users']; ?>
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Active Auctions</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--success-color);">
                    <?php echo $stats['active_auctions']; ?>
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Total Bids</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--secondary-color);">
                    <?php echo $stats['total_bids']; ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Pending Auctions</h2>
        </div>
        <div class="card-body">
            <?php if (count($pending_auctions) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Seller</th>
                            <th>Starting Price</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_auctions as $auction): ?>
                            <tr>
                                <td><?php echo $auction['id']; ?></td>
                                <td><?php echo htmlspecialchars($auction['title']); ?></td>
                                <td><?php echo htmlspecialchars($auction['seller_name']); ?></td>
                                <td>$<?php echo number_format($auction['starting_price'], 2); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($auction['created_at'])); ?></td>
                                <td>
                                    <a href="approve-auction.php?id=<?php echo $auction['id']; ?>" 
                                       class="btn btn-success" style="padding: 0.5rem 1rem;">Approve</a>
                                    <a href="reject-auction.php?id=<?php echo $auction['id']; ?>" 
                                       class="btn btn-danger" style="padding: 0.5rem 1rem;">Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pending auctions at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top: 2rem;">
        <a href="manage-users.php" class="btn btn-primary">Manage Users</a>
        <a href="manage-categories.php" class="btn btn-secondary">Manage Categories</a>
        <a href="all-auctions.php" class="btn btn-primary">All Auctions</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>