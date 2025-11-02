<?php
namespace Core;

use PDO;
use PDOException;
use Exception;

class Database {
    // Read DB config from environment when available (useful for local XAMPP/dev)
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->db_name = getenv('DB_NAME') ?: 'oem_ev_warranty';
        $this->username = getenv('DB_USER') ?: 'root';
        // default to empty password for common XAMPP setups unless DB_PASS provided
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $this->connect();
    }

    private function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            throw new Exception("Database connection error: " . $e->getMessage());
        }
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw new Exception("Query error: " . $e->getMessage());
        }
    }

    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch(PDOException $e) {
            throw new Exception("Execute error: " . $e->getMessage());
        }
    }

    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollback() {
        return $this->conn->rollBack();
    }
}
?>
