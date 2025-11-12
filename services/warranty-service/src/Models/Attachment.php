<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class Attachment
{
    public static function create(array $data): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO attachments (claim_id, filename, path, mimetype, size)
             VALUES (:cid, :fn, :p, :mt, :sz)"
        );
        $stmt->execute([
            ':cid' => $data['claim_id'],
            ':fn'  => $data['filename'],
            ':p'   => $data['path'],
            ':mt'  => $data['mimetype'] ?? null,
            ':sz'  => (int)($data['size'] ?? 0),
        ]);
        $id = (int)$db->lastInsertId();

        $q = $db->prepare("SELECT id, filename, path, mimetype, size, created_at FROM attachments WHERE id = :id");
        $q->execute([':id' => $id]);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    public static function listByClaim(string $claimId): array
    {
        $db = Database::getConnection();
        $q = $db->prepare(
            "SELECT id, filename, path, mimetype, size, created_at
             FROM attachments
             WHERE claim_id = :cid
             ORDER BY created_at DESC"
        );
        $q->execute([':cid' => $claimId]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
