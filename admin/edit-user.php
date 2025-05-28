<?php
$page_title = "Edit User - Admin";
require_once '../config/session.php';
requireAdmin();
require_once '../includes/header.php';
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: manage-users.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_GET['id'];
$error = '';
$success = '';

// Prevent admin from editing themselves
if ($user_id == $_SESSION['user_id']) {
    header("Location: manage-users.php");
    exit();
}

// Fetch user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: manage-users.php");
    exit();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    // Check if username or email is taken by another user
    $check_query = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':username', $username);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->bindParam(':id', $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $error = "Username or email is already taken by another user!";
    } else {
        $update_query = "UPDATE users SET username = :username, email = :email, full_name = :full_name, 
                        role = :role, phone = :phone, address = :address WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':username', $username);
        $update_stmt->bindParam(':email', $email);
        $update_stmt->bindParam(':full_name', $full_name);
        $update_stmt->bindParam(':role', $role);
        $update_stmt->bindParam(':phone', $phone);
        $update_stmt->bindParam(':address', $address);
        $update_stmt->bindParam(':id', $user_id);
        
        if ($update_stmt->execute()) {
            $success = "User updated successfully!";
            // Refresh user data
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to update user!";
        }
    }
}

// Get user statistics
$stats_query = "SELECT 
                (SELECT COUNT(*) FROM auctions WHERE seller_id = :id) as auction_count,
                (SELECT COUNT(*) FROM bids WHERE user_id = :id) as bid_count
                ";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':id', $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1>Edit User</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <link rel="stylesheet" href="../assets/css/style.css">
    <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Edit Form -->
        <div class="card">
            <div class="card-header">
                <h3>User Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control" required>
                            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="employee" <?php echo $user['role'] == 'employee' ? 'selected' : ''; ?>>Employee</option>
                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
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
                    
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="manage-users.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
        
        <!-- User Statistics -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3>User Statistics</h3>
                </div>
                <div class="card-body">
                    <p><strong>User ID:</strong> <?php echo $user['id']; ?></p>
                    <p><strong>Member Since:</strong> <?php echo date('Y-m-d', strtotime($user['created_at'])); ?></p>
                    <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i', strtotime($user['updated_at'])); ?></p>
                    <hr>
                    <p><strong>Auctions Created:</strong> <?php echo $stats['auction_count']; ?></p>
                    <p><strong>Bids Placed:</strong> <?php echo $stats['bid_count']; ?></p>
                </div>
            </div>
            
            <div class="card" style="margin-top: 1rem;">
                <div class="card-header">
                    <h3>Reset Password</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="reset-user-password.php">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <p style="color: #6b7280; margin-bottom: 1rem;">
                            Reset user's password to a temporary password. User will need to change it on next login.
                        </p>
                        <button type="submit" class="btn btn-warning" 
                                onclick="return confirm('Are you sure you want to reset this user\'s password?');">
                            Reset Password
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if ($stats['auction_count'] == 0 && $stats['bid_count'] == 0): ?>
                <div class="card" style="margin-top: 1rem;">
                    <div class="card-header">
                        <h3>Delete User</h3>
                    </div>
                    <div class="card-body">
                        <p style="color: var(--danger-color); margin-bottom: 1rem;">
                            This user has no auctions or bids and can be safely deleted.
                        </p>
                        <a href="delete-user.php?id=<?php echo $user['id']; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                            Delete User
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card" style="margin-top: 1rem;">
                    <div class="card-body">
                        <p style="color: #6b7280;">
                            <strong>Note:</strong> This user cannot be deleted because they have auction or bid history.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
