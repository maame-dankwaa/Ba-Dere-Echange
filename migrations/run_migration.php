<?php
/**
 * Database Migration Runner
 * Run this file once to add the new rental/exchange period columns
 */

require_once __DIR__ . '/../classes/Database.php';

echo "Starting migration: Add rental and exchange period fields...\n";

try {
    $db = Database::getInstance();

    // Check if columns already exist
    $columns = $db->query("DESCRIBE books");
    $existingColumns = array_column($columns, 'Field');

    $columnsToAdd = ['rental_price', 'rental_period_unit', 'rental_min_period', 'rental_max_period', 'exchange_duration', 'exchange_duration_unit'];
    $missingColumns = array_diff($columnsToAdd, $existingColumns);

    if (empty($missingColumns)) {
        echo "✓ All columns already exist. Migration not needed.\n";
        exit(0);
    }

    echo "Adding missing columns: " . implode(', ', $missingColumns) . "\n";

    // Add rental_price
    if (in_array('rental_price', $missingColumns)) {
        $db->query("ALTER TABLE `books` ADD COLUMN `rental_price` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Price per rental period' AFTER `price`");
        echo "  ✓ Added rental_price\n";
    }

    // Add rental_period_unit
    if (in_array('rental_period_unit', $missingColumns)) {
        $db->query("ALTER TABLE `books` ADD COLUMN `rental_period_unit` ENUM('day','week','month') NULL DEFAULT 'day' COMMENT 'Rental pricing unit' AFTER `rental_price`");
        echo "  ✓ Added rental_period_unit\n";
    }

    // Add rental_min_period
    if (in_array('rental_min_period', $missingColumns)) {
        $db->query("ALTER TABLE `books` ADD COLUMN `rental_min_period` INT(11) NULL DEFAULT 1 COMMENT 'Minimum rental duration' AFTER `rental_period_unit`");
        echo "  ✓ Added rental_min_period\n";
    }

    // Add rental_max_period
    if (in_array('rental_max_period', $missingColumns)) {
        $db->query("ALTER TABLE `books` ADD COLUMN `rental_max_period` INT(11) NULL DEFAULT 30 COMMENT 'Maximum rental duration' AFTER `rental_min_period`");
        echo "  ✓ Added rental_max_period\n";
    }

    // Add exchange_duration
    if (in_array('exchange_duration', $missingColumns)) {
        $db->query("ALTER TABLE `books` ADD COLUMN `exchange_duration` INT(11) NULL DEFAULT 14 COMMENT 'Exchange duration value' AFTER `rental_max_period`");
        echo "  ✓ Added exchange_duration\n";
    }

    // Add exchange_duration_unit
    if (in_array('exchange_duration_unit', $missingColumns)) {
        $db->query("ALTER TABLE `books` ADD COLUMN `exchange_duration_unit` ENUM('day','week','month') NULL DEFAULT 'day' COMMENT 'Exchange duration unit' AFTER `exchange_duration`");
        echo "  ✓ Added exchange_duration_unit\n";
    }

    echo "\n✓ Migration completed successfully!\n";
    echo "\nYou can now:\n";
    echo "  - Create listings with rental periods and prices\n";
    echo "  - Set exchange durations\n";
    echo "  - Update existing listings\n";

} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "\nPlease run the SQL manually from:\n";
    echo "  migrations/add_rental_exchange_periods.sql\n";
    exit(1);
}
