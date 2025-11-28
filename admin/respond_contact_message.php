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

// Get form data
$messageId = (int)($_POST['message_id'] ?? 0);
$response = trim($_POST['response'] ?? '');

// Validate inputs
if ($messageId <= 0) {
    $_SESSION['flash_error'] = 'Invalid message ID.';
    header('Location: ../admin/contact_messages.php');
    exit;
}

if (empty($response)) {
    $_SESSION['flash_error'] = 'Response cannot be empty.';
    header("Location: ../admin/view_contact_message.php?id=$messageId");
    exit;
}

// Get message to verify it exists
$message = $contactModel->getById($messageId);

if (!$message) {
    $_SESSION['flash_error'] = 'Message not found.';
    header('Location: ../admin/contact_messages.php');
    exit;
}

// Check if already responded
if ($message['admin_response']) {
    $_SESSION['flash_error'] = 'This message has already been responded to.';
    header("Location: ../admin/view_contact_message.php?id=$messageId");
    exit;
}

// Add response
$adminId = (int)$_SESSION['user_id'];
$success = $contactModel->addResponse($messageId, $response, $adminId);

if ($success) {
    $_SESSION['flash_success'] = 'Response sent successfully.';
} else {
    $_SESSION['flash_error'] = 'Failed to send response. Please try again.';
}

header("Location: ../admin/view_contact_message.php?id=$messageId");
exit;
