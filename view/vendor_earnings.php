<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/PayoutRequest.php';
require_once __DIR__ . '/../classes/Database.php';

// Require vendor access
if (!AuthHelper::isLoggedIn()) {
    header('Location: ../login/login.php');
    exit;
}

if (!AuthHelper::canCreateListing()) {
    $_SESSION['flash_error'] = 'You do not have permission to access this page.';
    header('Location: ../index.php');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$payoutModel = new PayoutRequest();
$db = Database::getInstance();

// Get earnings data
$availableEarnings = $payoutModel->getAvailableEarnings($userId);
$totalEarnings = $payoutModel->getTotalEarnings($userId);
$totalPaid = $totalEarnings - $availableEarnings;

// Get recent payout requests
$payoutRequests = $payoutModel->getVendorRequests($userId, 10);

// Get recent completed transactions
$sql = "SELECT t.*, b.title as book_title
        FROM fp_transactions t
        JOIN fp_books b ON t.book_id = b.book_id
        WHERE t.seller_id = :user_id 
        AND t.payment_status = 'completed'
        ORDER BY t.created_at DESC
        LIMIT 10";
$recentTransactions = $db->fetchAll($sql, ['user_id' => $userId]);

$flash = [
    'success' => $_SESSION['flash_success'] ?? null,
    'error' => $_SESSION['flash_error'] ?? null
];
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Earnings - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
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
                    </div>
                </a>
                <div class="nav-actions">
                    <a href="../view/manage_listings.php" class="btn-secondary">Manage Listings</a>
                    <a href="../view/analytics.php" class="btn-secondary">Analytics</a>
                    <a href="../login/logout.php" class="btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container" style="max-width: 1200px; margin-top: 30px;">
        <h1>My Earnings</h1>

        <?php if ($flash['success']): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
        <?php endif; ?>

        <?php if ($flash['error']): ?>
            <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
        <?php endif; ?>

        <!-- Earnings Summary -->
        <div class="stats-grid" style="margin-bottom: 30px;">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="stat-value" style="color: white;">GH₵<?= number_format($availableEarnings, 2) ?></div>
                <div class="stat-label" style="color: rgba(255,255,255,0.9);">Available for Payout</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">GH₵<?= number_format($totalEarnings, 2) ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">GH₵<?= number_format($totalPaid, 2) ?></div>
                <div class="stat-label">Total Paid Out</div>
            </div>
        </div>

        <!-- Request Payout Button -->
        <?php if ($availableEarnings > 0): ?>
            <div style="margin-bottom: 30px;">
                <a href="../view/request_payout.php" class="btn-primary" style="font-size: 1.1em; padding: 12px 24px;">
                    Request Payout
                </a>
            </div>
        <?php else: ?>
            <div class="alert" style="background: #f3f4f6; color: #6b7280;">
                You don't have any available earnings to request payout.
            </div>
        <?php endif; ?>

        <!-- Payout Requests History -->
        <div style="background: white; padding: 24px; border-radius: 8px; margin-bottom: 30px;">
            <h2 style="margin-bottom: 20px;">Payout Requests</h2>
            <?php if (empty($payoutRequests)): ?>
                <p style="color: #6b7280;">No payout requests yet.</p>
            <?php else: ?>
                <table class="listings-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Processed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payoutRequests as $request): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($request['created_at'])) ?></td>
                                <td><strong>GH₵<?= number_format($request['amount'], 2) ?></strong></td>
                                <td><?= ucfirst(str_replace('_', ' ', $request['payout_method'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($request['request_status']) ?>">
                                        <?= ucfirst($request['request_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($request['processed_at']): ?>
                                        <?= date('M d, Y', strtotime($request['processed_at'])) ?>
                                        <?php if ($request['processed_by_name']): ?>
                                            <br><small>by <?= htmlspecialchars($request['processed_by_name']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Recent Transactions -->
        <div style="background: white; padding: 24px; border-radius: 8px;">
            <h2 style="margin-bottom: 20px;">Recent Completed Transactions</h2>
            <?php if (empty($recentTransactions)): ?>
                <p style="color: #6b7280;">No completed transactions yet.</p>
            <?php else: ?>
                <table class="listings-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Book</th>
                            <th>Amount</th>
                            <th>Your Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $txn): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($txn['created_at'])) ?></td>
                                <td><?= htmlspecialchars($txn['book_title']) ?></td>
                                <td>GH₵<?= number_format($txn['total_amount'], 2) ?></td>
                                <td><strong>GH₵<?= number_format($txn['seller_amount'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>

