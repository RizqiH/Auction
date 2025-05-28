<?php
require_once '../config/session.php';
requireAdmin();
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: all-auctions.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$auction_id = $_GET['id'];

// Check if auction exists and is active
$check_query = "SELECT * FROM auctions WHERE id = :id AND status = 'active'";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':id', $auction_id);
$check_stmt->execute();

if ($check_stmt->rowCount() > 0) {
    // Find the highest bidder
    $bid_query = "
    SELECT user_id, bid_amount
    FROM bids
    WHERE auction_id = :auction_id AND bid_amount = (
        SELECT MAX(bid_amount) FROM bids WHERE auction_id = :auction_id
    )
    LIMIT 1
";
$bid_stmt = $db->prepare($bid_query);
$bid_stmt->bindParam(':auction_id', $auction_id);
$bid_stmt->execute();

    
    if ($bid_stmt->rowCount() > 0) {
        $winner = $bid_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update auction with winner
        $update_query = "UPDATE auctions SET status = 'completed', winner_id = :winner_id 
                        WHERE id = :auction_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':winner_id', $winner['user_id']);
        $update_stmt->bindParam(':auction_id', $auction_id);
        $update_stmt->execute();
        
        $_SESSION['message'] = "Auction ended successfully with winner!";
        $_SESSION['message_type'] = "success";
    } else {
        // No bids, just mark as completed
        $update_query = "UPDATE auctions SET status = 'completed' WHERE id = :auction_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':auction_id', $auction_id);
        $update_stmt->execute();
        
        $_SESSION['message'] = "Auction ended successfully (no bids)!";
        $_SESSION['message_type'] = "success";
    }
} else {
    $_SESSION['message'] = "Auction not found or not active!";
    $_SESSION['message_type'] = "error";
}

header("Location: all-auctions.php");
exit();
?>