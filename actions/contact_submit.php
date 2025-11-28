<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/ContactMessage.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../view/contact.php');
    exit;
}

// Get form data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate inputs
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address';
}

if (empty($subject)) {
    $errors[] = 'Subject is required';
}

if (empty($message)) {
    $errors[] = 'Message is required';
}

if (!empty($errors)) {
    $_SESSION['flash_error'] = implode('. ', $errors);
    header('Location: ../view/contact.php');
    exit;
}

// Create contact message
try {
    $contactMessage = new ContactMessage();

    $data = [
        'user_id' => AuthHelper::isLoggedIn() ? (int)$_SESSION['user_id'] : null,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
    ];

    $messageId = $contactMessage->create($data);

    if ($messageId > 0) {
        $_SESSION['flash_success'] = 'Thank you for contacting us! We will respond to your message shortly.';
    } else {
        $_SESSION['flash_error'] = 'Failed to send message. Please try again.';
    }
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'An error occurred while sending your message. Please try again later.';
}

header('Location: ../view/contact.php');
exit;
