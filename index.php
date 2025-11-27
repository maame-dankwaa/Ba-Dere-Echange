<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/controllers/HomeController.php';
    require_once __DIR__ . '/helpers/AuthHelper.php';

    $controller = new HomeController();
    $data = $controller->index();

    $featuredBooks = $data['featuredBooks'];
    $trendingBooks = $data['trendingBooks'];
    $recentBooks   = $data['recentBooks'];
    $categories    = $data['categories'];
    $totalBooks    = $data['totalBooks'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/app.js" defer></script>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <a href="index.php" class="logo">
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
                    <form class="search-bar" action="actions/search.php" method="get">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="2"/>
                            <path d="M14 14l4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input type="text" name="q" placeholder="Search for books..." required>
                        <button type="submit" style="display: none;">Search</button>
                    </form>

                    <?php if (AuthHelper::isLoggedIn()): ?>
                        <!-- Logged in users: Show cart, wishlist, profile, and list book (for vendors) -->
                        <a href="actions/cart.php" class="icon-btn" aria-label="Cart">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="9" cy="21" r="1" fill="currentColor"/>
                                <circle cx="20" cy="21" r="1" fill="currentColor"/>
                                <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>

                        <?php if (AuthHelper::canWishlist()): ?>
                        <a href="view/wishlist.php" class="icon-btn" aria-label="Favorites">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </a>
                        <?php endif; ?>

                        <a href="view/user_account.php" class="icon-btn" aria-label="Profile">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                <path d="M6 20c0-4 2.5-6 6-6s6 2 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </a>

                        <?php if (AuthHelper::canCreateListing()): ?>
                        <a href="view/list_book.php" class="btn-primary">List a Book</a>
                        <?php endif; ?>

                        <?php if (AuthHelper::isAdmin()): ?>
                        <a href="admin/admin_dashboard.php" class="btn-secondary">Admin</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Guest users: Show login and register -->
                        <a href="login/login.php" class="btn-secondary">Login</a>
                        <a href="login/register.php" class="btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Share what you have, get what you need</h1>
                <p class="hero-description">
                    Ba Dɛre Exchange connects students and book lovers to buy, sell, rent, or
                    exchange academic and non-academic books affordably.
                </p>
                <div class="hero-stats">
                    <div class="stat">
                        <h2 class="stat-number"><?= number_format($totalBooks) ?></h2>
                        <p class="stat-label">Books Listed</p>
                    </div>
                    <div class="stat">
                        <h2 class="stat-number"><?= count($categories) ?>+</h2>
                        <p class="stat-label">Categories</p>
                    </div>
                </div>
                <div class="hero-actions">
                    <?php if (AuthHelper::isLoggedIn()): ?>
                        <a href="actions/browse_books.php" class="btn-primary btn-large">Browse Books</a>
                        <?php if (AuthHelper::canCreateListing()): ?>
                        <a href="view/list_book.php" class="btn-secondary btn-large">List a Book</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login/register.php" class="btn-primary btn-large">Get Started</a>
                        <a href="actions/browse_books.php" class="btn-secondary btn-large">Browse Books</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <main class="container">
        <section>
            <h2>Featured Books</h2>
            <div class="book-list">
                <?php foreach ($featuredBooks as $book): ?>
                    <div class="book-card">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?= htmlspecialchars($book['cover_image']) ?>"
                                 alt="<?= htmlspecialchars($book['title']) ?>"
                                 class="book-card-image">
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($book['title']) ?></h3>
                        <p><?= htmlspecialchars($book['author'] ?? '') ?></p>
                        <p>GH₵<?= number_format($book['price'], 2) ?></p>
                        <a href="actions/single_book.php?id=<?= $book['book_id'] ?>">View</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <h2>Trending Now</h2>
            <div class="book-list">
                <?php foreach ($trendingBooks as $book): ?>
                    <div class="book-card">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?= htmlspecialchars($book['cover_image']) ?>"
                                 alt="<?= htmlspecialchars($book['title']) ?>"
                                 class="book-card-image">
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($book['title']) ?></h3>
                        <p><?= htmlspecialchars($book['author'] ?? '') ?></p>
                        <a href="actions/single_book.php?id=<?= $book['book_id'] ?>">View</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <h2>Recent Listings</h2>
            <div class="book-list">
                <?php foreach ($recentBooks as $book): ?>
                    <div class="book-card">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?= htmlspecialchars($book['cover_image']) ?>"
                                 alt="<?= htmlspecialchars($book['title']) ?>"
                                 class="book-card-image">
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($book['title']) ?></h3>
                        <p><?= htmlspecialchars($book['author'] ?? '') ?></p>
                        <p>By <?= htmlspecialchars($book['seller_username'] ?? '') ?></p>
                        <a href="actions/single_book.php?id=<?= $book['book_id'] ?>">View</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <h2>Browse by Category</h2>
            <ul class="category-list">
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="actions/browse_books.php?category_id=<?= $cat['category_id'] ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Ba Dɛre Exchange</h3>
                    <p>Share what you have, get what you need. Connecting students and book lovers across Ghana.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="actions/browse_books.php">Browse Books</a></li>
                        <?php if (AuthHelper::isLoggedIn()): ?>
                            <?php if (AuthHelper::canCreateListing()): ?>
                            <li><a href="view/list_book.php">List a Book</a></li>
                            <?php endif; ?>
                            <li><a href="view/user_account.php">My Account</a></li>
                            <li><a href="actions/cart.php">Cart</a></li>
                        <?php else: ?>
                            <li><a href="login/register.php">Sign Up</a></li>
                            <li><a href="login/login.php">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Categories</h4>
                    <ul>
                        <?php
                        $footerCategories = array_slice($categories, 0, 4);
                        foreach ($footerCategories as $cat):
                        ?>
                            <li><a href="actions/browse_books.php?category_id=<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>About</h4>
                    <ul>
                        <li><a href="view/about.php">About Us</a></li>
                        <li><a href="view/contact.php">Contact</a></li>
                        <li><a href="view/how_it_works.php">How It Works</a></li>
                        <li><a href="view/faq.php">FAQ</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Ba Dɛre Exchange. All rights reserved.</p>
                <p class="footer-tagline">Come and bring - Ba Dɛre</p>
            </div>
        </div>
    </footer>
</body>
</html>
