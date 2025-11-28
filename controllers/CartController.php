<?php
/**
 * Cart Controller
 * Handles shopping cart operations with safe redirects
 */

require_once __DIR__ . '/../classes/Book.php';

class CartController
{
    private Book $book;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->book = new Book();

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
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

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    private function getRedirectPath(string $default = '../index.php'): string
    {
        // Get safe redirect from POST/GET, fallback to referer, then default
        $redirect = $_POST['redirect_to'] ?? $_GET['redirect_to'] ?? null;

        if ($redirect) {
            // Ensure it's a local path
            if (strpos($redirect, 'http') === 0) {
                return $default;
            }
            return $redirect;
        }

        // Try referer as fallback
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            $host = $_SERVER['HTTP_HOST'];
            if (strpos($referer, $host) !== false) {
                return $referer;
            }
        }

        return $default;
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    private function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    /**
     * Display cart contents
     */
    public function index(): void
    {
        $this->render('cart', [
            'items' => $_SESSION['cart'],
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Add item to cart
     */
    public function add(): void
    {
        // Only accept POST
        if (!$this->isPost()) {
            $this->redirect('/index.php');
            return;
        }

        $bookId = $this->getInt('book_id', 'POST', 0, 1);
        $quantity = $this->getInt('quantity', 'POST', 1, 1, 10);
        $transactionType = $this->getString('transaction_type', 'POST', 20);
        $rentalDuration = $this->getInt('rental_duration', 'POST', 0, 0);

        // Get safe redirect path from form (not HTTP_REFERER)
        $redirectTo = $this->getRedirectPath('../actions/cart.php');

        if ($bookId <= 0) {
            $this->flash('error', 'Invalid book.');
            $this->redirect($redirectTo);
            return;
        }

        // Validate transaction type
        $validTypes = ['purchase', 'rent', 'exchange'];
        if (!in_array($transactionType, $validTypes, true)) {
            $transactionType = 'purchase'; // Default to purchase
        }

        // Check availability
        if (!$this->book->isAvailable($bookId, $quantity)) {
            $this->flash('error', 'This book is not available in the requested quantity.');
            $this->redirect($redirectTo);
            return;
        }

        // Get book details
        $book = $this->book->getBookDetails($bookId);

        if (!$book) {
            $this->flash('error', 'Book not found.');
            $this->redirect($redirectTo);
            return;
        }

        // Verify the selected transaction type is available for this book
        if ($transactionType === 'rent' && empty($book['is_rentable'])) {
            $this->flash('error', 'This book is not available for rent.');
            $this->redirect($redirectTo);
            return;
        }
        if ($transactionType === 'exchange' && empty($book['is_exchangeable'])) {
            $this->flash('error', 'This book is not available for exchange.');
            $this->redirect($redirectTo);
            return;
        }
        if ($transactionType === 'purchase' && (empty($book['price']) || $book['price'] <= 0)) {
            $this->flash('error', 'This book is not available for purchase.');
            $this->redirect($redirectTo);
            return;
        }

        // Validate rental duration if renting
        if ($transactionType === 'rent') {
            $minPeriod = (int)($book['rental_min_period'] ?? 1);
            $maxPeriod = (int)($book['rental_max_period'] ?? 30);

            if ($rentalDuration < $minPeriod || $rentalDuration > $maxPeriod) {
                $this->flash('error', "Rental duration must be between {$minPeriod} and {$maxPeriod} {$book['rental_period_unit']}(s).");
                $this->redirect($redirectTo);
                return;
            }
        }

        // Calculate price based on transaction type
        $price = 0;
        if ($transactionType === 'purchase') {
            $price = (float)$book['price'];
        } elseif ($transactionType === 'rent') {
            $rentalPricePerPeriod = (float)($book['rental_price'] ?? $book['price'] * 0.1);
            $price = $rentalPricePerPeriod * $rentalDuration; // Total rental cost
        }
        // Exchange has no price

        // Check if user is trying to add their own book
        if ($this->isAuthenticated() && (int)$book['seller_id'] === $this->getUserId()) {
            $this->flash('error', 'You cannot add your own book to cart.');
            $this->redirect($redirectTo);
            return;
        }

        // Create unique cart key combining book_id and transaction_type
        $cartKey = $bookId . '_' . $transactionType;

        // Add or update cart item
        if (!isset($_SESSION['cart'][$cartKey])) {
            $cartItem = [
                'book_id' => $bookId,
                'title' => $book['title'],
                'price' => $price,
                'transaction_type' => $transactionType,
                'seller_id' => (int)$book['seller_id'],
                'seller_username' => $book['seller_username'] ?? '',
                'cover_image' => $book['cover_image'] ?? null,
                'quantity' => $quantity,
            ];

            // Add rental-specific data
            if ($transactionType === 'rent') {
                $cartItem['rental_duration'] = $rentalDuration;
                $cartItem['rental_period_unit'] = $book['rental_period_unit'] ?? 'day';
                $cartItem['rental_price_per_period'] = (float)($book['rental_price'] ?? $book['price'] * 0.1);
            }

            $_SESSION['cart'][$cartKey] = $cartItem;
            $this->flash('success', 'Book added to cart.');
        } else {
            $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
            $this->flash('success', 'Cart updated.');
        }

        $this->redirect('../actions/cart.php');
    }

    /**
     * Update cart quantities
     */
    public function update(): void
    {
        // Only accept POST
        if (!$this->isPost()) {
            $this->redirect('../actions/cart.php');
            return;
        }

        $quantities = $_POST['quantities'] ?? [];
        $updated = false;
        $errors = [];

        foreach ($quantities as $cartKey => $qty) {
            $qty = max(0, (int)$qty);

            if ($qty === 0) {
                unset($_SESSION['cart'][$cartKey]);
                $updated = true;
            } elseif (isset($_SESSION['cart'][$cartKey])) {
                $item = $_SESSION['cart'][$cartKey];
                $bookId = (int)$item['book_id'];

                // Verify availability
                if ($this->book->isAvailable($bookId, $qty)) {
                    $_SESSION['cart'][$cartKey]['quantity'] = min($qty, 10); // Max 10 per item
                    $updated = true;
                } else {
                    $bookTitle = $item['title'] ?? 'Book';
                    $errors[] = "$bookTitle: requested quantity not available.";
                }
            }
        }

        if ($updated) {
            $this->flash('success', 'Cart updated.');
        }

        if (!empty($errors)) {
            $this->flash('warning', implode(' ', $errors));
        }

        $this->redirect('../actions/cart.php');
    }

    /**
     * Remove item from cart
     */
    public function remove(): void
    {
        // Support both cart_key and book_id for backwards compatibility
        $cartKey = $this->getString('cart_key', 'GET', 100);

        if (empty($cartKey)) {
            // Fallback to book_id for backwards compatibility
            $bookId = $this->getInt('book_id', 'GET', 0, 1);
            if ($bookId > 0) {
                // Try to find cart key with this book_id
                foreach ($_SESSION['cart'] as $key => $item) {
                    if ((int)$item['book_id'] === $bookId) {
                        unset($_SESSION['cart'][$key]);
                        break;
                    }
                }
            }
        } else {
            unset($_SESSION['cart'][$cartKey]);
            $this->flash('success', 'Item removed from cart.');
        }

        $this->redirect('../actions/cart.php');
    }

    /**
     * Clear entire cart
     */
    public function clear(): void
    {
        $_SESSION['cart'] = [];
        $this->flash('success', 'Cart cleared.');
        $this->redirect('../actions/cart.php');
    }

    /**
     * Get cart count (for AJAX)
     */
    public function count(): void
    {
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += (int)($item['quantity'] ?? 1);
        }

        $this->json(['count' => $count]);
    }
}
