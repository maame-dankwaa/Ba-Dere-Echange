<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/FeaturedListing.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../config/settings/paystack.php';

// Require authentication
AuthHelper::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$bookId = (int)($_POST['book_id'] ?? 0);
$durationDays = (int)($_POST['duration_days'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$userId = AuthHelper::getUserId();

// Validate inputs
if ($bookId <= 0 || $durationDays <= 0 || $amount <= 0) {
    $_SESSION['flash_error'] = 'Invalid request parameters';
    header('Location: ../view/manage_listings.php');
    exit;
}

// Verify package exists
$package = FeaturedListing::getPackage($durationDays);
if (!$package) {
    $_SESSION['flash_error'] = 'Invalid package selected';
    header('Location: ../view/manage_listings.php');
    exit;
}

// Verify amount matches package price
if (abs($amount - $package['price']) > 0.01) {
    $_SESSION['flash_error'] = 'Invalid amount';
    header('Location: ../view/manage_listings.php');
    exit;
}

// Get book and verify ownership
$bookModel = new Book();
$book = $bookModel->getBookDetails($bookId);

if (!$book || (int)$book['seller_id'] !== $userId) {
    $_SESSION['flash_error'] = 'Book not found or you do not own this listing';
    header('Location: ../view/manage_listings.php');
    exit;
}

// Check if already featured
$featuredModel = new FeaturedListing();
if ($featuredModel->isFeatured($bookId)) {
    $_SESSION['flash_warning'] = 'This listing is already featured';
    header('Location: ../view/manage_listings.php');
    exit;
}

// Create featured listing transaction
try {
    error_log("Creating featured listing transaction: book_id=$bookId, user_id=$userId, duration=$durationDays, amount=$amount");
    
    $transactionId = $featuredModel->createTransaction([
        'book_id' => $bookId,
        'user_id' => $userId,
        'duration_days' => $durationDays,
        'amount_paid' => $amount,
        'payment_method' => 'paystack',
        'payment_status' => 'pending'
    ]);

    if (!$transactionId || $transactionId <= 0) {
        throw new Exception('Failed to create featured listing transaction');
    }

    error_log("Transaction created with ID: $transactionId");

    // Get user email for Paystack
    $userModel = new User();
    $user = $userModel->find($userId);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    $userEmail = $user['email'] ?? '';

    if (empty($userEmail)) {
        throw new Exception('User email not found in user record');
    }

    error_log("User email retrieved: $userEmail");

    // Initialize Paystack transaction
    error_log("Initializing Paystack payment for featured listing: amount=$amount, email=$userEmail");
    
    // Check if Paystack function exists
    if (!function_exists('paystack_initialize_transaction')) {
        throw new Exception('Paystack initialization function not found. Check paystack.php config file.');
    }
    
    $paystackResponse = paystack_initialize_transaction(
        $amount,
        $userEmail,
        'FEATURED_' . $transactionId . '_' . time()
    );

    if (!is_array($paystackResponse)) {
        throw new Exception('Invalid response from Paystack initialization. Response: ' . var_export($paystackResponse, true));
    }

    error_log("Paystack response: " . json_encode($paystackResponse));

    if ($paystackResponse['status'] ?? false) {
        // Store transaction reference in session
        $_SESSION['pending_featured_transaction_id'] = $transactionId;
        $_SESSION['pending_featured_book_id'] = $bookId;
        $_SESSION['paystack_reference'] = $paystackResponse['data']['reference'] ?? '';

        // Redirect to Paystack payment page
        $authorizationUrl = $paystackResponse['data']['authorization_url'] ?? '';
        if (!empty($authorizationUrl)) {
            error_log("Redirecting to Paystack: $authorizationUrl");
            header('Location: ' . $authorizationUrl);
            exit;
        } else {
            error_log("No authorization URL in Paystack response");
            $_SESSION['flash_error'] = 'Payment initialization failed: No authorization URL received. Response: ' . json_encode($paystackResponse);
            header('Location: ../view/feature_listing.php?book_id=' . $bookId);
            exit;
        }
    }

    // If Paystack init fails, show detailed error
    $errorMessage = $paystackResponse['message'] ?? 'Failed to initialize payment';
    $errorDetails = '';
    if (isset($paystackResponse['data']['message'])) {
        $errorDetails = ': ' . $paystackResponse['data']['message'];
    }
    
    error_log("Paystack initialization failed: $errorMessage$errorDetails");
    $_SESSION['flash_error'] = 'Payment initialization failed: ' . $errorMessage . $errorDetails;
    header('Location: ../view/feature_listing.php?book_id=' . $bookId);
    exit;

} catch (Exception $e) {
    error_log("Featured listing payment error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Show actual error in debug mode
    $errorMessage = 'An error occurred. Please try again.';
    if (defined('SHOW_DEBUG_ERRORS') && SHOW_DEBUG_ERRORS) {
        $errorMessage = 'Error: ' . htmlspecialchars($e->getMessage());
        if ($e->getPrevious()) {
            $errorMessage .= ' (Previous: ' . htmlspecialchars($e->getPrevious()->getMessage()) . ')';
        }
    }
    
    $_SESSION['flash_error'] = $errorMessage;
    header('Location: ../view/feature_listing.php?book_id=' . $bookId);
    exit;
}
