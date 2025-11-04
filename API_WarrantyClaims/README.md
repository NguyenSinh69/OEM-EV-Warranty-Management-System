# 🚗 EV Warranty Management System API

Hệ thống quản lý bảo hành xe điện (Electric Vehicle Warranty Management)

---

## 🎯 Tính năng

- ✅ Tạo warranty claim mới
- ✅ Xem danh sách tất cả claims
- ✅ Cập nhật trạng thái claim
- ✅ Upload tài liệu đính kèm
- ✅ Xem chi phí bảo hành
- ✅ Phê duyệt claim

---

## 🚀 Cách chạy

### Option 1: Chạy với Docker (Khuyến nghị) 🐳

**Nhanh nhất:**
```bash
# Windows
docker-start.bat

# Linux/Mac
chmod +x docker-start.sh
./docker-start.sh
```

**Hoặc thủ công:**
```bash
docker-compose -f docker-compose.simple.yml up -d --build
```

**Truy cập:**
- API: http://localhost:8080/api/warranty-claims
- Test UI: http://localhost:8080/../test-api.html
- MySQL: localhost:3307

**Dừng:**
```bash
docker-compose -f docker-compose.simple.yml down
```

---

### Option 2: Chạy local với PHP 🔧

**Yêu cầu:**
- PHP 8.0+
- MySQL 8.0+
- PDO extension

**Bước 1: Setup Database**
```sql
-- Import database.sql vào MySQL
mysql -u root -p < database.sql
```

**Bước 2: Cấu hình Database**
Chỉnh trong `src/Database.php` nếu cần:
```php
host: localhost
database: warranty_db
username: root
password: (your password)
```

**Bước 3: Chạy server**
```bash
cd public
php -S localhost:8000 router.php
```

**Truy cập:**
- API: http://localhost:8000/api/warranty-claims
- Test UI: http://localhost:8000/../test-api.html

---

## 📋 API Endpoints

### 1. GET - Lấy tất cả claims
```
GET /api/warranty-claims
```

**Response:**
```json
[
  {
    "id": 1,
    "vin": "1HGBH41JXMN109186",
    "customer_id": 12345,
    "description": "Pin sạc không đầy",
    "status": "PENDING",
    "costs": "0.00",
    "attachment": null,
    "created_at": "2025-11-04 10:30:00"
  }
]
```

---

### 2. POST - Tạo claim mới
```
POST /api/warranty-claims
Content-Type: application/json
```

**Request Body:**
```json
{
  "vin": "1HGBH41JXMN109186",
  "customer_id": 12345,
  "description": "Pin sạc không đầy, cần kiểm tra"
}
```

**Response:**
```json
{
  "message": "Claim created successfully",
  "id": 1
}
```

---

## 🧪 Test API

### Sử dụng Web UI
Mở: http://localhost:8000/../test-api.html (local) hoặc http://localhost:8080/../test-api.html (Docker)

### Sử dụng curl

**GET all claims:**
```bash
curl http://localhost:8080/api/warranty-claims
```

**POST new claim:**
```bash
curl -X POST http://localhost:8080/api/warranty-claims \
  -H "Content-Type: application/json" \
  -d '{
    "vin": "TEST123456789",
    "customer_id": 999,
    "description": "Test warranty claim"
  }'
```

### Sử dụng Postman
Import collection từ endpoint: `http://localhost:8080/api/warranty-claims`

---

## 📁 Cấu trúc thư mục

```
API_WarrantyClaims/
├── database.sql                    # Database schema
├── docker-compose.yml              # Docker full (API + MySQL + phpMyAdmin)
├── docker-compose.simple.yml       # Docker simple (API + MySQL)
├── docker-start.bat                # Windows start script
├── Dockerfile                      # PHP Apache image
├── Dockerfile.simple               # PHP CLI image
├── test-api.html                   # Test UI
├── public/
│   ├── index.php                   # Main entry point
│   └── router.php                  # Router for built-in server
└── src/
    ├── Database.php                # Database connection
    ├── Controllers/
    │   └── WarrantyClaimController.php
    └── Models/
        └── WarrantyClaim.php
```

---

## 🗄️ Database Schema

```sql
CREATE TABLE warranty_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vin VARCHAR(50) NOT NULL,
    customer_id INT,
    description TEXT,
    status ENUM('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
    costs DECIMAL(10,2) DEFAULT 0,
    attachment VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🐛 Troubleshooting

### Docker không chạy được?
Xem file: `DOCKER_TROUBLESHOOTING.md`

Giải pháp nhanh:
1. Restart Docker Desktop
2. Chạy: `docker system prune -f`
3. Thử lại: `docker-start.bat`

### Lỗi kết nối database?
- Kiểm tra MySQL đã chạy: `docker ps` hoặc `netstat -an | findstr 3306`
- Import database.sql
- Kiểm tra thông tin kết nối trong `src/Database.php`

### Port đã được sử dụng?
```bash
# Đổi port trong docker-compose.simple.yml
ports:
  - "8090:8000"  # Thay vì 8080:8000
```

---

## 📚 Tài liệu thêm

- **DOCKER_README.md** - Hướng dẫn chi tiết về Docker
- **DOCKER_TROUBLESHOOTING.md** - Khắc phục lỗi Docker

---

## 🔐 Bảo mật

⚠️ **Lưu ý:** Đây là cấu hình development. 

Cho production cần:
- [ ] Thay đổi database password
- [ ] Sử dụng `.env` file cho sensitive data
- [ ] Enable HTTPS
- [ ] Cấu hình CORS properly
- [ ] Add authentication/authorization
- [ ] Remove phpMyAdmin
- [ ] Add rate limiting
- [ ] Input validation & sanitization

---

## 📝 License

MIT License

---

## 👥 Author

Feature Branch: `feature/warranty-service-1.2`
Repository: `OEM-EV-Warranty-Management-System`

---

## 🆘 Support

Nếu gặp vấn đề, hãy kiểm tra:
1. ✅ Docker Desktop đang chạy
2. ✅ MySQL đã được setup
3. ✅ Port 8000/8080 không bị sử dụng
4. ✅ PHP extensions (PDO, PDO_MySQL) đã được cài đặt
