<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

// Check authentication
if (!AuthHelper::isLoggedIn()) {
    $_SESSION['flash_error'] = 'Please login to view analytics.';
    header('Location: ../login/login.php');
    exit;
}

if (!AuthHelper::canCreateListing()) {
    $_SESSION['flash_error'] = 'You do not have permission to view analytics.';
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$userId = (int)($_SESSION['user_id'] ?? 0);

// Get overview stats
$sql1 = "SELECT COUNT(*) AS total_transactions,
                SUM(total_amount) AS total_revenue,
                SUM(seller_amount) AS total_earnings,
                SUM(commission_amount) AS total_commission
         FROM fp_transactions
         WHERE seller_id = :id AND payment_status = 'completed'";
$revenue = $db->fetch($sql1, ['id' => $userId]);

$sql2 = "SELECT COUNT(*) AS total_books,
                SUM(views_count) AS total_views,
                SUM(available_quantity) AS total_available
         FROM fp_books
         WHERE seller_id = :id";
$books = $db->fetch($sql2, ['id' => $userId]);

$sql3 = "SELECT COUNT(*) AS active_books FROM fp_books WHERE seller_id = :id AND status = 'active'";
$active = $db->fetch($sql3, ['id' => $userId]);

$overview = [
    'total_transactions' => (int)($revenue['total_transactions'] ?? 0),
    'total_revenue' => (float)($revenue['total_revenue'] ?? 0),
    'total_earnings' => (float)($revenue['total_earnings'] ?? 0),
    'total_commission' => (float)($revenue['total_commission'] ?? 0),
    'total_books' => (int)($books['total_books'] ?? 0),
    'active_books' => (int)($active['active_books'] ?? 0),
    'total_views' => (int)($books['total_views'] ?? 0),
    'total_available' => (int)($books['total_available'] ?? 0),
];

// Get sales trend (last 30 days)
$sql4 = "SELECT DATE(created_at) AS sale_date,
               COUNT(*) AS total_sales,
               SUM(total_amount) AS revenue
        FROM fp_transactions
        WHERE seller_id = :id
          AND payment_status = 'completed'
          AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY sale_date ASC";
$salesTrend = $db->fetchAll($sql4, ['id' => $userId]);

// Get top performing books
$sql5 = "SELECT b.book_id, b.title, b.cover_image, b.price,
               COUNT(t.transaction_id) AS total_sales,
               SUM(t.quantity) AS units_sold,
               SUM(t.seller_amount) AS earnings
        FROM fp_books b
        LEFT JOIN fp_transactions t ON b.book_id = t.book_id AND t.payment_status = 'completed'
        WHERE b.seller_id = :id
        GROUP BY b.book_id
        ORDER BY total_sales DESC, units_sold DESC
        LIMIT 5";
$topBooks = $db->fetchAll($sql5, ['id' => $userId]);

// Get recent transactions
$sql6 = "SELECT t.*, b.title AS book_title, u.username AS buyer_username
        FROM fp_transactions t
        INNER JOIN fp_books b ON t.book_id = b.book_id
        INNER JOIN fp_users u ON t.buyer_id = u.user_id
        WHERE t.seller_id = :id
        ORDER BY t.created_at DESC
        LIMIT 10";
$recentTransactions = $db->fetchAll($sql6, ['id' => $userId]);

// Get category breakdown
$sql7 = "SELECT c.name AS category_name,
               COUNT(b.book_id) AS book_count,
               SUM(b.views_count) AS total_views,
               COUNT(t.transaction_id) AS total_sales
        FROM fp_books b
        INNER JOIN fp_categories c ON b.category_id = c.category_id
        LEFT JOIN fp_transactions t ON b.book_id = t.book_id AND t.payment_status = 'completed'
        WHERE b.seller_id = :id
        GROUP BY c.category_id
        ORDER BY total_sales DESC, book_count DESC";
$categoryBreakdown = $db->fetchAll($sql7, ['id' => $userId]);

$flash = ['success' => $_SESSION['flash_success'] ?? null, 'error' => $_SESSION['flash_error'] ?? null];
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <a href="../index.php" class="logo">
                    <svg class="logo-icon" width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect x="4" y="8" width="24" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
                        <path d="M10 12h12M10 16h8M10 20h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <div class="logo-text">
                        <h1>Ba Dere Exchange</h1>
                        <p>Come and bring</p>
                    </div>
                </a>
                <div class="nav-actions">
                    <form class="search-bar" action="../actions/search.php" method="get">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="2"/>
                            <path d="M14 14l4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input type="text" name="q" placeholder="Search for books..." required>
                        <button type="submit" style="display: none;">Search</button>
                    </form>

                    <?php if (AuthHelper::isLoggedIn()): ?>
                        <a href="../actions/cart.php" class="icon-btn" aria-label="Cart">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="9" cy="21" r="1" fill="currentColor"/>
                                <circle cx="20" cy="21" r="1" fill="currentColor"/>
                                <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>

                        <?php if (AuthHelper::canWishlist()): ?>
                        <a href="../view/wishlist.php" class="icon-btn" aria-label="Favorites">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>
                        <?php endif; ?>

                        <a href="../view/user_account.php" class="icon-btn" aria-label="Profile">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M6 20c0-4 2.5-6 6-6s6 2 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </a>

                        <?php if (AuthHelper::canCreateListing()): ?>
                        <a href="../view/list_book.php" class="btn-primary">List a Book</a>
                        <?php endif; ?>

                        <?php if (AuthHelper::isAdmin()): ?>
                        <a href="../admin/admin_dashboard.php" class="btn-secondary">Admin</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="../login/login.php" class="btn-secondary">Login</a>
                        <a href="../login/register.php" class="btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="analytics-page">
            <div class="page-header">
                <h1>Analytics Dashboard</h1>
                <div class="header-actions">
                    <a href="../view/manage_listings.php" class="btn-secondary">Manage Listings</a>
                </div>
            </div>

            <?php if ($flash['success']): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
            <?php endif; ?>

            <?php if ($flash['error']): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>

            <!-- Overview Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e8f5e9;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#388e3c" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3>GH₵<?= number_format($overview['total_earnings'], 2) ?></h3>
                        <p>Total Earnings</p>
                        <small>Revenue: GH₵<?= number_format($overview['total_revenue'], 2) ?></small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #e3f2fd;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1976d2" stroke-width="2">
                            <circle cx="9" cy="21" r="1" fill="#1976d2"/>
                            <circle cx="20" cy="21" r="1" fill="#1976d2"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($overview['total_transactions']) ?></h3>
                        <p>Total Sales</p>
                        <small>Completed transactions</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2">
                            <rect x="4" y="8" width="16" height="12" rx="2"/>
                            <path d="M8 4h8" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($overview['active_books']) ?></h3>
                        <p>Active Listings</p>
                        <small>Out of <?= number_format($overview['total_books']) ?> total</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fce4ec;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c2185b" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($overview['total_views']) ?></h3>
                        <p>Total Views</p>
                        <small>Across all listings</small>
                    </div>
                </div>
            </div>

            <!-- Top Performing Books -->
            <div class="analytics-section">
                <h2>Top Performing Books</h2>
                <?php if (empty($topBooks)): ?>
                    <div class="empty-message">
                        <p>No sales data available yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Price</th>
                                    <th>Units Sold</th>
                                    <th>Total Sales</th>
                                    <th>Earnings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topBooks as $book): ?>
                                    <tr>
                                        <td>
                                            <div class="book-cell">
                                                <?php if (!empty($book['cover_image'])): ?>
                                                    <img src="../<?= htmlspecialchars($book['cover_image']) ?>"
                                                         alt="<?= htmlspecialchars($book['title']) ?>"
                                                         class="book-thumbnail">
                                                <?php endif; ?>
                                                <strong><?= htmlspecialchars($book['title']) ?></strong>
                                            </div>
                                        </td>
                                        <td>GH₵<?= number_format($book['price'], 2) ?></td>
                                        <td><?= (int)($book['units_sold'] ?? 0) ?></td>
                                        <td><?= (int)($book['total_sales'] ?? 0) ?></td>
                                        <td>GH₵<?= number_format($book['earnings'] ?? 0, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Category Breakdown -->
            <div class="analytics-section">
                <h2>Category Performance</h2>
                <?php if (empty($categoryBreakdown)): ?>
                    <div class="empty-message">
                        <p>No category data available</p>
                    </div>
                <?php else: ?>
                    <div class="category-grid">
                        <?php foreach ($categoryBreakdown as $category): ?>
                            <div class="category-card">
                                <h3><?= htmlspecialchars($category['category_name']) ?></h3>
                                <div class="category-stats">
                                    <div class="category-stat">
                                        <span class="stat-value"><?= (int)$category['book_count'] ?></span>
                                        <span class="stat-label">Books</span>
                                    </div>
                                    <div class="category-stat">
                                        <span class="stat-value"><?= (int)$category['total_views'] ?></span>
                                        <span class="stat-label">Views</span>
                                    </div>
                                    <div class="category-stat">
                                        <span class="stat-value"><?= (int)$category['total_sales'] ?></span>
                                        <span class="stat-label">Sales</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Transactions -->
            <div class="analytics-section">
                <h2>Recent Transactions</h2>
                <?php if (empty($recentTransactions)): ?>
                    <div class="empty-message">
                        <p>No recent transactions</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction Code</th>
                                    <th>Book</th>
                                    <th>Buyer</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $trans): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($trans['created_at'])) ?></td>
                                        <td><code><?= htmlspecialchars($trans['transaction_code']) ?></code></td>
                                        <td><?= htmlspecialchars($trans['book_title']) ?></td>
                                        <td><?= htmlspecialchars($trans['buyer_username']) ?></td>
                                        <td><span class="type-badge type-<?= strtolower($trans['transaction_type']) ?>"><?= ucfirst($trans['transaction_type']) ?></span></td>
                                        <td>GH₵<?= number_format($trans['seller_amount'], 2) ?></td>
                                        <td><span class="status-badge status-<?= strtolower($trans['payment_status']) ?>"><?= ucfirst($trans['payment_status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sales Trend Chart (Simple Text Display) -->
            <?php if (!empty($salesTrend)): ?>
                <div class="analytics-section">
                    <h2>Sales Trend (Last 30 Days)</h2>
                    <div class="trend-container">
                        <?php
                        $maxRevenue = max(array_column($salesTrend, 'revenue'));
                        foreach ($salesTrend as $day):
                            $percentage = $maxRevenue > 0 ? ($day['revenue'] / $maxRevenue) * 100 : 0;
                        ?>
                            <div class="trend-item">
                                <div class="trend-date"><?= date('M d', strtotime($day['sale_date'])) ?></div>
                                <div class="trend-bar-container">
                                    <div class="trend-bar" style="width: <?= $percentage ?>%;"></div>
                                </div>
                                <div class="trend-value">GH₵<?= number_format($day['revenue'], 2) ?> (<?= (int)$day['total_sales'] ?> sales)</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
