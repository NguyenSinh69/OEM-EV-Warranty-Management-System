<?php
$database = new Database();
$conn = $database->getConnection();

// Thống kê tổng quan
$stats = [];

// Thống kê yêu cầu bảo hành
$query = "SELECT status, COUNT(*) as count FROM warranty_requests GROUP BY status";
$stmt = $conn->prepare($query);
$stmt->execute();
$warranty_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tổng số xe đăng ký
$query = "SELECT COUNT(*) as total FROM vehicle_registrations WHERE status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$total_vehicles = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Yêu cầu bảo hành mới nhất
$query = "SELECT wr.*, u.full_name as customer_name, vm.name as vehicle_name, ic.name as issue_category
          FROM warranty_requests wr
          LEFT JOIN users u ON wr.customer_id = u.id
          LEFT JOIN vehicle_registrations vr ON wr.vehicle_registration_id = vr.id
          LEFT JOIN vehicle_models vm ON vr.vehicle_model_id = vm.id
          LEFT JOIN issue_categories ic ON wr.issue_category_id = ic.id
          ORDER BY wr.created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status badges
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
?>

<div class="row mt-4">
    <div class="col-12">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <hr>
    </div>
</div>

<!-- Thống kê cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $total_vehicles; ?></h4>
                        <p>Xe đã đăng ký</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-car fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    $total_requests = 0;
    $pending_requests = 0;
    foreach ($warranty_stats as $stat) {
        $total_requests += $stat['count'];
        if ($stat['status'] === 'pending') {
            $pending_requests = $stat['count'];
        }
    }
    ?>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $total_requests; ?></h4>
                        <p>Tổng yêu cầu</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-tools fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $pending_requests; ?></h4>
                        <p>Chờ xử lý</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>24/7</h4>
                        <p>Hỗ trợ khách hàng</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-headset fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Biểu đồ thống kê trạng thái -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie"></i> Thống kê trạng thái yêu cầu</h5>
            </div>
            <div class="card-body">
                <?php foreach ($warranty_stats as $stat): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><?php echo getStatusText($stat['status']); ?></span>
                    <span class="badge <?php echo getStatusBadge($stat['status']); ?>">
                        <?php echo $stat['count']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Yêu cầu mới nhất -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Yêu cầu bảo hành mới nhất</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_requests)): ?>
                <p class="text-muted">Chưa có yêu cầu bảo hành nào.</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_requests as $request): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold"><?php echo htmlspecialchars($request['title']); ?></div>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($request['customer_name']); ?> - 
                                <?php echo htmlspecialchars($request['vehicle_name']); ?>
                            </small>
                        </div>
                        <span class="badge <?php echo getStatusBadge($request['status']); ?> rounded-pill">
                            <?php echo getStatusText($request['status']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="?page=warranty-requests" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> Xem tất cả
                    </a>
                </div>
            </div>
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
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff'): ?>
                    <div class="col-md-3 mb-2">
                        <a href="?page=warranty-requests&action=create" class="btn btn-success w-100">
                            <i class="fas fa-plus"></i> Tạo yêu cầu mới
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="?page=vehicle-monitoring" class="btn btn-info w-100">
                            <i class="fas fa-car"></i> Giám sát xe
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] === 'customer'): ?>
                    <div class="col-md-3 mb-2">
                        <a href="?page=my-warranties&action=create" class="btn btn-success w-100">
                            <i class="fas fa-plus"></i> Yêu cầu bảo hành
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3 mb-2">
                        <a href="?page=customer-support" class="btn btn-warning w-100">
                            <i class="fas fa-headset"></i> Hỗ trợ khách hàng
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="?page=faq" class="btn btn-outline-primary w-100">
                            <i class="fas fa-question-circle"></i> FAQ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>