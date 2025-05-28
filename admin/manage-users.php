<?php
$page_title = "Manage Users - Admin";
require_once '../config/session.php';
requireAdmin();
require_once '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="container">
    <h1>Manage Users</h1>
    
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'completed' : 'active'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="edit-user.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-secondary" style="padding: 0.5rem 1rem;">Edit</a>
                                    <a href="delete-user.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-danger" style="padding: 0.5rem 1rem;"
                                       onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div style="margin-top: 2rem;">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>