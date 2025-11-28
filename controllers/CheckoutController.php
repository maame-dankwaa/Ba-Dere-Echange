<?php
/**
 * Checkout Controller
 * Handles checkout process with proper authorization and price verification
 */

require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../services/Logger.php';

class CheckoutController
{
    private Transaction $transaction;
    private Book $book;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->transaction = new Transaction();
        $this->book = new Book();
    }

    private function getInt(string $key, string $method = 'POST', int $default = 0, int $min = 0, int $max = PHP_INT_MAX): int
    {
        $source = $method === 'GET' ? $_GET : $_POST;
        $value = $source[$key] ?? $default;
        if (!is_numeric($value)) return $default;
        $value = (int)$value;
        return max($min, min($max, $value));
    }

    private function getString(string $key, string $method = 'POST', int $maxLength = 255): string
    {
        $source = $method === 'GET' ? $_GET : $_POST;
        $value = $source[$key] ?? '';
        if (!is_string($value)) return '';
        $value = trim($value);
        return strlen($value) > $maxLength ? substr($value, 0, $maxLength) : $value;
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../view/' . $view . '.php';
    }

    private function getFlash(): array
    {
        $flash = ['success' => $_SESSION['flash_success'] ?? null, 'error' => $_SESSION['flash_error'] ?? null];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        return $flash;
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Display checkout page
     */
    public function index(): void
    {
        // Require authentication
        $this->requireAuth('/actions/checkout.php');

        $cart = $_SESSION['cart'] ?? [];

        if (empty($cart)) {
            $this->flash('warning', 'Your cart is empty.');
            $this->redirect('../actions/cart.php');
            return;
        }

        // Verify all items are still available and get fresh prices
        $validatedCart = $this->validateCart($cart);

        if (empty($validatedCart['items'])) {
            $this->flash('error', 'Some items in your cart are no longer available.');
            $_SESSION['cart'] = [];
            $this->redirect('../actions/cart.php');
            return;
        }

        // Calculate total
        $total = 0;
        foreach ($validatedCart['items'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $this->render('checkout', [
            'items' => $validatedCart['items'],
            'warnings' => $validatedCart['warnings'],
            'total' => $total,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Process checkout
     */
    public function process(): void
    {
        error_log("=== CHECKOUT PROCESS STARTED ===");
        error_log("POST data: " . json_encode($_POST));

        // Require authentication
        $this->requireAuth();

        error_log("Auth check passed");

        // Only accept POST
        if (!$this->isPost()) {
            error_log("Not a POST request, redirecting");
            $this->redirect('checkout.php');
            return;
        }

        error_log("POST request confirmed");


        $cart = $_SESSION['cart'] ?? [];

        if (empty($cart)) {
            $this->flash('error', 'Your cart is empty.');
            $this->redirect('../index.php');
            return;
        }

        $buyerId = $this->getUserId();

        // Validate cart and get fresh prices from database
        $validatedCart = $this->validateCart($cart);

        if (empty($validatedCart['items'])) {
            $this->flash('error', 'Items in your cart are no longer available.');
            $_SESSION['cart'] = [];
            $this->redirect('cart.php');
            return;
        }

        // Get payment method and contact details from form
        $paymentMethod = $this->getString('payment_method', 'POST') ?: 'paystack';
        $deliveryMethod = $this->getString('delivery_method', 'POST') ?: 'pickup';
        $contactPhone = trim($this->getString('contact_phone', 'POST'));
        $deliveryAddress = trim($this->getString('delivery_address', 'POST'));

        // Debug logging
        error_log("Checkout Form Data: payment_method=$paymentMethod, delivery_method=$deliveryMethod");
        error_log("Contact Phone: '$contactPhone' (length: " . strlen($contactPhone) . ")");

        // Validate payment method
        $validPaymentMethods = ['paystack', 'cash'];
        if (!in_array($paymentMethod, $validPaymentMethods, true)) {
            $this->flash('error', 'Invalid payment method selected');
            $this->redirect('checkout.php');
            return;
        }

        // Validate required fields
        if (empty($contactPhone) || strlen($contactPhone) < 10) {
            $this->flash('error', 'Contact phone is required (10 digits)');
            $this->redirect('checkout.php');
            return;
        }

        if ($deliveryMethod === 'delivery' && empty($deliveryAddress)) {
            $this->flash('error', 'Delivery address is required for home delivery');
            $this->redirect('checkout.php');
            return;
        }

        // Start transaction for atomicity
        $db = Database::getInstance();
        error_log("Starting database transaction");
        $db->beginTransaction();

        try {
            $lastTransactionId = null;
            $errors = [];

            error_log("Processing " . count($validatedCart['items']) . " cart items");

            foreach ($validatedCart['items'] as $item) {
                error_log("Processing item: " . json_encode($item));

                // Skip if trying to buy own book
                if ((int)$item['seller_id'] === $buyerId) {
                    error_log("Skipping own book: {$item['title']}");
                    $errors[] = "Cannot purchase your own book: {$item['title']}";
                    continue;
                }

                // Verify availability one more time (within transaction)
                if (!$this->book->isAvailable($item['book_id'], $item['quantity'])) {
                    error_log("Book not available: {$item['title']}");
                    $errors[] = "'{$item['title']}' is no longer available in the requested quantity.";
                    continue;
                }

                // Create transaction with VERIFIED price from database
                $transactionData = [
                    'buyer_id' => $buyerId,
                    'seller_id' => $item['seller_id'],
                    'book_id' => $item['book_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'], // This is the VERIFIED price from DB
                    'transaction_type' => $item['transaction_type'] ?? 'purchase',
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'pending', // Default to pending, not completed!
                    'delivery_method' => $deliveryMethod,
                    'delivery_status' => 'pending',
                ];

                error_log("Transaction data: " . json_encode($transactionData));

                // Add rental duration data if this is a rental transaction
                if (($item['transaction_type'] ?? 'purchase') === 'rent') {
                    $transactionData['rental_duration'] = $item['rental_duration'] ?? null;
                    $transactionData['rental_period_unit'] = $item['rental_period_unit'] ?? null;
                }

                error_log("Creating transaction...");
                $lastTransactionId = $this->transaction->createTransaction($transactionData);
                error_log("Transaction created with ID: $lastTransactionId");

                // Update inventory
                error_log("Updating inventory for book {$item['book_id']}");
                $this->book->updateAvailability($item['book_id'], -$item['quantity']);
            }

            // Commit transaction
            error_log("Committing transaction");
            $db->commit();
            error_log("Transaction committed successfully");

            // Clear cart
            $_SESSION['cart'] = [];
            error_log("Cart cleared");

            // Log checkout
            Logger::info('Checkout completed', [
                'user_id' => $buyerId,
                'transaction_id' => $lastTransactionId,
                'item_count' => count($validatedCart['items']),
            ]);

            if (!empty($errors)) {
                error_log("Warnings: " . implode(', ', $errors));
                $this->flash('warning', implode(' ', $errors));
            }

            error_log("Last transaction ID: $lastTransactionId");

            if ($lastTransactionId) {
                // For cash on delivery, redirect to success page
                if ($paymentMethod === 'cash') {
                    error_log("Redirecting to checkout_success.php?id=$lastTransactionId");
                    $this->redirect('checkout_success.php?id=' . $lastTransactionId);
                    return;
                }

                // For Paystack payments, initialize payment gateway
                if ($paymentMethod === 'paystack') {
                    require_once __DIR__ . '/../config/settings/paystack.php';

                    // Get user email
                    $userModel = new User();
                    $user = $userModel->find($buyerId);
                    $userEmail = $user['email'] ?? '';

                    if (empty($userEmail)) {
                        $this->flash('error', 'User email not found');
                        $this->redirect('cart.php');
                        return;
                    }

                    // Calculate total
                    $total = 0;
                    foreach ($validatedCart['items'] as $item) {
                        $total += $item['price'] * $item['quantity'];
                    }

                    error_log("Initializing Paystack payment: amount=$total, email=$userEmail");

                    // Initialize Paystack transaction
                    $paystackResponse = paystack_initialize_transaction(
                        $total,
                        $userEmail,
                        'TXN_' . $lastTransactionId . '_' . time()
                    );

                    error_log("Paystack response: " . json_encode($paystackResponse));

                    if ($paystackResponse['status'] ?? false) {
                        // Store transaction reference in session
                        $_SESSION['pending_transaction_id'] = $lastTransactionId;
                        $_SESSION['paystack_reference'] = $paystackResponse['data']['reference'] ?? '';

                        // Redirect to Paystack payment page
                        $authorizationUrl = $paystackResponse['data']['authorization_url'] ?? '';
                        if (!empty($authorizationUrl)) {
                            error_log("Redirecting to Paystack: $authorizationUrl");
                            header('Location: ' . $authorizationUrl);
                            exit;
                        }
                    }

                    // If Paystack init fails, show error
                    $errorMessage = $paystackResponse['message'] ?? 'Failed to initialize payment';
                    error_log("Paystack initialization failed: $errorMessage");
                    $this->flash('error', 'Payment initialization failed: ' . $errorMessage);
                    $this->redirect('checkout.php');
                    return;
                }
            } else {
                $this->flash('error', 'No items could be processed. ' . implode(' ', $errors));
                $this->redirect('cart.php');
            }

        } catch (Exception $e) {
            // Rollback on error
            error_log("EXCEPTION CAUGHT: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            $db->rollback();

            Logger::error('Checkout failed', [
                'user_id' => $buyerId,
                'error' => $e->getMessage(),
            ]);

            $this->flash('error', 'An error occurred during checkout. Please try again.');
            $this->redirect('checkout.php');
        }
    }

    /**
     * Display checkout success page
     */
    public function success(): void
    {
        // Require authentication
        $this->requireAuth();

        $transactionId = $this->getInt('id', 'GET', 0, 1);

        if ($transactionId <= 0) {
            $this->redirect('/index.php');
            return;
        }

        $userId = $this->getUserId();

        // Get transaction with IDOR protection
        $order = $this->transaction->getTransactionDetails($transactionId, $userId);

        if (!$order) {
            // User is not authorized to view this transaction
            Logger::security('Unauthorized checkout success access', [
                'transaction_id' => $transactionId,
                'user_id' => $userId,
            ]);
            $this->renderError('Order not found or access denied.', 404);
            return;
        }

        $this->render('checkout_success', [
            'order' => $order,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Validate cart items against database
     * Returns items with VERIFIED prices from database
     *
     * @param array $cart Cart items
     * @return array ['items' => [], 'warnings' => []]
     */
    private function validateCart(array $cart): array
    {
        $validItems = [];
        $warnings = [];

        foreach ($cart as $cartKey => $item) {
            $bookId = (int)($item['book_id'] ?? 0);

            if ($bookId <= 0) {
                continue;
            }

            // Get fresh book data from database
            $book = $this->book->getBookDetails($bookId);

            if (!$book) {
                $warnings[] = "A book in your cart is no longer available.";
                continue;
            }

            // Check if book is still active
            if ($book['status'] !== 'active') {
                $warnings[] = "'{$book['title']}' is no longer available.";
                continue;
            }

            // Check quantity
            $requestedQty = (int)($item['quantity'] ?? 1);
            $availableQty = (int)$book['available_quantity'];

            if ($availableQty <= 0) {
                $warnings[] = "'{$book['title']}' is out of stock.";
                continue;
            }

            if ($requestedQty > $availableQty) {
                $warnings[] = "Only {$availableQty} copies of '{$book['title']}' available.";
                $requestedQty = $availableQty;
            }

            // Preserve transaction type from cart
            $transactionType = $item['transaction_type'] ?? 'purchase';

            // Calculate correct price based on transaction type
            $verifiedPrice = 0;
            if ($transactionType === 'purchase') {
                $verifiedPrice = (float)$book['price'];
            } elseif ($transactionType === 'rent') {
                $rentalDuration = (int)($item['rental_duration'] ?? 1);
                $rentalPricePerPeriod = (float)($book['rental_price'] ?? $book['price'] * 0.1);
                $verifiedPrice = $rentalPricePerPeriod * $rentalDuration;
            }
            // Exchange has no price

            // Check if price has changed
            $sessionPrice = (float)($item['price'] ?? 0);
            if ($transactionType !== 'exchange' && abs($sessionPrice - $verifiedPrice) > 0.01) {
                $warnings[] = "Price for '{$book['title']}' has been updated.";
            }

            // Use VERIFIED data from database
            $validItem = [
                'book_id' => $bookId,
                'title' => $book['title'],
                'price' => $verifiedPrice, // VERIFIED price based on transaction type
                'transaction_type' => $transactionType,
                'seller_id' => (int)$book['seller_id'],
                'seller_username' => $book['seller_username'],
                'quantity' => $requestedQty,
                'cover_image' => $book['cover_image'] ?? null,
            ];

            // Preserve rental-specific data if this is a rental
            if ($transactionType === 'rent') {
                $validItem['rental_duration'] = (int)($item['rental_duration'] ?? 1);
                $validItem['rental_period_unit'] = $item['rental_period_unit'] ?? $book['rental_period_unit'] ?? 'day';
                $validItem['rental_price_per_period'] = (float)($book['rental_price'] ?? $book['price'] * 0.1);
            }

            $validItems[$cartKey] = $validItem;
        }

        // Update session cart with validated items
        $_SESSION['cart'] = $validItems;

        return [
            'items' => $validItems,
            'warnings' => $warnings,
        ];
    }

    /**
     * Require authentication
     */
    private function requireAuth(string $redirectTo = '../login/login.php'): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    /**
     * Get current user ID
     */
    private function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if request is POST
     */
    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Flash message shorthand
     */
    private function flash(string $type, string $message): void
    {
        $this->setFlash($type, $message);
    }

    /**
     * Render error page
     */
    private function renderError(string $message, int $code = 404): void
    {
        http_response_code($code);
        echo "<h1>Error {$code}</h1><p>" . htmlspecialchars($message) . "</p>";
        exit;
    }
}
