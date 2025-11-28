<?php

require_once __DIR__ . '/User.php';

class Vendor extends User
{
    public function getVendorDashboard(int $vendorId): array
    {
        $sql = "SELECT
                    COUNT(DISTINCT t.transaction_id) AS total_orders,
                    SUM(CASE WHEN t.payment_status = 'completed' THEN t.total_amount ELSE 0 END) AS total_earned
                FROM fp_transactions t
                WHERE t.seller_id = :id";
        $row = $this->db->fetch($sql, ['id' => $vendorId]);
        return $row ?: ['total_orders' => 0, 'total_earned' => 0];
    }
}