<?php
require_once '../config/session.php';
requireAdmin();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['user_id'])) {
    header("Location: manage-users.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_POST['user_id'];

// Prevent admin from resetting their own password
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['message'] = "You cannot reset your own password from here!";
    $_SESSION['message_type'] = "error";
    header("Location: manage-users.php");
    exit();
}

// Generate temporary password
$temp_password = 'temp' . rand(1000, 9999);
$hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

// Update user password
$update_query = "UPDATE users SET password = :password WHERE id = :id";
$update_stmt = $db->prepare($update_query);
$update_stmt->bindParam(':password', $hashed_password);
$update_stmt->bindParam(':id', $user_id);

if ($update_stmt->execute()) {
    // Get username for message
    $user_query = "SELECT username FROM users WHERE id = :id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':id', $user_id);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    $_SESSION['message'] = "Password reset for user '{$user['username']}'. New temporary password: {$temp_password}";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to reset password!";
    $_SESSION['message_type'] = "error";
}

header("Location: edit-user.php?id=" . $user_id);
exit();
?>