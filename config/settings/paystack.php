<?php
/**
 * Paystack Configuration
 * Secure payment gateway settings
 */

// Paystack API Keys
define('PAYSTACK_SECRET_KEY', 'sk_test_a42809a2182f35b508c001be01fd6402b8ab7291'); //replace with your secret key
define('PAYSTACK_PUBLIC_KEY', 'pk_test_428ef7cc2db6b354d60ae7b2a7373a3d4a844ea3'); //replace with your public key

// Paystack URLs
define('PAYSTACK_API_URL', 'https://api.paystack.co');
define('PAYSTACK_INIT_ENDPOINT', PAYSTACK_API_URL . '/transaction/initialize');
define('PAYSTACK_VERIFY_ENDPOINT', PAYSTACK_API_URL . '/transaction/verify/');

define('APP_ENVIRONMENT', 'test');

// Auto-detect base URL from current request
if (!defined('APP_BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Get the script directory path
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    // Remove the filename and go up to the project root
    // If we're in config/settings/paystack.php, go up 2 levels
    $projectPath = dirname(dirname(dirname($scriptPath)));
    
    // Clean up the path
    $projectPath = str_replace('\\', '/', $projectPath);
    $projectPath = rtrim($projectPath, '/');
    
    // For the server, construct the full URL
    // http://169.239.251.102:442/~maame.afranie/final_project
    if (strpos($host, '169.239.251.102') !== false) {
        $baseUrl = $protocol . '://' . $host . '/~maame.afranie/final_project';
    } else {
        $baseUrl = $protocol . '://' . $host . $projectPath;
    }
    
    define('APP_BASE_URL', $baseUrl);
}

define('PAYSTACK_CALLBACK_URL', APP_BASE_URL . '/view/paystack_callback.php'); // Callback after payment

/**
 * Initialize a Paystack transaction
 *
 * @param float $amount Amount in GHS (will be converted to pesewas)
 * @param string $email Customer email
 * @param string $reference Optional reference
 * @return array Response with 'status' and 'data' containing authorization_url
 */
function paystack_initialize_transaction($amount, $email, $reference = null) {
    $reference = $reference ?? 'ref_' . uniqid();

    // Convert GHS to pesewas (1 GHS = 100 pesewas)
    $amount_in_pesewas = round($amount * 100);

    $data = [
        'amount' => $amount_in_pesewas,
        'email' => $email,
        'reference' => $reference,
        'callback_url' => PAYSTACK_CALLBACK_URL,
        'metadata' => [
            'currency' => 'GHS',
            'app' => 'Ba Dere Exchange',
            'environment' => APP_ENVIRONMENT
        ]
    ];

    $response = paystack_api_request('POST', PAYSTACK_INIT_ENDPOINT, $data);

    return $response;
}

/**
 * Verify a Paystack transaction
 *
 * @param string $reference Transaction reference
 * @return array Response with transaction details
 */
function paystack_verify_transaction($reference) {
    $response = paystack_api_request('GET', PAYSTACK_VERIFY_ENDPOINT . $reference);

    return $response;
}

/**
 * Make a request to Paystack API
 *
 * @param string $method HTTP method (GET, POST, etc)
 * @param string $url Full API endpoint URL
 * @param array $data Optional data to send
 * @return array API response decoded as array
 */
function paystack_api_request($method, $url, $data = null) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // Set headers
    $headers = [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Send data for POST/PUT requests
    if ($method !== 'GET' && $data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);

    curl_close($ch);

    // Handle curl errors
    if ($curl_error) {
        error_log("Paystack API CURL Error: $curl_error");
        return [
            'status' => false,
            'message' => 'Connection error: ' . $curl_error
        ];
    }

    // Decode response
    $result = json_decode($response, true);

    // Log for debugging
    error_log("Paystack API Response (HTTP $http_code): " . json_encode($result));

    return $result;
}

/**
 * Get currency symbol for display
 */
function get_currency_symbol($currency = 'GHS') {
    $symbols = [
        'GHS' => '₵',
        'USD' => '$',
        'EUR' => '€',
        'NGN' => '₦'
    ];

    return $symbols[$currency] ?? $currency;
}

/**
 * Create a Paystack transfer recipient
 * 
 * @param string $type Type: 'nuban' (bank account) or 'mobile_money'
 * @param string $name Recipient name
 * @param string $account_number Account number or phone number
 * @param string $bank_code Bank code (for bank transfers) or network (MTN, VOD, etc for mobile money)
 * @param string $currency Currency code (default: GHS)
 * @return array Response with recipient code
 */
function paystack_create_transfer_recipient($type, $name, $account_number, $bank_code, $currency = 'GHS') {
    $data = [
        'type' => $type,
        'name' => $name,
        'account_number' => $account_number,
        'bank_code' => $bank_code,
        'currency' => $currency
    ];

    $response = paystack_api_request('POST', PAYSTACK_API_URL . '/transferrecipient', $data);
    return $response;
}

/**
 * Initiate a Paystack transfer to a recipient
 * 
 * @param string $recipient_code Recipient code from create_transfer_recipient
 * @param float $amount Amount in GHS (will be converted to pesewas)
 * @param string $reason Reason for transfer
 * @param string $reference Optional reference
 * @return array Response with transfer details
 */
function paystack_initiate_transfer($recipient_code, $amount, $reason = '', $reference = null) {
    $reference = $reference ?? 'TRF_' . uniqid();
    
    // Convert GHS to pesewas (1 GHS = 100 pesewas)
    $amount_in_pesewas = round($amount * 100);

    $data = [
        'source' => 'balance', // Transfer from account balance
        'amount' => $amount_in_pesewas,
        'recipient' => $recipient_code,
        'reason' => $reason ?: 'Vendor payout',
        'reference' => $reference,
        'currency' => 'GHS'
    ];

    $response = paystack_api_request('POST', PAYSTACK_API_URL . '/transfer', $data);
    return $response;
}

/**
 * Verify a Paystack transfer
 * 
 * @param string $transfer_code Transfer code or reference
 * @return array Response with transfer details
 */
function paystack_verify_transfer($transfer_code) {
    $response = paystack_api_request('GET', PAYSTACK_API_URL . '/transfer/' . $transfer_code);
    return $response;
}

/**
 * Get list of banks for transfer
 * 
 * @param string $country Country code (default: 'ghana')
 * @return array Response with list of banks
 */
function paystack_get_banks($country = 'ghana') {
    $response = paystack_api_request('GET', PAYSTACK_API_URL . '/bank?country=' . $country);
    return $response;
}
