<?php
/**
 * Logger Service Class
 * Provides secure logging functionality without exposing sensitive information to users
 */

class Logger
{
    private static ?self $instance = null;
    private string $logPath;
    private bool $enabled;
    private string $minLevel;

    private const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4,
    ];

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->logPath = __DIR__ . '/../logs';
        $this->enabled = true;
        $this->minLevel = 'debug';

        // Create logs directory if it doesn't exist
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }

        // Create .htaccess to prevent direct access to logs
        $htaccessPath = $this->logPath . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n");
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
     * Log a debug message
     *
     * @param string $message
     * @param array $context
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->log('debug', $message, $context);
    }

    /**
     * Log an info message
     *
     * @param string $message
     * @param array $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->log('info', $message, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message
     * @param array $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->log('warning', $message, $context);
    }

    /**
     * Log an error message
     *
     * @param string $message
     * @param array $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->log('error', $message, $context);
    }

    /**
     * Log a critical message
     *
     * @param string $message
     * @param array $context
     */
    public static function critical(string $message, array $context = []): void
    {
        self::getInstance()->log('critical', $message, $context);
    }

    /**
     * Log an exception
     *
     * @param Throwable $e
     * @param array $context
     */
    public static function exception(Throwable $e, array $context = []): void
    {
        $context['exception'] = [
            'class' => get_class($e),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        self::getInstance()->log('error', $e->getMessage(), $context);
    }

    /**
     * Log a security event
     *
     * @param string $message
     * @param array $context
     */
    public static function security(string $message, array $context = []): void
    {
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $context['user_id'] = $_SESSION['user_id'] ?? null;

        self::getInstance()->log('warning', '[SECURITY] ' . $message, $context, 'security');
    }

    /**
     * Log an authentication event
     *
     * @param string $event Type of auth event (login, logout, failed_login, etc.)
     * @param array $context
     */
    public static function auth(string $event, array $context = []): void
    {
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        self::getInstance()->log('info', '[AUTH] ' . $event, $context, 'auth');
    }

    /**
     * Log a database error
     *
     * @param string $message
     * @param array $context
     */
    public static function database(string $message, array $context = []): void
    {
        // Never log sensitive data like passwords or full queries with user data
        if (isset($context['query'])) {
            // Truncate very long queries
            $context['query'] = substr($context['query'], 0, 500);
        }

        self::getInstance()->log('error', '[DATABASE] ' . $message, $context, 'database');
    }

    /**
     * Core logging method
     *
     * @param string $level Log level
     * @param string $message Message to log
     * @param array $context Additional context
     * @param string $channel Log channel/file
     */
    public function log(string $level, string $message, array $context = [], string $channel = 'app'): void
    {
        if (!$this->enabled) {
            return;
        }

        // Check minimum level
        if (!isset(self::LEVELS[$level]) || self::LEVELS[$level] < self::LEVELS[$this->minLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);

        // Sanitize context to remove sensitive data
        $context = $this->sanitizeContext($context);

        // Format the log entry
        $entry = "[{$timestamp}] [{$levelUpper}] {$message}";
        if (!empty($context)) {
            $entry .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        $entry .= PHP_EOL;

        // Write to file
        $filename = $this->logPath . '/' . $channel . '-' . date('Y-m-d') . '.log';
        file_put_contents($filename, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Sanitize context to remove sensitive data
     *
     * @param array $context
     * @return array
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password',
            'password_hash',
            'password_confirmation',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
            'token',
            'api_key',
            'secret',
            'auth',
            'authorization',
        ];

        foreach ($context as $key => $value) {
            $lowerKey = strtolower($key);

            // Check if key contains sensitive words
            foreach ($sensitiveKeys as $sensitive) {
                if (strpos($lowerKey, $sensitive) !== false) {
                    $context[$key] = '[REDACTED]';
                    break;
                }
            }

            // Recursively sanitize nested arrays
            if (is_array($value)) {
                $context[$key] = $this->sanitizeContext($value);
            }
        }

        return $context;
    }

    /**
     * Get a user-safe error message (doesn't expose internal details)
     *
     * @param string $internalMessage The actual error message (for logging)
     * @param string $userMessage The message to show to users
     * @param array $context Additional context for logging
     * @return string The user-safe message
     */
    public static function safeError(string $internalMessage, string $userMessage = 'An error occurred. Please try again.', array $context = []): string
    {
        // Log the real error
        self::error($internalMessage, $context);

        // Return safe message for users
        return $userMessage;
    }

    /**
     * Handle a database exception safely
     *
     * @param PDOException $e
     * @param string $operation Description of what operation was being performed
     * @return string User-safe error message
     */
    public static function handleDatabaseError(PDOException $e, string $operation = 'database operation'): string
    {
        self::database($e->getMessage(), [
            'code' => $e->getCode(),
            'operation' => $operation,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return "A database error occurred during {$operation}. Please try again later.";
    }

    /**
     * Enable or disable logging
     *
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Set minimum log level
     *
     * @param string $level
     */
    public function setMinLevel(string $level): void
    {
        if (isset(self::LEVELS[$level])) {
            $this->minLevel = $level;
        }
    }

    /**
     * Set custom log path
     *
     * @param string $path
     */
    public function setLogPath(string $path): void
    {
        $this->logPath = $path;
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Clean old log files
     *
     * @param int $daysToKeep Number of days to keep logs
     */
    public static function cleanup(int $daysToKeep = 30): void
    {
        $instance = self::getInstance();
        $cutoff = time() - ($daysToKeep * 86400);

        $files = glob($instance->logPath . '/*.log');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}
