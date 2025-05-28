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

try {
    // Start transaction
    $db->beginTransaction();
    
    // Delete all bids for this auction first
    $delete_bids_query = "DELETE FROM bids WHERE auction_id = :auction_id";
    $delete_bids_stmt = $db->prepare($delete_bids_query);
    $delete_bids_stmt->bindParam(':auction_id', $auction_id);
    $delete_bids_stmt->execute();
    
    // Then delete the auction
    $delete_auction_query = "DELETE FROM auctions WHERE id = :auction_id";
    $delete_auction_stmt = $db->prepare($delete_auction_query);
    $delete_auction_stmt->bindParam(':auction_id', $auction_id);
    $delete_auction_stmt->execute();
    
    if ($delete_auction_stmt->rowCount() > 0) {
        // Commit transaction
        $db->commit();
        $_SESSION['message'] = "Auction deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $db->rollback();
        $_SESSION['message'] = "Auction not found!";
        $_SESSION['message_type'] = "error";
    }
} catch (Exception $e) {
    $db->rollback();
    $_SESSION['message'] = "Error deleting auction: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

header("Location: all-auctions.php");
exit();
?>