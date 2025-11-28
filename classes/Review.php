<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Book.php';

class Review
{
    protected $db;
    protected $table = 'fp_reviews';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createReview(array $data): int
    {
        // expected keys: book_id, reviewer_id, rating, comment/review_text, transaction_id(optional)
        $data['created_at'] = date('Y-m-d H:i:s');

        $reviewId = $this->db->insert($this->table, [
            'book_id'        => $data['book_id'],
            'reviewer_id'    => $data['reviewer_id'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'rating'         => $data['rating'],
            'review_text'    => $data['comment'] ?? $data['review_text'] ?? null,
            'created_at'     => $data['created_at'],
        ]);

        // Update book average rating and seller rating if you want, simplified:
        $this->recalculateBookRating((int)$data['book_id']);

        return (int)$reviewId;
    }

    public function getBookReviews(int $bookId, ?int $limit = null): array
    {
        $sql = "SELECT r.*, r.review_text AS comment, u.username AS reviewer_username, u.profile_image
                FROM {$this->table} r
                INNER JOIN fp_users u ON r.reviewer_id = u.user_id
                WHERE r.book_id = :book_id
                ORDER BY r.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        return $this->db->fetchAll($sql, ['book_id' => $bookId]);
    }

    public function getBookRating(int $bookId): array
    {
        $sql = "SELECT 
                    COUNT(*) AS total_reviews,
                    AVG(rating) AS avg_rating
                FROM {$this->table}
                WHERE book_id = :book_id";
        $row = $this->db->fetch($sql, ['book_id' => $bookId]) ?: ['total_reviews' => 0, 'avg_rating' => null];

        return [
            'total_reviews' => (int)$row['total_reviews'],
            'avg_rating'    => $row['avg_rating'] !== null ? (float)$row['avg_rating'] : 0.0,
        ];
    }

    private function recalculateBookRating(int $bookId): void
    {
        $stats = $this->getBookRating($bookId);

        $sql = "UPDATE fp_books
                SET average_rating = :avg_rating,
                    total_ratings  = :total_reviews
                WHERE book_id = :book_id";
        $this->db->query($sql, [
            'avg_rating'    => $stats['avg_rating'],
            'total_reviews' => $stats['total_reviews'],
            'book_id'       => $bookId,
        ]);
    }
}
?>