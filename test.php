<?php
// Test PHP and Database Connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PHP Test</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current Directory: " . __DIR__ . "<br><br>";

// Test database configuration
echo "<h3>Testing Database Configuration...</h3>";

try {
    // Load database config
    if (file_exists(__DIR__ . '/config/settings/db_class.php')) {
        $config = require __DIR__ . '/config/settings/db_class.php';
        echo "✓ Database config file exists<br>";

        $dbConfig = $config['connections']['mysql'];
        echo "Host: " . $dbConfig['host'] . "<br>";
        echo "Database: " . $dbConfig['database'] . "<br>";
        echo "Username: " . $dbConfig['username'] . "<br>";
        echo "Password: " . (empty($dbConfig['password']) ? 'NOT SET' : 'SET (hidden)') . "<br><br>";

        // Test connection
        echo "<h3>Testing Database Connection...</h3>";
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";

        $pdo = new PDO(
            $dsn,
            $dbConfig['username'],
            $dbConfig['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        echo "✓ <strong>Database connection successful!</strong><br><br>";

        // Check if tables exist
        echo "<h3>Checking Tables...</h3>";
        $stmt = $pdo->query("SHOW TABLES LIKE 'fp_%'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($tables) > 0) {
            echo "✓ Found " . count($tables) . " tables with fp_ prefix:<br>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
        } else {
            echo "⚠ <strong>No tables found with fp_ prefix!</strong><br>";
            echo "You need to import db/database_schema.sql<br>";
        }

    } else {
        echo "✗ <strong>ERROR: config/settings/db_class.php not found!</strong><br>";
        echo "Expected path: " . __DIR__ . '/config/settings/db_class.php<br>';
    }

} catch (PDOException $e) {
    echo "✗ <strong>Database Connection Error:</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<br><h3>File Permissions Check:</h3>";
$dirs = ['uploads', 'logs', 'config', 'config/settings'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (file_exists($path)) {
        echo "$dir: " . substr(sprintf('%o', fileperms($path)), -4) . " - " .
             (is_writable($path) ? '✓ Writable' : '✗ Not writable') . "<br>";
    } else {
        echo "$dir: ✗ Does not exist<br>";
    }
}

echo "<br><h3>Required PHP Extensions:</h3>";
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? '✓ Loaded' : '✗ Not loaded') . "<br>";
}
?>
