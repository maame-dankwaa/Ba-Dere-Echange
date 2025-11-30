<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/PayoutRequest.php';
require_once __DIR__ . '/../config/settings/paystack.php';

// Require admin role
AuthHelper::requireAdmin('../index.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/payout_requests.php');
    exit;
}

$payoutModel = new PayoutRequest();
$adminId = (int)($_SESSION['user_id'] ?? 0);
$requestId = (int)($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

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

try {
    if ($action === 'approve') {
        // Approve the request
        $payoutModel->updateStatus($requestId, 'approved', $adminId);
        $_SESSION['flash_success'] = 'Payout request approved. You can now process it.';
        header("Location: ../admin/view_payout_request.php?id=$requestId");
        exit;

    } elseif ($action === 'reject') {
        // Reject the request
        $reason = trim($_POST['rejection_reason'] ?? '');
        $payoutModel->updateStatus($requestId, 'rejected', $adminId, $reason);
        $_SESSION['flash_success'] = 'Payout request rejected.';
        header("Location: ../admin/view_payout_request.php?id=$requestId");
        exit;

    } elseif ($action === 'process') {
        // Process the payout via Paystack
        $accountDetails = $request['account_details'] ?? [];
        
        // Validate account details
        if (empty($accountDetails)) {
            throw new Exception('Account details are missing.');
        }

        // Create transfer recipient
        $recipientType = $accountDetails['type'] ?? 'mobile_money';
        $recipientName = $accountDetails['name'] ?? $accountDetails['account_name'] ?? '';
        $accountNumber = $accountDetails['account_number'] ?? $accountDetails['phone'] ?? '';
        $bankCode = $accountDetails['bank_code'] ?? $accountDetails['network'] ?? '';

        if (empty($recipientName) || empty($accountNumber) || empty($bankCode)) {
            throw new Exception('Incomplete account details. Please ensure name, account number, and bank code/network are provided.');
        }

        // Update status to processing
        $payoutModel->updateStatus($requestId, 'processing', $adminId);

        // Create Paystack transfer recipient
        $recipientResponse = paystack_create_transfer_recipient(
            $recipientType,
            $recipientName,
            $accountNumber,
            $bankCode,
            'GHS'
        );

        if (!($recipientResponse['status'] ?? false)) {
            $errorMsg = $recipientResponse['message'] ?? 'Failed to create transfer recipient';
            $payoutModel->updateStatus($requestId, 'failed', $adminId, $errorMsg);
            throw new Exception('Failed to create transfer recipient: ' . $errorMsg);
        }

        $recipientCode = $recipientResponse['data']['code'] ?? null;
        if (!$recipientCode) {
            $payoutModel->updateStatus($requestId, 'failed', $adminId, 'No recipient code received from Paystack');
            throw new Exception('No recipient code received from Paystack');
        }

        // Initiate transfer
        $transferReference = 'PAYOUT_' . $requestId . '_' . time();
        $transferResponse = paystack_initiate_transfer(
            $recipientCode,
            $request['amount'],
            'Vendor payout - Request #' . $requestId,
            $transferReference
        );

        if (!($transferResponse['status'] ?? false)) {
            $errorMsg = $transferResponse['message'] ?? 'Failed to initiate transfer';
            $payoutModel->updateStatus($requestId, 'failed', $adminId, $errorMsg);
            throw new Exception('Failed to initiate transfer: ' . $errorMsg);
        }

        $transferCode = $transferResponse['data']['transfer_code'] ?? $transferResponse['data']['code'] ?? null;
        if (!$transferCode) {
            $payoutModel->updateStatus($requestId, 'failed', $adminId, 'No transfer code received from Paystack');
            throw new Exception('No transfer code received from Paystack');
        }

        // Update request with transfer code
        $payoutModel->updateTransferCode($requestId, $transferCode, $transferReference);

        // Check transfer status
        $verifyResponse = paystack_verify_transfer($transferCode);
        
        if ($verifyResponse['status'] ?? false) {
            $transferStatus = $verifyResponse['data']['status'] ?? 'pending';
            
            if ($transferStatus === 'success' || $transferStatus === 'successful') {
                $payoutModel->updateStatus($requestId, 'completed', $adminId);
                $_SESSION['flash_success'] = 'Payout processed successfully via Paystack.';
            } else {
                // Transfer is pending or processing
                $_SESSION['flash_success'] = 'Payout initiated successfully. Status: ' . $transferStatus;
            }
        } else {
            // Transfer initiated but verification failed - still mark as processing
            $_SESSION['flash_success'] = 'Payout initiated. Please verify the transfer status manually.';
        }

        header("Location: ../admin/view_payout_request.php?id=$requestId");
        exit;

    } else {
        $_SESSION['flash_error'] = 'Invalid action.';
        header("Location: ../admin/view_payout_request.php?id=$requestId");
        exit;
    }

} catch (Exception $e) {
    error_log('Payout processing error: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
    header("Location: ../admin/view_payout_request.php?id=$requestId");
    exit;
}

