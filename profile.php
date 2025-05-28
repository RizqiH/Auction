<?php
$page_title = "My Profile";
require_once 'config/session.php';
requireLogin();
require_once 'includes/header.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Fetch user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    // Check if email is taken by another user
    $check_query = "SELECT id FROM users WHERE email = :email AND id != :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->bindParam(':id', $_SESSION['user_id']);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $error = "Email is already taken by another user!";
    } else {
        $update_query = "UPDATE users SET full_name = :full_name, email = :email, 
                        phone = :phone, address = :address WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':full_name', $full_name);
        $update_stmt->bindParam(':email', $email);
        $update_stmt->bindParam(':phone', $phone);
        $update_stmt->bindParam(':address', $address);
        $update_stmt->bindParam(':id', $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            $success = "Profile updated successfully!";
            $_SESSION['full_name'] = $full_name;
            // Refresh user data
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to update profile!";
        }
    }
}
?>

<div class="container">
    <h1>My Profile</h1>
    
    <div class="card">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small style="color: #6b7280;">Username cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?php 
                        echo htmlspecialchars($user['address']); 
                    ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
    
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h3>Change Password</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="change-password.php">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>