<?php
require_once '../src/models/WarrantyRequest.php';

$database = new Database();
$conn = $database->getConnection();
$warranty = new WarrantyRequest();

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $warranty->customer_id = $_POST['customer_id'];
        $warranty->vehicle_registration_id = $_POST['vehicle_registration_id'];
        $warranty->issue_category_id = $_POST['issue_category_id'];
        $warranty->title = $_POST['title'];
        $warranty->description = $_POST['description'];
        $warranty->priority = $_POST['priority'];
        $warranty->current_mileage = $_POST['current_mileage'];
        
        if ($warranty->create()) {
            $success = "Yêu cầu bảo hành đã được tạo thành công!";
        } else {
            $error = "Có lỗi xảy ra khi tạo yêu cầu!";
        }
    } elseif ($action === 'update_status') {
        $warranty->id = $_POST['warranty_id'];
        $warranty->status = $_POST['status'];
        $warranty->reviewer_id = $_SESSION['user_id'];
        $warranty->review_notes = $_POST['review_notes'];
        
        if ($_POST['status'] === 'completed') {
            $warranty->completion_notes = $_POST['completion_notes'];
            $warranty->actual_cost = $_POST['actual_cost'];
        }
        
        if ($warranty->updateStatus()) {
            $success = "Trạng thái đã được cập nhật!";
        } else {
            $error = "Có lỗi xảy ra khi cập nhật trạng thái!";
        }
    }
}

// Lấy danh sách yêu cầu
$stmt = $warranty->readAll();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy dữ liệu cho form
$customers_query = "SELECT id, full_name FROM users WHERE role = 'customer' AND is_active = 1";
$customers_stmt = $conn->prepare($customers_query);
$customers_stmt->execute();
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

$vehicles_query = "SELECT vr.id, CONCAT(vm.name, ' - ', vr.license_plate) as display_name, vr.customer_id
                   FROM vehicle_registrations vr 
                   LEFT JOIN vehicle_models vm ON vr.vehicle_model_id = vm.id 
                   WHERE vr.status = 'active'";
$vehicles_stmt = $conn->prepare($vehicles_query);
$vehicles_stmt->execute();
$vehicles = $vehicles_stmt->fetchAll(PDO::FETCH_ASSOC);

$categories_query = "SELECT * FROM issue_categories WHERE is_active = 1";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <div class="d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-tools"></i> Quản lý yêu cầu bảo hành</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                <i class="fas fa-plus"></i> Tạo yêu cầu mới
            </button>
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

<!-- Filters -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="page" value="warranty-requests">
                    
                    <div class="col-md-3">
                        <label for="status_filter" class="form-label">Trạng thái</label>
                        <select class="form-select" id="status_filter" name="status">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending">Chờ xử lý</option>
                            <option value="in_review">Đang xem xét</option>
                            <option value="approved">Đã phê duyệt</option>
                            <option value="rejected">Từ chối</option>
                            <option value="in_progress">Đang xử lý</option>
                            <option value="completed">Hoàn thành</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="priority_filter" class="form-label">Độ ưu tiên</label>
                        <select class="form-select" id="priority_filter" name="priority">
                            <option value="">Tất cả độ ưu tiên</option>
                            <option value="urgent">Khẩn cấp</option>
                            <option value="high">Cao</option>
                            <option value="medium">Trung bình</option>
                            <option value="low">Thấp</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="search" class="form-label">Tìm kiếm</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Tiêu đề, khách hàng, VIN...">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Lọc
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Warranty Requests Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tiêu đề</th>
                        <th>Khách hàng</th>
                        <th>Xe</th>
                        <th>Danh mục</th>
                        <th>Trạng thái</th>
                        <th>Độ ưu tiên</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">Chưa có yêu cầu bảo hành nào.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><strong>#<?php echo $request['id']; ?></strong></td>
                        <td>
                            <a href="?page=warranty-detail&id=<?php echo $request['id']; ?>" 
                               class="text-decoration-none">
                                <?php echo htmlspecialchars($request['title']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($request['customer_name']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($request['vehicle_name']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($request['license_plate']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($request['issue_category']); ?></td>
                        <td>
                            <span class="badge <?php echo getStatusBadge($request['status']); ?>">
                                <?php echo getStatusText($request['status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo getPriorityBadge($request['priority']); ?>">
                                <?php echo getPriorityText($request['priority']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?page=warranty-detail&id=<?php echo $request['id']; ?>" 
                                   class="btn btn-outline-primary" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff'): ?>
                                <button class="btn btn-outline-success" 
                                        onclick="showStatusModal(<?php echo htmlspecialchars(json_encode($request)); ?>)"
                                        title="Cập nhật trạng thái">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo yêu cầu bảo hành mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Khách hàng</label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <option value="">Chọn khách hàng</option>
                                    <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>">
                                        <?php echo htmlspecialchars($customer['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vehicle_registration_id" class="form-label">Xe</label>
                                <select class="form-select" id="vehicle_registration_id" name="vehicle_registration_id" required>
                                    <option value="">Chọn xe</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="issue_category_id" class="form-label">Danh mục sự cố</label>
                                <select class="form-select" id="issue_category_id" name="issue_category_id" required>
                                    <option value="">Chọn danh mục</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Độ ưu tiên</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="medium">Trung bình</option>
                                    <option value="low">Thấp</option>
                                    <option value="high">Cao</option>
                                    <option value="urgent">Khẩn cấp</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả chi tiết</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="current_mileage" class="form-label">Số km hiện tại</label>
                        <input type="number" class="form-control" id="current_mileage" name="current_mileage">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo yêu cầu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cập nhật trạng thái</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="warranty_id" id="update_warranty_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="update_status" class="form-label">Trạng thái mới</label>
                        <select class="form-select" id="update_status" name="status" required>
                            <option value="pending">Chờ xử lý</option>
                            <option value="in_review">Đang xem xét</option>
                            <option value="approved">Đã phê duyệt</option>
                            <option value="rejected">Từ chối</option>
                            <option value="in_progress">Đang xử lý</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="review_notes" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="review_notes" name="review_notes" rows="3"></textarea>
                    </div>
                    
                    <div id="completion_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="completion_notes" class="form-label">Ghi chú hoàn thành</label>
                            <textarea class="form-control" id="completion_notes" name="completion_notes" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="actual_cost" class="form-label">Chi phí thực tế</label>
                            <input type="number" class="form-control" id="actual_cost" name="actual_cost" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Update vehicle list based on customer selection
document.getElementById('customer_id').addEventListener('change', function() {
    const customerId = this.value;
    const vehicleSelect = document.getElementById('vehicle_registration_id');
    
    vehicleSelect.innerHTML = '<option value="">Chọn xe</option>';
    
    if (customerId) {
        const vehicles = <?php echo json_encode($vehicles); ?>;
        vehicles.forEach(function(vehicle) {
            if (vehicle.customer_id == customerId) {
                const option = document.createElement('option');
                option.value = vehicle.id;
                option.textContent = vehicle.display_name;
                vehicleSelect.appendChild(option);
            }
        });
    }
});

// Show status update modal
function showStatusModal(request) {
    document.getElementById('update_warranty_id').value = request.id;
    document.getElementById('update_status').value = request.status;
    document.getElementById('review_notes').value = request.review_notes || '';
    
    const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
}

// Show/hide completion fields
document.getElementById('update_status').addEventListener('change', function() {
    const completionFields = document.getElementById('completion_fields');
    if (this.value === 'completed') {
        completionFields.style.display = 'block';
    } else {
        completionFields.style.display = 'none';
    }
});
</script>