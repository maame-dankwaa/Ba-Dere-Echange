<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../helpers/AuthHelper.php';
    require_once __DIR__ . '/../classes/VendorApplication.php';

    // Require admin role
    AuthHelper::requireAdmin('../index.php');

    $applicationModel = new VendorApplication();

    // Get filter from query string
    $filter = $_GET['status'] ?? 'pending';
    $validFilters = ['pending', 'approved', 'rejected', 'all'];
    if (!in_array($filter, $validFilters)) {
        $filter = 'pending';
    }

    // Get applications based on filter
    if ($filter === 'all') {
        $applications = $applicationModel->getAllApplications();
    } else {
        $applications = $applicationModel->getByStatus($filter);
    }

    // Get statistics
    $stats = $applicationModel->getStatistics();

    // Get success/error messages
    $successMessage = $_SESSION['success_message'] ?? '';
    $errorMessage = $_SESSION['error_message'] ?? '';
    unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Applications - Admin - Ba Dɛre Exchange</title>
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
                        <p>Admin Panel</p>
                    </div>
                </a>
                <div class="nav-actions">
                    <a href="../admin/admin_dashboard.php" class="btn-secondary">Dashboard</a>
                    <a href="user_account.php" class="btn-secondary">My Account</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container" style="margin-top: 32px; margin-bottom: 48px;">
        <h1>Vendor Applications</h1>

        <?php if (!empty($successMessage)): ?>
            <div class="alert" style="margin-top: 16px; padding: 16px; background: #d4edda; border: 1px solid #28a745; border-radius: 8px; color: #155724;">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error" style="margin-top: 16px;">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="account-stats" style="margin-top: 24px;">
            <div>
                <strong>Total Applications</strong>
                <p style="font-size: 24px; margin-top: 8px;"><?= (int)$stats['total_applications'] ?></p>
            </div>
            <div>
                <strong>Pending</strong>
                <p style="font-size: 24px; margin-top: 8px;"><?= (int)$stats['pending_count'] ?></p>
            </div>
            <div>
                <strong>Approved</strong>
                <p style="font-size: 24px; margin-top: 8px;"><?= (int)$stats['approved_count'] ?></p>
            </div>
            <div>
                <strong>Rejected</strong>
                <p style="font-size: 24px; margin-top: 8px;"><?= (int)$stats['rejected_count'] ?></p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div style="margin-top: 32px; border-bottom: 2px solid #eee;">
            <div style="display: flex; gap: 16px;">
                <a href="?status=pending"
                   style="padding: 12px 16px; text-decoration: none; border-bottom: 2px solid <?= $filter === 'pending' ? '#007bff' : 'transparent' ?>; color: <?= $filter === 'pending' ? '#007bff' : '#666' ?>; font-weight: <?= $filter === 'pending' ? 'bold' : 'normal' ?>; margin-bottom: -2px;">
                    Pending (<?= (int)$stats['pending_count'] ?>)
                </a>
                <a href="?status=approved"
                   style="padding: 12px 16px; text-decoration: none; border-bottom: 2px solid <?= $filter === 'approved' ? '#007bff' : 'transparent' ?>; color: <?= $filter === 'approved' ? '#007bff' : '#666' ?>; font-weight: <?= $filter === 'approved' ? 'bold' : 'normal' ?>; margin-bottom: -2px;">
                    Approved (<?= (int)$stats['approved_count'] ?>)
                </a>
                <a href="?status=rejected"
                   style="padding: 12px 16px; text-decoration: none; border-bottom: 2px solid <?= $filter === 'rejected' ? '#007bff' : 'transparent' ?>; color: <?= $filter === 'rejected' ? '#007bff' : '#666' ?>; font-weight: <?= $filter === 'rejected' ? 'bold' : 'normal' ?>; margin-bottom: -2px;">
                    Rejected (<?= (int)$stats['rejected_count'] ?>)
                </a>
                <a href="?status=all"
                   style="padding: 12px 16px; text-decoration: none; border-bottom: 2px solid <?= $filter === 'all' ? '#007bff' : 'transparent' ?>; color: <?= $filter === 'all' ? '#007bff' : '#666' ?>; font-weight: <?= $filter === 'all' ? 'bold' : 'normal' ?>; margin-bottom: -2px;">
                    All
                </a>
            </div>
        </div>

        <!-- Applications List -->
        <section style="margin-top: 32px;">
            <?php if (empty($applications)): ?>
                <p style="color: #666;">No <?= $filter !== 'all' ? $filter : '' ?> applications found.</p>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <div class="account-section" style="margin-bottom: 24px; padding: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                            <div>
                                <h3 style="margin-bottom: 8px;"><?= htmlspecialchars($app['username']) ?></h3>
                                <p style="color: #666; margin-bottom: 4px;">
                                    <strong>Email:</strong> <?= htmlspecialchars($app['email']) ?>
                                </p>
                                <p style="color: #666; margin-bottom: 4px;">
                                    <strong>Phone:</strong> <?= htmlspecialchars($app['phone'] ?? $app['user_phone'] ?? 'N/A') ?>
                                </p>
                                <p style="color: #666; margin-bottom: 4px;">
                                    <strong>Location:</strong> <?= htmlspecialchars($app['location'] ?? 'N/A') ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <span style="padding: 6px 12px; border-radius: 4px; font-size: 14px; font-weight: bold; background: <?php
                                    echo $app['status'] === 'pending' ? '#fff3cd' :
                                         ($app['status'] === 'approved' ? '#d4edda' : '#f8d7da');
                                ?>; color: <?php
                                    echo $app['status'] === 'pending' ? '#856404' :
                                         ($app['status'] === 'approved' ? '#155724' : '#721c24');
                                ?>;">
                                    <?= htmlspecialchars(ucfirst($app['status'])) ?>
                                </span>
                                <p style="margin-top: 8px; font-size: 14px; color: #666;">
                                    Applied: <?= date('M d, Y', strtotime($app['created_at'])) ?>
                                </p>
                            </div>
                        </div>

                        <?php if (!empty($app['business_name'])): ?>
                            <p style="margin-bottom: 8px;">
                                <strong>Business Name:</strong> <?= htmlspecialchars($app['business_name']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($app['business_description'])): ?>
                            <p style="margin-bottom: 8px;">
                                <strong>Business Description:</strong><br>
                                <?= nl2br(htmlspecialchars($app['business_description'])) ?>
                            </p>
                        <?php endif; ?>

                        <p style="margin-bottom: 8px;">
                            <strong>Reason for Application:</strong><br>
                            <?= nl2br(htmlspecialchars($app['application_reason'])) ?>
                        </p>

                        <?php if (!empty($app['id_document'])): ?>
                            <p style="margin-bottom: 8px;">
                                <strong>ID Document:</strong>
                                <a href="../<?= htmlspecialchars($app['id_document']) ?>" target="_blank" class="btn-secondary" style="display: inline-block; padding: 4px 12px; margin-left: 8px;">
                                    View Document
                                </a>
                            </p>
                        <?php endif; ?>

                        <?php if ($app['status'] === 'rejected' && !empty($app['rejection_reason'])): ?>
                            <div style="margin-top: 16px; padding: 12px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">
                                <strong>Rejection Reason:</strong><br>
                                <?= nl2br(htmlspecialchars($app['rejection_reason'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($app['status'] !== 'pending'): ?>
                            <p style="margin-top: 12px; font-size: 14px; color: #666;">
                                Reviewed by <?= htmlspecialchars($app['reviewer_username'] ?? 'N/A') ?>
                                on <?= date('M d, Y', strtotime($app['reviewed_at'])) ?>
                            </p>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <?php if ($app['status'] === 'pending'): ?>
                            <div style="margin-top: 20px; display: flex; gap: 12px;">
                                <form action="../actions/approve_vendor_application.php" method="post" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                                    <button type="submit" class="btn-primary" onclick="return confirm('Are you sure you want to approve this application?');">
                                        Approve Application
                                    </button>
                                </form>
                                <button type="button" class="btn-danger" onclick="showRejectModal(<?= $app['application_id'] ?>)">
                                    Reject Application
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <!-- Reject Modal -->
    <div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 32px; border-radius: 8px; max-width: 500px; width: 90%;">
            <h2 style="margin-bottom: 16px;">Reject Application</h2>
            <form id="rejectForm" action="../actions/reject_vendor_application.php" method="post">
                <input type="hidden" name="application_id" id="rejectApplicationId">
                <label class="field-label">Reason for Rejection (Optional)</label>
                <textarea name="rejection_reason" class="field-textarea" rows="5"
                          placeholder="Provide a reason for rejecting this application..."></textarea>
                <div style="margin-top: 20px; display: flex; gap: 12px;">
                    <button type="button" class="btn-secondary" onclick="hideRejectModal()">Cancel</button>
                    <button type="submit" class="btn-danger">Reject Application</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showRejectModal(applicationId) {
            document.getElementById('rejectApplicationId').value = applicationId;
            document.getElementById('rejectModal').style.display = 'block';
        }

        function hideRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            document.getElementById('rejectApplicationId').value = '';
        }

        // Close modal when clicking outside
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideRejectModal();
            }
        });
    </script>
</body>
</html>
