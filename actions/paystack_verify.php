<?php
/**
 * Paystack Payment Verification Endpoint
 * Verifies payment with Paystack and updates transaction status
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../config/settings/paystack.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../classes/FeaturedListing.php';

// Check if user is logged in
if (!AuthHelper::isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$reference = $input['reference'] ?? '';

error_log("=== PAYSTACK VERIFY ===");
error_log("Reference: $reference");

if (empty($reference)) {
    echo json_encode(['status' => 'error', 'message' => 'No reference provided']);
    exit();
}

try {
    // Verify transaction with Paystack
    $verifyResult = paystack_verify_transaction($reference);

    error_log("Paystack verification result: " . json_encode($verifyResult));

    if (!$verifyResult || !isset($verifyResult['status'])) {
        throw new Exception('Invalid response from Paystack');
    }

    if ($verifyResult['status'] !== true) {
        throw new Exception('Payment verification failed');
    }

    $paystackData = $verifyResult['data'] ?? [];
    $paymentStatus = $paystackData['status'] ?? '';
    $amountPaid = ($paystackData['amount'] ?? 0) / 100; // Convert from pesewas to cedis

    error_log("Payment status: $paymentStatus, Amount: $amountPaid");

    if ($paymentStatus !== 'success') {
        throw new Exception('Payment was not successful');
    }

    // Check if this is a featured listing payment or regular transaction
    $featuredTransactionId = $_SESSION['pending_featured_transaction_id'] ?? null;
    $regularTransactionId = $_SESSION['pending_transaction_id'] ?? null;

    if ($featuredTransactionId) {
        // Handle featured listing payment
        error_log("Updating featured listing transaction ID: $featuredTransactionId");

        $featuredModel = new FeaturedListing();
        $updated = $featuredModel->updatePaymentStatus($featuredTransactionId, 'completed', $reference);

        if (!$updated) {
            throw new Exception('Failed to update featured listing status');
        }

        // Clear session data
        $bookId = $_SESSION['pending_featured_book_id'] ?? null;
        unset($_SESSION['pending_featured_transaction_id']);
        unset($_SESSION['pending_featured_book_id']);
        unset($_SESSION['paystack_reference']);

        error_log("Featured listing payment verified and activated");

        echo json_encode([
            'status' => 'success',
            'verified' => true,
            'message' => 'Featured listing activated successfully',
            'transaction_id' => $featuredTransactionId,
            'book_id' => $bookId,
            'amount' => $amountPaid,
            'type' => 'featured_listing'
        ]);

    } elseif ($regularTransactionId) {
        // Handle regular book purchase transaction
        error_log("Updating transaction ID: $regularTransactionId");

        $transactionModel = new Transaction();
        $updated = $transactionModel->updatePaymentStatus($regularTransactionId, 'completed', $reference);

        if (!$updated) {
            throw new Exception('Failed to update transaction status');
        }

        // Clear session data
        unset($_SESSION['pending_transaction_id']);
        unset($_SESSION['paystack_reference']);

        error_log("Payment verified and transaction updated successfully");

        echo json_encode([
            'status' => 'success',
            'verified' => true,
            'message' => 'Payment verified successfully',
            'transaction_id' => $regularTransactionId,
            'amount' => $amountPaid,
            'type' => 'purchase'
        ]);

    } else {
        throw new Exception('No pending transaction found in session');
    }

} catch (Exception $e) {
    error_log("Verification error: " . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => $e->getMessage()
    ]);
}
