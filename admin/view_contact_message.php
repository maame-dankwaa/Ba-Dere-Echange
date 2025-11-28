<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/ContactMessage.php';

// Require admin role
AuthHelper::requireAdmin('../index.php');

$contactModel = new ContactMessage();

// Get message ID
$messageId = (int)($_GET['id'] ?? 0);

if ($messageId <= 0) {
    $_SESSION['flash_error'] = 'Invalid message ID.';
    header('Location: ../admin/contact_messages.php');
    exit;
}

// Get message details
$message = $contactModel->getById($messageId);

if (!$message) {
    $_SESSION['flash_error'] = 'Message not found.';
    header('Location: ../admin/contact_messages.php');
    exit;
}

// Mark as read if it's new
if ($message['status'] === 'new') {
    $contactModel->updateStatus($messageId, 'read');
    $message['status'] = 'read';
}

$flash = ['success' => $_SESSION['flash_success'] ?? null, 'error' => $_SESSION['flash_error'] ?? null];
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Message - Admin - Ba Dɛre Exchange</title>
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
                    <a href="../admin/contact_messages.php" class="btn-secondary">Back to Messages</a>
                    <a href="../login/logout.php" class="btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="content-page" style="max-width: 1000px;">
            <h1>Contact Message</h1>

            <?php if ($flash['success']): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
            <?php endif; ?>

            <?php if ($flash['error']): ?>
                <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>

            <div class="message-detail">
                <!-- Message Header -->
                <div class="message-header">
                    <h2><?= htmlspecialchars($message['subject']) ?></h2>
                    <span class="status-badge status-<?= strtolower($message['status']) ?>">
                        <?= ucfirst($message['status']) ?>
                    </span>

                    <div class="message-meta">
                        <div class="message-meta-item">
                            <label>From</label>
                            <span><?= htmlspecialchars($message['name']) ?></span>
                        </div>
                        <div class="message-meta-item">
                            <label>Email</label>
                            <span><?= htmlspecialchars($message['email']) ?></span>
                        </div>
                        <?php if ($message['username']): ?>
                        <div class="message-meta-item">
                            <label>Username</label>
                            <span>@<?= htmlspecialchars($message['username']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="message-meta-item">
                            <label>Received</label>
                            <span><?= date('F d, Y \a\t g:i A', strtotime($message['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Message Content -->
                <div class="message-content">
                    <h3>Message</h3>
                    <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                </div>

                <!-- Admin Response Section -->
                <?php if ($message['admin_response']): ?>
                    <div class="response-section">
                        <h3>Your Response</h3>
                        <div class="admin-response-display">
                            <p><?= nl2br(htmlspecialchars($message['admin_response'])) ?></p>
                            <div class="admin-response-meta">
                                Responded on <?= date('F d, Y \a\t g:i A', strtotime($message['responded_at'])) ?>
                                by <?= htmlspecialchars($message['responded_by_name'] ?? 'Admin') ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="response-section">
                        <h3>Send Response</h3>
                        <form action="../admin/respond_contact_message.php" method="POST" class="response-form">
                            <input type="hidden" name="message_id" value="<?= $messageId ?>">

                            <textarea name="response" required placeholder="Type your response here..."></textarea>

                            <div class="response-actions">
                                <button type="submit" class="btn-primary">Send Response</button>
                                <a href="../admin/contact_messages.php" class="btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                    <h3 style="margin-bottom: 16px;">Actions</h3>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <?php if ($message['status'] !== 'archived'): ?>
                            <form action="../admin/archive_contact_message.php" method="POST" style="display: inline;">
                                <input type="hidden" name="message_id" value="<?= $messageId ?>">
                                <button type="submit" class="btn-secondary" onclick="return confirm('Archive this message?');">
                                    Archive Message
                                </button>
                            </form>
                        <?php endif; ?>

                        <a href="mailto:<?= htmlspecialchars($message['email']) ?>" class="btn-secondary">
                            Email Directly
                        </a>

                        <a href="../admin/contact_messages.php" class="btn-secondary">
                            Back to Messages
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
