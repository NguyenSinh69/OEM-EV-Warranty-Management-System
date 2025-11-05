<?php
namespace App\Database;


use PDO;
use PDOException;


class Database {
private static ?PDO $pdo = null;


public static function getConnection(): PDO {
if (self::$pdo) return self::$pdo;


$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$db = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE');
$user = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME');
$pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 3306;


$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";


try {
$options = [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
self::$pdo = new PDO($dsn, $user, $pass, $options);
return self::$pdo;
} catch (PDOException $e) {
error_log('DB Connection failed: ' . $e->getMessage());
throw $e;
}
}
}