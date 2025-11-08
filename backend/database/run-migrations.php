<?php

// Simple migration runner
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env.local')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.local');
} else {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
}
$dotenv->load();

// Database connection
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'warranty_db';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

$dsn = "mysql:host={$host};port={$port};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Connected to database successfully.\n";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$database} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE {$database}");
    
    echo "Database '{$database}' ready.\n";
    
    // Get migration files
    $migrationDir = __DIR__ . '/migrations';
    $migrationFiles = glob($migrationDir . '/*.sql');
    sort($migrationFiles);
    
    if (empty($migrationFiles)) {
        echo "No migration files found.\n";
        exit(0);
    }
    
    // Run migrations
    foreach ($migrationFiles as $file) {
        $filename = basename($file);
        echo "Running migration: {$filename}\n";
        
        $sql = file_get_contents($file);
        
        // Split multiple statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    echo "Warning in {$filename}: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "Completed: {$filename}\n";
    }
    
    echo "\nAll migrations completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}