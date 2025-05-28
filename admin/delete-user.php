<?php
require_once '../config/session.php';
requireAdmin();
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: manage-users.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_GET['id'];
$force_delete = isset($_GET['force']) && $_GET['force'] == 'true';

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['message'] = "You cannot delete your own account!";
    $_SESSION['message_type'] = "error";
    header("Location: manage-users.php");
    exit();
}

// Check if user exists
$check_query = "SELECT username, role FROM users WHERE id = :id";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':id', $user_id);
$check_stmt->execute();

if ($check_stmt->rowCount() == 0) {
    $_SESSION['message'] = "User not found!";
    $_SESSION['message_type'] = "error";
    header("Location: manage-users.php");
    exit();
}

$user = $check_stmt->fetch(PDO::FETCH_ASSOC);

// Check if user has auctions or bids
$activity_query = "SELECT 
                   (SELECT COUNT(*) FROM auctions WHERE seller_id = :id) as auction_count,
                   (SELECT COUNT(*) FROM bids WHERE user_id = :id) as bid_count,
                   (SELECT COUNT(*) FROM auctions WHERE winner_id = :id) as won_auction_count";
$activity_stmt = $db->prepare($activity_query);
$activity_stmt->bindParam(':id', $user_id);
$activity_stmt->execute();
$activity = $activity_stmt->fetch(PDO::FETCH_ASSOC);

$has_activity = ($activity['auction_count'] > 0 || $activity['bid_count'] > 0 || $activity['won_auction_count'] > 0);

if ($has_activity && !$force_delete) {
    // Show confirmation page for users with activity
    $page_title = "Delete User - Confirmation";
    require_once '../includes/header.php';
    ?>
    <link rel="stylesheet" href="../assets/css/style.css">
    <div class="container">
        <div class="card">
            <div class="card-header" style="background: var(--danger-color); color: white;">
                <h2>⚠️ Confirm User Deletion</h2>
            </div>
            <div class="card-body">
                <h3>Delete User: <?php echo htmlspecialchars($user['username']); ?></h3>
                
                <div class="alert alert-error">
                    <strong>Warning!</strong> This user has activity in the system:
                </div>
                
                <ul style="margin: 1rem 0;">
                    <?php if ($activity['auction_count'] > 0): ?>
                        <li><strong><?php echo $activity['auction_count']; ?></strong> auction(s) created</li>
                    <?php endif; ?>
                    <?php if ($activity['bid_count'] > 0): ?>
                        <li><strong><?php echo $activity['bid_count']; ?></strong> bid(s) placed</li>
                    <?php endif; ?>
                    <?php if ($activity['won_auction_count'] > 0): ?>
                        <li><strong><?php echo $activity['won_auction_count']; ?></strong> auction(s) won</li>
                    <?php endif; ?>
                </ul>
                
                <p><strong>What will happen if you proceed:</strong></p>
                <ul style="margin: 1rem 0; color: var(--danger-color);">
                    <li>All auctions created by this user will be <strong>cancelled</strong></li>
                    <li>All bids placed by this user will be <strong>removed</strong></li>
                    <li>Won auctions will have their winner status <strong>cleared</strong></li>
                    <li>The user account will be <strong>permanently deleted</strong></li>
                </ul>
                
                <div style="margin-top: 2rem;">
                    <a href="delete-user.php?id=<?php echo $user_id; ?>&force=true" 
                       class="btn btn-danger"
                       onclick="return confirm('ARE YOU SURE? This will permanently delete the user and modify their related data. This action CANNOT be undone!');">
                        ⚠️ Yes, Delete User & Clean Data
                    </a>
                    <a href="manage-users.php" class="btn btn-secondary">Cancel</a>
                </div>
                
                <hr style="margin: 2rem 0;">
                
                <h4>Alternative Actions:</h4>
                <p>Instead of deleting, you could:</p>
                <div style="margin-top: 1rem;">
                    <a href="edit-user.php?id=<?php echo $user_id; ?>" class="btn btn-primary">Edit User Instead</a>
                    <a href="disable-user.php?id=<?php echo $user_id; ?>" class="btn btn-warning">Disable Account</a>
                </div>
            </div>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; ?>
    <?php
    exit();
}

// Proceed with deletion
try {
    // Start transaction for safe deletion
    $db->beginTransaction();
    
    if ($has_activity) {
        // Clean up related data first
        
        // 1. Cancel all auctions by this user
        $cancel_auctions_query = "UPDATE auctions SET status = 'cancelled' WHERE seller_id = :id";
        $cancel_auctions_stmt = $db->prepare($cancel_auctions_query);
        $cancel_auctions_stmt->bindParam(':id', $user_id);
        $cancel_auctions_stmt->execute();
        
        // 2. Remove winner status from auctions they won
        $clear_wins_query = "UPDATE auctions SET winner_id = NULL WHERE winner_id = :id";
        $clear_wins_stmt = $db->prepare($clear_wins_query);
        $clear_wins_stmt->bindParam(':id', $user_id);
        $clear_wins_stmt->execute();
        
        // 3. Delete all bids by this user
        $delete_bids_query = "DELETE FROM bids WHERE user_id = :id";
        $delete_bids_stmt = $db->prepare($delete_bids_query);
        $delete_bids_stmt->bindParam(':id', $user_id);
        $delete_bids_stmt->execute();
        
        // 4. Update current prices for affected auctions
        $update_prices_query = "UPDATE auctions a SET current_price = (
            SELECT COALESCE(MAX(b.bid_amount), a.starting_price) 
            FROM bids b 
            WHERE b.auction_id = a.id
        ) WHERE a.seller_id = :id OR a.id IN (
            SELECT DISTINCT auction_id FROM bids WHERE user_id = :id
        )";
        $update_prices_stmt = $db->prepare($update_prices_query);
        $update_prices_stmt->bindParam(':id', $user_id);
        $update_prices_stmt->execute();
    }
    
    // 5. Finally delete the user
    $delete_user_query = "DELETE FROM users WHERE id = :id";
    $delete_user_stmt = $db->prepare($delete_user_query);
    $delete_user_stmt->bindParam(':id', $user_id);
    $delete_user_stmt->execute();
    
    // Commit transaction
    $db->commit();
    
    if ($has_activity) {
        $_SESSION['message'] = "User '{$user['username']}' and all related data deleted successfully! {$activity['auction_count']} auctions cancelled, {$activity['bid_count']} bids removed.";
    } else {
        $_SESSION['message'] = "User '{$user['username']}' deleted successfully!";
    }
    $_SESSION['message_type'] = "success";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollback();
    $_SESSION['message'] = "Failed to delete user: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

header("Location: manage-users.php");
exit();
?>