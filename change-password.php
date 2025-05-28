<?php
require_once 'config/session.php';
requireLogin();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current user data
    $query = "SELECT password FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_query = "UPDATE users SET password = :password WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':password', $hashed_password);
            $update_stmt->bindParam(':id', $_SESSION['user_id']);
            
            if ($update_stmt->execute()) {
                $_SESSION['message'] = "Password changed successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to change password!";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "New passwords do not match!";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Current password is incorrect!";
        $_SESSION['message_type'] = "error";
    }
}

header("Location: profile.php");
exit();
?>
