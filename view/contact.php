<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';

$flash = ['success' => $_SESSION['flash_success'] ?? null, 'error' => $_SESSION['flash_error'] ?? null];
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
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
            <h1>Contact Us</h1>

            <?php if ($flash['success']): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
            <?php endif; ?>

            <?php if ($flash['error']): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>

            <section class="content-section">
                <p>Have questions, feedback, or need assistance? We're here to help! Reach out to us through any of the following channels:</p>
            </section>

            <div class="contact-grid">
                <div class="contact-card">
                    <div class="contact-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <h3>Email</h3>
                    <p>support@badereexchange.com</p>
                    <p style="font-size: 0.9rem; color: var(--text-secondary);">We'll respond within 24 hours</p>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                        </svg>
                    </div>
                    <h3>Phone</h3>
                    <p>+233 XX XXX XXXX</p>
                    <p style="font-size: 0.9rem; color: var(--text-secondary);">Mon-Fri, 9AM-5PM GMT</p>
                </div>

                <div class="contact-card">
                    <div class="contact-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <h3>Address</h3>
                    <p>Accra, Ghana</p>
                    <p style="font-size: 0.9rem; color: var(--text-secondary);">Visit by appointment</p>
                </div>
            </div>

            <section class="content-section" style="margin-top: 40px;">
                <h2>Send Us a Message</h2>
                <form class="contact-form" action="../actions/contact_submit.php" method="POST">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" class="field-input" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="field-input" required>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" class="field-input" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" class="field-input" rows="6" required></textarea>
                    </div>

                    <button type="submit" class="btn-primary">Send Message</button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
