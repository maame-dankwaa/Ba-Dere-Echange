<?php

require_once __DIR__ . '/Database.php';

class Category
{
    protected $db;
    protected $table = 'fp_categories';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getActiveCategories(): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE is_active = 1
                ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getPopularCategories(int $limit = 8): array
    {
        $sql = "SELECT c.*, COUNT(b.book_id) AS book_count
                FROM {$this->table} c
                INNER JOIN fp_books b ON c.category_id = b.category_id
                WHERE c.is_active = 1 AND b.status = 'active'
                GROUP BY c.category_id
                ORDER BY book_count DESC
                LIMIT {$limit}";
        return $this->db->fetchAll($sql);
    }

    public function getTopLevelCategories(): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE parent_id IS NULL AND is_active = 1
                ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getSubcategories(int $parentId): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE parent_id = :parent_id
                  AND is_active = 1
                ORDER BY name ASC";
        return $this->db->fetchAll($sql, ['parent_id' => $parentId]);
    }
}
