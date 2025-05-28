<?php
require_once 'config/session.php';
requireLogin();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: my-auctions.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Check if user owns this auction and it's deletable
$check_query = "SELECT * FROM auctions WHERE id = :id AND seller_id = :user_id AND status = 'pending'";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':id', $_GET['id']);
$check_stmt->bindParam(':user_id', $_SESSION['user_id']);
$check_stmt->execute();

if ($check_stmt->rowCount() > 0) {
    // Delete auction
    $delete_query = "DELETE FROM auctions WHERE id = :id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(':id', $_GET['id']);
    $delete_stmt->execute();
    
    $_SESSION['message'] = "Auction deleted successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Cannot delete this auction!";
    $_SESSION['message_type'] = "error";
}

header("Location: my-auctions.php");
exit();
?>