<?php

require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

class AnalyticsController
{
    private $book;
    private $transaction;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!AuthHelper::isLoggedIn()) {
            $_SESSION['flash_error'] = 'Please login to view analytics.';
            header('Location: ../login/login.php');
            exit;
        }

        if (!AuthHelper::canCreateListing()) {
            $_SESSION['flash_error'] = 'You do not have permission to view analytics.';
            header('Location: ../index.php');
            exit;
        }

        $this->book = new Book();
        $this->transaction = new Transaction();
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

        // Get analytics data
        $overview = $this->getOverviewStats($userId);
        $salesTrend = $this->getSalesTrend($userId);
        $topBooks = $this->getTopBooks($userId);
        $recentTransactions = $this->getRecentTransactions($userId);
        $categoryBreakdown = $this->getCategoryBreakdown($userId);

        $data = [
            'overview' => $overview,
            'salesTrend' => $salesTrend,
            'topBooks' => $topBooks,
            'recentTransactions' => $recentTransactions,
            'categoryBreakdown' => $categoryBreakdown,
        ];

        $this->render('analytics', $data);
    }

    private function getOverviewStats(int $sellerId): array
    {
        $db = $this->book->db;

        // Total revenue and earnings
        $sql1 = "SELECT COUNT(*) AS total_transactions,
                        SUM(total_amount) AS total_revenue,
                        SUM(seller_amount) AS total_earnings,
                        SUM(commission_amount) AS total_commission
                 FROM fp_transactions
                 WHERE seller_id = :id AND payment_status = 'completed'";
        $revenue = $db->fetch($sql1, ['id' => $sellerId]);

        // Total books and views
        $sql2 = "SELECT COUNT(*) AS total_books,
                        SUM(views_count) AS total_views,
                        SUM(available_quantity) AS total_available
                 FROM fp_books
                 WHERE seller_id = :id";
        $books = $db->fetch($sql2, ['id' => $sellerId]);

        // Active listings
        $sql3 = "SELECT COUNT(*) AS active_books FROM fp_books WHERE seller_id = :id AND status = 'active'";
        $active = $db->fetch($sql3, ['id' => $sellerId]);

        // Average rating
        $sql4 = "SELECT AVG(b.average_rating) AS avg_rating
                 FROM fp_books b
                 WHERE b.seller_id = :id AND b.average_rating > 0";
        $rating = $db->fetch($sql4, ['id' => $sellerId]);

        return [
            'total_transactions' => (int)($revenue['total_transactions'] ?? 0),
            'total_revenue' => (float)($revenue['total_revenue'] ?? 0),
            'total_earnings' => (float)($revenue['total_earnings'] ?? 0),
            'total_commission' => (float)($revenue['total_commission'] ?? 0),
            'total_books' => (int)($books['total_books'] ?? 0),
            'active_books' => (int)($active['active_books'] ?? 0),
            'total_views' => (int)($books['total_views'] ?? 0),
            'total_available' => (int)($books['total_available'] ?? 0),
            'average_rating' => round((float)($rating['avg_rating'] ?? 0), 2),
        ];
    }

    private function getSalesTrend(int $sellerId, int $days = 30): array
    {
        $db = $this->book->db;

        $sql = "SELECT DATE(created_at) AS sale_date,
                       COUNT(*) AS total_sales,
                       SUM(total_amount) AS revenue
                FROM fp_transactions
                WHERE seller_id = :id
                  AND payment_status = 'completed'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY sale_date ASC";

        return $db->fetchAll($sql, ['id' => $sellerId, 'days' => $days]);
    }

    private function getTopBooks(int $sellerId, int $limit = 5): array
    {
        $db = $this->book->db;

        $sql = "SELECT b.book_id, b.title, b.cover_image, b.price,
                       COUNT(t.transaction_id) AS total_sales,
                       SUM(t.quantity) AS units_sold,
                       SUM(t.seller_amount) AS earnings
                FROM fp_books b
                LEFT JOIN fp_transactions t ON b.book_id = t.book_id AND t.payment_status = 'completed'
                WHERE b.seller_id = :id
                GROUP BY b.book_id
                ORDER BY total_sales DESC, units_sold DESC
                LIMIT {$limit}";

        return $db->fetchAll($sql, ['id' => $sellerId]);
    }

    private function getRecentTransactions(int $sellerId, int $limit = 10): array
    {
        $db = $this->book->db;

        $sql = "SELECT t.*, b.title AS book_title, u.username AS buyer_username
                FROM fp_transactions t
                INNER JOIN fp_books b ON t.book_id = b.book_id
                INNER JOIN fp_users u ON t.buyer_id = u.user_id
                WHERE t.seller_id = :id
                ORDER BY t.created_at DESC
                LIMIT {$limit}";

        return $db->fetchAll($sql, ['id' => $sellerId]);
    }

    private function getCategoryBreakdown(int $sellerId): array
    {
        $db = $this->book->db;

        $sql = "SELECT c.name AS category_name,
                       COUNT(b.book_id) AS book_count,
                       SUM(b.views_count) AS total_views,
                       COUNT(t.transaction_id) AS total_sales
                FROM fp_books b
                INNER JOIN fp_categories c ON b.category_id = c.category_id
                LEFT JOIN fp_transactions t ON b.book_id = t.book_id AND t.payment_status = 'completed'
                WHERE b.seller_id = :id
                GROUP BY c.category_id
                ORDER BY total_sales DESC, book_count DESC";

        return $db->fetchAll($sql, ['id' => $sellerId]);
    }
}
