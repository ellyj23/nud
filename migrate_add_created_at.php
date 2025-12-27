<?php
/**
 * Migration script to add created_at column to clients table
 * Run this once to update the database schema
 */

require_once 'db.php';

if (!isset($pdo)) {
    die("Database connection failed. Check db.php configuration.\n");
}

try {
    echo "Starting migration to add created_at column...\n";
    
    // Check if column already exists
    $checkSql = "SHOW COLUMNS FROM clients LIKE 'created_at'";
    $stmt = $pdo->query($checkSql);
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "Column 'created_at' already exists. Migration skipped.\n";
        exit(0);
    }
    
    // Add created_at column
    echo "Adding created_at column...\n";
    $pdo->exec("ALTER TABLE clients ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER created_by_id");
    echo "✓ Column added successfully.\n";
    
    // Update existing records to use the date field as created_at
    echo "Updating existing records...\n";
    $updateSql = "UPDATE clients SET created_at = CONCAT(date, ' 00:00:00') WHERE created_at IS NULL AND date IS NOT NULL";
    $rowsUpdated = $pdo->exec($updateSql);
    echo "✓ Updated $rowsUpdated existing records.\n";
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
