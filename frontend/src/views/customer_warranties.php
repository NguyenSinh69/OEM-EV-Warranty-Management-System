<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ?page=login');
    exit();
}

require_once '../src/models/WarrantyRequest.php';

$database = new Database();
$conn = $database->getConnection();
$warranty = new WarrantyRequest();

// Xử lý tạo yêu cầu bảo hành mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $warranty->customer_id = $_SESSION['user_id'];
    $warranty->vehicle_registration_id = $_POST['vehicle_registration_id'];
    $warranty->issue_category_id = $_POST['issue_category_id'];
    $warranty->title = $_POST['title'];
    $warranty->description = $_POST['description'];
    $warranty->priority = $_POST['priority'];
    $warranty->current_mileage = $_POST['current_mileage'];
    
    if ($warranty->create()) {
        $success = "Yêu cầu bảo hành đã được gửi thành công! Chúng tôi sẽ xem xét và phản hồi trong thời gian sớm nhất.";
    } else {
        $error = "Có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại.";
    }
}

// Lấy danh sách xe của khách hàng
$vehicles_query = "SELECT vr.*, vm.name as vehicle_name, m.name as manufacturer_name
                   FROM vehicle_registrations vr
                   LEFT JOIN vehicle_models vm ON vr.vehicle_model_id = vm.id
                   LEFT JOIN manufacturers m ON vm.manufacturer_id = m.id
                   WHERE vr.customer_id = ? AND vr.status = 'active'
                   ORDER BY vr.created_at DESC";
$vehicles_stmt = $conn->prepare($vehicles_query);
$vehicles_stmt->execute([$_SESSION['user_id']]);
$customer_vehicles = $vehicles_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách yêu cầu bảo hành của khách hàng
$requests = $warranty->getByCustomer($_SESSION['user_id']);
$customer_requests = $requests->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh mục sự cố
$categories_query = "SELECT * FROM issue_categories WHERE is_active = 1 ORDER BY name";
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

function getWarrantyStatus($warranty_end_date) {
    $end_date = strtotime($warranty_end_date);
    $current_date = time();
    
    if ($end_date < $current_date) {
        return ['status' => 'expired', 'text' => 'Hết hạn', 'class' => 'danger'];
    } elseif ($end_date - $current_date <= 30 * 24 * 60 * 60) { // 30 days
        return ['status' => 'expiring', 'text' => 'Sắp hết hạn', 'class' => 'warning'];
    } else {
        return ['status' => 'active', 'text' => 'Còn hiệu lực', 'class' => 'success'];
    }
}
?>

<div class="row mt-4">
    <div class="col-12">
        <h1><i class="fas fa-file-alt"></i> Bảo hành của tôi</h1>
        <p class="lead">Quản lý thông tin xe và yêu cầu bảo hành của bạn</p>
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

<!-- Thông tin xe của khách hàng -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-car"></i> Xe của tôi</h5>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createRequestModal"
                        <?php echo empty($customer_vehicles) ? 'disabled' : ''; ?>>
                    <i class="fas fa-plus"></i> Tạo yêu cầu bảo hành
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($customer_vehicles)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-car fa-3x text-muted mb-3"></i>
                    <h5>Chưa có xe nào được đăng ký</h5>
                    <p class="text-muted">Liên hệ với đại lý để đăng ký thông tin xe của bạn.</p>
                    <a href="?page=customer-support" class="btn btn-primary">
                        <i class="fas fa-headset"></i> Liên hệ hỗ trợ
                    </a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($customer_vehicles as $vehicle): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <?php echo htmlspecialchars($vehicle['manufacturer_name'] . ' ' . $vehicle['vehicle_name']); ?>
                                </h6>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">VIN:</small><br>
                                        <strong><?php echo htmlspecialchars($vehicle['vin']); ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Biển số:</small><br>
                                        <strong><?php echo htmlspecialchars($vehicle['license_plate']); ?></strong>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Ngày mua:</small><br>
                                        <?php echo date('d/m/Y', strtotime($vehicle['purchase_date'])); ?>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Bảo hành đến:</small><br>
                                        <?php 
                                        echo date('d/m/Y', strtotime($vehicle['warranty_end_date']));
                                        $warranty_status = getWarrantyStatus($vehicle['warranty_end_date']);
                                        ?>
                                        <br>
                                        <span class="badge bg-<?php echo $warranty_status['class']; ?> mt-1">
                                            <?php echo $warranty_status['text']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <button class="btn btn-outline-primary btn-sm" 
                                            onclick="createRequestForVehicle(<?php echo $vehicle['id']; ?>, '<?php echo htmlspecialchars($vehicle['manufacturer_name'] . ' ' . $vehicle['vehicle_name']); ?>')">
                                        <i class="fas fa-tools"></i> Tạo yêu cầu bảo hành
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Lịch sử yêu cầu bảo hành -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Lịch sử yêu cầu bảo hành</h5>
            </div>
            <div class="card-body">
                <?php if (empty($customer_requests)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h5>Chưa có yêu cầu bảo hành nào</h5>
                    <p class="text-muted">Khi bạn tạo yêu cầu bảo hành, chúng sẽ được hiển thị tại đây.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Xe</th>
                                <th>Vấn đề</th>
                                <th>Trạng thái</th>
                                <th>Độ ưu tiên</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customer_requests as $request): ?>
                            <tr>
                                <td><strong>#<?php echo $request['id']; ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($request['vehicle_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($request['license_plate']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($request['issue_category']); ?></small>
                                </td>
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
                                    <a href="?page=warranty-detail&id=<?php echo $request['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i> Chi tiết
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo yêu cầu bảo hành</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="vehicle_registration_id" class="form-label">Chọn xe</label>
                        <select class="form-select" id="vehicle_registration_id" name="vehicle_registration_id" required>
                            <option value="">Chọn xe cần bảo hành</option>
                            <?php foreach ($customer_vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['id']; ?>">
                                <?php echo htmlspecialchars($vehicle['manufacturer_name'] . ' ' . $vehicle['vehicle_name'] . ' - ' . $vehicle['license_plate']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="issue_category_id" class="form-label">Danh mục sự cố</label>
                                <select class="form-select" id="issue_category_id" name="issue_category_id" required>
                                    <option value="">Chọn loại sự cố</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            data-covered="<?php echo $category['warranty_covered'] ? 'true' : 'false'; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                        <?php if (!$category['warranty_covered']): ?>
                                        (Không thuộc bảo hành)
                                        <?php endif; ?>
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
                        <label for="title" class="form-label">Tiêu đề vấn đề</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="Mô tả ngắn gọn vấn đề gặp phải" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả chi tiết</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  placeholder="Mô tả chi tiết vấn đề, triệu chứng, thời điểm xảy ra..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="current_mileage" class="form-label">Số km hiện tại</label>
                        <input type="number" class="form-control" id="current_mileage" name="current_mileage" 
                               placeholder="Nhập số km hiện tại của xe">
                    </div>
                    
                    <div class="alert alert-info" id="warranty-notice" style="display: none;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Lưu ý:</strong> Vấn đề này có thể không thuộc phạm vi bảo hành. 
                        Bạn có thể cần phải trả phí dịch vụ.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Gửi yêu cầu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt"></i> Thao tác nhanh</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="?page=customer-support" class="btn btn-outline-primary w-100">
                            <i class="fas fa-search"></i><br>
                            Tra cứu bảo hành
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-2">
                        <a href="?page=faq" class="btn btn-outline-info w-100">
                            <i class="fas fa-question-circle"></i><br>
                            Câu hỏi thường gặp
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-2">
                        <a href="?page=customer-support" class="btn btn-outline-success w-100">
                            <i class="fas fa-headset"></i><br>
                            Liên hệ hỗ trợ
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-2">
                        <a href="tel:1900-1234" class="btn btn-outline-warning w-100">
                            <i class="fas fa-phone"></i><br>
                            Hotline 24/7<br>
                            <small>1900-1234</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show warranty notice for non-covered issues
document.getElementById('issue_category_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const warrantyCovered = selectedOption.getAttribute('data-covered') === 'true';
    const notice = document.getElementById('warranty-notice');
    
    if (!warrantyCovered && this.value) {
        notice.style.display = 'block';
    } else {
        notice.style.display = 'none';
    }
});

// Function to pre-select vehicle for request
function createRequestForVehicle(vehicleId, vehicleName) {
    const modal = new bootstrap.Modal(document.getElementById('createRequestModal'));
    const vehicleSelect = document.getElementById('vehicle_registration_id');
    vehicleSelect.value = vehicleId;
    modal.show();
}

// Auto-fill title based on category selection
document.getElementById('issue_category_id').addEventListener('change', function() {
    const categoryText = this.options[this.selectedIndex].text;
    const titleField = document.getElementById('title');
    
    if (this.value && !titleField.value) {
        titleField.placeholder = `Vấn đề về ${categoryText.split('(')[0].trim()}`;
    }
});
</script>