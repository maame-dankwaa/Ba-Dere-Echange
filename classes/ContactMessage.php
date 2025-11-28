<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../services/Logger.php';

class ContactMessage
{
    protected $db;
    protected $table = 'fp_contact_messages';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new contact message
     */
    public function create(array $data): int
    {
        $row = [
            'user_id' => $data['user_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return (int)$this->db->insert($this->table, $row);
    }

    /**
     * Get all contact messages with optional filters
     */
    public function getAll(?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT cm.*, u.username, u.email as user_email
                FROM {$this->table} cm
                LEFT JOIN fp_users u ON cm.user_id = u.user_id";

        $params = [];

        if ($status) {
            $sql .= " WHERE cm.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY cm.created_at DESC LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get messages for a specific user
     */
    public function getUserMessages(int $userId, int $limit = 20): array
    {
        $sql = "SELECT cm.*, a.username as responded_by_name
                FROM {$this->table} cm
                LEFT JOIN fp_users a ON cm.responded_by = a.user_id
                WHERE cm.user_id = :user_id
                ORDER BY cm.created_at DESC
                LIMIT {$limit}";

        try {
            return $this->db->fetchAll($sql, ['user_id' => $userId]);
        } catch (Throwable $e) {
            Logger::database('Failed to load contact messages', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            if (defined('SHOW_DEBUG_ERRORS') && SHOW_DEBUG_ERRORS) {
                echo '<pre style="color:#b91c1c;background:#fee2e2;padding:12px;border-radius:6px;">';
                echo 'Contact messages error: ' . htmlspecialchars($e->getMessage());
                echo '</pre>';
            }

            return [];
        }
    }

    /**
     * Get a single message by ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT cm.*, u.username, u.email as user_email,
                       a.username as responded_by_name
                FROM {$this->table} cm
                LEFT JOIN fp_users u ON cm.user_id = u.user_id
                LEFT JOIN fp_users a ON cm.responded_by = a.user_id
                WHERE cm.message_id = :id";

        $result = $this->db->fetch($sql, ['id' => $id]);
        return $result ?: null;
    }

    /**
     * Update message status
     */
    public function updateStatus(int $id, string $status): bool
    {
        $validStatuses = ['new', 'read', 'responded', 'archived'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $updated = $this->db->update(
            $this->table,
            ['status' => $status],
            'message_id = :id',
            ['id' => $id]
        );

        return $updated > 0;
    }

    /**
     * Add admin response to a message
     */
    public function addResponse(int $messageId, string $response, int $adminId): bool
    {
        $data = [
            'admin_response' => $response,
            'responded_by' => $adminId,
            'responded_at' => date('Y-m-d H:i:s'),
            'status' => 'responded',
        ];

        $updated = $this->db->update(
            $this->table,
            $data,
            'message_id = :id',
            ['id' => $messageId]
        );

        return $updated > 0;
    }

    /**
     * Get count of new messages
     */
    public function getNewCount(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'new'";
        $result = $this->db->fetch($sql);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get statistics
     */
    public function getStats(): array
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
                    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
                    SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded_count,
                    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count
                FROM {$this->table}";

        return $this->db->fetch($sql);
    }

    /**
     * Delete a message (soft delete by archiving)
     */
    public function archive(int $id): bool
    {
        return $this->updateStatus($id, 'archived');
    }

    /**
     * Unarchive a message (restore from archive to read status)
     */
    public function unarchive(int $id): bool
    {
        return $this->updateStatus($id, 'read');
    }
}
