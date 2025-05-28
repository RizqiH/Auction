<?php
require_once 'config/session.php';
requireLogin();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['auction_id']) && isset($_POST['bid_amount'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $auction_id = $_POST['auction_id'];
    $bid_amount = $_POST['bid_amount'];
    $user_id = $_SESSION['user_id'];
    
    // Check if auction is active
    $check_query = "SELECT * FROM auctions WHERE id = :id AND status = 'active' AND end_date > NOW()";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $auction_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $auction = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if bid is higher than current price
        $min_bid = max($auction['current_price'], $auction['starting_price']);
        
        if ($bid_amount > $min_bid) {
            // Insert bid
            $bid_query = "INSERT INTO bids (auction_id, user_id, bid_amount) VALUES (:auction_id, :user_id, :bid_amount)";
            $bid_stmt = $db->prepare($bid_query);
            $bid_stmt->bindParam(':auction_id', $auction_id);
            $bid_stmt->bindParam(':user_id', $user_id);
            $bid_stmt->bindParam(':bid_amount', $bid_amount);
            
            if ($bid_stmt->execute()) {
                // Update auction current price
                $update_query = "UPDATE auctions SET current_price = :price WHERE id = :id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':price', $bid_amount);
                $update_stmt->bindParam(':id', $auction_id);
                $update_stmt->execute();
                
                $_SESSION['message'] = "Bid placed successfully!";
                $_SESSION['message_type'] = "success";
            }
        } else {
            $_SESSION['message'] = "Bid must be higher than current price!";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Auction is not active!";
        $_SESSION['message_type'] = "error";
    }
}

header("Location: auction-detail.php?id=" . $auction_id);
exit();
?>
