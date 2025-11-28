<?php

require_once __DIR__ . '/Database.php';

class Book
{
    /** @var Database */
    protected $db;
    protected $table = 'books';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE book_id = :id";
        $book = $this->db->fetch($sql, ['id' => $id]);
        return $book ?: null;
    }

    public function getBookDetails(int $id): ?array
    {
        $sql = "SELECT b.*, 
                       c.name AS category_name,
                       u.username AS seller_username,
                       u.location AS seller_location
                FROM {$this->table} b
                INNER JOIN categories c ON b.category_id = c.category_id
                INNER JOIN users u ON b.seller_id = u.user_id
                WHERE b.book_id = :id";

        $book = $this->db->fetch($sql, ['id' => $id]);
        return $book ?: null;
    }

    public function getBooksWithFilters(array $filters, int $page = 1, int $perPage = 20): array
    {
        $sql = "SELECT b.*, 
                       c.name AS category_name,
                       u.username AS seller_username,
                       u.location AS seller_location
                FROM {$this->table} b
                INNER JOIN categories c ON b.category_id = c.category_id
                INNER JOIN users u ON b.seller_id = u.user_id
                WHERE b.status = 'active'";

        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= " AND b.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (b.title LIKE :search1 OR b.author LIKE :search2 OR b.description LIKE :search3)";
            $params['search1'] = $searchTerm;
            $params['search2'] = $searchTerm;
            $params['search3'] = $searchTerm;
        }

        if (!empty($filters['min_price'])) {
            $sql .= " AND b.price >= :min_price";
            $params['min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $sql .= " AND b.price <= :max_price";
            $params['max_price'] = $filters['max_price'];
        }

        if (!empty($filters['condition'])) {
            $sql .= " AND b.condition_type = :condition_type";
            $params['condition_type'] = $filters['condition'];
        }

        if (!empty($filters['is_rentable'])) {
            $sql .= " AND b.is_rentable = 1";
        }

        if (!empty($filters['is_exchangeable'])) {
            $sql .= " AND b.is_exchangeable = 1";
        }

        if (!empty($filters['location'])) {
            $sql .= " AND u.location = :location";
            $params['location'] = $filters['location'];
        }

        // Sorting - Always prioritize featured listings
        $sql .= " ORDER BY (b.is_featured = 1 AND b.featured_until > NOW()) DESC";

        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_low':
                    $sql .= ", b.price ASC";
                    break;
                case 'price_high':
                    $sql .= ", b.price DESC";
                    break;
                case 'rating':
                    $sql .= ", b.average_rating DESC";
                    break;
                default:
                    $sql .= ", b.created_at DESC";
            }
        } else {
            $sql .= ", b.created_at DESC";
        }

        // Pagination
        $offset = ($page - 1) * $perPage;
        $sql   .= " LIMIT {$perPage} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    public function searchBooks(string $query, int $limit = 20): array
    {
        if ($query === '') return [];

        $searchTerm = '%' . $query . '%';

        $sql = "SELECT b.*, c.name AS category_name, u.username AS seller_username
                FROM {$this->table} b
                INNER JOIN categories c ON b.category_id = c.category_id
                INNER JOIN users u ON b.seller_id = u.user_id
                WHERE b.status = 'active'
                  AND (b.title LIKE :q1 OR b.author LIKE :q2 OR b.description LIKE :q3)
                ORDER BY b.created_at DESC
                LIMIT {$limit}";

        return $this->db->fetchAll($sql, [
            'q1' => $searchTerm,
            'q2' => $searchTerm,
            'q3' => $searchTerm
        ]);
    }

    public function getFeaturedBooks(int $limit = 6): array
    {
        $sql = "SELECT b.*, c.name AS category_name, u.username AS seller_username
                FROM {$this->table} b
                INNER JOIN categories c ON b.category_id = c.category_id
                INNER JOIN users u ON b.seller_id = u.user_id
                WHERE b.status = 'active' AND b.is_featured = 1
                ORDER BY b.featured_until DESC, b.created_at DESC
                LIMIT {$limit}";
        return $this->db->fetchAll($sql);
    }

    public function getTrendingBooks(int $days = 7, int $limit = 8): array
    {
        // Simplified: based on views + completed transactions in last N days
        $sql = "SELECT b.*, c.name AS category_name, u.username AS seller_username,
                       COUNT(t.transaction_id) AS recent_sales
                FROM {$this->table} b
                INNER JOIN categories c ON b.category_id = c.category_id
                INNER JOIN users u ON b.seller_id = u.user_id
                LEFT JOIN transactions t
                    ON t.book_id = b.book_id
                   AND t.payment_status = 'completed'
                   AND t.created_at >= (NOW() - INTERVAL :days DAY)
                WHERE b.status = 'active'
                GROUP BY b.book_id
                ORDER BY recent_sales DESC, b.views_count DESC
                LIMIT {$limit}";
        return $this->db->fetchAll($sql, ['days' => $days]);
    }

    public function getRecentBooks(int $limit = 12): array
    {
        $sql = "SELECT b.*, c.name AS category_name, u.username AS seller_username, u.location AS seller_location
                FROM {$this->table} b
                INNER JOIN categories c ON b.category_id = c.category_id
                INNER JOIN users u ON b.seller_id = u.user_id
                WHERE b.status = 'active'
                ORDER BY b.created_at DESC
                LIMIT {$limit}";
        return $this->db->fetchAll($sql);
    }

    public function incrementViews(int $bookId): void
    {
        $sql = "UPDATE {$this->table}
                SET views_count = views_count + 1
                WHERE book_id = :id";
        $this->db->query($sql, ['id' => $bookId]);
    }

    public function isAvailable(int $bookId, int $quantity): bool
    {
        $sql = "SELECT available_quantity, status
                FROM {$this->table}
                WHERE book_id = :id";
        $book = $this->db->fetch($sql, ['id' => $bookId]);
        if (!$book) return false;
        if ($book['status'] !== 'active') return false;
        return (int)$book['available_quantity'] >= $quantity;
    }

    public function updateAvailability(int $bookId, int $deltaQuantity): void
    {
        $sql = "UPDATE {$this->table}
                SET available_quantity = GREATEST(0, available_quantity + :delta)
                WHERE book_id = :id";
        $this->db->query($sql, ['delta' => $deltaQuantity, 'id' => $bookId]);
    }

    public function getTotalActiveBooks(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'active'";
        $result = $this->db->fetch($sql);
        return (int)($result['total'] ?? 0);
    }

    public function getSimilarBooks(int $bookId, int $limit = 4): array
    {
        $sql = "SELECT b2.*
                FROM {$this->table} b2
                WHERE b2.status = 'active'
                  AND b2.category_id = (
                      SELECT category_id FROM {$this->table} WHERE book_id = :id1
                  )
                  AND b2.book_id <> :id2
                ORDER BY b2.created_at DESC
                LIMIT {$limit}";
        return $this->db->fetchAll($sql, ['id1' => $bookId, 'id2' => $bookId]);
    }

    public function createListing(array $data): int
    {
        $row = [
            'seller_id'            => $data['seller_id'],
            'title'                => $data['title'],
            'author'               => $data['author'],
            'isbn'                 => $data['isbn_doi'] ?? null,
            'publication_year'     => $data['publication_year'] ?: null,
            'pages'                => $data['pages'] ?: null,
            'condition_type'       => $data['condition_type'],
            'category_id'          => $data['category_id'],
            'description'          => $data['description'],
            'is_rentable'          => $data['available_rent'] ?? 0,
            'is_exchangeable'      => $data['available_exchange'] ?? 0,
            'price'                => $data['price'] ?: 0,
            'rental_price'         => $data['rental_price'] ?: null,
            'rental_period_unit'   => $data['rental_period_unit'] ?? null,
            'rental_min_period'    => $data['rental_min_period'] ?? null,
            'rental_max_period'    => $data['rental_max_period'] ?? null,
            'exchange_duration'    => $data['exchange_duration'] ?? null,
            'exchange_duration_unit' => $data['exchange_duration_unit'] ?? null,
            'cover_image'          => $data['cover_image'] ?? null,
            'status'               => 'active',
            'available_quantity'   => $data['available_quantity'] ?? 1,
            'views_count'          => 0,
        ];

        return (int)$this->db->insert($this->table, $row);
    }

    public function updateListing(int $bookId, array $data): bool
    {
        $row = [
            'title'                => $data['title'],
            'author'               => $data['author'],
            'condition_type'       => $data['condition_type'],
            'category_id'          => $data['category_id'],
            'description'          => $data['description'],
            'is_rentable'          => $data['available_rent'] ?? 0,
            'is_exchangeable'      => $data['available_exchange'] ?? 0,
            'price'                => $data['price'] ?: 0,
            'available_quantity'   => $data['available_quantity'] ?? 1,
        ];

        // Add optional fields only if they have values
        if (!empty($data['isbn_doi'])) {
            $row['isbn'] = $data['isbn_doi'];
        }
        if (!empty($data['publication_year'])) {
            $row['publication_year'] = $data['publication_year'];
        }
        if (!empty($data['pages'])) {
            $row['pages'] = $data['pages'];
        }
        if (!empty($data['rental_price'])) {
            $row['rental_price'] = $data['rental_price'];
        }
        if (!empty($data['rental_period_unit'])) {
            $row['rental_period_unit'] = $data['rental_period_unit'];
        }
        if (!empty($data['rental_min_period'])) {
            $row['rental_min_period'] = $data['rental_min_period'];
        }
        if (!empty($data['rental_max_period'])) {
            $row['rental_max_period'] = $data['rental_max_period'];
        }
        if (!empty($data['exchange_duration'])) {
            $row['exchange_duration'] = $data['exchange_duration'];
        }
        if (!empty($data['exchange_duration_unit'])) {
            $row['exchange_duration_unit'] = $data['exchange_duration_unit'];
        }

        // Only update cover_image if provided
        if (isset($data['cover_image']) && $data['cover_image'] !== null) {
            $row['cover_image'] = $data['cover_image'];
        }

        $affectedRows = $this->db->update($this->table, $row, 'book_id = :id', ['id' => $bookId]);
        return $affectedRows > 0;
    }
}
