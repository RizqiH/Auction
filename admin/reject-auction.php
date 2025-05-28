<?php
require_once '../config/session.php';
requireAdmin();
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Update auction status to cancelled
$query = "UPDATE auctions SET status = 'cancelled' WHERE id = :id AND status = 'pending'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_GET['id']);

if ($stmt->execute()) {
    $_SESSION['message'] = "Auction rejected successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to reject auction!";
    $_SESSION['message_type'] = "error";
}

header("Location: dashboard.php");
exit();
?>