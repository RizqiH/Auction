<?php
/* ===== EMPLOYEE DASHBOARD (employee/dashboard.php) ===== */
$page_title = "Employee Dashboard";
require_once '../config/session.php';
requireEmployee();
require_once '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch statistics relevant to employees
$stats = [];

// Active auctions count
$query = "SELECT COUNT(*) as count FROM auctions WHERE status = 'active'";
$stmt = $db->query($query);
$stats['active_auctions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending auctions count
$query = "SELECT COUNT(*) as count FROM auctions WHERE status = 'pending'";
$stmt = $db->query($query);
$stats['pending_auctions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Today's bids count
$query = "SELECT COUNT(*) as count FROM bids WHERE DATE(bid_time) = CURDATE()";
$stmt = $db->query($query);
$stats['todays_bids'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Auctions ending today
$query = "SELECT COUNT(*) as count FROM auctions WHERE DATE(end_date) = CURDATE() AND status = 'active'";
$stmt = $db->query($query);
$stats['ending_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent auctions for monitoring
$query = "SELECT a.*, u.username as seller_name, c.name as category_name,
          (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
          FROM auctions a 
          JOIN users u ON a.seller_id = u.id 
          LEFT JOIN categories c ON a.category_id = c.id 
          WHERE a.status IN ('active', 'pending') 
          ORDER BY a.created_at DESC 
          LIMIT 10";
$stmt = $db->query($query);
$recent_auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent activity - latest bids
$query = "SELECT b.*, a.title as auction_title, u.username as bidder_name
          FROM bids b
          JOIN auctions a ON b.auction_id = a.id
          JOIN users u ON b.user_id = u.id
          ORDER BY b.bid_time DESC
          LIMIT 15";
$stmt = $db->query($query);
$recent_bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="container">
    <h1>Employee Dashboard</h1>
    <p style="color: #6b7280; margin-bottom: 2rem;">Monitor auction activities and assist with customer support</p>
    
    <!-- Statistics Cards -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin: 2rem 0;">
        <div class="card">
            <div class="card-body">
                <h3>Active Auctions</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--success-color);">
                    <?php echo $stats['active_auctions']; ?>
                </p>
                <small style="color: #6b7280;">Currently running</small>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Pending Review</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--warning-color);">
                    <?php echo $stats['pending_auctions']; ?>
                </p>
                <small style="color: #6b7280;">Awaiting approval</small>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Today's Bids</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--primary-color);">
                    <?php echo $stats['todays_bids']; ?>
                </p>
                <small style="color: #6b7280;">Bids placed today</small>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Ending Today</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--danger-color);">
                    <?php echo $stats['ending_today']; ?>
                </p>
                <small style="color: #6b7280;">Auctions closing</small>
            </div>
        </div>
    </div>
    
    <!-- Two Column Layout -->
    <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Recent Auctions -->
        <div class="card">
            <div class="card-header">
                <h2>Recent Auctions</h2>
            </div>
            <div class="card-body">
                <?php if (count($recent_auctions) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Bids</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_auctions as $auction): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars(substr($auction['title'], 0, 30)); ?></strong><br>
                                        <small style="color: #6b7280;">
                                            by <?php echo htmlspecialchars($auction['seller_name']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $auction['status']; ?>">
                                            <?php echo ucfirst($auction['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $auction['bid_count']; ?></td>
                                    <td>
                                        <a href="../auction-detail.php?id=<?php echo $auction['id']; ?>" 
                                           class="btn btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.875rem;">
                                           View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No recent auctions to display.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Bidding Activity -->
        <div class="card">
            <div class="card-header">
                <h2>Recent Bids</h2>
            </div>
            <div class="card-body">
                <?php if (count($recent_bids) > 0): ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($recent_bids as $bid): ?>
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>$<?php echo number_format($bid['bid_amount'], 2); ?></strong>
                                        <br>
                                        <small style="color: #6b7280;">
                                            <?php echo htmlspecialchars($bid['bidder_name']); ?> 
                                            on <?php echo htmlspecialchars(substr($bid['auction_title'], 0, 25)); ?>...
                                        </small>
                                    </div>
                                    <small style="color: #6b7280;">
                                        <?php echo date('H:i', strtotime($bid['bid_time'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No recent bidding activity.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h2>Quick Actions</h2>
        </div>
        <div class="card-body">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="../auctions.php" class="btn btn-primary">Browse All Auctions</a>
                <a href="auction-reports.php" class="btn btn-secondary">Generate Reports</a>
                <a href="user-support.php" class="btn btn-secondary">User Support</a>
                <a href="../auctions.php?status=completed" class="btn btn-secondary">Completed Auctions</a>
                <?php if (isAdmin()): ?>
                    <a href="../admin/dashboard.php" class="btn btn-primary">Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- System Status -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h2>System Status</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <p style="color: #6b7280;">Database Status</p>
                    <p style="color: var(--success-color); font-weight: bold;">✓ Connected</p>
                </div>
                <div>
                    <p style="color: #6b7280;">Last Cron Run</p>
                    <p style="font-weight: bold;">
                        <?php
                        // This would need to be implemented to track cron job runs
                        echo "Check system logs";
                        ?>
                    </p>
                </div>
                <div>
                    <p style="color: #6b7280;">Server Time</p>
                    <p style="font-weight: bold;"><?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

