<?php
$database = new Database();
$conn = $database->getConnection();

// Thống kê tổng quan
$stats_query = "
    SELECT 
        COUNT(*) as total_vehicles,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_vehicles,
        SUM(CASE WHEN warranty_end_date < CURDATE() THEN 1 ELSE 0 END) as expired_warranty,
        SUM(CASE WHEN warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
    FROM vehicle_registrations
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Thống kê theo hãng xe
$manufacturer_stats_query = "
    SELECT m.name, COUNT(vr.id) as count
    FROM manufacturers m
    LEFT JOIN vehicle_models vm ON m.id = vm.manufacturer_id
    LEFT JOIN vehicle_registrations vr ON vm.id = vr.vehicle_model_id
    WHERE vr.status = 'active'
    GROUP BY m.id, m.name
    ORDER BY count DESC
";
$manufacturer_stats_stmt = $conn->prepare($manufacturer_stats_query);
$manufacturer_stats_stmt->execute();
$manufacturer_stats = $manufacturer_stats_stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê đăng ký theo tháng (6 tháng gần nhất)
$monthly_stats_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as registrations
    FROM vehicle_registrations 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
";
$monthly_stats_stmt = $conn->prepare($monthly_stats_query);
$monthly_stats_stmt->execute();
$monthly_stats = $monthly_stats_stmt->fetchAll(PDO::FETCH_ASSOC);

// Danh sách xe sắp hết bảo hành
$expiring_query = "
    SELECT vr.*, vm.name as vehicle_name, u.full_name as customer_name, u.email as customer_email
    FROM vehicle_registrations vr
    LEFT JOIN vehicle_models vm ON vr.vehicle_model_id = vm.id
    LEFT JOIN users u ON vr.customer_id = u.id
    WHERE vr.warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    AND vr.status = 'active'
    ORDER BY vr.warranty_end_date ASC
";
$expiring_stmt = $conn->prepare($expiring_query);
$expiring_stmt->execute();
$expiring_vehicles = $expiring_stmt->fetchAll(PDO::FETCH_ASSOC);

// Xe đăng ký gần đây
$recent_query = "
    SELECT vr.*, vm.name as vehicle_name, u.full_name as customer_name, m.name as manufacturer_name
    FROM vehicle_registrations vr
    LEFT JOIN vehicle_models vm ON vr.vehicle_model_id = vm.id
    LEFT JOIN manufacturers m ON vm.manufacturer_id = m.id
    LEFT JOIN users u ON vr.customer_id = u.id
    ORDER BY vr.created_at DESC
    LIMIT 10
";
$recent_stmt = $conn->prepare($recent_query);
$recent_stmt->execute();
$recent_vehicles = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    $badges = [
        'active' => 'bg-success',
        'expired' => 'bg-danger',
        'transferred' => 'bg-info',
        'recalled' => 'bg-warning'
    ];
    return $badges[$status] ?? 'bg-secondary';
}

function getStatusText($status) {
    $texts = [
        'active' => 'Hoạt động',
        'expired' => 'Hết hạn',
        'transferred' => 'Chuyển nhượng',
        'recalled' => 'Thu hồi'
    ];
    return $texts[$status] ?? $status;
}
?>

<div class="row mt-4">
    <div class="col-12">
        <h1><i class="fas fa-car"></i> Giám sát đăng ký xe</h1>
        <hr>
    </div>
</div>

<!-- Thống kê tổng quan -->
<div class="row">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($stats['total_vehicles']); ?></h4>
                        <p>Tổng số xe</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-car fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($stats['active_vehicles']); ?></h4>
                        <p>Xe đang hoạt động</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
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
                        <h4><?php echo number_format($stats['expiring_soon']); ?></h4>
                        <p>Sắp hết bảo hành</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo number_format($stats['expired_warranty']); ?></h4>
                        <p>Hết bảo hành</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Biểu đồ đăng ký theo tháng -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Đăng ký theo tháng (6 tháng gần nhất)</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Thống kê theo hãng xe -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie"></i> Thống kê theo hãng xe</h5>
            </div>
            <div class="card-body">
                <?php foreach ($manufacturer_stats as $stat): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><?php echo htmlspecialchars($stat['name']); ?></span>
                    <span class="badge bg-primary"><?php echo number_format($stat['count']); ?></span>
                </div>
                <div class="progress mb-3" style="height: 10px;">
                    <?php 
                    $percentage = $stats['active_vehicles'] > 0 ? ($stat['count'] / $stats['active_vehicles']) * 100 : 0;
                    ?>
                    <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Xe sắp hết bảo hành -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-exclamation-triangle text-warning"></i> Xe sắp hết bảo hành</h5>
            </div>
            <div class="card-body">
                <?php if (empty($expiring_vehicles)): ?>
                <p class="text-muted">Không có xe nào sắp hết bảo hành trong 60 ngày tới.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Xe</th>
                                <th>Khách hàng</th>
                                <th>Hết hạn</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiring_vehicles as $vehicle): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($vehicle['vehicle_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($vehicle['license_plate']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($vehicle['customer_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($vehicle['customer_email']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $days_left = (strtotime($vehicle['warranty_end_date']) - time()) / (60 * 60 * 24);
                                    echo date('d/m/Y', strtotime($vehicle['warranty_end_date'])); 
                                    ?>
                                    <br>
                                    <small class="text-<?php echo $days_left <= 30 ? 'danger' : 'warning'; ?>">
                                        <?php echo round($days_left); ?> ngày
                                    </small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="sendWarrantyReminder(<?php echo $vehicle['id']; ?>)">
                                        <i class="fas fa-bell"></i>
                                    </button>
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
    
    <!-- Xe đăng ký gần đây -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clock"></i> Xe đăng ký gần đây</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_vehicles)): ?>
                <p class="text-muted">Chưa có xe nào được đăng ký.</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_vehicles as $vehicle): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">
                                <?php echo htmlspecialchars($vehicle['manufacturer_name'] . ' ' . $vehicle['vehicle_name']); ?>
                            </div>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($vehicle['customer_name']); ?> - 
                                <?php echo htmlspecialchars($vehicle['license_plate']); ?>
                            </small><br>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($vehicle['created_at'])); ?>
                            </small>
                        </div>
                        <span class="badge <?php echo getStatusBadge($vehicle['status']); ?> rounded-pill">
                            <?php echo getStatusText($vehicle['status']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#registerVehicleModal">
                            <i class="fas fa-plus"></i> Đăng ký xe mới
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-info w-100" onclick="exportReport()">
                            <i class="fas fa-download"></i> Xuất báo cáo
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-warning w-100" onclick="sendBulkReminders()">
                            <i class="fas fa-bell"></i> Gửi thông báo hàng loạt
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="?page=warranty-requests" class="btn btn-primary w-100">
                            <i class="fas fa-tools"></i> Quản lý bảo hành
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Registration Modal -->
<div class="modal fade" id="registerVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đăng ký xe mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=vehicle-registration&action=create">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Khách hàng</label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <option value="">Chọn khách hàng</option>
                                    <!-- Load customers via AJAX -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vehicle_model_id" class="form-label">Mẫu xe</label>
                                <select class="form-select" id="vehicle_model_id" name="vehicle_model_id" required>
                                    <option value="">Chọn mẫu xe</option>
                                    <!-- Load vehicle models via AJAX -->
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vin" class="form-label">Số VIN</label>
                                <input type="text" class="form-control" id="vin" name="vin" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="license_plate" class="form-label">Biển số xe</label>
                                <input type="text" class="form-control" id="license_plate" name="license_plate">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="purchase_date" class="form-label">Ngày mua</label>
                                <input type="date" class="form-control" id="purchase_date" name="purchase_date" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dealer_name" class="form-label">Đại lý</label>
                                <input type="text" class="form-control" id="dealer_name" name="dealer_name">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">Đăng ký xe</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly registrations chart
const monthlyData = <?php echo json_encode($monthly_stats); ?>;
const monthLabels = monthlyData.map(item => {
    const date = new Date(item.month + '-01');
    return date.toLocaleDateString('vi-VN', { month: 'short', year: 'numeric' });
});
const monthValues = monthlyData.map(item => item.registrations);

const ctx = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Số xe đăng ký',
            data: monthValues,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Functions
function sendWarrantyReminder(vehicleId) {
    if (confirm('Gửi thông báo nhắc nhở bảo hành cho khách hàng?')) {
        // AJAX call to send reminder
        alert('Đã gửi thông báo thành công!');
    }
}

function exportReport() {
    // Export functionality
    window.open('?page=reports&type=vehicle-monitoring&format=excel', '_blank');
}

function sendBulkReminders() {
    if (confirm('Gửi thông báo cho tất cả xe sắp hết bảo hành?')) {
        // AJAX call to send bulk reminders
        alert('Đã gửi thông báo hàng loạt thành công!');
    }
}
</script>