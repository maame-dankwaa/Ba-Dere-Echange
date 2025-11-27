<?php
/**
 * Database Connection Settings
 * Ba Dɛre Exchange
 */

return [
    // Default Database Connection
    'default' => 'mysql',

    // Database Connections
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_NAME') ?: 'ba_dere_exchange',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ],

        // Backup/Read Replica (if needed)
        'mysql_read' => [
            'driver' => 'mysql',
            'host' => getenv('DB_READ_HOST') ?: 'localhost',
            'port' => getenv('DB_READ_PORT') ?: '3306',
            'database' => getenv('DB_NAME') ?: 'ba_dere_exchange',
            'username' => getenv('DB_READ_USER') ?: 'root',
            'password' => getenv('DB_READ_PASS') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
    ],

    // PDO Options
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_TIMEOUT => 5,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ],

    // Connection Pool Settings
    'pool' => [
        'min_connections' => 2,
        'max_connections' => 10,
        'wait_timeout' => 30,
    ],

    // Query Logging
    'logging' => [
        'enabled' => getenv('DB_LOG_QUERIES') === 'true' ? true : false,
        'slow_query_threshold' => 1000, // Log queries slower than 1 second
    ],
];
