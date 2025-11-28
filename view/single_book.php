<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


require_once __DIR__ . '/../helpers/AuthHelper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($book['title']) ?> - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
    <script src="../js/book.js" defer></script>
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
                        <!-- Logged in users: Show cart, wishlist, profile, and list book (for vendors) -->
                        <a href="../actions/cart.php" class="icon-btn" aria-label="Cart">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <circle cx="9" cy="21" r="1" fill="currentColor"/>
                                <circle cx="20" cy="21" r="1" fill="currentColor"/>
                                <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>

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
    <main class="container single-book-layout">
        <section class="book-main">
            <?php if (!empty($book['cover_image'])): ?>
                <div class="book-cover">
                    <img src="../<?= htmlspecialchars($book['cover_image']) ?>"
                         alt="<?= htmlspecialchars($book['title']) ?>"
                         style="max-width: 100%; max-height: 400px; object-fit: contain; margin-bottom: 20px;">
                </div>
            <?php endif; ?>

            <h1><?= htmlspecialchars($book['title']) ?></h1>
            <p>by <?= htmlspecialchars($book['author'] ?? 'Unknown author') ?></p>
            <p>Category: <?= htmlspecialchars($book['category_name'] ?? '') ?></p>
            <p>Seller: <?= htmlspecialchars($book['seller_username'] ?? '') ?></p>
            <p>Location: <?= htmlspecialchars($book['seller_location'] ?? '') ?></p>

            <?php
            $hasPurchase = !empty($book['price']) && $book['price'] > 0;
            $hasRent = !empty($book['is_rentable']);
            $hasExchange = !empty($book['is_exchangeable']);
            $availableOptions = [];

            if ($hasPurchase) {
                $availableOptions[] = ['value' => 'purchase', 'label' => 'Purchase - GH₵' . number_format($book['price'], 2)];
            }

            if ($hasRent) {
                $rentalPrice = $book['rental_price'] ?? $book['price'] * 0.1;
                $rentalUnit = $book['rental_period_unit'] ?? 'day';
                $rentalLabel = 'Rent - GH₵' . number_format($rentalPrice, 2) . '/' . $rentalUnit;

                // Add period info if available
                if (!empty($book['rental_min_period']) || !empty($book['rental_max_period'])) {
                    $minPeriod = $book['rental_min_period'] ?? 1;
                    $maxPeriod = $book['rental_max_period'] ?? '';
                    $periodInfo = ' (' . $minPeriod;
                    if ($maxPeriod) {
                        $periodInfo .= '-' . $maxPeriod;
                    }
                    $periodInfo .= ' ' . $rentalUnit . ($maxPeriod > 1 || !$maxPeriod ? 's' : '') . ')';
                    $rentalLabel .= $periodInfo;
                }

                $availableOptions[] = ['value' => 'rent', 'label' => $rentalLabel];
            }

            if ($hasExchange) {
                $exchangeLabel = 'Exchange';
                if (!empty($book['exchange_duration'])) {
                    $exchangeUnit = $book['exchange_duration_unit'] ?? 'day';
                    $exchangeLabel .= ' - ' . $book['exchange_duration'] . ' ' . $exchangeUnit;
                    if ($book['exchange_duration'] > 1) $exchangeLabel .= 's';
                }
                $availableOptions[] = ['value' => 'exchange', 'label' => $exchangeLabel];
            }
            ?>

            <?php if (!empty($availableOptions)): ?>
                <form class="add-to-cart" action="../actions/cart_add.php" method="post">
                    <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">

                    <?php if (count($availableOptions) > 1): ?>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="transaction_type">How would you like to get this book?</label>
                            <select name="transaction_type" id="transaction_type" required class="field-select" style="width: 100%; padding: 10px;">
                                <?php foreach ($availableOptions as $option): ?>
                                    <option value="<?= $option['value'] ?>"><?= htmlspecialchars($option['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="transaction_type" value="<?= $availableOptions[0]['value'] ?>">
                        <p class="price"><?= htmlspecialchars($availableOptions[0]['label']) ?></p>
                    <?php endif; ?>

                    <?php if ($hasRent): ?>
                        <div class="form-group" id="rentalDurationGroup" style="margin-bottom: 15px; display: none;">
                            <label for="rental_duration">Rental Duration *</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="number" name="rental_duration" id="rental_duration"
                                       min="<?= $book['rental_min_period'] ?? 1 ?>"
                                       max="<?= $book['rental_max_period'] ?? 30 ?>"
                                       value="<?= $book['rental_min_period'] ?? 1 ?>"
                                       style="width: 80px; padding: 8px;" class="field-input">
                                <span><?= htmlspecialchars($book['rental_period_unit'] ?? 'day') ?>(s)</span>
                            </div>
                            <small style="color: #666; font-size: 0.85em;">
                                Min: <?= $book['rental_min_period'] ?? 1 ?>, Max: <?= $book['rental_max_period'] ?? 30 ?> <?= htmlspecialchars($book['rental_period_unit'] ?? 'day') ?>(s)
                            </small>
                        </div>

                        <script>
                        (function() {
                            const transactionTypeSelect = document.getElementById('transaction_type');
                            const rentalDurationGroup = document.getElementById('rentalDurationGroup');
                            const rentalDurationInput = document.getElementById('rental_duration');

                            function toggleRentalDuration() {
                                if (transactionTypeSelect && transactionTypeSelect.value === 'rent') {
                                    rentalDurationGroup.style.display = 'block';
                                    rentalDurationInput.required = true;
                                } else {
                                    rentalDurationGroup.style.display = 'none';
                                    rentalDurationInput.required = false;
                                }
                            }

                            if (transactionTypeSelect) {
                                transactionTypeSelect.addEventListener('change', toggleRentalDuration);
                                toggleRentalDuration(); // Check on page load
                            } else {
                                // Single option, check if it's rent
                                const hiddenType = document.querySelector('input[name="transaction_type"]');
                                if (hiddenType && hiddenType.value === 'rent') {
                                    rentalDurationGroup.style.display = 'block';
                                    rentalDurationInput.required = true;
                                }
                            }
                        })();
                        </script>
                    <?php endif; ?>

                    <div data-qty-wrapper>
                        <button type="button" id="qtyMinus" data-qty-minus>-</button>
                        <input type="number" id="quantity" name="quantity" value="1" min="1">
                        <button type="button" id="qtyPlus" data-qty-plus>+</button>
                    </div>
                    <button type="submit" class="btn-primary">Add to Cart</button>
                </form>
            <?php else: ?>
                <p class="error">This book is not currently available for any transaction type.</p>
            <?php endif; ?>

            <form action="../actions/wishlist_add.php" method="post">
                <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                <button type="submit" class="btn-secondary">Add to Wishlist</button>
            </form>

            <?php if (AuthHelper::isLoggedIn() && (AuthHelper::getUserId() == $book['seller_id'] || AuthHelper::isAdmin())): ?>
                <a href="../view/edit_book.php?id=<?= $book['book_id'] ?>" class="btn-primary" style="display: inline-block; margin-top: 10px;">Edit Listing</a>
            <?php endif; ?>

            <section class="book-description">
                <h2>Description</h2>
                <p><?= nl2br(htmlspecialchars($book['description'] ?? '')) ?></p>
            </section>
        </section>

        <aside class="book-sidebar">
            <section>
                <h2>Rating</h2>
                <p><?= number_format($ratingStats['avg_rating'] ?? 0, 1) ?> / 5</p>
                <p><?= (int)($ratingStats['total_reviews'] ?? 0) ?> reviews</p>
            </section>

            <section class="reviews">
                <h2>Reviews</h2>

                <?php if (AuthHelper::isLoggedIn() && AuthHelper::getUserId() != $book['seller_id']): ?>
                    <form action="../actions/review_submit.php" method="post" class="review-form">
                        <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">

                        <div class="form-group">
                            <label for="rating">Rating *</label>
                            <select name="rating" id="rating" required class="field-select">
                                <option value="">Select rating</option>
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Good</option>
                                <option value="3">3 - Average</option>
                                <option value="2">2 - Below Average</option>
                                <option value="1">1 - Poor</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="comment">Your Review</label>
                            <textarea name="comment" id="comment" rows="4" placeholder="Share your thoughts about this book..." class="field-textarea"></textarea>
                        </div>

                        <button type="submit" class="btn-primary">Submit Review</button>
                    </form>
                    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
                <?php endif; ?>

                <?php if (empty($reviews)): ?>
                    <p>No reviews yet. Be the first to review this book!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <article class="review">
                            <strong><?= htmlspecialchars($review['reviewer_username']) ?></strong>
                            <span><?= (int)$review['rating'] ?>/5</span>
                            <p><?= nl2br(htmlspecialchars($review['comment'] ?? $review['review_text'] ?? '')) ?></p>
                            <small style="color: #666;"><?= date('M d, Y', strtotime($review['created_at'])) ?></small>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="similar-books">
                <h2>Other Books</h2>
                <?php foreach ($similarBooks as $b): ?>
                    <div class="book-card">
                        <a href="../actions/single_book.php?id=<?= $b['book_id'] ?>">
                            <?= htmlspecialchars($b['title']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </section>
        </aside>
    </main>
</body>
</html>
