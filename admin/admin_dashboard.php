<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

// Temporary flag to surface backend errors directly in the browser
if (!defined('SHOW_DEBUG_ERRORS')) {
    define('SHOW_DEBUG_ERRORS', true);
}

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../helpers/AuthHelper.php';
    require_once __DIR__ . '/../classes/Database.php';
    require_once __DIR__ . '/../classes/User.php';
    require_once __DIR__ . '/../classes/Book.php';
    require_once __DIR__ . '/../classes/VendorApplication.php';
    require_once __DIR__ . '/../classes/ContactMessage.php';
    require_once __DIR__ . '/../classes/Transaction.php';

    // Require admin role
    AuthHelper::requireAdmin('../index.php');

    // Load statistics
    $userModel = new User();
    $bookModel = new Book();
    $applicationModel = new VendorApplication();
    $contactModel = new ContactMessage();
    $transactionModel = new Transaction();

    // Get database instance for queries
    $db = Database::getInstance();

    // Get real statistics with error handling
    try {
        $userCountResult = $db->fetch("SELECT COUNT(*) as total FROM fp_users", []);
        $totalUsers = $userCountResult['total'] ?? 0;
    } catch (Throwable $e) {
        $totalUsers = 0;
        if (SHOW_DEBUG_ERRORS) {
            echo '<pre style="color:#b91c1c;background:#fee2e2;padding:12px;border-radius:6px;">';
            echo 'Error loading user count: ' . htmlspecialchars($e->getMessage());
            echo '</pre>';
        }
    }

    try {
        $bookCountResult = $db->fetch("SELECT COUNT(*) as total FROM fp_books", []);
        $totalBooks = $bookCountResult['total'] ?? 0;
    } catch (Throwable $e) {
        $totalBooks = 0;
        if (SHOW_DEBUG_ERRORS) {
            echo '<pre style="color:#b91c1c;background:#fee2e2;padding:12px;border-radius:6px;">';
            echo 'Error loading book count: ' . htmlspecialchars($e->getMessage());
            echo '</pre>';
        }
    }

    try {
        $transactionCountResult = $db->fetch("SELECT COUNT(*) as total FROM fp_transactions", []);
        $totalTransactions = $transactionCountResult['total'] ?? 0;
    } catch (Throwable $e) {
        $totalTransactions = 0;
        if (SHOW_DEBUG_ERRORS) {
            echo '<pre style="color:#b91c1c;background:#fee2e2;padding:12px;border-radius:6px;">';
            echo 'Error loading transaction count: ' . htmlspecialchars($e->getMessage());
            echo '</pre>';
        }
    }

    try {
        $pendingListingsResult = $db->fetch("SELECT COUNT(*) as total FROM fp_books WHERE status = 'pending'", []);
        $pendingListings = $pendingListingsResult['total'] ?? 0;
    } catch (Throwable $e) {
        $pendingListings = 0;
        if (SHOW_DEBUG_ERRORS) {
            echo '<pre style="color:#b91c1c;background:#fee2e2;padding:12px;border-radius:6px;">';
            echo 'Error loading pending listings: ' . htmlspecialchars($e->getMessage());
            echo '</pre>';
        }
    }

    // Get vendor application stats
    try {
        $appStats = $applicationModel->getStatistics();
        $pendingApplications = (int)($appStats['pending_count'] ?? 0);
    } catch (Throwable $e) {
        $pendingApplications = 0;
        if (SHOW_DEBUG_ERRORS) {
            echo '<pre style="color:#b91c1c;background:#fee2e2;padding:12px;border-radius:6px;">';
            echo 'Error loading vendor application stats: ' . htmlspecialchars($e->getMessage());
            echo '</pre>';
        }
    }

    // Get contact message stats
    try {
        $contactStats = $contactModel->getStats();
        $newContactMessages = (int)($contactStats['new_count'] ?? 0);
    } catch (Throwable $e) {
        $newContactMessages = 0;
        if (SHOW_DEBUG_ERRORS) {
            echo '<pre style="color:#b91c1c;background:#fee2e2;padding:12px;border-radius:6px;">';
            echo 'Error loading contact message stats: ' . htmlspecialchars($e->getMessage());
            echo '</pre>';
        }
    }

    // Get recent transactions
    try {
        $recentTransactions = $db->fetchAll("
            SELECT
                t.*,
                b.title as book_title,
                buyer.username as buyer_username,
                seller.username as seller_username
            FROM fp_transactions t
            JOIN fp_books b ON t.book_id = b.book_id
            JOIN fp_users buyer ON t.buyer_id = buyer.user_id
            JOIN fp_users seller ON t.seller_id = seller.user_id
            ORDER BY t.created_at DESC
            LIMIT 10
        ", []);
    } catch (Throwable $e) {
        $recentTransactions = [];
        if (SHOW_DEBUG_ERRORS) {
            echo '<pre style="color:#b91c1c;background:#fee2e2;padding:12px;border-radius:6px;">';
            echo 'Error loading recent transactions: ' . htmlspecialchars($e->getMessage());
            echo '</pre>';
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Ba Dɛre Exchange</title>
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
                        <p>Admin Panel</p>
                    </div>
                </a>
                <div class="nav-actions">
                    <a href="../view/user_account.php" class="btn-secondary">My Account</a>
                    <a href="../login/logout.php" class="btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">
        <section>
            <h1>Admin Dashboard</h1>
            <p>Welcome, <?= htmlspecialchars(AuthHelper::getUsername()) ?>. Manage your platform from here.</p>
        </section>

        <!-- Statistics Overview -->
        <section style="margin-top: 32px;">
            <h2>Platform Statistics</h2>
            <div class="account-stats" style="margin-top: 16px;">
                <div>
                    <strong>Total Users</strong>
                    <p style="font-size: 24px; margin-top: 8px;"><?= (int)$totalUsers ?></p>
                </div>
                <div>
                    <strong>Total Listings</strong>
                    <p style="font-size: 24px; margin-top: 8px;"><?= (int)$totalBooks ?></p>
                </div>
                <div>
                    <strong>Transactions</strong>
                    <p style="font-size: 24px; margin-top: 8px;"><?= (int)$totalTransactions ?></p>
                </div>
                <div>
                    <strong>Pending Approval</strong>
                    <p style="font-size: 24px; margin-top: 8px;"><?= (int)$pendingListings ?></p>
                </div>
                <div>
                    <strong>Vendor Applications</strong>
                    <p style="font-size: 24px; margin-top: 8px;"><?= (int)$pendingApplications ?></p>
                </div>
            </div>
        </section>

        <!-- Admin Actions -->
        <section style="margin-top: 32px;">
            <h2>Admin Tools</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-top: 16px;">
                <div class="account-section" style="padding: 24px;">
                    <h3 style="margin-bottom: 12px;">Vendor Applications</h3>
                    <p style="margin-bottom: 16px; color: #666;">Review and approve vendor applications</p>
                    <a href="../view/vendor_applications_admin.php" class="btn-primary">
                        Review Applications
                        <?php if ($pendingApplications > 0): ?>
                            <span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;">
                                <?= $pendingApplications ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="account-section" style="padding: 24px;">
                    <h3 style="margin-bottom: 12px;">Contact Messages</h3>
                    <p style="margin-bottom: 16px; color: #666;">View and respond to user messages</p>
                    <a href="../admin/contact_messages.php" class="btn-primary">
                        View Messages
                        <?php if ($newContactMessages > 0): ?>
                            <span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;">
                                <?= $newContactMessages ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="account-section" style="padding: 24px;">
                    <h3 style="margin-bottom: 12px;">Transaction Management</h3>
                    <p style="margin-bottom: 16px; color: #666;">View and manage all platform transactions</p>
                    <a href="../admin/transactions.php" class="btn-primary">Manage Transactions</a>
                </div>

                <div class="account-section" style="padding: 24px;">
                    <h3 style="margin-bottom: 12px;">Browse Books</h3>
                    <p style="margin-bottom: 16px; color: #666;">View all books on the platform</p>
                    <a href="../actions/browse_books.php" class="btn-primary">Browse Books</a>
                </div>
            </div>
        </section>

        <!-- Recent Transactions -->
        <section style="margin-top: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2>Recent Transactions</h2>
                <a href="../admin/transactions.php" class="btn-secondary">View All</a>
            </div>
            <?php if (empty($recentTransactions)): ?>
                <div class="account-section" style="padding: 40px; text-align: center;">
                    <p style="color: #666;">No transactions yet</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Transaction Code</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Book</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Buyer</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Seller</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Amount</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Payment Status</th>
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $txn): ?>
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 12px;">
                                        <strong><?= htmlspecialchars($txn['transaction_code']) ?></strong>
                                    </td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($txn['book_title']) ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($txn['buyer_username']) ?></td>
                                    <td style="padding: 12px;"><?= htmlspecialchars($txn['seller_username']) ?></td>
                                    <td style="padding: 12px;"><strong>GH₵<?= number_format($txn['total_amount'], 2) ?></strong></td>
                                    <td style="padding: 12px;">
                                        <?php
                                        $statusColors = [
                                            'pending' => 'background: #fef3c7; color: #92400e;',
                                            'completed' => 'background: #d1fae5; color: #065f46;',
                                            'failed' => 'background: #fee2e2; color: #991b1b;',
                                            'refunded' => 'background: #dbeafe; color: #1e40af;'
                                        ];
                                        $statusStyle = $statusColors[$txn['payment_status']] ?? 'background: #f3f4f6; color: #374151;';
                                        ?>
                                        <span style="padding: 4px 12px; border-radius: 12px; font-size: 0.85em; font-weight: 500; <?= $statusStyle ?>">
                                            <?= ucfirst($txn['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <small><?= date('M d, Y', strtotime($txn['created_at'])) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Quick Actions -->
        <section style="margin-top: 32px; margin-bottom: 48px;">
            <h2>Quick Actions</h2>
            <div style="margin-top: 16px;">
                <p style="color: #666; margin-bottom: 16px;">Common administrative tasks</p>
                <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <a href="../view/vendor_applications_admin.php" class="btn-primary">Review Vendor Applications</a>
                    <a href="../admin/transactions.php" class="btn-secondary">Manage Transactions</a>
                    <a href="../actions/browse_books.php" class="btn-secondary">Browse All Books</a>
                    <a href="../index.php" class="btn-secondary">View Platform</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
