<?php
/**
 * Database Class
 * Singleton PDO wrapper with proper error handling and type hints
 */

require_once __DIR__ . '/../services/Logger.php';

class Database
{
    private static ?self $instance = null;
    private PDO $connection;
    private array $config;

    /**
     * Private constructor - use getInstance()
     */
    private function __construct()
    {
        $dbConfig = require __DIR__ . '/../config/settings/db_class.php';
        $this->config = $dbConfig['connections'][$dbConfig['default']];
        $options = $dbConfig['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );
        } catch (PDOException $e) {
            // Log the actual error securely
            Logger::database('Connection failed', [
                'host' => $this->config['host'],
                'database' => $this->config['database'],
                'error_code' => $e->getCode(),
            ]);

            // Throw a generic exception without exposing details
            throw new RuntimeException('Database connection failed. Please try again later.');
        }
    }

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Execute a query with parameters
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return PDOStatement
     * @throws RuntimeException on query failure
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log the actual error for debugging
            error_log("PDO Error: " . $e->getMessage());
            error_log("Error Code: " . $e->getCode());

            Logger::database('Query failed', [
                'query' => $this->truncateQuery($sql),
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);

            // Surface the original PDO error message so callers can provide more context.
            // This is helpful during active debugging (the caller decides what to show to users).
            throw new RuntimeException('Database error: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Fetch a single row
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return array|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Fetch all rows
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single column value
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return mixed
     */
    public function fetchColumn(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Insert a row and return the last insert ID
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|string Last insert ID
     */
    public function insert(string $table, array $data)
    {
        // Validate table name (prevent SQL injection)
        $table = $this->sanitizeIdentifier($table);

        $columns = implode(', ', array_map([$this, 'sanitizeIdentifier'], array_keys($data)));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        // Debug logging
        error_log("=== Database INSERT Debug ===");
        error_log("Table: " . $table);
        error_log("SQL: " . $sql);
        error_log("Data: " . json_encode($data));

        try {
            $this->query($sql, $data);
            $insertId = $this->connection->lastInsertId();
            error_log("Insert successful! ID: " . $insertId);
            return $insertId;
        } catch (Exception $e) {
            error_log("Insert failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update rows
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause (with placeholders)
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $table = $this->sanitizeIdentifier($table);

        $set = [];
        foreach ($data as $col => $val) {
            $safeCol = $this->sanitizeIdentifier($col);
            $set[] = "{$safeCol} = :{$col}";
        }
        $setClause = implode(', ', $set);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Delete rows
     *
     * @param string $table Table name
     * @param string $where WHERE clause (with placeholders)
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $table = $this->sanitizeIdentifier($table);
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Check if a record exists
     *
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters
     * @return bool
     */
    public function exists(string $table, string $where, array $params = []): bool
    {
        $table = $this->sanitizeIdentifier($table);
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        return $this->fetch($sql, $params) !== null;
    }

    /**
     * Count records
     *
     * @param string $table Table name
     * @param string $where WHERE clause (optional)
     * @param array $params Parameters
     * @return int
     */
    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $table = $this->sanitizeIdentifier($table);
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int)$this->fetchColumn($sql, $params);
    }

    /**
     * Begin a transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback a transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Check if in a transaction
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * Sanitize a table or column identifier
     *
     * @param string $identifier
     * @return string
     */
    private function sanitizeIdentifier(string $identifier): string
    {
        // Only allow alphanumeric and underscores
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }

    /**
     * Truncate query for logging (don't log full queries with potential sensitive data)
     *
     * @param string $sql
     * @return string
     */
    private function truncateQuery(string $sql): string
    {
        return substr($sql, 0, 200) . (strlen($sql) > 200 ? '...' : '');
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup()
    {
        throw new RuntimeException('Cannot unserialize singleton');
    }
}
