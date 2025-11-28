<?php
/**
 * Transaction Model
 * Handles purchase, rental, and exchange transactions
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../services/Logger.php';
require_once __DIR__ . '/../config/Config.php';

class Transaction
{
    protected Database $db;
    protected string $table = 'transactions';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate a cryptographically secure transaction code
     *
     * @return string
     */
    private function generateTransactionCode(): string
    {
        $prefix = 'BDE';
        $timestamp = date('ymd');
        // Use cryptographically secure random bytes instead of MD5
        $random = strtoupper(bin2hex(random_bytes(4))); // 8 hex characters
        return $prefix . $timestamp . $random;
    }

    /**
     * Create a new transaction
     *
     * @param array $data Transaction data
     * @return int Transaction ID
     * @throws InvalidArgumentException if required fields are missing
     */
    public function createTransaction(array $data): int
    {
        // Validate required fields
        $required = ['buyer_id', 'seller_id', 'book_id', 'unit_price', 'transaction_type'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === null) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Prevent self-purchase
        if ((int)$data['buyer_id'] === (int)$data['seller_id']) {
            throw new InvalidArgumentException('Cannot purchase your own book');
        }

        $quantity = max(1, (int)($data['quantity'] ?? 1));
        $unitPrice = (float)$data['unit_price'];

        if ($unitPrice <= 0) {
            throw new InvalidArgumentException('Invalid price');
        }

        $total = $quantity * $unitPrice;

        // Get commission rate from config
        $commissionRate = Config::commissionRate();
        $commission = round($total * $commissionRate, 2);
        $sellerAmt = $total - $commission;

        // Validate transaction type
        $validTypes = ['purchase', 'rental', 'exchange'];
        $transactionType = $data['transaction_type'];
        if (!in_array($transactionType, $validTypes, true)) {
            throw new InvalidArgumentException('Invalid transaction type');
        }

        // Payment status should default to 'pending' not 'completed'
        // Completed should only be set after actual payment verification
        $validPaymentStatuses = ['pending', 'processing', 'completed', 'failed', 'refunded'];
        $paymentStatus = $data['payment_status'] ?? 'pending';
        if (!in_array($paymentStatus, $validPaymentStatuses, true)) {
            $paymentStatus = 'pending';
        }

        $row = [
            'transaction_code'  => $this->generateTransactionCode(),
            'buyer_id'          => (int)$data['buyer_id'],
            'seller_id'         => (int)$data['seller_id'],
            'book_id'           => (int)$data['book_id'],
            'transaction_type'  => $transactionType,
            'rental_duration'   => $data['rental_duration'] ?? null,
            'rental_period_unit' => $data['rental_period_unit'] ?? null,
            'quantity'          => $quantity,
            'unit_price'        => $unitPrice,
            'total_amount'      => $total,
            'commission_amount' => $commission,
            'seller_amount'     => $sellerAmt,
            'payment_method'    => $data['payment_method'] ?? 'mobile_money',
            'payment_status'    => $paymentStatus,
            'payment_reference' => $data['payment_reference'] ?? null,
            'delivery_method'   => $data['delivery_method'] ?? 'pickup',
            'delivery_status'   => $data['delivery_status'] ?? 'pending',
            'created_at'        => date('Y-m-d H:i:s'),
        ];

        $transactionId = (int)$this->db->insert($this->table, $row);

        // Log the transaction
        Logger::info('Transaction created', [
            'transaction_id' => $transactionId,
            'type' => $transactionType,
            'buyer_id' => $data['buyer_id'],
            'book_id' => $data['book_id'],
            'amount' => $total,
        ]);

        return $transactionId;
    }

    /**
     * Get transaction details with authorization check
     *
     * @param int $id Transaction ID
     * @param int|null $userId User ID to verify ownership (null skips check)
     * @return array|null
     */
    public function getTransactionDetails(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT t.*,
                       b.title AS book_title,
                       b.cover_image AS book_image,
                       u1.username AS buyer_username,
                       u1.email AS buyer_email,
                       u2.username AS seller_username,
                       u2.email AS seller_email
                FROM {$this->table} t
                INNER JOIN books b ON t.book_id = b.book_id
                INNER JOIN users u1 ON t.buyer_id = u1.user_id
                INNER JOIN users u2 ON t.seller_id = u2.user_id
                WHERE t.transaction_id = :id";

        $row = $this->db->fetch($sql, ['id' => $id]);

        if (!$row) {
            return null;
        }

        // Authorization check - user must be buyer, seller, or admin
        if ($userId !== null) {
            if ((int)$row['buyer_id'] !== $userId && (int)$row['seller_id'] !== $userId) {
                Logger::security('Unauthorized transaction access attempt', [
                    'transaction_id' => $id,
                    'user_id' => $userId,
                ]);
                return null;
            }
        }

        return $row;
    }

    /**
     * Get all transactions for a user
     *
     * @param int $userId User ID
     * @param int $limit Maximum number of transactions
     * @param int $offset Offset for pagination
     * @return array
     */
    public function getUserTransactions(int $userId, int $limit = 50, int $offset = 0): array
    {
        // Ensure reasonable limits
        $limit = min(100, max(1, $limit));
        $offset = max(0, $offset);

        $sql = "SELECT t.*, b.title AS book_title,
                       b.cover_image AS book_image,
                       u1.username AS buyer_username,
                       u2.username AS seller_username
                FROM {$this->table} t
                INNER JOIN books b ON t.book_id = b.book_id
                INNER JOIN users u1 ON t.buyer_id = u1.user_id
                INNER JOIN users u2 ON t.seller_id = u2.user_id
                WHERE t.buyer_id = :id OR t.seller_id = :id
                ORDER BY t.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, ['id' => $userId]);
    }

    /**
     * Get purchases for a specific user
     *
     * @param int $userId User ID
     * @param int $limit Maximum number of transactions
     * @return array
     */
    public function getUserPurchases(int $userId, int $limit = 20): array
    {
        $limit = min(100, max(1, $limit));

        $sql = "SELECT t.*, b.title AS book_title,
                       b.cover_image AS book_image,
                       u.username AS seller_username
                FROM {$this->table} t
                INNER JOIN books b ON t.book_id = b.book_id
                INNER JOIN users u ON t.seller_id = u.user_id
                WHERE t.buyer_id = :id
                ORDER BY t.created_at DESC
                LIMIT {$limit}";

        return $this->db->fetchAll($sql, ['id' => $userId]);
    }

    /**
     * Get sales for a specific user
     *
     * @param int $userId User ID
     * @param int $limit Maximum number of transactions
     * @return array
     */
    public function getUserSales(int $userId, int $limit = 20): array
    {
        $limit = min(100, max(1, $limit));

        $sql = "SELECT t.*, b.title AS book_title,
                       b.cover_image AS book_image,
                       u.username AS buyer_username
                FROM {$this->table} t
                INNER JOIN books b ON t.book_id = b.book_id
                INNER JOIN users u ON t.buyer_id = u.user_id
                WHERE t.seller_id = :id
                ORDER BY t.created_at DESC
                LIMIT {$limit}";

        return $this->db->fetchAll($sql, ['id' => $userId]);
    }

    /**
     * Update payment status
     *
     * @param int $transactionId Transaction ID
     * @param string $status New payment status
     * @param string|null $reference Payment reference
     * @return bool
     */
    public function updatePaymentStatus(int $transactionId, string $status, ?string $reference = null): bool
    {
        $validStatuses = ['pending', 'processing', 'completed', 'failed', 'refunded'];
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
            'transaction_id = :id',
            ['id' => $transactionId]
        );

        if ($updated > 0) {
            Logger::info('Payment status updated', [
                'transaction_id' => $transactionId,
                'status' => $status,
            ]);
        }

        return $updated > 0;
    }

    /**
     * Update delivery status
     *
     * @param int $transactionId Transaction ID
     * @param string $status New delivery status
     * @return bool
     */
    public function updateDeliveryStatus(int $transactionId, string $status): bool
    {
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'returned'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        $updated = $this->db->update(
            $this->table,
            ['delivery_status' => $status],
            'transaction_id = :id',
            ['id' => $transactionId]
        );

        return $updated > 0;
    }

    /**
     * Check if user can view a transaction
     *
     * @param int $transactionId Transaction ID
     * @param int $userId User ID
     * @return bool
     */
    public function canUserView(int $transactionId, int $userId): bool
    {
        $sql = "SELECT 1 FROM {$this->table}
                WHERE transaction_id = :tid
                AND (buyer_id = :uid OR seller_id = :uid)";

        return $this->db->fetch($sql, ['tid' => $transactionId, 'uid' => $userId]) !== null;
    }

    /**
     * Delete a transaction (admin only)
     * This will permanently remove the transaction from the database
     *
     * @param int $transactionId Transaction ID
     * @return bool
     */
    public function deleteTransaction(int $transactionId): bool
    {
        if ($transactionId <= 0) {
            return false;
        }

        $deleted = $this->db->delete(
            $this->table,
            'transaction_id = :id',
            ['id' => $transactionId]
        );

        if ($deleted > 0) {
            Logger::info('Transaction deleted', [
                'transaction_id' => $transactionId
            ]);
        }

        return $deleted > 0;
    }

    /**
     * Cancel a transaction (sets payment and delivery status to cancelled)
     * This is a soft delete that preserves the transaction record
     *
     * @param int $transactionId Transaction ID
     * @return bool
     */
    public function cancelTransaction(int $transactionId): bool
    {
        if ($transactionId <= 0) {
            return false;
        }

        $updated = $this->db->update(
            $this->table,
            [
                'payment_status' => 'cancelled',
                'delivery_status' => 'cancelled'
            ],
            'transaction_id = :id',
            ['id' => $transactionId]
        );

        if ($updated > 0) {
            Logger::info('Transaction cancelled', [
                'transaction_id' => $transactionId
            ]);
        }

        return $updated > 0;
    }
}
