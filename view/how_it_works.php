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
    <title>How It Works - Ba Dɛre Exchange</title>
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
            <h1>How It Works</h1>

            <section class="content-section">
                <p>Ba Dɛre Exchange makes it easy to buy, sell, rent, or exchange books. Here's how to get started:</p>
            </section>

            <div class="steps-container">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Create Your Account</h3>
                    <p>Sign up as a customer to buy and rent books, or as a vendor/institution to sell books to our community.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Browse or List Books</h3>
                    <p>Search our catalog for the books you need, or list your own books with details, photos, and pricing.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Choose Your Option</h3>
                    <p>Decide whether to buy, rent, or exchange. Each book listing shows available transaction types.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Complete Your Transaction</h3>
                    <p>Add items to cart, proceed to checkout, and complete payment securely through our platform.</p>
                </div>
            </div>

            <section class="content-section">
                <h2>For Buyers</h2>
                <ul>
                    <li><strong>Search & Browse</strong> - Find books by title, author, category, or keyword</li>
                    <li><strong>Compare Options</strong> - See different sellers and transaction types for the same book</li>
                    <li><strong>Add to Cart</strong> - Collect items from multiple sellers in one cart</li>
                    <li><strong>Secure Checkout</strong> - Pay safely using Mobile Money or other payment methods</li>
                    <li><strong>Wishlist</strong> - Save books for later and get notified of price changes</li>
                </ul>
            </section>

            <section class="content-section">
                <h2>For Sellers (Vendors & Institutions)</h2>
                <ul>
                    <li><strong>List Your Books</strong> - Create detailed listings with photos and descriptions</li>
                    <li><strong>Set Your Terms</strong> - Choose to sell, rent, or allow exchanges</li>
                    <li><strong>Flexible Pricing</strong> - Set purchase prices and rental rates</li>
                    <li><strong>Manage Inventory</strong> - Track availability and update listings</li>
                    <li><strong>Analytics Dashboard</strong> - Monitor sales, views, and earnings</li>
                </ul>
            </section>

            <section class="content-section">
                <h2>Transaction Types Explained</h2>

                <h3>Purchase</h3>
                <p>Buy the book outright and own it permanently. Best for books you'll use repeatedly or want to keep.</p>

                <h3>Rental</h3>
                <p>Borrow the book for a specific period (days, weeks, or months). Perfect for textbooks needed for one semester or books you'll only read once. Select your rental duration at checkout.</p>

                <h3>Exchange</h3>
                <p>Trade one of your books for another member's book. Both parties agree on the exchange terms and duration.</p>
            </section>

            <section class="content-section">
                <h2>Payment & Safety</h2>
                <ul>
                    <li>Secure payment processing through trusted payment gateways</li>
                    <li>Mobile Money integration for convenient payments</li>
                    <li>Verified seller accounts for institutional vendors</li>
                    <li>Transaction history and receipts available in your account</li>
                    <li>Customer support available to resolve any issues</li>
                </ul>
            </section>

            <section class="content-section">
                <h2>Ready to Get Started?</h2>
                <div style="margin-top: 20px;">
                    <?php if (!AuthHelper::isLoggedIn()): ?>
                        <a href="../login/register.php" class="btn-primary" style="margin-right: 12px;">Create Account</a>
                    <?php endif; ?>
                    <a href="../actions/browse_books.php" class="btn-secondary">Browse Books</a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
