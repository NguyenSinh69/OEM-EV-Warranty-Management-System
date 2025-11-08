<?php
require_once '../Database.php';

class WarrantyRequest {
    private $conn;
    private $table_name = "warranty_requests";

    public $id;
    public $customer_id;
    public $vehicle_registration_id;
    public $issue_category_id;
    public $title;
    public $description;
    public $status;
    public $priority;
    public $current_mileage;
    public $estimated_cost;
    public $actual_cost;
    public $labor_hours;
    public $assigned_staff_id;
    public $reviewer_id;
    public $review_notes;
    public $completion_notes;
    public $requested_date;
    public $reviewed_date;
    public $approved_date;
    public $completed_date;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Lấy tất cả yêu cầu bảo hành
    public function readAll() {
        $query = "SELECT wr.*, u.full_name as customer_name, vm.name as vehicle_name, 
                         ic.name as issue_category, vr.vin, vr.license_plate
                  FROM " . $this->table_name . " wr
                  LEFT JOIN users u ON wr.customer_id = u.id
                  LEFT JOIN vehicle_registrations vr ON wr.vehicle_registration_id = vr.id
                  LEFT JOIN vehicle_models vm ON vr.vehicle_model_id = vm.id
                  LEFT JOIN issue_categories ic ON wr.issue_category_id = ic.id
                  ORDER BY wr.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Lấy yêu cầu bảo hành theo ID
    public function readOne() {
        $query = "SELECT wr.*, u.full_name as customer_name, u.email as customer_email,
                         vm.name as vehicle_name, ic.name as issue_category, 
                         vr.vin, vr.license_plate, vr.purchase_date,
                         staff.full_name as assigned_staff_name,
                         reviewer.full_name as reviewer_name
                  FROM " . $this->table_name . " wr
                  LEFT JOIN users u ON wr.customer_id = u.id
                  LEFT JOIN vehicle_registrations vr ON wr.vehicle_registration_id = vr.id
                  LEFT JOIN vehicle_models vm ON vr.vehicle_model_id = vm.id
                  LEFT JOIN issue_categories ic ON wr.issue_category_id = ic.id
                  LEFT JOIN users staff ON wr.assigned_staff_id = staff.id
                  LEFT JOIN users reviewer ON wr.reviewer_id = reviewer.id
                  WHERE wr.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        return $stmt;
    }

    // Tạo yêu cầu bảo hành mới
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET customer_id=:customer_id, vehicle_registration_id=:vehicle_registration_id,
                      issue_category_id=:issue_category_id, title=:title, description=:description,
                      priority=:priority, current_mileage=:current_mileage";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":vehicle_registration_id", $this->vehicle_registration_id);
        $stmt->bindParam(":issue_category_id", $this->issue_category_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":priority", $this->priority);
        $stmt->bindParam(":current_mileage", $this->current_mileage);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    // Cập nhật trạng thái yêu cầu
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . "
                  SET status=:status, reviewer_id=:reviewer_id, review_notes=:review_notes";

        if ($this->status === 'approved') {
            $query .= ", approved_date=NOW()";
        } elseif ($this->status === 'in_review') {
            $query .= ", reviewed_date=NOW()";
        } elseif ($this->status === 'completed') {
            $query .= ", completed_date=NOW(), completion_notes=:completion_notes, actual_cost=:actual_cost";
        }

        $query .= " WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":reviewer_id", $this->reviewer_id);
        $stmt->bindParam(":review_notes", $this->review_notes);
        $stmt->bindParam(":id", $this->id);

        if ($this->status === 'completed') {
            $stmt->bindParam(":completion_notes", $this->completion_notes);
            $stmt->bindParam(":actual_cost", $this->actual_cost);
        }

        return $stmt->execute();
    }

    // Gán nhân viên xử lý
    public function assignStaff() {
        $query = "UPDATE " . $this->table_name . "
                  SET assigned_staff_id=:assigned_staff_id, status='in_progress'
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":assigned_staff_id", $this->assigned_staff_id);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Thống kê theo trạng thái
    public function getStatusStats() {
        $query = "SELECT status, COUNT(*) as count FROM " . $this->table_name . " GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Lấy yêu cầu theo khách hàng
    public function getByCustomer($customer_id) {
        $query = "SELECT wr.*, vm.name as vehicle_name, ic.name as issue_category, 
                         vr.vin, vr.license_plate
                  FROM " . $this->table_name . " wr
                  LEFT JOIN vehicle_registrations vr ON wr.vehicle_registration_id = vr.id
                  LEFT JOIN vehicle_models vm ON vr.vehicle_model_id = vm.id
                  LEFT JOIN issue_categories ic ON wr.issue_category_id = ic.id
                  WHERE wr.customer_id = ?
                  ORDER BY wr.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $customer_id);
        $stmt->execute();
        return $stmt;
    }
}
?>