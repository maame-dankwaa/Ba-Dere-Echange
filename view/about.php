<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - Ba Dɛre Exchange</title>
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
        <div class="content-page">
            <h1>About Ba Dɛre Exchange</h1>

            <section class="content-section">
                <h2>Our Mission</h2>
                <p>Ba Dɛre Exchange is a platform dedicated to making academic and non-academic books more accessible and affordable for students and book lovers across Ghana. Our name, which means "Come and bring" in the local language, reflects our commitment to creating a community-driven marketplace where knowledge is shared and books find new homes.</p>
            </section>

            <section class="content-section">
                <h2>What We Offer</h2>
                <p>We provide a comprehensive platform for students, institutions, and book lovers to:</p>
                <ul>
                    <li><strong>Buy</strong> - Purchase books at affordable prices from trusted sellers</li>
                    <li><strong>Sell</strong> - List your books and reach a wide audience of potential buyers</li>
                    <li><strong>Rent</strong> - Access books for a limited period at a fraction of the purchase price</li>
                    <li><strong>Exchange</strong> - Trade books directly with other members of our community</li>
                </ul>
            </section>

            <section class="content-section">
                <h2>Our Story</h2>
                <p>Ba Dɛre Exchange was founded with a simple observation: too many students struggle to afford textbooks, while others have books sitting unused on their shelves. We saw an opportunity to connect these two groups and create value for everyone involved.</p>
                <p>Every day, we help people access the knowledge they need while promoting sustainable consumption through book reuse and sharing.</p>
            </section>

            <section class="content-section">
                <h2>Why Choose Us?</h2>
                <ul>
                    <li><strong>Affordable Prices</strong> - Save money by buying used books or renting instead of purchasing new</li>
                    <li><strong>Wide Selection</strong> - Find academic textbooks and non-academic books across various categories</li>
                    <li><strong>Trusted Community</strong> - Connect with verified buyers and sellers</li>
                    <li><strong>Flexible Options</strong> - Choose to buy, sell, rent, or exchange based on your needs</li>
                    <li><strong>Sustainable</strong> - Reduce waste by giving books a second life</li>
                </ul>
            </section>

            <section class="content-section">
                <h2>Join Our Community</h2>
                <p>Whether you're a student looking for affordable textbooks, a reader wanting to expand your library, or someone with books to share, Ba Dɛre Exchange welcomes you. Together, we're building a community where knowledge is accessible to all.</p>
                <div style="margin-top: 30px;">
                    <?php if (!AuthHelper::isLoggedIn()): ?>
                        <a href="../login/register.php" class="btn-primary" style="margin-right: 12px;">Get Started</a>
                    <?php endif; ?>
                    <a href="../actions/browse_books.php" class="btn-secondary">Browse Books</a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
