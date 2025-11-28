<?php
/**
 * Wishlist Controller
 * Handles wishlist operations with safe redirects and proper auth
 */

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Database.php';

class WishlistController
{
    private User $user;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->user = new User();
    }

    private function getInt(string $key, string $method = 'POST', int $default = 0, int $min = 0, int $max = PHP_INT_MAX): int
    {
        $source = $method === 'GET' ? $_GET : $_POST;
        $value = $source[$key] ?? $default;
        if (!is_numeric($value)) return $default;
        $value = (int)$value;
        return max($min, min($max, $value));
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

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    private function getRedirectPath(string $default = '/index.php'): string
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

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    private function requireAuth(string $redirectTo = '/login/login.php'): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect($redirectTo);
        }
    }

    private function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Display wishlist
     */
    public function index(): void
    {
        $this->requireAuth('/actions/wishlist.php');

        $userId = $this->getUserId();
        $items = $this->user->getWishlist($userId);

        $this->render('wishlist', [
            'items' => $items,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Add book to wishlist
     */
    public function add(): void
    {
        // Only accept POST
        if (!$this->isPost()) {
            $this->redirect('/index.php');
            return;
        }

        $this->requireAuth('/login/login.php');

        $userId = $this->getUserId();
        $bookId = $this->getInt('book_id', 'POST', 0, 1);

        // Get safe redirect path (not HTTP_REFERER)
        $redirectTo = $this->getRedirectPath('/actions/wishlist.php');

        if ($bookId <= 0) {
            $this->flash('error', 'Invalid book.');
            $this->redirect($redirectTo);
            return;
        }

        // Add to wishlist using Database directly
        $db = Database::getInstance();

        try {
            $db->query(
                "INSERT IGNORE INTO wishlists (user_id, book_id, created_at)
                 VALUES (:user_id, :book_id, NOW())",
                [
                    'user_id' => $userId,
                    'book_id' => $bookId,
                ]
            );
            $this->flash('success', 'Book added to wishlist.');
        } catch (Exception $e) {
            $this->flash('error', 'Could not add to wishlist.');
        }

        $this->redirect($redirectTo);
    }

    /**
     * Remove book from wishlist
     */
    public function remove(): void
    {
        $this->requireAuth('/login/login.php');

        $userId = $this->getUserId();
        $bookId = $this->getInt('book_id', 'GET', 0, 1);

        if ($bookId > 0) {
            $db = Database::getInstance();

            try {
                $db->query(
                    "DELETE FROM wishlists WHERE user_id = :user_id AND book_id = :book_id",
                    [
                        'user_id' => $userId,
                        'book_id' => $bookId,
                    ]
                );
                $this->flash('success', 'Book removed from wishlist.');
            } catch (Exception $e) {
                $this->flash('error', 'Could not remove from wishlist.');
            }
        }

        $this->redirect('/actions/wishlist.php');
    }

    /**
     * Check if book is in user's wishlist (for AJAX)
     */
    public function check(): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(['in_wishlist' => false]);
            return;
        }

        $userId = $this->getUserId();
        $bookId = $this->getInt('book_id', 'GET', 0, 1);

        if ($bookId <= 0) {
            $this->json(['in_wishlist' => false]);
            return;
        }

        $db = Database::getInstance();
        $result = $db->fetch(
            "SELECT 1 FROM wishlists WHERE user_id = :user_id AND book_id = :book_id",
            ['user_id' => $userId, 'book_id' => $bookId]
        );

        $this->json(['in_wishlist' => $result !== null]);
    }
}
