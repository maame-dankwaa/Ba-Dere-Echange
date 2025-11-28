<?php

require_once __DIR__ . '/Database.php';

class VendorApplication
{
    protected $db;
    protected $table = 'fp_vendor_applications';
    protected $primaryKey = 'application_id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new vendor application
     */
    public function createApplication(array $data): int
    {
        $row = [
            'user_id' => $data['user_id'],
            'business_name' => $data['business_name'] ?? null,
            'business_description' => $data['business_description'] ?? null,
            'phone' => $data['phone'] ?? null,
            'id_document' => $data['id_document'] ?? null,
            'application_reason' => $data['application_reason'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];

        return (int)$this->db->insert($this->table, $row);
    }

    /**
     * Get application by ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT a.*,
                       u.username, u.email, u.user_role,
                       admin.username as reviewer_username
                FROM {$this->table} a
                INNER JOIN fp_users u ON a.user_id = u.user_id
                LEFT JOIN fp_users admin ON a.reviewed_by = admin.user_id
                WHERE a.{$this->primaryKey} = :id
                LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }

    /**
     * Get application by user ID and status
     */
    public function findByUserAndStatus(int $userId, string $status): ?array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE user_id = :user_id AND status = :status
                ORDER BY created_at DESC
                LIMIT 1";
        return $this->db->fetch($sql, [
            'user_id' => $userId,
            'status' => $status
        ]);
    }

    /**
     * Check if user has a pending application
     */
    public function hasPendingApplication(int $userId): bool
    {
        $application = $this->findByUserAndStatus($userId, 'pending');
        return $application !== null;
    }

    /**
     * Get latest application for a user
     */
    public function getLatestByUser(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT 1";
        return $this->db->fetch($sql, ['user_id' => $userId]);
    }

    /**
     * Get all applications with a specific status
     */
    public function getByStatus(string $status, int $limit = 50): array
    {
        $sql = "SELECT a.*,
                       u.username, u.email, u.phone as user_phone, u.location
                FROM {$this->table} a
                INNER JOIN fp_users u ON a.user_id = u.user_id
                WHERE a.status = :status
                ORDER BY a.created_at DESC
                LIMIT {$limit}";
        return $this->db->fetchAll($sql, ['status' => $status]);
    }

    /**
     * Get all pending applications (for admin review)
     */
    public function getPendingApplications(int $limit = 50): array
    {
        return $this->getByStatus('pending', $limit);
    }

    /**
     * Get all applications (for admin)
     */
    public function getAllApplications(int $limit = 100): array
    {
        $sql = "SELECT a.*,
                       u.username, u.email, u.phone as user_phone, u.location,
                       admin.username as reviewer_username
                FROM {$this->table} a
                INNER JOIN fp_users u ON a.user_id = u.user_id
                LEFT JOIN fp_users admin ON a.reviewed_by = admin.user_id
                ORDER BY
                    CASE a.status
                        WHEN 'pending' THEN 1
                        WHEN 'approved' THEN 2
                        WHEN 'rejected' THEN 3
                    END,
                    a.created_at DESC
                LIMIT {$limit}";
        return $this->db->fetchAll($sql);
    }

    /**
     * Approve an application and promote user to vendor
     */
    public function approveApplication(int $applicationId, int $reviewedBy): bool
    {
        $application = $this->find($applicationId);

        if (!$application || $application['status'] !== 'pending') {
            return false;
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Update application status
            $updated = $this->db->update(
                $this->table,
                [
                    'status' => 'approved',
                    'reviewed_by' => $reviewedBy,
                    'reviewed_at' => date('Y-m-d H:i:s')
                ],
                "{$this->primaryKey} = :id",
                ['id' => $applicationId]
            );

            if ($updated === 0) {
                throw new Exception('Failed to update application');
            }

            // Promote user to vendor
            $userUpdated = $this->db->update(
                'fp_users',
                ['user_role' => 'vendor'],
                "user_id = :id",
                ['id' => $application['user_id']]
            );

            if ($userUpdated === 0) {
                throw new Exception('Failed to update user role');
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Failed to approve vendor application: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject an application
     */
    public function rejectApplication(int $applicationId, int $reviewedBy, string $reason = null): bool
    {
        $application = $this->find($applicationId);

        if (!$application || $application['status'] !== 'pending') {
            return false;
        }

        return $this->db->update(
            $this->table,
            [
                'status' => 'rejected',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'rejection_reason' => $reason
            ],
            "{$this->primaryKey} = :id",
            ['id' => $applicationId]
        ) > 0;
    }

    /**
     * Get statistics for admin dashboard
     */
    public function getStatistics(): array
    {
        $sql = "SELECT
                COUNT(*) as total_applications,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                FROM {$this->table}";

        $row = $this->db->fetch($sql);
        return $row ?: [
            'total_applications' => 0,
            'pending_count' => 0,
            'approved_count' => 0,
            'rejected_count' => 0
        ];
    }
}
