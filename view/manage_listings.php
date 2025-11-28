<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

// Check authentication
if (!AuthHelper::isLoggedIn()) {
    $_SESSION['flash_error'] = 'Please login to manage listings.';
    header('Location: ../login/login.php');
    exit;
}

if (!AuthHelper::canCreateListing()) {
    $_SESSION['flash_error'] = 'You do not have permission to manage listings.';
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();
$userId = (int)($_SESSION['user_id'] ?? 0);

// Get all books listed by this vendor
$sql = "SELECT b.*, c.name AS category_name,
               COUNT(DISTINCT t.transaction_id) AS total_sales,
               SUM(CASE WHEN t.payment_status = 'completed' THEN t.quantity ELSE 0 END) AS sold_quantity
        FROM fp_books b
        LEFT JOIN fp_categories c ON b.category_id = c.category_id
        LEFT JOIN fp_transactions t ON b.book_id = t.book_id AND t.payment_status = 'completed'
        WHERE b.seller_id = :seller_id
        GROUP BY b.book_id
        ORDER BY b.created_at DESC";
$listings = $db->fetchAll($sql, ['seller_id' => $userId]);

// Get statistics
$sql1 = "SELECT COUNT(*) AS total FROM fp_books WHERE seller_id = :id AND status = 'active'";
$activeListings = $db->fetch($sql1, ['id' => $userId]);

$sql2 = "SELECT SUM(views_count) AS total_views FROM fp_books WHERE seller_id = :id";
$viewsData = $db->fetch($sql2, ['id' => $userId]);

$sql3 = "SELECT COUNT(*) AS total_sold,
                SUM(total_amount) AS total_revenue,
                SUM(seller_amount) AS total_earnings
         FROM fp_transactions
         WHERE seller_id = :id AND payment_status = 'completed'";
$salesData = $db->fetch($sql3, ['id' => $userId]);

$stats = [
    'active_listings' => (int)($activeListings['total'] ?? 0),
    'total_views' => (int)($viewsData['total_views'] ?? 0),
    'total_sold' => (int)($salesData['total_sold'] ?? 0),
    'total_revenue' => (float)($salesData['total_revenue'] ?? 0),
    'total_earnings' => (float)($salesData['total_earnings'] ?? 0),
];

// Check if featured listing was activated
if (isset($_GET['featured_success']) && $_GET['featured_success'] == 1) {
    $_SESSION['flash_success'] = 'Your listing has been featured successfully! It will now appear at the top of search results.';
}

$flash = ['success' => $_SESSION['flash_success'] ?? null, 'error' => $_SESSION['flash_error'] ?? null];
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Listings - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
    <?php include __DIR__ . '/../includes/sweetalert.php'; ?>
</head><body>
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
        <div class="manage-listings-page">
            <div class="page-header">
                <h1>Manage Listings</h1>
                <div class="header-actions">
                    <a href="../view/analytics.php" class="btn-secondary">View Analytics</a>
                    <a href="../view/list_book.php" class="btn-primary">Add New Listing</a>
                </div>
            </div>

            <?php if ($flash['success']): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
            <?php endif; ?>

            <?php if ($flash['error']): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e3f2fd;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1976d2" stroke-width="2">
                            <rect x="4" y="8" width="16" height="12" rx="2"/>
                            <path d="M8 4h8M8 12h8" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['active_listings']) ?></h3>
                        <p>Active Listings</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['total_views']) ?></h3>
                        <p>Total Views</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #e8f5e9;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#388e3c" stroke-width="2">
                            <circle cx="9" cy="21" r="1" fill="#388e3c"/>
                            <circle cx="20" cy="21" r="1" fill="#388e3c"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['total_sold']) ?></h3>
                        <p>Books Sold</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #f3e5f5;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7b1fa2" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3>GH₵<?= number_format($stats['total_earnings'], 2) ?></h3>
                        <p>Total Earnings</p>
                    </div>
                </div>
            </div>

            <!-- Listings Table -->
            <div class="listings-section">
                <h2>Your Listings</h2>

                <?php if (empty($listings)): ?>
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="4" y="8" width="16" height="12" rx="2"/>
                            <path d="M8 4h8" stroke-linecap="round"/>
                        </svg>
                        <h3>No listings yet</h3>
                        <p>Start selling by creating your first book listing</p>
                        <a href="../view/list_book.php" class="btn-primary" style="margin-top: 20px;">Create Listing</a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="listings-table">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Available</th>
                                    <th>Sold</th>
                                    <th>Views</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listings as $listing): ?>
                                    <tr>
                                        <td>
                                            <div class="book-cell">
                                                <?php if (!empty($listing['cover_image'])): ?>
                                                    <img src="../<?= htmlspecialchars($listing['cover_image']) ?>"
                                                         alt="<?= htmlspecialchars($listing['title']) ?>"
                                                         class="book-thumbnail">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($listing['title']) ?></strong>
                                                    <br>
                                                    <small><?= htmlspecialchars($listing['author']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($listing['category_name']) ?></td>
                                        <td>GH₵<?= number_format($listing['price'], 2) ?></td>
                                        <td><?= (int)$listing['available_quantity'] ?></td>
                                        <td><?= (int)($listing['sold_quantity'] ?? 0) ?></td>
                                        <td><?= (int)$listing['views_count'] ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($listing['status']) ?>">
                                                <?= ucfirst($listing['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="../actions/single_book.php?id=<?= $listing['book_id'] ?>"
                                                   class="btn-sm btn-view" title="View">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                        <circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                </a>
                                                <a href="edit_book.php?id=<?= $listing['book_id'] ?>"
                                                   class="btn-sm btn-edit" title="Edit">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                    </svg>
                                                </a>
                                                <?php if ($listing['is_featured'] == 1 && strtotime($listing['featured_until']) > time()): ?>
                                                    <span class="btn-sm" style="background: #fbbf24; color: #78350f; cursor: default;" title="Featured until <?= date('M d, Y', strtotime($listing['featured_until'])) ?>">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                        </svg>
                                                    </span>
                                                <?php else: ?>
                                                    <a href="feature_listing.php?book_id=<?= $listing['book_id'] ?>"
                                                       class="btn-sm" style="background: #10b981; color: white;" title="Feature This Listing">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                        </svg>
                                                    </a>
                                                <?php endif; ?>
                                                <form method="POST" action="../actions/toggle_listing_status.php" style="display: inline;">
                                                    <input type="hidden" name="book_id" value="<?= $listing['book_id'] ?>">
                                                    <button type="submit" class="btn-sm btn-toggle" title="Toggle Status">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <path d="M8 12h8"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                                <form method="POST" action="../actions/delete_listing.php"
                                                      onsubmit="return confirm('Are you sure you want to delete this listing?');"
                                                      style="display: inline;">
                                                    <input type="hidden" name="book_id" value="<?= $listing['book_id'] ?>">
                                                    <button type="submit" class="btn-sm btn-delete" title="Delete">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polyline points="3 6 5 6 21 6"/>
                                                            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
