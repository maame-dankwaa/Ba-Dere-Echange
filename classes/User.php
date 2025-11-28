<?php

require_once __DIR__ . '/Database.php';

class User
{
    protected $db;
    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function hideSensitiveFields(array $user): array
    {
        unset($user['password_hash'], $user['verification_token']);
        return $user;
    }

    public function createUser(array $data): int
    {
        if (!empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        if (empty($data['full_name']) && !empty($data['username'])) {
            $data['full_name'] = $data['username'];
        }

        if (!isset($data['user_role'])) {
            $data['user_role'] = 'customer';
        }

        if (!isset($data['verification_token'])) {
            $data['verification_token'] = bin2hex(random_bytes(16));
        }

        $row = [
            'username'           => $data['username'],
            'email'              => $data['email'],
            'password_hash'      => $data['password_hash'],
            'full_name'          => $data['full_name'],
            'phone'              => $data['phone'] ?? null,
            'profile_image'      => $data['profile_image'] ?? null,
            'bio'                => $data['bio'] ?? null,
            'location'           => $data['location'] ?? null,
            'user_role'          => $data['user_role'],
            'account_type'       => $data['account_type'] ?? 'individual',
            'institution_name'   => $data['institution_name'] ?? null,
            'institution_type'   => $data['institution_type'] ?? null,
            'institution_verified' => $data['institution_verified'] ?? 0,
            'institution_verification_document' => $data['institution_verification_document'] ?? null,
            'business_registration_number' => $data['institution_registration_number'] ?? $data['business_registration_number'] ?? null,
            'business_address'   => $data['institution_address'] ?? $data['business_address'] ?? null,
            'website_url'        => $data['institution_website'] ?? $data['website_url'] ?? null,
            'email_verified'     => $data['email_verified'] ?? 0,
            'verification_token' => $data['verification_token'],
            'created_at'         => date('Y-m-d H:i:s'),
        ];

        return (int)$this->db->insert($this->table, $row);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE {$this->primaryKey} = :id LIMIT 1";
        $row = $this->db->fetch($sql, ['id' => $id]);
        return $row ? $this->hideSensitiveFields($row) : null;
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE email = :email LIMIT 1";
        $row = $this->db->fetch($sql, ['email' => $email]);
        return $row ? $this->hideSensitiveFields($row) : null;
    }

    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE username = :username LIMIT 1";
        $row = $this->db->fetch($sql, ['username' => $username]);
        return $row ? $this->hideSensitiveFields($row) : null;
    }

    public function verifyCredentials(string $email, string $password): ?array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE email = :email LIMIT 1";
        $user = $this->db->fetch($sql, ['email' => $email]);
        if (!$user) return null;

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $this->hideSensitiveFields($user);
    }

    public function updateLastLogin(int $userId): void
    {
        $sql = "UPDATE {$this->table}
                SET last_login = NOW()
                WHERE {$this->primaryKey} = :id";
        $this->db->query($sql, ['id' => $userId]);
    }

    public function getUsersByRole(string $role): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE user_role = :role
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, ['role' => $role]);
    }

    public function getWishlist(int $userId): array
    {
        $sql = "SELECT w.*, b.*
                FROM wishlists w
                INNER JOIN books b ON w.book_id = b.book_id
                WHERE w.user_id = :user_id
                ORDER BY w.created_at DESC";
        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }

    public function getStatistics(int $userId): array
    {
        try {
            $sql = "SELECT
                    (SELECT COUNT(*) FROM transactions t WHERE t.buyer_id = :id) AS total_purchases,
                    (SELECT COUNT(*) FROM transactions t WHERE t.seller_id = :id) AS total_sales,
                    (SELECT COUNT(*) FROM wishlists w WHERE w.user_id = :id) AS wishlist_items";
            $row = $this->db->fetch($sql, ['id' => $userId]);
            return $row ?: [
                'total_purchases' => 0,
                'total_sales'     => 0,
                'wishlist_items'  => 0,
            ];
        } catch (Exception $e) {
            // Return default values if tables don't exist yet
            return [
                'total_purchases' => 0,
                'total_sales'     => 0,
                'wishlist_items'  => 0,
            ];
        }
    }

    public function getPurchases(int $userId, int $limit = 10): array
    {
        try {
            $sql = "SELECT t.*, b.title AS book_title
                    FROM transactions t
                    INNER JOIN books b ON t.book_id = b.book_id
                    WHERE t.buyer_id = :id
                    ORDER BY t.created_at DESC
                    LIMIT {$limit}";
            return $this->db->fetchAll($sql, ['id' => $userId]);
        } catch (Exception $e) {
            // Return empty array if tables don't exist yet
            return [];
        }
    }

    public function getSales(int $userId, int $limit = 10): array
    {
        try {
            $sql = "SELECT t.*, b.title AS book_title
                    FROM transactions t
                    INNER JOIN books b ON t.book_id = b.book_id
                    WHERE t.seller_id = :id
                    ORDER BY t.created_at DESC
                    LIMIT {$limit}";
            return $this->db->fetchAll($sql, ['id' => $userId]);
        } catch (Exception $e) {
            // Return empty array if tables don't exist yet
            return [];
        }
    }

    /**
     * Check if an email already exists in the database
     *
     * @param string $email Email to check
     * @param int|null $excludeUserId Exclude this user ID from check (for updates)
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeUserId !== null) {
            $sql .= " AND {$this->primaryKey} != :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }

        $sql .= " LIMIT 1";

        return $this->db->fetch($sql, $params) !== null;
    }

    /**
     * Check if a username already exists in the database
     *
     * @param string $username Username to check
     * @param int|null $excludeUserId Exclude this user ID from check (for updates)
     * @return bool
     */
    public function usernameExists(string $username, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE username = :username";
        $params = ['username' => $username];

        if ($excludeUserId !== null) {
            $sql .= " AND {$this->primaryKey} != :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }

        $sql .= " LIMIT 1";

        return $this->db->fetch($sql, $params) !== null;
    }

    /**
     * Update user profile
     *
     * @param int $userId User ID
     * @param array $data Profile data to update
     * @return bool
     */
    public function updateProfile(int $userId, array $data): bool
    {
        // Only allow certain fields to be updated
        $allowedFields = ['full_name', 'phone', 'bio', 'location', 'profile_image', 'institution_name'];

        $updateData = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update(
            $this->table,
            $updateData,
            "{$this->primaryKey} = :id",
            ['id' => $userId]
        ) > 0;
    }

    /**
     * Change user password
     *
     * @param int $userId User ID
     * @param string $currentPassword Current password for verification
     * @param string $newPassword New password
     * @return bool True if password changed, false if current password is wrong
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        // Get current password hash
        $sql = "SELECT password_hash FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $user = $this->db->fetch($sql, ['id' => $userId]);

        if (!$user) {
            return false;
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }

        // Hash and update new password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        return $this->db->update(
            $this->table,
            ['password_hash' => $newHash, 'updated_at' => date('Y-m-d H:i:s')],
            "{$this->primaryKey} = :id",
            ['id' => $userId]
        ) > 0;
    }

    /**
     * Get user by ID with all fields (for internal use)
     *
     * @param int $id User ID
     * @return array|null
     */
    public function findWithSensitive(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }
}
