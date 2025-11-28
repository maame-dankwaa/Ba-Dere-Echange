<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/ContactMessage.php';

// Require admin role
AuthHelper::requireAdmin('../index.php');

$contactModel = new ContactMessage();

// Handle status filter
$status = $_GET['status'] ?? '';
$validStatuses = ['', 'new', 'read', 'responded', 'archived'];
if (!in_array($status, $validStatuses)) {
    $status = '';
}

// Get all messages
$messages = $contactModel->getAll($status);

// Get statistics
$stats = $contactModel->getStats();

$flash = ['success' => $_SESSION['flash_success'] ?? null, 'error' => $_SESSION['flash_error'] ?? null];
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Messages - Admin - Ba Dɛre Exchange</title>
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
                    <a href="../admin/admin_dashboard.php" class="btn-secondary">Dashboard</a>
                    <a href="../login/logout.php" class="btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="manage-listings-page">
            <div class="page-header">
                <h1>Contact Messages</h1>
                <div class="header-actions">
                    <a href="../admin/admin_dashboard.php" class="btn-secondary">Back to Dashboard</a>
                </div>
            </div>

            <?php if ($flash['success']): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
            <?php endif; ?>

            <?php if ($flash['error']): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e3f2fd;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1976d2" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['total']) ?></h3>
                        <p>Total Messages</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff3e0;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['new_count']) ?></h3>
                        <p>New Messages</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #e8f5e9;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#388e3c" stroke-width="2">
                            <polyline points="20 6 9 17 4 12" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['responded_count']) ?></h3>
                        <p>Responded</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fce4ec;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c2185b" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($stats['read_count']) ?></h3>
                        <p>Read</p>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs" style="margin: 30px 0;">
                <a href="?status=" class="filter-tab <?= $status === '' ? 'active' : '' ?>">All</a>
                <a href="?status=new" class="filter-tab <?= $status === 'new' ? 'active' : '' ?>">New</a>
                <a href="?status=read" class="filter-tab <?= $status === 'read' ? 'active' : '' ?>">Read</a>
                <a href="?status=responded" class="filter-tab <?= $status === 'responded' ? 'active' : '' ?>">Responded</a>
                <a href="?status=archived" class="filter-tab <?= $status === 'archived' ? 'active' : '' ?>">Archived</a>
            </div>

            <!-- Messages Table -->
            <div class="listings-section">
                <h2>Messages</h2>

                <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <h3>No messages found</h3>
                        <p>There are no <?= $status ? $status : '' ?> messages at this time</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="listings-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $msg): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($msg['created_at'])) ?></td>
                                        <td>
                                            <?= htmlspecialchars($msg['name']) ?>
                                            <?php if ($msg['username']): ?>
                                                <br><small style="color: var(--text-secondary);">@<?= htmlspecialchars($msg['username']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($msg['email']) ?></td>
                                        <td><?= htmlspecialchars($msg['subject']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($msg['status']) ?>">
                                                <?= ucfirst($msg['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="../admin/view_contact_message.php?id=<?= $msg['message_id'] ?>"
                                                   class="btn-sm btn-view" title="View & Respond">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                        <circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                </a>

                                                <?php if ($msg['status'] === 'archived'): ?>
                                                    <!-- Unarchive button -->
                                                    <form method="POST" action="../admin/unarchive_contact_message.php" style="display: inline;">
                                                        <input type="hidden" name="message_id" value="<?= $msg['message_id'] ?>">
                                                        <button type="submit" class="btn-sm" style="background: #10b981; color: white; border: none;" title="Restore message">
                                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                                                <polyline points="9 22 9 12 15 12 15 22"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
