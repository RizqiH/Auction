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

// Prevent admin from disabling themselves
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['message'] = "You cannot disable your own account!";
    $_SESSION['message_type'] = "error";
    header("Location: manage-users.php");
    exit();
}

// For this example, we'll change the user's role to 'disabled' 
// You could also add a separate 'status' column to the users table

$disable_query = "UPDATE users SET role = 'disabled' WHERE id = :id";
$disable_stmt = $db->prepare($disable_query);
$disable_stmt->bindParam(':id', $user_id);

if ($disable_stmt->execute()) {
    // Get username for message
    $user_query = "SELECT username FROM users WHERE id = :id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':id', $user_id);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    $_SESSION['message'] = "User '{$user['username']}' has been disabled successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to disable user!";
    $_SESSION['message_type'] = "error";
}

header("Location: manage-users.php");
exit();
?>