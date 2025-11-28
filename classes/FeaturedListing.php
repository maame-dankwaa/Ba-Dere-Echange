<?php
/**
 * Featured Listing Model
 * Handles featured listing purchases and management
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../services/Logger.php';

class FeaturedListing
{
    protected Database $db;
    protected string $table = 'featured_listing_transactions';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get featured listing packages from config
     *
     * @return array
     */
    public static function getPackages(): array
    {
        $config = require __DIR__ . '/../config/settings/featured_listings.php';
        return $config['packages'] ?? [];
    }

    /**
     * Get package details by duration
     *
     * @param int $days Duration in days
     * @return array|null
     */
    public static function getPackage(int $days): ?array
    {
        $packages = self::getPackages();
        return $packages[$days] ?? null;
    }

    /**
     * Create a featured listing transaction
     *
     * @param array $data Transaction data
     * @return int Transaction ID
     */
    public function createTransaction(array $data): int
    {
        $required = ['book_id', 'user_id', 'duration_days', 'amount_paid'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $featuredFrom = new DateTime();
        $featuredUntil = (clone $featuredFrom)->modify('+' . $data['duration_days'] . ' days');

        $row = [
            'book_id' => (int)$data['book_id'],
            'user_id' => (int)$data['user_id'],
            'duration_days' => (int)$data['duration_days'],
            'amount_paid' => (float)$data['amount_paid'],
            'featured_from' => $featuredFrom->format('Y-m-d H:i:s'),
            'featured_until' => $featuredUntil->format('Y-m-d H:i:s'),
            'payment_method' => $data['payment_method'] ?? 'paystack',
            'payment_status' => $data['payment_status'] ?? 'pending',
            'payment_reference' => $data['payment_reference'] ?? null,
        ];

        return $this->db->insert($this->table, $row);
    }

    /**
     * Update payment status for a featured listing transaction
     *
     * @param int $featuredId Transaction ID
     * @param string $status New status
     * @param string|null $reference Payment reference
     * @return bool
     */
    public function updatePaymentStatus(int $featuredId, string $status, ?string $reference = null): bool
    {
        $validStatuses = ['pending', 'completed', 'failed', 'refunded'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        $data = ['payment_status' => $status];
        if ($reference !== null) {
            $data['payment_reference'] = $reference;
        }

        $updated = $this->db->update(
            $this->table,
            $data,
            'featured_id = :id',
            ['id' => $featuredId]
        );

        // If payment completed, activate the featured listing
        if ($status === 'completed' && $updated > 0) {
            $this->activateFeaturedListing($featuredId);
        }

        return $updated > 0;
    }

    /**
     * Activate a featured listing after successful payment
     *
     * @param int $featuredId Transaction ID
     * @return bool
     */
    private function activateFeaturedListing(int $featuredId): bool
    {
        // Get transaction details
        $sql = "SELECT * FROM {$this->table} WHERE featured_id = :id";
        $transaction = $this->db->fetch($sql, ['id' => $featuredId]);

        if (!$transaction) {
            return false;
        }

        // Update the book's featured status
        $updated = $this->db->update(
            'books',
            [
                'is_featured' => 1,
                'featured_from' => $transaction['featured_from'],
                'featured_until' => $transaction['featured_until'],
                'featured_payment_reference' => $transaction['payment_reference']
            ],
            'book_id = :id',
            ['id' => $transaction['book_id']]
        );

        if ($updated > 0) {
            Logger::info('Featured listing activated', [
                'book_id' => $transaction['book_id'],
                'featured_id' => $featuredId,
                'featured_until' => $transaction['featured_until']
            ]);
        }

        return $updated > 0;
    }

    /**
     * Check if a book is currently featured
     *
     * @param int $bookId Book ID
     * @return bool
     */
    public function isFeatured(int $bookId): bool
    {
        $sql = "SELECT is_featured, featured_until FROM books
                WHERE book_id = :id
                AND is_featured = 1
                AND featured_until > NOW()";

        $result = $this->db->fetch($sql, ['id' => $bookId]);
        return $result !== null;
    }

    /**
     * Get count of active featured listings for a user
     *
     * @param int $userId User ID
     * @return int
     */
    public function getUserFeaturedCount(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count FROM books
                WHERE seller_id = :user_id
                AND is_featured = 1
                AND featured_until > NOW()";

        $result = $this->db->fetch($sql, ['user_id' => $userId]);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Get all featured listings for a user
     *
     * @param int $userId User ID
     * @return array
     */
    public function getUserFeaturedListings(int $userId): array
    {
        $sql = "SELECT b.*, f.*
                FROM books b
                LEFT JOIN {$this->table} f ON b.book_id = f.book_id
                WHERE b.seller_id = :user_id
                AND b.is_featured = 1
                AND b.featured_until > NOW()
                ORDER BY b.featured_until DESC";

        return $this->db->query($sql, ['user_id' => $userId])->fetchAll();
    }

    /**
     * Expire featured listings that have passed their end date
     * This should be run via cron job or called periodically
     *
     * @return int Number of expired listings
     */
    public function expireFeaturedListings(): int
    {
        $updated = $this->db->update(
            'books',
            ['is_featured' => 0],
            'is_featured = 1 AND featured_until <= NOW()',
            []
        );

        if ($updated > 0) {
            Logger::info('Expired featured listings', ['count' => $updated]);
        }

        return $updated;
    }
}
