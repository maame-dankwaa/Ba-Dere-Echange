<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/PayoutRequest.php';

// Require admin role
AuthHelper::requireAdmin('../index.php');

$payoutModel = new PayoutRequest();

// Get request ID
$requestId = (int)($_GET['id'] ?? 0);

if ($requestId <= 0) {
    $_SESSION['flash_error'] = 'Invalid request ID.';
    header('Location: ../admin/payout_requests.php');
    exit;
}

// Get request details
$request = $payoutModel->getById($requestId);

if (!$request) {
    $_SESSION['flash_error'] = 'Payout request not found.';
    header('Location: ../admin/payout_requests.php');
    exit;
}

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
    <title>View Payout Request - Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .detail-section {
            background: white;
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #374151;
            width: 200px;
        }
        .detail-value {
            color: #6b7280;
            flex: 1;
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
                    <a href="../admin/payout_requests.php" class="btn-secondary">Back to Requests</a>
                    <a href="../login/logout.php" class="btn-secondary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container" style="max-width: 1000px; margin-top: 30px;">
        <h1>Payout Request Details</h1>

        <?php if ($flash['success']): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
        <?php endif; ?>

        <?php if ($flash['error']): ?>
            <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
        <?php endif; ?>

        <!-- Request Information -->
        <div class="detail-section">
            <h2 style="margin-bottom: 20px;">Request Information</h2>
            <div class="detail-row">
                <div class="detail-label">Request ID</div>
                <div class="detail-value">#<?= $request['request_id'] ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date</div>
                <div class="detail-value"><?= date('F d, Y \a\t g:i A', strtotime($request['created_at'])) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Vendor</div>
                <div class="detail-value">
                    <?= htmlspecialchars($request['vendor_username']) ?><br>
                    <small><?= htmlspecialchars($request['vendor_email']) ?></small>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Amount</div>
                <div class="detail-value"><strong style="font-size: 1.2em; color: #1f2937;">GH₵<?= number_format($request['amount'], 2) ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Payout Method</div>
                <div class="detail-value"><?= ucfirst(str_replace('_', ' ', $request['payout_method'])) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <span class="status-badge status-<?= strtolower($request['request_status']) ?>">
                        <?= ucfirst($request['request_status']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Account Details -->
        <div class="detail-section">
            <h2 style="margin-bottom: 20px;">Account Details</h2>
            <?php
            $accountDetails = $request['account_details'] ?? [];
            foreach ($accountDetails as $key => $value):
            ?>
                <div class="detail-row">
                    <div class="detail-label"><?= ucfirst(str_replace('_', ' ', $key)) ?></div>
                    <div class="detail-value"><?= htmlspecialchars($value) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Processing Information -->
        <?php if ($request['processed_at']): ?>
            <div class="detail-section">
                <h2 style="margin-bottom: 20px;">Processing Information</h2>
                <div class="detail-row">
                    <div class="detail-label">Processed At</div>
                    <div class="detail-value"><?= date('F d, Y \a\t g:i A', strtotime($request['processed_at'])) ?></div>
                </div>
                <?php if ($request['processed_by_name']): ?>
                    <div class="detail-row">
                        <div class="detail-label">Processed By</div>
                        <div class="detail-value"><?= htmlspecialchars($request['processed_by_name']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($request['paystack_transfer_code']): ?>
                    <div class="detail-row">
                        <div class="detail-label">Paystack Transfer Code</div>
                        <div class="detail-value"><?= htmlspecialchars($request['paystack_transfer_code']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($request['transaction_reference']): ?>
                    <div class="detail-row">
                        <div class="detail-label">Transaction Reference</div>
                        <div class="detail-value"><?= htmlspecialchars($request['transaction_reference']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($request['rejection_reason']): ?>
                    <div class="detail-row">
                        <div class="detail-label">Rejection Reason</div>
                        <div class="detail-value" style="color: #dc2626;"><?= htmlspecialchars($request['rejection_reason']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($request['failure_reason']): ?>
                    <div class="detail-row">
                        <div class="detail-label">Failure Reason</div>
                        <div class="detail-value" style="color: #dc2626;"><?= htmlspecialchars($request['failure_reason']) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="detail-section">
            <h2 style="margin-bottom: 20px;">Actions</h2>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <?php if ($request['request_status'] === 'pending'): ?>
                    <form action="../admin/process_payout.php" method="POST" style="display: inline;">
                        <input type="hidden" name="request_id" value="<?= $requestId ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn-primary">Approve & Process</button>
                    </form>
                    <form action="../admin/process_payout.php" method="POST" style="display: inline;">
                        <input type="hidden" name="request_id" value="<?= $requestId ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn-danger" onclick="return confirm('Are you sure you want to reject this payout request?')">Reject</button>
                    </form>
                <?php elseif ($request['request_status'] === 'approved'): ?>
                    <form action="../admin/process_payout.php" method="POST" style="display: inline;">
                        <input type="hidden" name="request_id" value="<?= $requestId ?>">
                        <input type="hidden" name="action" value="process">
                        <button type="submit" class="btn-primary">Process via Paystack</button>
                    </form>
                <?php endif; ?>
                <a href="../admin/payout_requests.php" class="btn-secondary">Back to Requests</a>
            </div>
        </div>
    </main>
</body>
</html>

