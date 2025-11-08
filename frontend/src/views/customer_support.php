<?php
$database = new Database();
$conn = $database->getConnection();

// Xử lý form tìm kiếm bảo hành
$warranty_info = null;
$search_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'search_warranty') {
        $vin = trim($_POST['vin']);
        $license_plate = trim($_POST['license_plate']);
        
        if ($vin || $license_plate) {
            $search_query = "
                SELECT vr.*, vm.name as vehicle_name, m.name as manufacturer_name, u.full_name as customer_name
                FROM vehicle_registrations vr
                LEFT JOIN vehicle_models vm ON vr.vehicle_model_id = vm.id
                LEFT JOIN manufacturers m ON vm.manufacturer_id = m.id
                LEFT JOIN users u ON vr.customer_id = u.id
                WHERE 1=1
            ";
            
            $params = [];
            if ($vin) {
                $search_query .= " AND vr.vin = ?";
                $params[] = $vin;
            }
            if ($license_plate) {
                $search_query .= " AND vr.license_plate = ?";
                $params[] = $license_plate;
            }
            
            $search_stmt = $conn->prepare($search_query);
            $search_stmt->execute($params);
            
            if ($search_stmt->rowCount() > 0) {
                $warranty_info = $search_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Lấy lịch sử bảo hành
                $history_query = "
                    SELECT wr.*, ic.name as issue_category
                    FROM warranty_requests wr
                    LEFT JOIN issue_categories ic ON wr.issue_category_id = ic.id
                    WHERE wr.vehicle_registration_id = ?
                    ORDER BY wr.created_at DESC
                ";
                $history_stmt = $conn->prepare($history_query);
                $history_stmt->execute([$warranty_info['id']]);
                $warranty_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $search_error = "Không tìm thấy thông tin xe với VIN hoặc biển số đã nhập.";
            }
        } else {
            $search_error = "Vui lòng nhập VIN hoặc biển số xe.";
        }
    } elseif ($_POST['action'] === 'submit_support') {
        // Xử lý form hỗ trợ
        $customer_name = trim($_POST['customer_name']);
        $email = trim($_POST['email']);
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        
        if ($customer_name && $email && $subject && $message) {
            $insert_query = "
                INSERT INTO support_tickets (customer_id, subject, message, status)
                VALUES (?, ?, ?, 'open')
            ";
            
            // Tìm customer_id nếu có
            $customer_query = "SELECT id FROM users WHERE email = ? AND role = 'customer'";
            $customer_stmt = $conn->prepare($customer_query);
            $customer_stmt->execute([$email]);
            $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
            $customer_id = $customer ? $customer['id'] : null;
            
            $insert_stmt = $conn->prepare($insert_query);
            if ($insert_stmt->execute([$customer_id, $subject, $message])) {
                $support_success = "Yêu cầu hỗ trợ đã được gửi thành công. Chúng tôi sẽ phản hồi trong thời gian sớm nhất.";
            } else {
                $support_error = "Có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại.";
            }
        } else {
            $support_error = "Vui lòng điền đầy đủ thông tin.";
        }
    }
}

// Lấy danh sách FAQ
$faq_query = "SELECT * FROM faqs WHERE is_active = 1 ORDER BY display_order ASC, id ASC";
$faq_stmt = $conn->prepare($faq_query);
$faq_stmt->execute();
$faqs = $faq_stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <h1><i class="fas fa-headset"></i> Hỗ trợ khách hàng</h1>
        <p class="lead">Tra cứu thông tin bảo hành, gửi yêu cầu hỗ trợ và tìm hiểu câu hỏi thường gặp</p>
        <hr>
    </div>
</div>

<!-- Alert messages -->
<?php if (isset($support_success)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $support_success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($support_error)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?php echo $support_error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- Tra cứu bảo hành -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-search"></i> Tra cứu thông tin bảo hành</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="search_warranty">
                    
                    <div class="mb-3">
                        <label for="vin" class="form-label">Số VIN</label>
                        <input type="text" class="form-control" id="vin" name="vin" 
                               placeholder="Nhập số VIN của xe" value="<?php echo $_POST['vin'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="license_plate" class="form-label">Biển số xe</label>
                        <input type="text" class="form-control" id="license_plate" name="license_plate" 
                               placeholder="Nhập biển số xe" value="<?php echo $_POST['license_plate'] ?? ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tra cứu
                    </button>
                </form>
                
                <?php if ($search_error): ?>
                <div class="alert alert-warning mt-3">
                    <?php echo $search_error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($warranty_info): ?>
                <div class="mt-4">
                    <h6>Thông tin xe</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Hãng xe:</strong></td>
                                <td><?php echo htmlspecialchars($warranty_info['manufacturer_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Mẫu xe:</strong></td>
                                <td><?php echo htmlspecialchars($warranty_info['vehicle_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>VIN:</strong></td>
                                <td><?php echo htmlspecialchars($warranty_info['vin']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Biển số:</strong></td>
                                <td><?php echo htmlspecialchars($warranty_info['license_plate']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Ngày mua:</strong></td>
                                <td><?php echo date('d/m/Y', strtotime($warranty_info['purchase_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Bảo hành từ:</strong></td>
                                <td><?php echo date('d/m/Y', strtotime($warranty_info['warranty_start_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Bảo hành đến:</strong></td>
                                <td>
                                    <?php 
                                    echo date('d/m/Y', strtotime($warranty_info['warranty_end_date']));
                                    $warranty_status = getWarrantyStatus($warranty_info['warranty_end_date']);
                                    ?>
                                    <span class="badge bg-<?php echo $warranty_status['class']; ?> ms-2">
                                        <?php echo $warranty_status['text']; ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <?php if (!empty($warranty_history)): ?>
                    <h6 class="mt-3">Lịch sử bảo hành</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Vấn đề</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($warranty_history as $history): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($history['requested_date'])); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($history['title']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($history['issue_category']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadge($history['status']); ?>">
                                            <?php echo getStatusText($history['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Form hỗ trợ -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-envelope"></i> Gửi yêu cầu hỗ trợ</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="submit_support">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Họ tên</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Chủ đề</label>
                        <select class="form-select" id="subject" name="subject" required>
                            <option value="">Chọn chủ đề</option>
                            <option value="Hỏi đáp về bảo hành">Hỏi đáp về bảo hành</option>
                            <option value="Khiếu nại dịch vụ">Khiếu nại dịch vụ</option>
                            <option value="Yêu cầu hỗ trợ kỹ thuật">Yêu cầu hỗ trợ kỹ thuật</option>
                            <option value="Thông tin sản phẩm">Thông tin sản phẩm</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Nội dung</label>
                        <textarea class="form-control" id="message" name="message" rows="5" 
                                  placeholder="Mô tả chi tiết vấn đề hoặc yêu cầu của bạn..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Gửi yêu cầu
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Thông tin liên hệ -->
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-phone"></i> Thông tin liên hệ</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Hotline:</strong><br>
                        <i class="fas fa-phone text-success"></i> 1900-1234 (24/7)</p>
                        
                        <p><strong>Email:</strong><br>
                        <i class="fas fa-envelope text-primary"></i> support@evwarranty.com</p>
                    </div>
                    
                    <div class="col-md-6">
                        <p><strong>Địa chỉ:</strong><br>
                        <i class="fas fa-map-marker-alt text-danger"></i> 123 Đường ABC, Quận 1, TP.HCM</p>
                        
                        <p><strong>Giờ làm việc:</strong><br>
                        <i class="fas fa-clock text-warning"></i> T2-T6: 8:00-17:30<br>
                        <i class="fas fa-clock text-warning"></i> T7: 8:00-12:00</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-question-circle"></i> Câu hỏi thường gặp (FAQ)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($faqs)): ?>
                <p class="text-muted">Hiện tại chưa có câu hỏi thường gặp nào.</p>
                <?php else: ?>
                <div class="accordion" id="faqAccordion">
                    <?php foreach ($faqs as $index => $faq): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                    type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapse<?php echo $index; ?>" 
                                    aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                    aria-controls="collapse<?php echo $index; ?>">
                                <?php echo htmlspecialchars($faq['question']); ?>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $index; ?>" 
                             class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                             aria-labelledby="heading<?php echo $index; ?>" 
                             data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <p class="text-muted">Không tìm thấy câu trả lời bạn cần?</p>
                    <button class="btn btn-outline-primary" onclick="scrollToSupport()">
                        <i class="fas fa-headset"></i> Liên hệ hỗ trợ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-link"></i> Liên kết hữu ích</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="?page=faq" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-question-circle"></i><br>
                            FAQ chi tiết
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-download"></i><br>
                            Tài liệu hướng dẫn
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-warning w-100 mb-2">
                            <i class="fas fa-tools"></i><br>
                            Trung tâm bảo hành
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="?page=login" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-sign-in-alt"></i><br>
                            Đăng nhập hệ thống
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function scrollToSupport() {
    document.querySelector('[name="customer_name"]').scrollIntoView({ 
        behavior: 'smooth',
        block: 'center'
    });
    document.querySelector('[name="customer_name"]').focus();
}

// Auto-fill subject based on FAQ category clicked
document.querySelectorAll('.accordion-button').forEach(button => {
    button.addEventListener('click', function() {
        // Could implement auto-suggest for support form based on FAQ category
    });
});
</script>