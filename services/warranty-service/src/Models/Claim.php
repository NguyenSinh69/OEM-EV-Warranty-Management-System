<?php
namespace App\Models;

use App\Database\Database;
use Ramsey\Uuid\Uuid;
use PDO;

class Claim
{
    /**
     * Tạo claim mới (id = UUID v4, status mặc định PENDING)
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public static function create(array $data)
    {
        // Validate
        $vin = isset($data['vin']) ? trim((string) $data['vin']) : '';
        if ($vin === '') {
            throw new \InvalidArgumentException('vin is required and cannot be empty');
        }
        if (!isset($data['customer_id']) || !is_numeric($data['customer_id'])) {
            throw new \InvalidArgumentException('customer_id is required and must be a number');
        }

        $allowedStatuses = ['PENDING', 'APPROVED', 'REJECTED', 'IN_PROGRESS', 'CLOSED'];
        $status = $data['status'] ?? 'PENDING';
        if (!in_array($status, $allowedStatuses, true)) {
            throw new \InvalidArgumentException('status is invalid');
        }

        // Insert DB
        try {
            $db = Database::getConnection();
            $id = Uuid::uuid4()->toString();

            $sql = 'INSERT INTO claims (id, vin, customer_id, status, description)
                    VALUES (:id, :vin, :customer_id, :status, :description)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id'          => $id,
                ':vin'         => $vin,
                ':customer_id' => (int) $data['customer_id'],
                ':status'      => $status,
                ':description' => $data['description'] ?? null,
            ]);

            return self::find($id);
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                'Database error while creating claim: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Cập nhật claim theo id
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public static function update(string $id, array $data)
    {
        $allowedStatuses = ['PENDING', 'APPROVED', 'REJECTED', 'IN_PROGRESS', 'CLOSED'];

        if (array_key_exists('vin', $data) && trim((string) $data['vin']) === '') {
            throw new \InvalidArgumentException('vin cannot be empty');
        }
        if (array_key_exists('customer_id', $data) && !is_numeric($data['customer_id'])) {
            throw new \InvalidArgumentException('customer_id must be a number');
        }
        if (array_key_exists('status', $data) && !in_array($data['status'], $allowedStatuses, true)) {
            throw new \InvalidArgumentException('status is invalid');
        }

        $fields = [];
        $params = [':id' => $id];

        foreach (['vin', 'customer_id', 'status', 'description'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = :$f";
                $params[":$f"] = ($f === 'customer_id') ? (int) $data[$f] : $data[$f];
            }
        }

        if (empty($fields)) {
            return self::find($id);
        }

        try {
            $db = Database::getConnection();
            $sql = 'UPDATE claims SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return self::find($id);
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                'Database error while updating claim: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Xoá claim
     */
    public static function delete(string $id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM claims WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Tìm claim theo id
     */
    public static function find(string $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM claims WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Danh sách claims với bộ lọc + phân trang
     */
    public static function all(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $db = Database::getConnection();
        $where  = [];
        $params = [];

        if (!empty($filters['vin'])) {
            $where[] = 'vin = :vin';
            $params[':vin'] = $filters['vin'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $where[] = 'customer_id = :customer_id';
            $params[':customer_id'] = (int) $filters['customer_id'];
        }

        $sql = 'SELECT * FROM claims';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
