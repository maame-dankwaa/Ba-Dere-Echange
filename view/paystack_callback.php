<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


/**
 * Paystack Payment Callback Handler
 * This page is called after Paystack payment process
 * User is redirected here by Paystack after payment
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';

// Check if user is logged in
AuthHelper::requireLogin('../login/login.php');

// Get reference from URL
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : null;

if (!$reference) {
    // Payment cancelled or reference missing
    header('Location: checkout.php?error=cancelled');
    exit();
}

error_log("=== PAYSTACK CALLBACK PAGE ===");
error_log("Reference from URL: $reference");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - Ba Dɛre Exchange</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }

        .container { max-width: 500px; width: 90%; background: white; padding: 60px 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1); text-align: center; }

        .spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary-color, #2563eb);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 30px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        h1 { font-size: 2rem; color: #1a1a1a; margin-bottom: 15px; }
        p { color: #6b7280; font-size: 16px; line-height: 1.6; margin-bottom: 20px; }

        .reference { background: #f9fafb; padding: 15px; border-radius: 8px; margin: 25px 0; word-break: break-all; font-family: monospace; font-size: 12px; color: #6b7280; }

        .error { color: #dc2626; background: #fee2e2; border: 2px solid #fecaca; padding: 15px; border-radius: 8px; margin: 20px 0; display: none; }
        .success { color: #065f46; background: #d1fae5; border: 2px solid #6ee7b7; padding: 15px; border-radius: 8px; margin: 20px 0; display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner" id="spinner"></div>

        <h1>Verifying Payment</h1>
        <p>Please wait while we verify your payment with Paystack...</p>

        <div class="reference">
            Payment Reference: <strong><?= htmlspecialchars($reference) ?></strong>
        </div>

        <div class="error" id="errorBox">
            <strong>Error:</strong> <span id="errorMessage"></span>
        </div>

        <div class="success" id="successBox">
            <strong>Success!</strong> Your payment has been verified. Redirecting...
        </div>
    </div>

    <script>
        /**
         * Verify payment with backend
         */
        async function verifyPayment() {
            const reference = '<?= htmlspecialchars($reference) ?>';

            try {
                const response = await fetch('../actions/paystack_verify.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        reference: reference
                    })
                });

                const data = await response.json();
                console.log('Verification response:', data);

                // Hide spinner
                document.getElementById('spinner').style.display = 'none';

                if (data.status === 'success' && data.verified) {
                    // Payment verified successfully
                    document.getElementById('successBox').style.display = 'block';

                    // Redirect based on payment type
                    const transactionId = data.transaction_id || '';
                    const paymentType = data.type || 'purchase';

                    setTimeout(() => {
                        if (paymentType === 'featured_listing') {
                            window.location.replace(`../view/manage_listings.php?featured_success=1`);
                        } else {
                            window.location.replace(`../actions/checkout_success.php?id=${transactionId}`);
                        }
                    }, 1000);

                } else {
                    // Payment verification failed
                    const errorMsg = data.message || 'Payment verification failed';
                    showError(errorMsg);

                    // Redirect after 5 seconds
                    setTimeout(() => {
                        window.location.href = '../actions/checkout.php?error=verification_failed';
                    }, 5000);
                }

            } catch (error) {
                console.error('Verification error:', error);
                showError('Connection error. Please try again or contact support.');

                // Redirect after 5 seconds
                setTimeout(() => {
                    window.location.href = '../actions/checkout.php?error=connection_error';
                }, 5000);
            }
        }

        /**
         * Show error message
         */
        function showError(message) {
            document.getElementById('errorBox').style.display = 'block';
            document.getElementById('errorMessage').textContent = message;
        }

        // Start verification when page loads
        window.addEventListener('load', verifyPayment);
    </script>
</body>
</html>
