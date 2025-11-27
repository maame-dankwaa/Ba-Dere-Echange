<?php
require_once __DIR__ . '/../helpers/AuthHelper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/app.js" defer></script>
    <script src="../js/checkout.js" defer></script>
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
        <h1>Checkout</h1>

        <?php if (!empty($flash['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
        <?php endif; ?>
        <?php if (!empty($warnings)): ?>
            <?php foreach ($warnings as $warning): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($warning) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <p>Your cart is empty.</p>
            <a href="../index.php" class="btn-primary">Continue Shopping</a>
        <?php else: ?>
            <?php $total = 0; ?>
            <table class="cart-table" style="margin-bottom: 20px;">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $transactionType = $item['transaction_type'] ?? 'purchase';
                        $typeLabel = ucfirst($transactionType);
                        $lineTotal = $item['price'] * $item['quantity'];
                        $total += $lineTotal;
                        ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($item['title']) ?>
                                <?php if ($transactionType === 'rent' && !empty($item['rental_duration'])): ?>
                                    <br><small style="color: #666;">
                                        <?= (int)$item['rental_duration'] ?> <?= htmlspecialchars($item['rental_period_unit'] ?? 'day') ?>(s)
                                        @ GH₵<?= number_format($item['rental_price_per_period'] ?? 0, 2) ?>/<?= htmlspecialchars($item['rental_period_unit'] ?? 'day') ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><span style="font-size: 0.9em; color: #666;"><?= htmlspecialchars($typeLabel) ?></span></td>
                            <td>
                                <?php if ($transactionType === 'exchange'): ?>
                                    <em>Exchange</em>
                                <?php else: ?>
                                    GH₵<?= number_format($item['price'], 2) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $item['quantity'] ?></td>
                            <td>
                                <?php if ($transactionType === 'exchange'): ?>
                                    <em>-</em>
                                <?php else: ?>
                                    GH₵<?= number_format($lineTotal, 2) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="checkout-total" style="font-size: 1.5em; font-weight: bold; margin: 20px 0;">
                Total: GH₵<?= number_format($total, 2) ?>
            </p>

            <form action="../actions/checkout_process.php" method="post" style="max-width: 600px;">
                <h2>Payment & Delivery Details</h2>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 10px; display: block;">Payment Method *</label>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <label style="display: flex; align-items: center; padding: 15px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.3s;" class="payment-option">
                            <input type="radio" name="payment_method" value="paystack" required style="margin-right: 12px; width: 18px; height: 18px;">
                            <div>
                                <div style="font-weight: 600; font-size: 16px;">💳 Pay with Paystack</div>
                                <div style="color: #6b7280; font-size: 14px; margin-top: 4px;">Mobile Money, Visa, Mastercard</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; padding: 15px; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.3s;" class="payment-option">
                            <input type="radio" name="payment_method" value="cash" style="margin-right: 12px; width: 18px; height: 18px;">
                            <div>
                                <div style="font-weight: 600; font-size: 16px;">💵 Cash on Delivery</div>
                                <div style="color: #6b7280; font-size: 14px; margin-top: 4px;">Pay when you receive your order</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="contact_phone">Contact Phone Number *</label>
                    <input type="tel" name="contact_phone" id="contact_phone"
                           class="field-input" placeholder="e.g., 0241234567"
                           minlength="10" maxlength="10" required>
                    <small style="color: #666; font-size: 0.875rem;">We'll use this to contact you about your order</small>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="delivery_method">Delivery Method *</label>
                    <select name="delivery_method" id="delivery_method" required class="field-select">
                        <option value="pickup">Pickup</option>
                        <option value="delivery">Home Delivery</option>
                    </select>
                </div>

                <div class="form-group" id="delivery_address_group" style="margin-bottom: 15px; display: none;">
                    <label for="delivery_address">Delivery Address *</label>
                    <textarea name="delivery_address" id="delivery_address"
                              class="field-input" rows="3"
                              placeholder="Enter your full delivery address"></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <a href="../actions/cart.php" class="btn-secondary">Back to Cart</a>
                    <button id="checkoutButton" type="submit" class="btn-primary">
                        <span id="buttonText">Confirm Order</span>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </main>

<script>
// Show/hide delivery address based on delivery method
document.addEventListener('DOMContentLoaded', function() {
    const deliveryMethod = document.getElementById('delivery_method');
    const deliveryAddressGroup = document.getElementById('delivery_address_group');
    const deliveryAddress = document.getElementById('delivery_address');

    if (deliveryMethod && deliveryAddressGroup) {
        deliveryMethod.addEventListener('change', function() {
            if (this.value === 'delivery') {
                deliveryAddressGroup.style.display = 'block';
                deliveryAddress.required = true;
            } else {
                deliveryAddressGroup.style.display = 'none';
                deliveryAddress.required = false;
            }
        });
    }
});
</script>
</body>
</html>
