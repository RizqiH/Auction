<?php
$page_title = "All Auctions - Admin";
require_once '../config/session.php';
requireAdmin();
require_once '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$where_conditions = [];
$params = [];

if ($status) {
    $where_conditions[] = "a.status = :status";
    $params[':status'] = $status;
}

if ($search) {
    $where_conditions[] = "(a.title LIKE :search OR u.username LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total auctions
$count_query = "SELECT COUNT(*) as total FROM auctions a 
                LEFT JOIN users u ON a.seller_id = u.id $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_auctions = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_auctions / $limit);

// Fetch auctions
$query = "SELECT a.*, c.name as category_name, u.username as seller_name,
          (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
          FROM auctions a 
          LEFT JOIN categories c ON a.category_id = c.id 
          LEFT JOIN users u ON a.seller_id = u.id 
          $where_clause
          ORDER BY a.created_at DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="container">
    <h1>All Auctions Management</h1>
    
    <!-- Filters -->
    <div class="card" style="margin: 2rem 0;">
        <div class="card-body">
            <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div class="form-group" style="margin: 0; flex: 1;">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search auctions or sellers..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
    </div>
    
    <!-- Auctions Table -->
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Seller</th>
                        <th>Category</th>
                        <th>Starting Price</th>
                        <th>Current Price</th>
                        <th>Bids</th>
                        <th>Status</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auctions as $auction): ?>
                        <tr>
                            <td><?php echo $auction['id']; ?></td>
                            <td><?php echo htmlspecialchars(substr($auction['title'], 0, 30)); ?>...</td>
                            <td><?php echo htmlspecialchars($auction['seller_name']); ?></td>
                            <td><?php echo htmlspecialchars($auction['category_name']); ?></td>
                            <td>$<?php echo number_format($auction['starting_price'], 2); ?></td>
                            <td>$<?php echo number_format($auction['current_price'] ?: $auction['starting_price'], 2); ?></td>
                            <td><?php echo $auction['bid_count']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $auction['status']; ?>">
                                    <?php echo ucfirst($auction['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($auction['end_date'])); ?></td>
                            <td>
                                <a href="../auction-detail.php?id=<?php echo $auction['id']; ?>" 
                                   class="btn btn-primary" style="padding: 0.5rem 0.75rem;">View</a>
                                
                                <?php if ($auction['status'] == 'pending'): ?>
                                    <a href="approve-auction.php?id=<?php echo $auction['id']; ?>" 
                                       class="btn btn-success" style="padding: 0.5rem 0.75rem;">Approve</a>
                                    <a href="reject-auction.php?id=<?php echo $auction['id']; ?>" 
                                       class="btn btn-danger" style="padding: 0.5rem 0.75rem;">Reject</a>
                                <?php elseif ($auction['status'] == 'active'): ?>
                                    <a href="force-end-auction.php?id=<?php echo $auction['id']; ?>" 
                                       class="btn btn-warning" style="padding: 0.5rem 0.75rem;"
                                       onclick="return confirm('Are you sure you want to force end this auction?');">End</a>
                                <?php endif; ?>
                                
                                <a href="delete-auction-admin.php?id=<?php echo $auction['id']; ?>" 
                                   class="btn btn-danger" style="padding: 0.5rem 0.75rem;"
                                   onclick="return confirm('Are you sure you want to delete this auction? This action cannot be undone.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div style="text-align: center; margin-top: 2rem;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" 
                   class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>" 
                   style="margin: 0 0.25rem;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 2rem;">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>