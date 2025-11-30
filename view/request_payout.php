<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../classes/PayoutRequest.php';

// Require vendor access
if (!AuthHelper::isLoggedIn()) {
    header('Location: ../login/login.php');
    exit;
}

if (!AuthHelper::canCreateListing()) {
    $_SESSION['flash_error'] = 'You do not have permission to access this page.';
    header('Location: ../index.php');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$payoutModel = new PayoutRequest();

// Get available earnings
$availableEarnings = $payoutModel->getAvailableEarnings($userId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    // DEBUG: Show all POST data
    $debugInfo = '<div style="background: #f3f4f6; padding: 20px; margin: 20px 0; border: 2px solid #dc2626; border-radius: 8px;">';
    $debugInfo .= '<h3 style="color: #dc2626; margin-top: 0;">DEBUG INFO:</h3>';
    $debugInfo .= '<strong>All POST data:</strong><pre>' . htmlspecialchars(print_r($_POST, true)) . '</pre>';
    $debugInfo .= '<strong>POST keys:</strong> ' . implode(', ', array_keys($_POST)) . '<br>';
    
    $amount = (float)($_POST['amount'] ?? 0);
    $payoutMethod = $_POST['payout_method'] ?? 'paystack';
    
    $debugInfo .= '<strong>Payout Method:</strong> ' . htmlspecialchars($payoutMethod) . '<br>';
    $debugInfo .= '<strong>Amount:</strong> ' . $amount . '<br>';
    
    $accountDetails = [];

    // Validate amount
    if ($amount <= 0) {
        $_SESSION['flash_error'] = 'Invalid amount.';
        $_SESSION['debug_info'] = $debugInfo;
        header('Location: request_payout.php');
        exit;
    }

    if ($amount > $availableEarnings) {
        $_SESSION['flash_error'] = 'Amount exceeds available earnings.';
        $_SESSION['debug_info'] = $debugInfo;
        header('Location: request_payout.php');
        exit;
    }

    // Collect account details based on method
    // IMPORTANT: Fields in display:none containers aren't submitted by browsers
    // So we need to check ALL possible field names regardless of method
    if ($payoutMethod === 'paystack') {
        $accountDetails = [
            'type' => trim($_POST['account_type'] ?? 'mobile_money'),
            'bank_code' => trim($_POST['bank_code'] ?? ''),
        ];
        
        $debugInfo .= '<strong>Paystack Account Details:</strong><pre>' . htmlspecialchars(print_r($accountDetails, true)) . '</pre>';
        $debugInfo .= '<strong>bank_code value:</strong> "' . htmlspecialchars($_POST['bank_code'] ?? 'NOT SET') . '"<br>';
    } elseif ($payoutMethod === 'mobile_money') {
        $accountDetails = [
            'phone' => trim($_POST['phone'] ?? ''),
            'network' => trim($_POST['network'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
        ];
        $debugInfo .= '<strong>Mobile Money Account Details:</strong><pre>' . htmlspecialchars(print_r($accountDetails, true)) . '</pre>';
    } elseif ($payoutMethod === 'bank_transfer') {
        $accountDetails = [
            'account_name' => trim($_POST['account_name'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'bank_code' => trim($_POST['bank_code'] ?? ''),
        ];
        $debugInfo .= '<strong>Bank Transfer Account Details:</strong><pre>' . htmlspecialchars(print_r($accountDetails, true)) . '</pre>';
    }

    // Validate required fields with detailed error messages
    $missingFields = [];
    if ($payoutMethod === 'paystack') {
        if (empty($accountDetails['bank_code'])) {
            $missingFields[] = 'Bank Code / Network';
        }
        
        $debugInfo .= '<strong>Missing Fields Check:</strong><br>';
        $debugInfo .= '- bank_code empty: ' . (empty($accountDetails['bank_code']) ? 'YES' : 'NO') . '<br>';
        $debugInfo .= '<strong>Missing Fields:</strong> ' . (empty($missingFields) ? 'NONE' : implode(', ', $missingFields)) . '<br>';
        $debugInfo .= '</div>';
        
        if (!empty($missingFields)) {
            $_SESSION['flash_error'] = 'Please fill in all required account details. Missing: ' . implode(', ', $missingFields);
            $_SESSION['debug_info'] = $debugInfo;
            header('Location: request_payout.php');
            exit;
        }
    } elseif ($payoutMethod === 'mobile_money') {
        if (empty($accountDetails['name'])) {
            $missingFields[] = 'Name';
        }
        if (empty($accountDetails['phone'])) {
            $missingFields[] = 'Phone Number';
        }
        if (empty($accountDetails['network'])) {
            $missingFields[] = 'Network';
        }
        if (!empty($missingFields)) {
            $_SESSION['flash_error'] = 'Please fill in all required mobile money details. Missing: ' . implode(', ', $missingFields);
            header('Location: request_payout.php');
            exit;
        }
    } elseif ($payoutMethod === 'bank_transfer') {
        if (empty($accountDetails['account_name'])) {
            $missingFields[] = 'Account Name';
        }
        if (empty($accountDetails['account_number'])) {
            $missingFields[] = 'Account Number';
        }
        if (empty($accountDetails['bank_name'])) {
            $missingFields[] = 'Bank Name';
        }
        if (!empty($missingFields)) {
            $_SESSION['flash_error'] = 'Please fill in all required bank details. Missing: ' . implode(', ', $missingFields);
            header('Location: request_payout.php');
            exit;
        }
    }

    // Create payout request
    try {
        $requestId = $payoutModel->create([
            'vendor_id' => $userId,
            'amount' => $amount,
            'payout_method' => $payoutMethod,
            'account_details' => $accountDetails,
        ]);

        if ($requestId) {
            $_SESSION['flash_success'] = 'Payout request submitted successfully. It will be processed by an admin.';
            header('Location: vendor_earnings.php');
            exit;
        } else {
            $_SESSION['flash_error'] = 'Failed to create payout request. Please try again.';
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
    }
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
    <title>Request Payout - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .form-section {
            background: white;
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1em;
        }
        .form-group small {
            display: block;
            margin-top: 4px;
            color: #6b7280;
        }
        #mobile_money_details,
        #bank_transfer_details {
            display: none;
        }
    </style>
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
                    </div>
                </a>
                <div class="nav-actions">
                    <a href="../view/vendor_earnings.php" class="btn-secondary">Back to Earnings</a>
                    <a href="../login/logout.php" class="btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container" style="max-width: 800px; margin-top: 30px;">
        <h1>Request Payout</h1>

        <?php if ($flash['success']): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
        <?php endif; ?>

        <?php if ($flash['error']): ?>
            <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['debug_info'])): ?>
            <?= $_SESSION['debug_info'] ?>
            <?php unset($_SESSION['debug_info']); ?>
        <?php endif; ?>

        <div class="form-section">
            <p style="margin-bottom: 20px; color: #6b7280;">
                Available Earnings: <strong style="color: #1f2937;">GH₵<?= number_format($availableEarnings, 2) ?></strong>
            </p>

            <form method="POST" id="payout_form">
                <div class="form-group">
                    <label for="amount">Amount (GHS) *</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" max="<?= $availableEarnings ?>" required>
                    <small>Maximum: GH₵<?= number_format($availableEarnings, 2) ?></small>
                </div>

                <div class="form-group">
                    <label for="payout_method">Payout Method *</label>
                    <select id="payout_method" name="payout_method" required onchange="toggleAccountDetails()">
                        <option value="paystack">Paystack (Mobile Money/Bank)</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>

                <!-- Paystack Account Details -->
                <div id="paystack_details">
                    <div class="form-group">
                        <label for="account_type">Account Type *</label>
                        <select id="account_type" name="account_type" required>
                            <option value="mobile_money" <?= (isset($_POST['account_type']) && $_POST['account_type'] === 'mobile_money') ? 'selected' : '' ?>>Mobile Money</option>
                            <option value="nuban" <?= (isset($_POST['account_type']) && $_POST['account_type'] === 'nuban') ? 'selected' : '' ?>>Bank Account</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="bank_code">Bank Code / Network *</label>
                        <input type="text" id="bank_code" name="bank_code" placeholder="e.g., MTN, VOD, TIGO for mobile money or bank code for bank account" required value="<?= htmlspecialchars($_POST['bank_code'] ?? '') ?>">
                        <small>For Mobile Money: MTN, VOD, or TIGO. For Bank Account: Enter the bank code.</small>
                    </div>
                </div>

                <!-- Mobile Money Details -->
                <div id="mobile_money_details" class="account-details">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" placeholder="e.g., 0244123456">
                    </div>
                    <div class="form-group">
                        <label for="network">Network *</label>
                        <select id="network" name="network">
                            <option value="">Select Network</option>
                            <option value="MTN">MTN</option>
                            <option value="VOD">Vodafone</option>
                            <option value="TIGO">Tigo</option>
                        </select>
                    </div>
                </div>

                <!-- Bank Transfer Details -->
                <div id="bank_transfer_details" class="account-details">
                    <div class="form-group">
                        <label for="bank_account_name">Account Name *</label>
                        <input type="text" id="bank_account_name" name="account_name">
                    </div>
                    <div class="form-group">
                        <label for="bank_account_number">Account Number *</label>
                        <input type="text" id="bank_account_number" name="account_number">
                    </div>
                    <div class="form-group">
                        <label for="bank_name">Bank Name *</label>
                        <input type="text" id="bank_name" name="bank_name">
                    </div>
                    <div class="form-group">
                        <label for="bank_code_field">Bank Code</label>
                        <input type="text" id="bank_code_field" name="bank_code">
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-primary">Submit Request</button>
                    <a href="../view/vendor_earnings.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        function toggleAccountDetails() {
            const method = document.getElementById('payout_method').value;
            
            // Hide all
            document.getElementById('paystack_details').style.display = 'none';
            document.getElementById('mobile_money_details').style.display = 'none';
            document.getElementById('bank_transfer_details').style.display = 'none';
            
            // Show relevant one
            if (method === 'paystack') {
                document.getElementById('paystack_details').style.display = 'block';
            } else if (method === 'mobile_money') {
                document.getElementById('mobile_money_details').style.display = 'block';
            } else if (method === 'bank_transfer') {
                document.getElementById('bank_transfer_details').style.display = 'block';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleAccountDetails();
        });
    </script>
</body>
</html>

