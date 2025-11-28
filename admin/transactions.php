<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../classes/User.php';

// Require admin access
AuthHelper::requireAdmin();

$transactionModel = new Transaction();
$userModel = new User();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $transactionId = (int)($_POST['transaction_id'] ?? 0);

    if ($_POST['action'] === 'update_payment_status' && $transactionId > 0) {
        $newStatus = $_POST['payment_status'] ?? '';
        $validStatuses = ['pending', 'completed', 'failed', 'refunded', 'cancelled'];

        if (in_array($newStatus, $validStatuses, true)) {
            $transactionModel->updatePaymentStatus($transactionId, $newStatus);
            $_SESSION['flash_success'] = 'Payment status updated successfully';
        }
    }

    if ($_POST['action'] === 'update_delivery_status' && $transactionId > 0) {
        $newStatus = $_POST['delivery_status'] ?? '';
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        if (in_array($newStatus, $validStatuses, true)) {
            $transactionModel->updateDeliveryStatus($transactionId, $newStatus);
            $_SESSION['flash_success'] = 'Delivery status updated successfully';
        }
    }

    if ($_POST['action'] === 'cancel_transaction' && $transactionId > 0) {
        if ($transactionModel->cancelTransaction($transactionId)) {
            $_SESSION['flash_success'] = 'Transaction cancelled successfully';
        } else {
            $_SESSION['flash_error'] = 'Failed to cancel transaction';
        }
    }

    if ($_POST['action'] === 'delete_transaction' && $transactionId > 0) {
        if ($transactionModel->deleteTransaction($transactionId)) {
            $_SESSION['flash_success'] = 'Transaction deleted successfully';
        } else {
            $_SESSION['flash_error'] = 'Failed to delete transaction';
        }
    }

    header('Location: transactions.php');
    exit;
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$paymentFilter = $_GET['payment'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// Get all transactions
$db = Database::getInstance();

$sql = "SELECT
            t.*,
            b.title as book_title,
            buyer.username as buyer_username,
            buyer.email as buyer_email,
            seller.username as seller_username,
            seller.email as seller_email
        FROM fp_transactions t
        JOIN fp_books b ON t.book_id = b.book_id
        JOIN fp_users buyer ON t.buyer_id = buyer.user_id
        JOIN fp_users seller ON t.seller_id = seller.user_id
        WHERE 1=1";

$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND t.payment_status = :status";
    $params['status'] = $statusFilter;
}

if ($paymentFilter !== 'all') {
    $sql .= " AND t.payment_method = :payment";
    $params['payment'] = $paymentFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (t.transaction_code LIKE :search
                   OR b.title LIKE :search
                   OR buyer.username LIKE :search
                   OR seller.username LIKE :search)";
    $params['search'] = "%$searchQuery%";
}

$sql .= " ORDER BY t.created_at DESC LIMIT " . (($page - 1) * $perPage) . ", $perPage";

$transactions = $db->fetchAll($sql, $params);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM fp_transactions t WHERE 1=1";
$countParams = [];
if ($statusFilter !== 'all') {
    $countSql .= " AND t.payment_status = :status";
    $countParams['status'] = $statusFilter;
}
if ($paymentFilter !== 'all') {
    $countSql .= " AND t.payment_method = :payment";
    $countParams['payment'] = $paymentFilter;
}
$totalResult = $db->fetch($countSql, $countParams);
$totalTransactions = $totalResult['total'] ?? 0;
$totalPages = ceil($totalTransactions / $perPage);

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
    <title>Transaction Management - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <?php include __DIR__ . '/../includes/sweetalert.php'; ?>
    <style>
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filters select, .filters input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-refunded { background: #dbeafe; color: #1e40af; }
        .status-processing { background: #e0e7ff; color: #3730a3; }
        .status-shipped { background: #ddd6fe; color: #5b21b6; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #f3f4f6; color: #374151; }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .transaction-table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        .transaction-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        .transaction-table tr:hover {
            background: #f9fafb;
        }
        .action-select {
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #374151;
        }
        .pagination .active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #1f2937;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.875em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <a href="../index.php" class="logo">
                    <h1>Ba Dere Exchange - Admin</h1>
                </a>
                <div class="nav-actions">
                    <a href="../admin/admin_dashboard.php" class="btn-secondary">Dashboard</a>
                    <a href="../login/logout.php" class="btn-secondary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container" style="max-width: 1400px; margin-top: 30px;">
        <h1>Transaction Management</h1>

        <?php if ($flash['success']): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
        <?php endif; ?>
        <?php if ($flash['error']): ?>
            <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
        <?php endif; ?>

        <?php
        // Calculate stats
        $statsData = $db->fetch("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN payment_status = 'completed' THEN total_amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN payment_status = 'completed' THEN commission_amount ELSE 0 END) as total_commission,
            SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count
        FROM fp_transactions", []);
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($statsData['total'] ?? 0) ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">GH₵<?= number_format($statsData['total_revenue'] ?? 0, 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">GH₵<?= number_format($statsData['total_commission'] ?? 0, 2) ?></div>
                <div class="stat-label">Platform Commission</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($statsData['pending_count'] ?? 0) ?></div>
                <div class="stat-label">Pending Payments</div>
            </div>
        </div>

        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Search transactions..."
                   value="<?= htmlspecialchars($searchQuery) ?>" style="flex: 1; min-width: 250px;">

            <select name="status" onchange="this.form.submit()">
                <option value="all">All Payment Status</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
            </select>

            <select name="payment" onchange="this.form.submit()">
                <option value="all">All Payment Methods</option>
                <option value="paystack" <?= $paymentFilter === 'paystack' ? 'selected' : '' ?>>Paystack</option>
                <option value="cash" <?= $paymentFilter === 'cash' ? 'selected' : '' ?>>Cash</option>
                <option value="mobile_money" <?= $paymentFilter === 'mobile_money' ? 'selected' : '' ?>>Mobile Money</option>
            </select>

            <button type="submit" class="btn-primary">Filter</button>
            <?php if ($statusFilter !== 'all' || $paymentFilter !== 'all' || !empty($searchQuery)): ?>
                <a href="transactions.php" class="btn-secondary">Clear Filters</a>
            <?php endif; ?>
        </form>

        <table class="transaction-table">
            <thead>
                <tr>
                    <th>Transaction Code</th>
                    <th>Book</th>
                    <th>Buyer</th>
                    <th>Seller</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
                    <th>Delivery Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px;">No transactions found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($txn['transaction_code']) ?></strong></td>
                            <td><?= htmlspecialchars($txn['book_title']) ?></td>
                            <td>
                                <?= htmlspecialchars($txn['buyer_username']) ?><br>
                                <small style="color: #6b7280;"><?= htmlspecialchars($txn['buyer_email']) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($txn['seller_username']) ?><br>
                                <small style="color: #6b7280;"><?= htmlspecialchars($txn['seller_email']) ?></small>
                            </td>
                            <td><strong>GH₵<?= number_format($txn['total_amount'], 2) ?></strong></td>
                            <td><?= ucfirst(str_replace('_', ' ', $txn['payment_method'])) ?></td>
                            <td>
                                <span class="status-badge status-<?= $txn['payment_status'] ?>">
                                    <?= ucfirst($txn['payment_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $txn['delivery_status'] ?>">
                                    <?= ucfirst($txn['delivery_status']) ?>
                                </span>
                            </td>
                            <td>
                                <small><?= date('M d, Y', strtotime($txn['created_at'])) ?></small>
                            </td>
                            <td>
                                <form method="POST" style="display: flex; gap: 5px; flex-direction: column;">
                                    <input type="hidden" name="transaction_id" value="<?= $txn['transaction_id'] ?>">

                                    <select name="payment_status" class="action-select">
                                        <option value="pending" <?= $txn['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="completed" <?= $txn['payment_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="failed" <?= $txn['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                                        <option value="refunded" <?= $txn['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                        <option value="cancelled" <?= $txn['payment_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="action" value="update_payment_status"
                                            class="btn-secondary" style="font-size: 0.8em; padding: 4px 8px;">
                                        Update Payment
                                    </button>

                                    <select name="delivery_status" class="action-select">
                                        <option value="pending" <?= $txn['delivery_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $txn['delivery_status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= $txn['delivery_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $txn['delivery_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="cancelled" <?= $txn['delivery_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="action" value="update_delivery_status"
                                            class="btn-secondary" style="font-size: 0.8em; padding: 4px 8px;">
                                        Update Delivery
                                    </button>

                                    <hr style="margin: 8px 0; border: none; border-top: 1px solid #e5e7eb;">

                                    <button type="submit" name="action" value="cancel_transaction"
                                            class="btn-secondary" style="font-size: 0.8em; padding: 4px 8px; background: #f59e0b; color: white; border: none;"
                                            onclick="return confirm('Cancel this transaction? This will mark it as cancelled but preserve the record.')">
                                        Cancel Transaction
                                    </button>

                                    <button type="submit" name="action" value="delete_transaction"
                                            class="btn-danger" style="font-size: 0.8em; padding: 4px 8px;"
                                            onclick="return confirm('Permanently delete this transaction? This action cannot be undone!')">
                                        Delete Permanently
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&status=<?= $statusFilter ?>&payment=<?= $paymentFilter ?>&search=<?= urlencode($searchQuery) ?>">← Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&status=<?= $statusFilter ?>&payment=<?= $paymentFilter ?>&search=<?= urlencode($searchQuery) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= $statusFilter ?>&payment=<?= $paymentFilter ?>&search=<?= urlencode($searchQuery) ?>">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
