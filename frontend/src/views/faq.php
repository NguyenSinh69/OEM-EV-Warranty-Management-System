<?php
$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách FAQ theo category
$faq_query = "SELECT * FROM faqs WHERE is_active = 1 ORDER BY category, display_order ASC, id ASC";
$faq_stmt = $conn->prepare($faq_query);
$faq_stmt->execute();
$all_faqs = $faq_stmt->fetchAll(PDO::FETCH_ASSOC);

// Nhóm FAQ theo category
$faqs_by_category = [];
foreach ($all_faqs as $faq) {
    $category = $faq['category'] ?: 'Chung';
    if (!isset($faqs_by_category[$category])) {
        $faqs_by_category[$category] = [];
    }
    $faqs_by_category[$category][] = $faq;
}

// Lấy tìm kiếm nếu có
$search_term = $_GET['search'] ?? '';
$filtered_faqs = [];

if ($search_term) {
    foreach ($all_faqs as $faq) {
        if (stripos($faq['question'], $search_term) !== false || 
            stripos($faq['answer'], $search_term) !== false) {
            $filtered_faqs[] = $faq;
        }
    }
}
?>

<div class="row mt-4">
    <div class="col-12">
        <h1><i class="fas fa-question-circle"></i> Câu hỏi thường gặp (FAQ)</h1>
        <p class="lead">Tìm câu trả lời nhanh chóng cho các câu hỏi thường gặp về xe điện và dịch vụ bảo hành</p>
        <hr>
    </div>
</div>

<!-- Search Bar -->
<div class="row mb-4">
    <div class="col-md-8 mx-auto">
        <form method="GET" action="?page=faq">
            <div class="input-group">
                <input type="text" class="form-control form-control-lg" 
                       name="search" value="<?php echo htmlspecialchars($search_term); ?>"
                       placeholder="Tìm kiếm câu hỏi...">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($search_term): ?>
<!-- Search Results -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-search"></i> Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($search_term); ?>"</h5>
                <a href="?page=faq" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Xóa tìm kiếm
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($filtered_faqs)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>Không tìm thấy kết quả</h5>
                    <p class="text-muted">Thử tìm kiếm với từ khóa khác hoặc liên hệ hỗ trợ để được giúp đỡ.</p>
                    <a href="?page=customer-support" class="btn btn-primary">
                        <i class="fas fa-headset"></i> Liên hệ hỗ trợ
                    </a>
                </div>
                <?php else: ?>
                <div class="accordion" id="searchResults">
                    <?php foreach ($filtered_faqs as $index => $faq): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="searchHeading<?php echo $index; ?>">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#searchCollapse<?php echo $index; ?>" 
                                    aria-expanded="false" aria-controls="searchCollapse<?php echo $index; ?>">
                                <?php echo htmlspecialchars($faq['question']); ?>
                                <?php if ($faq['category']): ?>
                                <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($faq['category']); ?></span>
                                <?php endif; ?>
                            </button>
                        </h2>
                        <div id="searchCollapse<?php echo $index; ?>" 
                             class="accordion-collapse collapse" 
                             aria-labelledby="searchHeading<?php echo $index; ?>" 
                             data-bs-parent="#searchResults">
                            <div class="accordion-body">
                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
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

<?php else: ?>
<!-- Category Navigation -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h6>Danh mục:</h6>
                <div class="row">
                    <?php foreach (array_keys($faqs_by_category) as $category): ?>
                    <div class="col-md-3 mb-2">
                        <a href="#category-<?php echo urlencode($category); ?>" class="btn btn-outline-primary w-100">
                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($category); ?>
                            <span class="badge bg-primary ms-2"><?php echo count($faqs_by_category[$category]); ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAQ by Categories -->
<?php foreach ($faqs_by_category as $category => $faqs): ?>
<div class="row mb-4" id="category-<?php echo urlencode($category); ?>">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($category); ?></h4>
            </div>
            <div class="card-body">
                <div class="accordion" id="accordion<?php echo urlencode($category); ?>">
                    <?php foreach ($faqs as $index => $faq): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $category . $index; ?>">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#collapse<?php echo $category . $index; ?>" 
                                    aria-expanded="false" 
                                    aria-controls="collapse<?php echo $category . $index; ?>">
                                <?php echo htmlspecialchars($faq['question']); ?>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $category . $index; ?>" 
                             class="accordion-collapse collapse" 
                             aria-labelledby="heading<?php echo $category . $index; ?>" 
                             data-bs-parent="#accordion<?php echo urlencode($category); ?>">
                            <div class="accordion-body">
                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Quick Help Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5><i class="fas fa-lightbulb text-warning"></i> Vẫn cần hỗ trợ?</h5>
                <p class="text-muted">Nếu không tìm thấy câu trả lời bạn cần, đội ngũ hỗ trợ của chúng tôi luôn sẵn sàng giúp đỡ.</p>
                
                <div class="row">
                    <div class="col-md-3">
                        <a href="?page=customer-support" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-headset"></i><br>
                            Liên hệ hỗ trợ
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="tel:1900-1234" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-phone"></i><br>
                            Gọi Hotline<br>
                            <small>1900-1234</small>
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="mailto:support@evwarranty.com" class="btn btn-info w-100 mb-2">
                            <i class="fas fa-envelope"></i><br>
                            Gửi Email<br>
                            <small>support@evwarranty.com</small>
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="?page=login" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-user"></i><br>
                            Đăng nhập<br>
                            <small>Tài khoản cá nhân</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Popular Topics -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-fire"></i> Chủ đề phổ biến</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Về bảo hành:</h6>
                        <ul class="list-unstyled">
                            <li><a href="?page=faq&search=thời gian bảo hành" class="text-decoration-none">
                                <i class="fas fa-chevron-right text-muted"></i> Thời gian bảo hành xe điện
                            </a></li>
                            <li><a href="?page=faq&search=pin bảo hành" class="text-decoration-none">
                                <i class="fas fa-chevron-right text-muted"></i> Bảo hành pin xe điện
                            </a></li>
                            <li><a href="?page=faq&search=điều kiện bảo hành" class="text-decoration-none">
                                <i class="fas fa-chevron-right text-muted"></i> Điều kiện áp dụng bảo hành
                            </a></li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Về xe điện:</h6>
                        <ul class="list-unstyled">
                            <li><a href="?page=faq&search=sạc xe" class="text-decoration-none">
                                <i class="fas fa-chevron-right text-muted"></i> Cách sạc xe điện
                            </a></li>
                            <li><a href="?page=faq&search=bảo dưỡng" class="text-decoration-none">
                                <i class="fas fa-chevron-right text-muted"></i> Bảo dưỡng định kỳ
                            </a></li>
                            <li><a href="?page=faq&search=vận hành" class="text-decoration-none">
                                <i class="fas fa-chevron-right text-muted"></i> Hướng dẫn vận hành
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Smooth scroll to category
document.querySelectorAll('a[href^="#category-"]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Highlight search terms
<?php if ($search_term): ?>
function highlightSearchTerms() {
    const searchTerm = '<?php echo addslashes($search_term); ?>';
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    
    document.querySelectorAll('.accordion-body, .accordion-button').forEach(element => {
        if (element.children.length === 0) { // Only text nodes
            element.innerHTML = element.innerHTML.replace(regex, '<mark>$1</mark>');
        }
    });
}

// Highlight after page load
document.addEventListener('DOMContentLoaded', highlightSearchTerms);
<?php endif; ?>
</script>