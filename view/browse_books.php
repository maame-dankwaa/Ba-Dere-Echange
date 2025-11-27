<?php
// Ensure required data is available
require_once __DIR__ . '/../helpers/AuthHelper.php';

// Initialize variables if not set by controller
$books = $books ?? [];
$categories = $categories ?? [];
$filters = $filters ?? [];
$page = $page ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Books - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
    <script src="../js/book_search.js" defer></script>
    <?php include __DIR__ . '/../includes/sweetalert.php'; ?>
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
                        <!-- Logged in users: Show wishlist, profile, and list book (for vendors) -->
                        <a href="../actions/wishlist.php" class="icon-btn" aria-label="Favorites">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>

                        <a href="../view/user_account.php" class="icon-btn" aria-label="Profile">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M6 20c0-4 2.5-6 6-6s6 2 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </a>

                        <?php if (AuthHelper::canCreateListing()): ?>
                        <a href="../view/list_book.php" class="btn-primary">List a Book</a>
                        <?php else: ?>
                        <a href="../view/apply_vendor.php" class="btn-primary">Become a Vendor</a>
                        <?php endif; ?>

                        <?php if (AuthHelper::isAdmin()): ?>
                        <a href="../admin/admin_dashboard.php" class="btn-secondary">Admin</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Guest users: Show login and register -->
                        <a href="../login/login.php" class="btn-secondary">Login</a>
                        <a href="../login/register.php" class="btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="container browse-layout">
        <aside class="sidebar">
            <form id="filterForm" action="../actions/browse_books.php" method="get">
                <h3>Search</h3>
                <input type="text" name="q"
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                       placeholder="Title, author...">

                <h3>Category</h3>
                <select name="category_id">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>"
                            <?= (isset($filters['category_id']) && $filters['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <h3>Price</h3>
                <input type="number" name="min_price" step="0.01" placeholder="Min"
                       value="<?= htmlspecialchars($filters['min_price'] ?? '') ?>">
                <input type="number" name="max_price" step="0.01" placeholder="Max"
                       value="<?= htmlspecialchars($filters['max_price'] ?? '') ?>">

                <h3>Condition</h3>
                <select name="condition">
                    <option value="">Any</option>
                    <option value="like_new"  <?= ($filters['condition'] ?? '') === 'like_new' ? 'selected' : '' ?>>Like New</option>
                    <option value="good" <?= ($filters['condition'] ?? '') === 'good' ? 'selected' : '' ?>>Good</option>
                    <option value="acceptable" <?= ($filters['condition'] ?? '') === 'acceptable' ? 'selected' : '' ?>>Acceptable</option>
                    <option value="poor" <?= ($filters['condition'] ?? '') === 'poor' ? 'selected' : '' ?>>Poor</option>
                </select>

                <h3>Options</h3>
                <label>
                    <input type="checkbox" name="is_rentable" value="1"
                        <?= !empty($filters['is_rentable']) ? 'checked' : '' ?>>
                    Rentable
                </label>
                <label>
                    <input type="checkbox" name="is_exchangeable" value="1"
                        <?= !empty($filters['is_exchangeable']) ? 'checked' : '' ?>>
                    Exchangeable
                </label>

                <h3>Sort By</h3>
                <select name="sort">
                    <option value="">Most recent</option>
                    <option value="price_low"  <?= ($filters['sort'] ?? '') === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_high" <?= ($filters['sort'] ?? '') === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="rating"     <?= ($filters['sort'] ?? '') === 'rating' ? 'selected' : '' ?>>Rating</option>
                </select>

                <button type="submit" class="btn-secondary">Apply filters</button>
            </form>
        </aside>

        <section class="content">
            <header class="page-header">
                <h1>Browse Books</h1>
            </header>

            <div class="book-list">
                <?php if (empty($books)): ?>
                    <p>No books found. Try adjusting your filters.</p>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <article class="book-card" style="position: relative;">
                            <?php if ($book['is_featured'] == 1 && strtotime($book['featured_until']) > time()): ?>
                                <div style="position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75em; font-weight: 600; display: flex; align-items: center; gap: 4px; box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3);">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    FEATURED
                                </div>
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($book['title']) ?></h3>
                            <p><?= htmlspecialchars($book['author'] ?? '') ?></p>
                            <p>GH₵<?= number_format($book['price'], 2) ?></p>
                            <p>By <?= htmlspecialchars($book['seller_username'] ?? '') ?></p>
                            <a class="btn-small"
                               href="../actions/single_book.php?id=<?= $book['book_id'] ?>">View details</a>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
