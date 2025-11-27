<?php
    require_once __DIR__ . '/../helpers/AuthHelper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Wishlist - Ba Dɛre Exchange</title>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="container">
        <h1>My Wishlist</h1>

        <?php if (empty($items)): ?>
            <p>Your wishlist is empty.</p>
        <?php else: ?>
            <div class="book-list">
                <?php foreach ($items as $item): ?>
                    <div class="book-card">
                        <?php if (!empty($item['cover_image'])): ?>
                            <img src="../<?= htmlspecialchars($item['cover_image']) ?>"
                                 alt="<?= htmlspecialchars($item['title']) ?>"
                                 class="book-card-image">
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p><?= htmlspecialchars($item['author'] ?? '') ?></p>
                        <p>GH₵<?= number_format($item['price'], 2) ?></p>
                        <a href="../actions/single_book.php?id=<?= $item['book_id'] ?>">View</a>
                        <a href="../actions/wishlist_remove.php?book_id=<?= $item['book_id'] ?>">Remove</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
