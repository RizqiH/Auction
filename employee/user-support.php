<?php
/* ===== USER SUPPORT (employee/user-support.php) ===== */
$page_title = "User Support";
require_once '../config/session.php';
requireEmployee();
require_once '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$search_results = [];
$selected_user = null;
$user_auctions = [];
$user_bids = [];
$error_message = '';
$success_message = '';

// Handle user search
if (isset($_GET['search']) && !empty($_GET['search_term'])) {
    $search_term = trim($_GET['search_term']);
    
    // Search users by username, email, or full name
    $query = "SELECT id, username, email, full_name, phone, created_at, role 
              FROM users 
              WHERE (username LIKE :search OR email LIKE :search OR full_name LIKE :search)
              AND role = 'user'
              ORDER BY username ASC
              LIMIT 20";
    $stmt = $db->prepare($query);
    $search_param = '%' . $search_term . '%';
    $stmt->bindParam(':search', $search_param);
    $stmt->execute();
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle user details view
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    
    // Get user details
    $query = "SELECT * FROM users WHERE id = :user_id AND role = 'user'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_user) {
        // Get user's auctions
        $query = "SELECT a.*, c.name as category_name,
                  (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                  (SELECT MAX(bid_amount) FROM bids WHERE auction_id = a.id) as highest_bid
                  FROM auctions a 
                  LEFT JOIN categories c ON a.category_id = c.id 
                  WHERE a.seller_id = :user_id 
                  ORDER BY a.created_at DESC 
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user_auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get user's bids
        $query = "SELECT b.*, a.title as auction_title, a.status as auction_status,
                  (SELECT MAX(bid_amount) FROM bids b2 WHERE b2.auction_id = b.auction_id) as current_highest
                  FROM bids b
                  JOIN auctions a ON b.auction_id = a.id
                  WHERE b.user_id = :user_id 
                  ORDER BY b.bid_time DESC 
                  LIMIT 15";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user_bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle password reset
if (isset($_POST['reset_password']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    // Generate temporary password
    $temp_password = bin2hex(random_bytes(8));
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password = :password WHERE id = :user_id AND role = 'user'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Password reset successfully. Temporary password: <strong>" . $temp_password . "</strong> (Share this securely with the user)";
    } else {
        $error_message = "Failed to reset password.";
    }
}

// Get recent support statistics
$stats = [];

// Total users count
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$stmt = $db->query($query);
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Users who created auctions
$query = "SELECT COUNT(DISTINCT seller_id) as count FROM auctions WHERE seller_id IN (SELECT id FROM users WHERE role = 'user')";
$stmt = $db->query($query);
$stats['active_sellers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Users who placed bids
$query = "SELECT COUNT(DISTINCT user_id) as count FROM bids WHERE user_id IN (SELECT id FROM users WHERE role = 'user')";
$stmt = $db->query($query);
$stats['active_bidders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent registrations (last 7 days)
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'user' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$stmt = $db->query($query);
$stats['recent_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<link rel="stylesheet" href="../assets/css/style.css">
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1>User Support</h1>
            <p style="color: #6b7280;">Search and manage user accounts</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error" style="margin-bottom: 1rem;">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success" style="margin-bottom: 1rem;">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Support Statistics -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
        <div class="card">
            <div class="card-body">
                <h3>Total Users</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                    <?php echo $stats['total_users']; ?>
                </p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3>Active Sellers</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: var(--success-color);">
                    <?php echo $stats['active_sellers']; ?>
                </p>
                <small style="color: #6b7280;">Users with auctions</small>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3>Active Bidders</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: var(--secondary-color);">
                    <?php echo $stats['active_bidders']; ?>
                </p>
                <small style="color: #6b7280;">Users who bid</small>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3>New This Week</h3>
                <p style="font-size: 1.5rem; font-weight: bold; color: var(--warning-color);">
                    <?php echo $stats['recent_registrations']; ?>
                </p>
                <small style="color: #6b7280;">Recent registrations</small>
            </div>
        </div>
    </div>
    
    <!-- User Search -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2>Search Users</h2>
        </div>
        <div class="card-body">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                <input type="text" 
                       name="search_term" 
                       placeholder="Enter username, email, or full name..." 
                       value="<?php echo htmlspecialchars($_GET['search_term'] ?? ''); ?>"
                       style="flex: 1; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                <button type="submit" name="search" class="btn btn-primary">Search</button>
                <?php if (isset($_GET['search'])): ?>
                    <a href="user-support.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <?php if (!empty($search_results)): ?>
        <!-- Search Results -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2>Search Results (<?php echo count($search_results); ?> found)</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Phone</th>
                            <th>Member Since</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="user-support.php?user_id=<?php echo $user['id']; ?>" 
                                       class="btn btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.875rem;">
                                       View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif (isset($_GET['search'])): ?>
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-body">
                <p>No users found matching your search criteria.</p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($selected_user): ?>
        <!-- User Details -->
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2>User Details</h2>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 1rem;">
                        <strong>Username:</strong> <?php echo htmlspecialchars($selected_user['username']); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Email:</strong> <?php echo htmlspecialchars($selected_user['email']); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Full Name:</strong> <?php echo htmlspecialchars($selected_user['full_name'] ?? 'N/A'); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Phone:</strong> <?php echo htmlspecialchars($selected_user['phone'] ?? 'N/A'); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Address:</strong> <?php echo htmlspecialchars($selected_user['address'] ?? 'N/A'); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Member Since:</strong> <?php echo date('M j, Y', strtotime($selected_user['created_at'])); ?>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong>Last Updated:</strong> <?php echo date('M j, Y H:i', strtotime($selected_user['updated_at'])); ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Account Actions</h2>
                </div>
                <div class="card-body">
                    <!-- Password Reset -->
                    <form method="POST" onsubmit="return confirm('Are you sure you want to reset this user\'s password?');">
                        <input type="hidden" name="user_id" value="<?php echo $selected_user['id']; ?>">
                        <button type="submit" name="reset_password" class="btn btn-warning" style="width: 100%; margin-bottom: 1rem;">
                            Reset Password
                        </button>
                    </form>
                    
                    <!-- Quick Stats -->
                    <div style="padding: 1rem; background: #f3f4f6; border-radius: 0.375rem;">
                        <h4 style="margin-bottom: 0.5rem;">Quick Stats</h4>
                        <p style="margin: 0.25rem 0;">Auctions Created: <?php echo count($user_auctions); ?></p>
                        <p style="margin: 0.25rem 0;">Bids Placed: <?php echo count($user_bids); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User's Auctions and Bids -->
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h2>User's Auctions (<?php echo count($user_auctions); ?>)</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($user_auctions)): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($user_auctions as $auction): ?>
                                <div style="padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                                    <div>
                                        <strong><?php echo htmlspecialchars(substr($auction['title'], 0, 40)); ?></strong>
                                        <span class="badge badge-<?php echo $auction['status']; ?>" style="margin-left: 0.5rem;">
                                            <?php echo ucfirst($auction['status']); ?>
                                        </span>
                                    </div>
                                    <small style="color: #6b7280;">
                                        Starting: $<?php echo number_format($auction['starting_price'], 2); ?> • 
                                        Bids: <?php echo $auction['bid_count']; ?> • 
                                        <?php if ($auction['highest_bid']): ?>
                                            Current: $<?php echo number_format($auction['highest_bid'], 2); ?>
                                        <?php else: ?>
                                            No bids yet
                                        <?php endif; ?>
                                    </small>
                                    <br>
                                    <small style="color: #6b7280;">
                                        Created: <?php echo date('M j, Y', strtotime($auction['created_at'])); ?>
                                    </small>
                                    <div style="margin-top: 0.25rem;">
                                        <a href="../auction-detail.php?id=<?php echo $auction['id']; ?>" 
                                           class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                           View
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No auctions created by this user.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>User's Bids (<?php echo count($user_bids); ?>)</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($user_bids)): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($user_bids as $bid): ?>
                                <div style="padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>$<?php echo number_format($bid['bid_amount'], 2); ?></strong>
                                            <?php if ($bid['bid_amount'] == $bid['current_highest']): ?>
                                                <span style="color: var(--success-color); font-size: 0.75rem;">WINNING</span>
                                            <?php endif; ?>
                                            <br>
                                            <small style="color: #6b7280;">
                                                <?php echo htmlspecialchars(substr($bid['auction_title'], 0, 30)); ?>...
                                            </small>
                                        </div>
                                        <div style="text-align: right;">
                                            <small style="color: #6b7280;">
                                                <?php echo date('M j', strtotime($bid['bid_time'])); ?>
                                            </small>
                                            <br>
                                            <span class="badge badge-<?php echo $bid['auction_status']; ?>" style="font-size: 0.75rem;">
                                                <?php echo ucfirst($bid['auction_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No bids placed by this user.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.alert {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}

.alert-success {
    background-color: #d1fae5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.alert-error {
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.badge-active {
    background-color: var(--success-color);
    color: white;
}

.badge-pending {
    background-color: var(--warning-color);
    color: white;
}

.badge-completed {
    background-color: var(--primary-color);
    color: white;
}

.badge-cancelled {
    background-color: var(--danger-color);
    color: white;
}
</style>

<?php require_once '../includes/footer.php'; ?>