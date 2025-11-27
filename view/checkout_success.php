<?php
require_once __DIR__ . '/../helpers/AuthHelper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Complete - Ba Dɛre Exchange</title>
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
    <main class="container" style="text-align: center; padding: 40px 20px;">
        <h1 style="font-size: 2.5em; color: #10b981;">Order Confirmed!</h1>

        <?php if (!empty($flash['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
        <?php endif; ?>

        <?php if (!$order): ?>
            <p>We couldn't find that order.</p>
            <a href="../index.php" class="btn-primary">Back to Home</a>
        <?php else: ?>
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 30px; margin: 30px auto; max-width: 600px; text-align: left;">
                <h2 style="margin-top: 0;">Order Details</h2>
                <p><strong>Order ID:</strong> <?= htmlspecialchars($order['transaction_code'] ?? $order['transaction_id']) ?></p>
                <p><strong>Book:</strong> <?= htmlspecialchars($order['book_title']) ?></p>
                <p><strong>Transaction Type:</strong> <?= htmlspecialchars(ucfirst($order['transaction_type'] ?? 'purchase')) ?></p>
                <p><strong>Quantity:</strong> <?= (int)$order['quantity'] ?></p>
                <p><strong>Total Amount:</strong> GH₵<?= number_format($order['total_amount'], 2) ?></p>
                <p><strong>Payment Status:</strong> <span style="color: <?= $order['payment_status'] === 'completed' ? '#10b981' : '#f59e0b' ?>;"><?= htmlspecialchars(ucfirst($order['payment_status'])) ?></span></p>
                <p><strong>Payment Method:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'N/A'))) ?></p>
                <p><strong>Delivery Method:</strong> <?= htmlspecialchars(ucfirst($order['delivery_method'] ?? 'N/A')) ?></p>
            </div>

            <div style="margin-top: 30px;">
                <p style="color: #666;">We'll send you updates about your order to your registered email.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: center;">
            <a href="../index.php" class="btn-primary">Continue Shopping</a>
            <a href="../view/user_account.php" class="btn-secondary">View My Orders</a>
        </div>
    </main>
</body>
</html>
