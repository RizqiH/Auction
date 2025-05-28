<?php
$page_title = "Auction Reports - Employee";
require_once '../config/session.php';
requireEmployee();
require_once '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Date filter
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Auction statistics for the period
$stats_query = "SELECT 
    COUNT(*) as total_auctions,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_auctions,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_auctions,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_auctions,
    AVG(CASE WHEN status = 'completed' THEN current_price END) as avg_sale_price,
    SUM(CASE WHEN status = 'completed' THEN current_price END) as total_sales
    FROM auctions 
    WHERE DATE(created_at) BETWEEN :start_date AND :end_date";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':start_date', $start_date);
$stats_stmt->bindParam(':end_date', $end_date);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Top categories
$cat_query = "SELECT c.name, COUNT(a.id) as auction_count, 
              SUM(CASE WHEN a.status = 'completed' THEN a.current_price ELSE 0 END) as total_sales
              FROM categories c
              LEFT JOIN auctions a ON c.id = a.category_id 
              WHERE DATE(a.created_at) BETWEEN :start_date AND :end_date
              GROUP BY c.id, c.name
              ORDER BY auction_count DESC
              LIMIT 10";

$cat_stmt = $db->prepare($cat_query);
$cat_stmt->bindParam(':start_date', $start_date);
$cat_stmt->bindParam(':end_date', $end_date);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="container">
    <h1>Auction Reports</h1>
    
    <!-- Date Filter -->
    <div class="card" style="margin: 2rem 0;">
        <div class="card-body">
            <form method="GET" action="" style="display: flex; gap: 1rem; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </form>
        </div>
    </div>
    
    <!-- Statistics Overview -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin: 2rem 0;">
        <div class="card">
            <div class="card-body">
                <h3>Total Auctions</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--primary-color);">
                    <?php echo $stats['total_auctions'] ?: 0; ?>
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Completed</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--success-color);">
                    <?php echo $stats['completed_auctions'] ?: 0; ?>
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Total Sales</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--success-color);">
                    $<?php echo number_format($stats['total_sales'] ?: 0, 2); ?>
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h3>Average Sale</h3>
                <p style="font-size: 2rem; font-weight: bold; color: var(--secondary-color);">
                    $<?php echo number_format($stats['avg_sale_price'] ?: 0, 2); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Category Performance -->
    <div class="card">
        <div class="card-header">
            <h2>Category Performance</h2>
        </div>
        <div class="card-body">
            <?php if (count($categories) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Auctions</th>
                            <th>Total Sales</th>
                            <th>Avg per Auction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo $category['auction_count']; ?></td>
                                <td>$<?php echo number_format($category['total_sales'], 2); ?></td>
                                <td>$<?php echo number_format($category['auction_count'] > 0 ? $category['total_sales'] / $category['auction_count'] : 0, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No data available for the selected period.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top: 2rem;">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>