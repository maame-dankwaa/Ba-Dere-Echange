<?php

require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

class ManageListingsController
{
    private $book;
    private $category;
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!AuthHelper::isLoggedIn()) {
            $_SESSION['flash_error'] = 'Please login to manage listings.';
            header('Location: ../login/login.php');
            exit;
        }

        if (!AuthHelper::canCreateListing()) {
            $_SESSION['flash_error'] = 'You do not have permission to manage listings.';
            header('Location: ../index.php');
            exit;
        }

        $this->book = new Book();
        $this->category = new Category();
        $this->db = Database::getInstance();
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../views/' . $view . '.php';
    }

    private function getUserId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    public function index(): void
    {
        $userId = $this->getUserId();

        // Get all books listed by this vendor
        $listings = $this->getVendorListings($userId);

        // Get statistics
        $stats = $this->getVendorStats($userId);

        $data = [
            'listings' => $listings,
            'stats' => $stats,
        ];

        $this->render('manage_listings', $data);
    }

    private function getVendorListings(int $sellerId): array
    {
        $sql = "SELECT b.*, c.name AS category_name,
                       COUNT(DISTINCT t.transaction_id) AS total_sales,
                       SUM(CASE WHEN t.payment_status = 'completed' THEN t.quantity ELSE 0 END) AS sold_quantity
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.category_id
                LEFT JOIN transactions t ON b.book_id = t.book_id AND t.payment_status = 'completed'
                WHERE b.seller_id = :seller_id
                GROUP BY b.book_id
                ORDER BY b.created_at DESC";

        return $this->db->fetchAll($sql, ['seller_id' => $sellerId]);
    }

    private function getVendorStats(int $sellerId): array
    {
        // Total active listings
        $sql1 = "SELECT COUNT(*) AS total FROM books WHERE seller_id = :id AND status = 'active'";
        $activeListings = $this->db->fetch($sql1, ['id' => $sellerId]);

        // Total views
        $sql2 = "SELECT SUM(views_count) AS total_views FROM books WHERE seller_id = :id";
        $viewsData = $this->db->fetch($sql2, ['id' => $sellerId]);

        // Total sold
        $sql3 = "SELECT COUNT(*) AS total_sold,
                        SUM(total_amount) AS total_revenue,
                        SUM(seller_amount) AS total_earnings
                 FROM transactions
                 WHERE seller_id = :id AND payment_status = 'completed'";
        $salesData = $this->db->fetch($sql3, ['id' => $sellerId]);

        return [
            'active_listings' => (int)($activeListings['total'] ?? 0),
            'total_views' => (int)($viewsData['total_views'] ?? 0),
            'total_sold' => (int)($salesData['total_sold'] ?? 0),
            'total_revenue' => (float)($salesData['total_revenue'] ?? 0),
            'total_earnings' => (float)($salesData['total_earnings'] ?? 0),
        ];
    }

    public function deleteListing(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../view/manage_listings.php');
            return;
        }

        $bookId = (int)($_POST['book_id'] ?? 0);
        $userId = $this->getUserId();

        // Verify ownership
        $book = $this->book->getById($bookId);
        if (!$book || (int)$book['seller_id'] !== $userId) {
            $_SESSION['flash_error'] = 'You do not have permission to delete this listing.';
            header('Location: ../view/manage_listings.php');
            return;
        }

        // Soft delete - update status to 'deleted'
        $sql = "UPDATE books SET status = 'deleted' WHERE book_id = :id";
        $this->db->query($sql, ['id' => $bookId]);

        $_SESSION['flash_success'] = 'Listing deleted successfully.';
        header('Location: ../view/manage_listings.php');
    }

    public function toggleStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ../view/manage_listings.php');
            return;
        }

        $bookId = (int)($_POST['book_id'] ?? 0);
        $userId = $this->getUserId();

        // Verify ownership
        $book = $this->book->getById($bookId);
        if (!$book || (int)$book['seller_id'] !== $userId) {
            $_SESSION['flash_error'] = 'You do not have permission to modify this listing.';
            header('Location: ../view/manage_listings.php');
            return;
        }

        // Toggle between active and inactive
        $newStatus = $book['status'] === 'active' ? 'inactive' : 'active';
        $sql = "UPDATE books SET status = :status WHERE book_id = :id";
        $db = Database::getInstance();
        $db->query($sql, ['status' => $newStatus, 'id' => $bookId]);

        $_SESSION['flash_success'] = 'Listing status updated successfully.';
        header('Location: ../view/manage_listings.php');
    }
}
