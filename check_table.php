<?php
$host = 'localhost';
$port = 3308;
$dbname = 'evm_vehicle_db';
$username = 'evm_user';
$password = 'evmpass123';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("DESCRIBE warranty_claims");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current warranty_claims table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} {$column['Null']} {$column['Default']}\n";
    }
    
    echo "\nChecking if table exists:\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'warranty_claims'");
    $exists = $stmt->fetch();
    echo $exists ? "Table exists\n" : "Table does not exist\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>