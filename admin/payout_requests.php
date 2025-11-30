<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/PayoutRequest.php';
require_once __DIR__ . '/../classes/Database.php';

// Require admin role
AuthHelper::requireAdmin('../index.php');

$payoutModel = new PayoutRequest();
$db = Database::getInstance();

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// Get all payout requests
$requests = $payoutModel->getAll($statusFilter !== 'all' ? $statusFilter : null, $perPage, ($page - 1) * $perPage);

// Get statistics
$stats = $payoutModel->getStats();

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM fp_payout_requests";
$countParams = [];
if ($statusFilter !== 'all') {
    $countSql .= " WHERE request_status = :status";
    $countParams['status'] = $statusFilter;
}
$totalResult = $db->fetch($countSql, $countParams);
$totalRequests = $totalResult['total'] ?? 0;
$totalPages = ceil($totalRequests / $perPage);

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
    <title>Payout Requests - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
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
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dbeafe; color: #1e40af; }
        .status-processing { background: #e0e7ff; color: #3730a3; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-failed { background: #fee2e2; color: #991b1b; }
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
        <h1>Payout Requests</h1>

        <?php if ($flash['success']): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
        <?php endif; ?>

        <?php if ($flash['error']): ?>
            <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total'] ?? 0) ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['pending_count'] ?? 0) ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['completed_count'] ?? 0) ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">GH₵<?= number_format($stats['total_paid'] ?? 0, 2) ?></div>
                <div class="stat-label">Total Paid</div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" style="margin-bottom: 20px;">
            <select name="status" onchange="this.form.submit()">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </form>

        <!-- Payout Requests Table -->
        <table class="transaction-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Vendor</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">No payout requests found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($request['created_at'])) ?></td>
                            <td>
                                <?= htmlspecialchars($request['vendor_username']) ?><br>
                                <small style="color: #6b7280;"><?= htmlspecialchars($request['vendor_email']) ?></small>
                            </td>
                            <td><strong>GH₵<?= number_format($request['amount'], 2) ?></strong></td>
                            <td><?= ucfirst(str_replace('_', ' ', $request['payout_method'])) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($request['request_status']) ?>">
                                    <?= ucfirst($request['request_status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="../admin/view_payout_request.php?id=<?= $request['request_id'] ?>" class="btn-secondary" style="font-size: 0.875em; padding: 6px 12px;">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top: 30px;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&status=<?= $statusFilter ?>">← Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&status=<?= $statusFilter ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= $statusFilter ?>">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>

