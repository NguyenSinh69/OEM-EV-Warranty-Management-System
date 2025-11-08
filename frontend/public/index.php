<?php
session_start();

// Autoload classes
spl_autoload_register(function ($class_name) {
    if (file_exists('../src/' . $class_name . '.php')) {
        include '../src/' . $class_name . '.php';
    } elseif (file_exists('../src/models/' . $class_name . '.php')) {
        include '../src/models/' . $class_name . '.php';
    } elseif (file_exists('../src/controllers/' . $class_name . '.php')) {
        include '../src/controllers/' . $class_name . '.php';
    }
});

// Routing đơn giản
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

// Kiểm tra đăng nhập (trừ trang login và customer support)
if (!isset($_SESSION['user_id']) && $page !== 'login' && $page !== 'customer-support' && $page !== 'faq') {
    $page = 'login';
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống quản lý bảo hành xe điện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="?page=dashboard">
                    <i class="fas fa-car-battery"></i> EV Warranty System
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" 
                               href="?page=dashboard">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        
                        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'warranty-requests' ? 'active' : ''; ?>" 
                               href="?page=warranty-requests">
                                <i class="fas fa-tools"></i> Yêu cầu bảo hành
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'vehicle-monitoring' ? 'active' : ''; ?>" 
                               href="?page=vehicle-monitoring">
                                <i class="fas fa-car"></i> Giám sát xe
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['role'] === 'customer'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'my-warranties' ? 'active' : ''; ?>" 
                               href="?page=my-warranties">
                                <i class="fas fa-file-alt"></i> Bảo hành của tôi
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'customer-support' ? 'active' : ''; ?>" 
                               href="?page=customer-support">
                                <i class="fas fa-headset"></i> Hỗ trợ
                            </a>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                               data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['full_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?page=profile">Hồ sơ</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?page=logout">Đăng xuất</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container-fluid">
        <?php
        switch ($page) {
            case 'login':
                include '../src/views/login.php';
                break;
            case 'dashboard':
                include '../src/views/dashboard.php';
                break;
            case 'warranty-requests':
                include '../src/views/warranty_requests.php';
                break;
            case 'warranty-detail':
                include '../src/views/warranty_detail.php';
                break;
            case 'vehicle-monitoring':
                include '../src/views/vehicle_monitoring.php';
                break;
            case 'customer-support':
                include '../src/views/customer_support.php';
                break;
            case 'my-warranties':
                include '../src/views/customer_warranties.php';
                break;
            case 'faq':
                include '../src/views/faq.php';
                break;
            case 'logout':
                session_destroy();
                header('Location: ?page=login');
                exit();
                break;
            default:
                include '../src/views/dashboard.php';
                break;
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>