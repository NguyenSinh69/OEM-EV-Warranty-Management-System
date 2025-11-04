<?php
require_once __DIR__ . '/../Database.php';

class WarrantyClaim {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // 1️⃣ Tạo claim mới
    public function create($data) {
        $query = "INSERT INTO warranty_claims (vin, customer_id, description) 
                  VALUES (:vin, :customer_id, :description)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":vin" => $data['vin'],
            ":customer_id" => $data['customer_id'],
            ":description" => $data['description']
        ]);
        $id = $this->conn->lastInsertId();
        return ["message" => "Claim created successfully", "id" => $id];
    }

    // 2️⃣ Lấy danh sách tất cả claims
    public function getAll() {
        $query = "SELECT * FROM warranty_claims ORDER BY created_at DESC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3️⃣ Cập nhật trạng thái
    public function updateStatus($id, $status) {
        $query = "UPDATE warranty_claims SET status=:status WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([":status" => $status, ":id" => $id]);
        return ["message" => "Status updated successfully"];
    }

    // 4️⃣ Upload hình ảnh
    public function addAttachment($id, $file) {
        $targetDir = __DIR__ . '/../../uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $filename = uniqid("claim_") . "_" . basename($file["name"]);
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            $query = "UPDATE warranty_claims SET attachment=:attachment WHERE id=:id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([":attachment" => $filename, ":id" => $id]);
            return ["message" => "File uploaded successfully", "filename" => $filename];
        } else {
            return ["error" => "File upload failed"];
        }
    }

    // 5️⃣ Xem chi phí
    public function getCosts($id) {
        $query = "SELECT id, vin, costs FROM warranty_claims WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
        return ["error" => "Claim not found"];
    }

    // 6️⃣ Phê duyệt claim
    public function approve($id) {
        $query = "UPDATE warranty_claims SET status='APPROVED' WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([":id" => $id]);
        return ["message" => "Claim approved successfully"];
    }
}
