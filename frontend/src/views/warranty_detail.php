<?php
$warranty_id = $_GET['id'] ?? 0;

if (!$warranty_id) {
    header('Location: ?page=warranty-requests');
    exit();
}

require_once '../src/models/WarrantyRequest.php';

$database = new Database();
$conn = $database->getConnection();
$warranty = new WarrantyRequest();
$warranty->id = $warranty_id;

// Lấy thông tin yêu cầu bảo hành
$stmt = $warranty->readOne();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: ?page=warranty-requests');
    exit();
}

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'approve') {
        $warranty->status = 'approved';
        $warranty->reviewer_id = $_SESSION['user_id'];
        $warranty->review_notes = $_POST['review_notes'];
        
        if ($warranty->updateStatus()) {
            // Thêm vào lịch sử
            $history_query = "INSERT INTO warranty_status_history (warranty_request_id, old_status, new_status, changed_by, reason)
                             VALUES (?, ?, ?, ?, ?)";
            $history_stmt = $conn->prepare($history_query);
            $history_stmt->execute([$warranty_id, $request['status'], 'approved', $_SESSION['user_id'], $_POST['review_notes']]);
            
            $success = "Yêu cầu bảo hành đã được phê duyệt!";
            $request['status'] = 'approved';
        } else {
            $error = "Có lỗi xảy ra khi phê duyệt yêu cầu!";
        }
    } elseif ($action === 'reject') {
        $warranty->status = 'rejected';
        $warranty->reviewer_id = $_SESSION['user_id'];
        $warranty->review_notes = $_POST['review_notes'];
        
        if ($warranty->updateStatus()) {
            // Thêm vào lịch sử
            $history_query = "INSERT INTO warranty_status_history (warranty_request_id, old_status, new_status, changed_by, reason)
                             VALUES (?, ?, ?, ?, ?)";
            $history_stmt = $conn->prepare($history_query);
            $history_stmt->execute([$warranty_id, $request['status'], 'rejected', $_SESSION['user_id'], $_POST['review_notes']]);
            
            $success = "Yêu cầu bảo hành đã bị từ chối!";
            $request['status'] = 'rejected';
        } else {
            $error = "Có lỗi xảy ra khi từ chối yêu cầu!";
        }
    } elseif ($action === 'assign') {
        $warranty->assigned_staff_id = $_POST['assigned_staff_id'];
        
        if ($warranty->assignStaff()) {
            $success = "Đã gán nhân viên xử lý!";
            $request['assigned_staff_id'] = $_POST['assigned_staff_id'];
            $request['status'] = 'in_progress';
        } else {
            $error = "Có lỗi xảy ra khi gán nhân viên!";
        }
    }
}

// Lấy lịch sử trạng thái
$history_query = "SELECT wsh.*, u.full_name as changed_by_name
                  FROM warranty_status_history wsh
                  LEFT JOIN users u ON wsh.changed_by = u.id
                  WHERE wsh.warranty_request_id = ?
                  ORDER BY wsh.created_at DESC";
$history_stmt = $conn->prepare($history_query);
$history_stmt->execute([$warranty_id]);
$history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách staff
$staff_query = "SELECT id, full_name FROM users WHERE role IN ('admin', 'staff') AND is_active = 1";
$staff_stmt = $conn->prepare($staff_query);
$staff_stmt->execute();
$staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper functions
function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-warning',
        'in_review' => 'bg-info',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'in_progress' => 'bg-primary',
        'completed' => 'bg-success',
        'cancelled' => 'bg-secondary'
    ];
    return $badges[$status] ?? 'bg-secondary';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'Chờ xử lý',
        'in_review' => 'Đang xem xét',
        'approved' => 'Đã phê duyệt',
        'rejected' => 'Từ chối',
        'in_progress' => 'Đang xử lý',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy'
    ];
    return $texts[$status] ?? $status;
}

function getPriorityBadge($priority) {
    $badges = [
        'low' => 'bg-success',
        'medium' => 'bg-warning',
        'high' => 'bg-danger',
        'urgent' => 'bg-dark'
    ];
    return $badges[$priority] ?? 'bg-secondary';
}

function getPriorityText($priority) {
    $texts = [
        'low' => 'Thấp',
        'medium' => 'Trung bình',
        'high' => 'Cao',
        'urgent' => 'Khẩn cấp'
    ];
    return $texts[$priority] ?? $priority;
}
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="?page=warranty-requests">Yêu cầu bảo hành</a></li>
                <li class="breadcrumb-item active">Chi tiết #<?php echo $request['id']; ?></li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-tools"></i> Chi tiết yêu cầu bảo hành #<?php echo $request['id']; ?></h1>
            <div>
                <span class="badge <?php echo getStatusBadge($request['status']); ?> fs-6">
                    <?php echo getStatusText($request['status']); ?>
                </span>
                <span class="badge <?php echo getPriorityBadge($request['priority']); ?> fs-6 ms-2">
                    <?php echo getPriorityText($request['priority']); ?>
                </span>
            </div>
        </div>
        <hr>
    </div>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Thông tin chi tiết -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Thông tin yêu cầu</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Tiêu đề:</strong><br>
                        <p><?php echo htmlspecialchars($request['title']); ?></p>
                        
                        <strong>Mô tả:</strong><br>
                        <p><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                        
                        <strong>Số km hiện tại:</strong><br>
                        <p><?php echo number_format($request['current_mileage']); ?> km</p>
                    </div>
                    
                    <div class="col-md-6">
                        <strong>Khách hàng:</strong><br>
                        <p><?php echo htmlspecialchars($request['customer_name']); ?></p>
                        
                        <strong>Email:</strong><br>
                        <p><?php echo htmlspecialchars($request['customer_email']); ?></p>
                        
                        <strong>Danh mục sự cố:</strong><br>
                        <p><?php echo htmlspecialchars($request['issue_category']); ?></p>
                        
                        <strong>Ngày tạo:</strong><br>
                        <p><?php echo date('d/m/Y H:i', strtotime($request['requested_date'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thông tin xe -->
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-car"></i> Thông tin xe</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Mẫu xe:</strong><br>
                        <p><?php echo htmlspecialchars($request['vehicle_name']); ?></p>
                        
                        <strong>Biển số:</strong><br>
                        <p><?php echo htmlspecialchars($request['license_plate']); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <strong>VIN:</strong><br>
                        <p><?php echo htmlspecialchars($request['vin']); ?></p>
                        
                        <strong>Ngày mua:</strong><br>
                        <p><?php echo date('d/m/Y', strtotime($request['purchase_date'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lịch sử trạng thái -->
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Lịch sử trạng thái</h5>
            </div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                <p class="text-muted">Chưa có lịch sử thay đổi trạng thái.</p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($history as $item): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">
                                Từ <span class="badge bg-secondary"><?php echo getStatusText($item['old_status']); ?></span>
                                sang <span class="badge <?php echo getStatusBadge($item['new_status']); ?>">
                                    <?php echo getStatusText($item['new_status']); ?>
                                </span>
                            </h6>
                            <p class="timeline-subtitle">
                                <?php echo htmlspecialchars($item['changed_by_name']); ?> - 
                                <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                            </p>
                            <?php if ($item['reason']): ?>
                            <p class="timeline-description"><?php echo nl2br(htmlspecialchars($item['reason'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Panel điều khiển -->
    <div class="col-lg-4">
        <!-- Actions -->
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff'): ?>
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-cogs"></i> Thao tác</h5>
            </div>
            <div class="card-body">
                <?php if ($request['status'] === 'pending'): ?>
                <div class="d-grid gap-2">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="fas fa-check"></i> Phê duyệt
                    </button>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="fas fa-times"></i> Từ chối
                    </button>
                </div>
                <?php elseif ($request['status'] === 'approved' && !$request['assigned_staff_id']): ?>
                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#assignModal">
                    <i class="fas fa-user-plus"></i> Gán nhân viên
                </button>
                <?php endif; ?>
                
                <?php if ($request['status'] === 'in_progress'): ?>
                <button class="btn btn-success w-100" onclick="markCompleted()">
                    <i class="fas fa-check-circle"></i> Đánh dấu hoàn thành
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Thông tin xử lý -->
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-user-cog"></i> Thông tin xử lý</h5>
            </div>
            <div class="card-body">
                <?php if ($request['assigned_staff_name']): ?>
                <strong>Nhân viên xử lý:</strong><br>
                <p><?php echo htmlspecialchars($request['assigned_staff_name']); ?></p>
                <?php endif; ?>
                
                <?php if ($request['reviewer_name']): ?>
                <strong>Người phê duyệt:</strong><br>
                <p><?php echo htmlspecialchars($request['reviewer_name']); ?></p>
                <?php endif; ?>
                
                <?php if ($request['reviewed_date']): ?>
                <strong>Ngày xem xét:</strong><br>
                <p><?php echo date('d/m/Y H:i', strtotime($request['reviewed_date'])); ?></p>
                <?php endif; ?>
                
                <?php if ($request['approved_date']): ?>
                <strong>Ngày phê duyệt:</strong><br>
                <p><?php echo date('d/m/Y H:i', strtotime($request['approved_date'])); ?></p>
                <?php endif; ?>
                
                <?php if ($request['completed_date']): ?>
                <strong>Ngày hoàn thành:</strong><br>
                <p><?php echo date('d/m/Y H:i', strtotime($request['completed_date'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chi phí -->
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-money-bill"></i> Chi phí</h5>
            </div>
            <div class="card-body">
                <?php if ($request['estimated_cost']): ?>
                <strong>Chi phí ước tính:</strong><br>
                <p><?php echo number_format($request['estimated_cost']); ?> VNĐ</p>
                <?php endif; ?>
                
                <?php if ($request['actual_cost']): ?>
                <strong>Chi phí thực tế:</strong><br>
                <p><?php echo number_format($request['actual_cost']); ?> VNĐ</p>
                <?php endif; ?>
                
                <?php if ($request['labor_hours']): ?>
                <strong>Số giờ làm việc:</strong><br>
                <p><?php echo $request['labor_hours']; ?> giờ</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Phê duyệt yêu cầu bảo hành</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="approve">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approve_notes" class="form-label">Ghi chú phê duyệt</label>
                        <textarea class="form-control" id="approve_notes" name="review_notes" rows="3" 
                                  placeholder="Nhập ghi chú về quyết định phê duyệt..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">Phê duyệt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Từ chối yêu cầu bảo hành</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_notes" class="form-label">Lý do từ chối</label>
                        <textarea class="form-control" id="reject_notes" name="review_notes" rows="3" 
                                  placeholder="Nhập lý do từ chối yêu cầu..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Từ chối</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gán nhân viên xử lý</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="assign">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="assigned_staff_id" class="form-label">Chọn nhân viên</label>
                        <select class="form-select" id="assigned_staff_id" name="assigned_staff_id" required>
                            <option value="">Chọn nhân viên xử lý</option>
                            <?php foreach ($staff_list as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>">
                                <?php echo htmlspecialchars($staff['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Gán nhân viên</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #007bff;
    border: 2px solid #fff;
    box-shadow: 0 0 0 3px #007bff;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -31px;
    top: 15px;
    bottom: -20px;
    width: 2px;
    background-color: #007bff;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 14px;
}

.timeline-subtitle {
    margin-bottom: 10px;
    color: #6c757d;
    font-size: 12px;
}

.timeline-description {
    margin-bottom: 0;
    font-size: 13px;
}
</style>

<script>
function markCompleted() {
    if (confirm('Bạn có chắc chắn muốn đánh dấu yêu cầu này là hoàn thành?')) {
        // Redirect to completion form
        window.location.href = '?page=warranty-requests&action=complete&id=<?php echo $request['id']; ?>';
    }
}
</script>