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

// Update auction status to active
$query = "UPDATE auctions SET status = 'active' WHERE id = :id AND status = 'pending'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_GET['id']);

if ($stmt->execute()) {
    $_SESSION['message'] = "Auction approved successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to approve auction!";
    $_SESSION['message_type'] = "error";
}

header("Location: dashboard.php");
exit();
?>