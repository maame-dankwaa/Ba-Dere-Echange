<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../services/Logger.php';

class PayoutRequest
{
    protected $db;
    protected $table = 'fp_payout_requests';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new payout request
     */
    public function create(array $data): int
    {
        $row = [
            'vendor_id' => $data['vendor_id'],
            'amount' => $data['amount'],
            'payout_method' => $data['payout_method'] ?? 'paystack',
            'account_details' => json_encode($data['account_details'] ?? []),
            'request_status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return (int)$this->db->insert($this->table, $row);
    }

    /**
     * Get payout request by ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT pr.*, 
                       u.username as vendor_username, 
                       u.email as vendor_email,
                       u.phone as vendor_phone,
                       admin.username as processed_by_name
                FROM {$this->table} pr
                LEFT JOIN fp_users u ON pr.vendor_id = u.user_id
                LEFT JOIN fp_users admin ON pr.processed_by = admin.user_id
                WHERE pr.request_id = :id";

        $result = $this->db->fetch($sql, ['id' => $id]);
        if ($result) {
            $result['account_details'] = json_decode($result['account_details'] ?? '{}', true);
        }
        return $result ?: null;
    }

    /**
     * Get all payout requests with optional filters
     */
    public function getAll(?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT pr.*, 
                       u.username as vendor_username, 
                       u.email as vendor_email,
                       admin.username as processed_by_name
                FROM {$this->table} pr
                LEFT JOIN fp_users u ON pr.vendor_id = u.user_id
                LEFT JOIN fp_users admin ON pr.processed_by = admin.user_id
                WHERE 1=1";

        $params = [];

        if ($status) {
            $sql .= " AND pr.request_status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY pr.created_at DESC LIMIT {$limit} OFFSET {$offset}";

        $results = $this->db->fetchAll($sql, $params);
        foreach ($results as &$result) {
            $result['account_details'] = json_decode($result['account_details'] ?? '{}', true);
        }
        return $results;
    }

    /**
     * Get payout requests for a specific vendor
     */
    public function getVendorRequests(int $vendorId, int $limit = 20): array
    {
        $sql = "SELECT pr.*, 
                       admin.username as processed_by_name
                FROM {$this->table} pr
                LEFT JOIN fp_users admin ON pr.processed_by = admin.user_id
                WHERE pr.vendor_id = :vendor_id
                ORDER BY pr.created_at DESC
                LIMIT {$limit}";

        $results = $this->db->fetchAll($sql, ['vendor_id' => $vendorId]);
        foreach ($results as &$result) {
            $result['account_details'] = json_decode($result['account_details'] ?? '{}', true);
        }
        return $results;
    }

    /**
     * Update payout request status
     */
    public function updateStatus(int $id, string $status, ?int $processedBy = null, ?string $reason = null): bool
    {
        $validStatuses = ['pending', 'approved', 'processing', 'completed', 'rejected', 'failed'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $data = [
            'request_status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($processedBy) {
            $data['processed_by'] = $processedBy;
            $data['processed_at'] = date('Y-m-d H:i:s');
        }

        if ($reason) {
            if ($status === 'rejected') {
                $data['rejection_reason'] = $reason;
            } elseif ($status === 'failed') {
                $data['failure_reason'] = $reason;
            }
        }

        $updated = $this->db->update(
            $this->table,
            $data,
            'request_id = :id',
            ['id' => $id]
        );

        return $updated > 0;
    }

    /**
     * Update Paystack transfer code
     */
    public function updateTransferCode(int $id, string $transferCode, ?string $transactionRef = null): bool
    {
        $data = [
            'paystack_transfer_code' => $transferCode,
        ];

        if ($transactionRef) {
            $data['transaction_reference'] = $transactionRef;
        }

        $updated = $this->db->update(
            $this->table,
            $data,
            'request_id = :id',
            ['id' => $id]
        );

        return $updated > 0;
    }

    /**
     * Get vendor's available earnings (completed transactions not yet paid out)
     */
    public function getAvailableEarnings(int $vendorId): float
    {
        // Get total earnings from completed transactions
        $sql = "SELECT COALESCE(SUM(seller_amount), 0) as total_earnings
                FROM fp_transactions
                WHERE seller_id = :vendor_id 
                AND payment_status = 'completed'";

        $result = $this->db->fetch($sql, ['vendor_id' => $vendorId]);
        $totalEarnings = (float)($result['total_earnings'] ?? 0);

        // Get total already paid out (completed payout requests)
        $sql2 = "SELECT COALESCE(SUM(amount), 0) as total_paid
                 FROM {$this->table}
                 WHERE vendor_id = :vendor_id 
                 AND request_status IN ('completed', 'processing')";

        $result2 = $this->db->fetch($sql2, ['vendor_id' => $vendorId]);
        $totalPaid = (float)($result2['total_paid'] ?? 0);

        return max(0, $totalEarnings - $totalPaid);
    }

    /**
     * Get vendor's total earnings
     */
    public function getTotalEarnings(int $vendorId): float
    {
        $sql = "SELECT COALESCE(SUM(seller_amount), 0) as total_earnings
                FROM fp_transactions
                WHERE seller_id = :vendor_id 
                AND payment_status = 'completed'";

        $result = $this->db->fetch($sql, ['vendor_id' => $vendorId]);
        return (float)($result['total_earnings'] ?? 0);
    }

    /**
     * Get statistics
     */
    public function getStats(): array
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN request_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN request_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN request_status = 'processing' THEN 1 ELSE 0 END) as processing_count,
                    SUM(CASE WHEN request_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN request_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                    SUM(CASE WHEN request_status = 'completed' THEN amount ELSE 0 END) as total_paid
                FROM {$this->table}";

        return $this->db->fetch($sql);
    }
}

