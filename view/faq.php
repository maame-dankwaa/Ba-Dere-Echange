<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ - Ba Dɛre Exchange</title>
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
            <h1>Frequently Asked Questions</h1>

            <section class="content-section">
                <p>Find answers to common questions about Ba Dɛre Exchange. If you don't find what you're looking for, feel free to <a href="../view/contact.php">contact us</a>.</p>
            </section>

            <div class="faq-section">
                <h2>General Questions</h2>

                <div class="faq-item">
                    <h3>What is Ba Dɛre Exchange?</h3>
                    <p>Ba Dɛre Exchange is a platform that connects students and book lovers to buy, sell, rent, or exchange academic and non-academic books affordably across Ghana.</p>
                </div>

                <div class="faq-item">
                    <h3>Who can use Ba Dɛre Exchange?</h3>
                    <p>Anyone can browse and buy books. To sell books, you need to register as a vendor or institutional account. Students and book lovers can register as customers to access buying, renting, and exchange features.</p>
                </div>

                <div class="faq-item">
                    <h3>Is it free to create an account?</h3>
                    <p>Yes! Creating an account is completely free. We only charge a small commission on successful sales to maintain and improve the platform.</p>
                </div>
            </div>

            <div class="faq-section">
                <h2>Buying Books</h2>

                <div class="faq-item">
                    <h3>How do I search for books?</h3>
                    <p>Use the search bar at the top of any page to search by title, author, or keywords. You can also browse by category or filter by price, condition, and transaction type.</p>
                </div>

                <div class="faq-item">
                    <h3>What payment methods do you accept?</h3>
                    <p>We accept Mobile Money (MTN, Vodafone, AirtelTigo) and other secure payment methods. All transactions are processed through our secure payment gateway.</p>
                </div>

                <div class="faq-item">
                    <h3>Can I buy from multiple sellers at once?</h3>
                    <p>Yes! Add books from different sellers to your cart and checkout once. Each seller will receive their portion of the payment after the transaction is complete.</p>
                </div>

                <div class="faq-item">
                    <h3>What if I don't receive my book?</h3>
                    <p>Contact us immediately through our support channels. We will work with the seller to resolve the issue. Our buyer protection ensures you get your book or a refund.</p>
                </div>
            </div>

            <div class="faq-section">
                <h2>Renting Books</h2>

                <div class="faq-item">
                    <h3>How does book rental work?</h3>
                    <p>When you rent a book, you pay a rental fee to use it for a specific period (days, weeks, or months). The seller sets the rental price and minimum/maximum duration.</p>
                </div>

                <div class="faq-item">
                    <h3>What happens if I return a rented book late?</h3>
                    <p>Late returns may incur additional fees as specified by the seller. Contact the seller if you need an extension before the due date.</p>
                </div>

                <div class="faq-item">
                    <h3>Can I buy a book I'm currently renting?</h3>
                    <p>Yes! Contact the seller to discuss converting your rental into a purchase. The seller may credit some of your rental payments toward the purchase price.</p>
                </div>
            </div>

            <div class="faq-section">
                <h2>Selling Books</h2>

                <div class="faq-item">
                    <h3>How do I list a book for sale?</h3>
                    <p>After creating a vendor or institution account, click "List a Book" and fill in the book details including title, author, condition, price, and photos.</p>
                </div>

                <div class="faq-item">
                    <h3>What commission does Ba Dɛre Exchange charge?</h3>
                    <p>We charge a small commission on each successful sale to maintain the platform. The exact rate is displayed during checkout, and you'll see your net earnings clearly.</p>
                </div>

                <div class="faq-item">
                    <h3>When do I receive payment for sold books?</h3>
                    <p>Payments are processed after the buyer confirms receipt of the book or after a specified period. Your earnings will be transferred to your registered Mobile Money account.</p>
                </div>

                <div class="faq-item">
                    <h3>Can I offer books for rent and sale simultaneously?</h3>
                    <p>Yes! When creating a listing, you can enable multiple transaction types. Buyers will see all available options and choose their preferred method.</p>
                </div>
            </div>

            <div class="faq-section">
                <h2>Exchanges</h2>

                <div class="faq-item">
                    <h3>How does book exchange work?</h3>
                    <p>Book exchange allows you to trade your book for another member's book. Both parties agree on the exchange terms, duration, and any additional payments if needed.</p>
                </div>

                <div class="faq-item">
                    <h3>Are book exchanges free?</h3>
                    <p>Direct book-for-book exchanges have no platform fees. However, if there's a price difference between books, the party receiving the more valuable book pays the difference.</p>
                </div>
            </div>

            <section class="content-section" style="margin-top: 40px;">
                <h2>Still Have Questions?</h2>
                <p>If you couldn't find the answer you're looking for, please don't hesitate to reach out to our support team.</p>
                <a href="../view/contact.php" class="btn-primary">Contact Support</a>
            </section>
        </div>
    </main>
</body>
</html>
