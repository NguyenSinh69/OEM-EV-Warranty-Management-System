<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $query = "SELECT id, username, password, full_name, role FROM users WHERE username = ? AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Kiểm tra password (demo: password đơn giản)
        if (password_verify($password, $user['password']) || $password === 'password') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: ?page=dashboard');
            exit();
        } else {
            $error = "Sai tên đăng nhập hoặc mật khẩu!";
        }
    } else {
        $error = "Sai tên đăng nhập hoặc mật khẩu!";
    }
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4><i class="fas fa-car-battery"></i> Đăng nhập hệ thống</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </button>
                </form>
                
                <hr>
                <div class="text-center">
                    <small class="text-muted">
                        Demo accounts:<br>
                        Admin: admin / password<br>
                        Staff: staff1 / password<br>
                        Customer: customer1 / password
                    </small>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="?page=customer-support" class="btn btn-outline-primary">
                <i class="fas fa-headset"></i> Hỗ trợ khách hàng
            </a>
            <a href="?page=faq" class="btn btn-outline-info">
                <i class="fas fa-question-circle"></i> FAQ
            </a>
        </div>
    </div>
</div>