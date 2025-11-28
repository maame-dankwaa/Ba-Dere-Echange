<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Temporary flag to surface backend errors directly in the browser
    if (!defined('SHOW_DEBUG_ERRORS')) {
        define('SHOW_DEBUG_ERRORS', true);
    }

    require_once __DIR__ . '/../helpers/AuthHelper.php';
    require_once __DIR__ . '/../classes/User.php';
    require_once __DIR__ . '/../classes/VendorApplication.php';
    require_once __DIR__ . '/../classes/ContactMessage.php';
    require_once __DIR__ . '/../classes/FeaturedListing.php';

    // Require login
    AuthHelper::requireLogin('../login/login.php');

    // Get user info
    $user_id = AuthHelper::getUserId();
    $user_role = AuthHelper::getUserRole();

    // Load user data from database
    $userModel = new User();
    $user = $userModel->find($user_id);

    // Load stats from database
    $stats = $userModel->getStatistics($user_id);

    // Load recent purchases and sales
    $purchases = $userModel->getPurchases($user_id, 5);
    $sales = $userModel->getSales($user_id, 5);

    // Check for vendor application if user is a customer
    $vendorApplication = null;
    if (AuthHelper::isCustomer()) {
        $applicationModel = new VendorApplication();
        $vendorApplication = $applicationModel->getLatestByUser($user_id);
    }

    // Load contact messages
    $contactModel = new ContactMessage();
    $contactMessages = $contactModel->getUserMessages($user_id);

    // Load featured listings count for vendors
    $featuredCount = 0;
    if (AuthHelper::isVendor() || AuthHelper::isAdmin()) {
        $featuredModel = new FeaturedListing();
        $featuredCount = $featuredModel->getUserFeaturedCount($user_id);
    }

    // Get success message if any
    $successMessage = $_SESSION['success_message'] ?? '';
    unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Account - Ba Dɛre Exchange</title>
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
                    <a href="wishlist.php" class="icon-btn" aria-label="Favorites">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </a>
                    <a href="user_account.php" class="icon-btn" aria-label="Profile">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M6 20c0-4 2.5-6 6-6s6 2 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </a>
                    <a href="list_book.php" class="btn-primary">List a Book</a>
                </div>
            </div>
        </div>
    </nav>
    <main class="container account-layout">
        <?php if (!empty($successMessage)): ?>
            <div class="alert" style="margin-top: 16px; padding: 16px; background: #d4edda; border: 1px solid #28a745; border-radius: 8px; color: #155724;">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <section class="account-summary">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                <div>
                    <h1 style="margin-bottom: 8px;">Hi, <?= htmlspecialchars($user['username'] ?? '') ?></h1>
                    <p style="margin-bottom: 4px;">Email: <?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <p style="margin-bottom: 0;">Location: <?= htmlspecialchars($user['location'] ?? '') ?></p>
                </div>
                <a href="../login/logout.php" class="btn-danger" style="margin-top: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Logout
                </a>
            </div>

            <div class="account-stats">
                <div>Role: <strong><?= htmlspecialchars(ucfirst($user_role)) ?></strong></div>
                <div>Purchases: <?= (int)($stats['total_purchases'] ?? 0) ?></div>
                <?php if (AuthHelper::isVendorOrAbove()): ?>
                <div>Sales: <?= (int)($stats['total_sales'] ?? 0) ?></div>
                <div>Featured Listings: <?= $featuredCount ?></div>
                <?php endif; ?>
                <div>Wishlist items: <?= (int)($stats['wishlist_items'] ?? 0) ?></div>
            </div>
        </section>

        <?php if (AuthHelper::isCustomer() && $vendorApplication): ?>
        <section class="account-section">
            <h2>Vendor Application Status</h2>
            <div style="margin-top: 16px;">
                <?php if ($vendorApplication['status'] === 'pending'): ?>
                    <div style="padding: 16px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;">
                        <p style="margin-bottom: 8px;">
                            <strong>Status:</strong> <span style="color: #856404; font-weight: bold;">Pending Review</span>
                        </p>
                        <p style="color: #856404;">
                            Your application to become a vendor is currently being reviewed by our admin team. We'll notify you once a decision has been made.
                        </p>
                        <p style="margin-top: 8px; font-size: 14px; color: #666;">
                            Applied on: <?= date('F j, Y', strtotime($vendorApplication['created_at'])) ?>
                        </p>
                    </div>
                <?php elseif ($vendorApplication['status'] === 'rejected'): ?>
                    <div style="padding: 16px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px;">
                        <p style="margin-bottom: 8px;">
                            <strong>Status:</strong> <span style="color: #721c24; font-weight: bold;">Rejected</span>
                        </p>
                        <?php if (!empty($vendorApplication['rejection_reason'])): ?>
                            <p style="color: #721c24; margin-bottom: 8px;">
                                <strong>Reason:</strong> <?= htmlspecialchars($vendorApplication['rejection_reason']) ?>
                            </p>
                        <?php endif; ?>
                        <p style="margin-top: 12px;">
                            <a href="apply_vendor.php" class="btn-primary">Apply Again</a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php elseif (AuthHelper::isCustomer()): ?>
        <section class="account-section">
            <h2>Become a Vendor</h2>
            <p style="margin-top: 12px; color: #666;">
                Want to sell your books and academic resources on our platform? Apply to become a vendor!
            </p>
            <div style="margin-top: 16px;">
                <a href="apply_vendor.php" class="btn-primary">Apply to Become a Vendor</a>
            </div>
        </section>
        <?php endif; ?>

        <?php if (AuthHelper::isVendorOrAbove()): ?>
        <section class="account-section">
            <h2>Vendor Dashboard</h2>
            <div style="display: flex; gap: 16px; margin-top: 16px;">
                <a href="list_book.php" class="btn-primary">Create New Listing</a>
                <a href="manage_listings.php" class="btn-secondary">Manage Listings</a>
                <a href="analytics.php" class="btn-secondary">View Analytics</a>
            </div>

            <?php if ($featuredCount > 0): ?>
            <div style="margin-top: 24px; padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 8px; border-left: 4px solid #f59e0b;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="#f59e0b">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <h3 style="margin: 0; color: #92400e;">Featured Listings</h3>
                </div>
                <p style="color: #92400e; margin: 8px 0;">
                    You currently have <strong><?= $featuredCount ?></strong> active featured listing<?= $featuredCount !== 1 ? 's' : '' ?>.
                </p>
                <p style="color: #78350f; font-size: 0.9em; margin-top: 8px;">
                    Featured listings appear at the top of browse results with a special badge.
                </p>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if (AuthHelper::isAdmin()): ?>
        <section class="account-section">
            <h2>Admin Tools</h2>
            <div style="display: flex; gap: 16px; margin-top: 16px;">
                <a href="../admin/admin_dashboard.php" class="btn-danger">Admin Dashboard</a>
                <a href="vendor_applications_admin.php" class="btn-secondary">Vendor Applications</a>
            </div>
        </section>
        <?php endif; ?>

        <section class="account-section">
            <h2>Recent Purchases</h2>
            <?php if (empty($purchases)): ?>
                <p>No purchases yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($purchases as $t): ?>
                        <li>
                            <?= htmlspecialchars($t['book_title']) ?>
                            – GH₵<?= number_format($t['total_amount'], 2) ?>
                            (<?= htmlspecialchars($t['created_at']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if (AuthHelper::isVendorOrAbove()): ?>
        <section class="account-section">
            <h2>Recent Sales</h2>
            <?php if (empty($sales)): ?>
                <p>No sales yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($sales as $t): ?>
                        <li>
                            <?= htmlspecialchars($t['book_title']) ?>
                            – GH₵<?= number_format($t['total_amount'], 2) ?>
                            (<?= htmlspecialchars($t['created_at']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <section class="account-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="margin: 0;">My Contact Messages</h2>
                <a href="contact.php" class="btn-primary" style="font-size: 14px; padding: 8px 16px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    Send New Message
                </a>
            </div>
            <?php if (empty($contactMessages)): ?>
                <p style="color: #666;">No messages yet. Use the button above to contact us if you have any questions.</p>
            <?php else: ?>
                <div style="margin-top: 16px;">
                    <?php foreach ($contactMessages as $msg): ?>
                        <div style="padding: 16px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 600;">
                                    <?= htmlspecialchars($msg['subject']) ?>
                                </h3>
                                <span class="status-badge status-<?= strtolower($msg['status']) ?>" style="font-size: 12px; padding: 4px 10px; border-radius: 12px;">
                                    <?= ucfirst($msg['status']) ?>
                                </span>
                            </div>

                            <p style="margin: 8px 0; color: #666; font-size: 14px;">
                                <strong>Your Message:</strong><br>
                                <?= nl2br(htmlspecialchars(strlen($msg['message']) > 200 ? substr($msg['message'], 0, 200) . '...' : $msg['message'])) ?>
                            </p>

                            <?php if ($msg['admin_response']): ?>
                                <div style="margin-top: 16px; padding: 16px; background: #f8f9fa; border-left: 4px solid var(--primary-color); border-radius: 4px;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                        </svg>
                                        <strong style="color: var(--primary-color);">Admin Response</strong>
                                    </div>
                                    <p style="margin: 0; color: #333;">
                                        <?= nl2br(htmlspecialchars($msg['admin_response'])) ?>
                                    </p>
                                    <p style="margin-top: 8px; font-size: 12px; color: #666;">
                                        Responded on <?= $msg['responded_at'] ? date('F d, Y \a\t g:i A', strtotime($msg['responded_at'])) : 'N/A' ?>
                                        <?php if ($msg['responded_by_name']): ?>
                                            by <?= htmlspecialchars($msg['responded_by_name']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <p style="margin-top: 12px; font-size: 12px; color: #999;">
                                Sent on <?= $msg['created_at'] ? date('F d, Y \a\t g:i A', strtotime($msg['created_at'])) : 'N/A' ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
