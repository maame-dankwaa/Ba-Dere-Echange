<?phpini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/ContactMessage.php';

// Require admin role
AuthHelper::requireAdmin('../index.php');

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Invalid request method.';
    header('Location: ../admin/contact_messages.php');
    exit;
}

$contactModel = new ContactMessage();

// Get message ID
$messageId = (int)($_POST['message_id'] ?? 0);

// Validate input
if ($messageId <= 0) {
    $_SESSION['flash_error'] = 'Invalid message ID.';
    header('Location: ../admin/contact_messages.php?status=archived');
    exit;
}

// Get message to verify it exists
$message = $contactModel->getById($messageId);

if (!$message) {
    $_SESSION['flash_error'] = 'Message not found.';
    header('Location: ../admin/contact_messages.php?status=archived');
    exit;
}

// Unarchive the message
$success = $contactModel->unarchive($messageId);

if ($success) {
    $_SESSION['flash_success'] = 'Message restored successfully.';
} else {
    $_SESSION['flash_error'] = 'Failed to restore message. Please try again.';
}

// Redirect back to messages list
header('Location: ../admin/contact_messages.php');
exit;
