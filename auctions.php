<?php
$page_title = "All Auctions";
require_once 'includes/header.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Filters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'active';

// Build query
$where_conditions = ["a.status = :status"];
$params = [':status' => $status];

if ($category) {
    $where_conditions[] = "a.category_id = :category";
    $params[':category'] = $category;
}

if ($search) {
    $where_conditions[] = "(a.title LIKE :search OR a.description LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total auctions
$count_query = "SELECT COUNT(*) as total FROM auctions a WHERE $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_auctions = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_auctions / $limit);

// Fetch auctions
$query = "SELECT a.*, c.name as category_name, u.username as seller_name 
          FROM auctions a 
          LEFT JOIN categories c ON a.category_id = c.id 
          LEFT JOIN users u ON a.seller_id = u.id 
          WHERE $where_clause 
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

// Fetch categories for filter
$cat_query = "SELECT * FROM categories ORDER BY name";
$cat_stmt = $db->query($cat_query);
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h1>All Auctions</h1>
    
    <!-- Filters -->
    <div class="card" style="margin: 2rem 0;">
        <div class="card-body">
            <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div class="form-group" style="margin: 0; flex: 1;">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search auctions..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>
    
    <!-- Auctions Grid -->
    <div class="grid grid-3">
        <?php foreach ($auctions as $auction): ?>
            <div class="card">
                <img src="<?php echo $auction['image_url'] ?: 'assets/images/placeholder.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($auction['title']); ?>" 
                     class="card-image">
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($auction['title']); ?></h3>
                    <p style="color: #6b7280; margin: 0.5rem 0;">
                        <?php echo htmlspecialchars($auction['category_name']); ?>
                    </p>
                    <p style="margin: 1rem 0;">
                        <?php echo substr(htmlspecialchars($auction['description']), 0, 100); ?>...
                    </p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <p style="color: #6b7280; font-size: 0.875rem;">Current Price</p>
                            <p style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                $<?php echo number_format($auction['current_price'] ?: $auction['starting_price'], 2); ?>
                            </p>
                        </div>
                        <a href="auction-detail.php?id=<?php echo $auction['id']; ?>" 
                           class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div style="text-align: center; margin-top: 3rem;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" 
                   class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>" 
                   style="margin: 0 0.25rem;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>