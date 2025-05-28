<?php
// Detect if we're in a subdirectory (admin/ or employee/)
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_subdirectory = in_array($current_dir, ['admin', 'employee']);
$path_prefix = $is_subdirectory ? '../' : '';

require_once $path_prefix . 'config/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Auction System'; ?></title>
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo $path_prefix; ?>index.php" class="nav-brand">AuctionHub</a>
            <ul class="nav-menu">
                <li><a href="<?php echo $path_prefix; ?>index.php" class="nav-link">Home</a></li>
                <li><a href="<?php echo $path_prefix; ?>auctions.php" class="nav-link">Auctions</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo $path_prefix; ?>admin/dashboard.php" class="nav-link">Admin Panel</a></li>
                    <?php elseif (isEmployee()): ?>
                        <li><a href="<?php echo $path_prefix; ?>employee/dashboard.php" class="nav-link">Employee Panel</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $path_prefix; ?>my-auctions.php" class="nav-link">My Auctions</a></li>
                    <li><a href="<?php echo $path_prefix; ?>my-bids.php" class="nav-link">My Bids</a></li>
                    <li><a href="<?php echo $path_prefix; ?>profile.php" class="nav-link">Profile</a></li>
                    <li><a href="<?php echo $path_prefix; ?>logout.php" class="btn btn-danger">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $path_prefix; ?>login.php" class="btn btn-primary">Login</a></li>
                    <li><a href="<?php echo $path_prefix; ?>register.php" class="btn btn-secondary">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>