# EV Warranty Management System - Docker Setup

## 🐳 Chạy ứng dụng với Docker

### Yêu cầu:
- Docker Desktop đã được cài đặt
- Docker Compose

### Hướng dẫn chạy:

#### 1. Build và khởi động containers:
```bash
docker-compose up -d
```

#### 2. Kiểm tra containers đang chạy:
```bash
docker-compose ps
```

#### 3. Xem logs:
```bash
docker-compose logs -f
```

#### 4. Dừng containers:
```bash
docker-compose down
```

#### 5. Xóa cả volumes (database data):
```bash
docker-compose down -v
```

---

## 🌐 Truy cập ứng dụng:

- **API**: http://localhost:8080/api/warranty-claims
- **phpMyAdmin**: http://localhost:8081
  - Server: `db`
  - Username: `root`
  - Password: `root123`
- **Test Page**: http://localhost:8080/../test-api.html

---

## 📋 API Endpoints:

### 1. Tạo Warranty Claim mới
```bash
POST http://localhost:8080/api/warranty-claims
Content-Type: application/json

{
  "vin": "1HGBH41JXMN109186",
  "customer_id": 12345,
  "description": "Pin sạc không đầy"
}
```

### 2. Lấy tất cả Claims
```bash
GET http://localhost:8080/api/warranty-claims
```

---

## 🔧 Services:

| Service | Container Name | Port | Description |
|---------|---------------|------|-------------|
| API | warranty_api | 8080 | PHP 8.0 + Apache |
| Database | warranty_db | 3306 | MySQL 8.0 |
| phpMyAdmin | warranty_phpmyadmin | 8081 | Database Management |

---

## 🗄️ Database:

Database sẽ tự động được tạo khi khởi động container với:
- Database: `warranty_db`
- User: `root`
- Password: `root123`
- Init script: `database.sql`

---

## 📁 Cấu trúc Docker:

```
API_WarrantyClaims/
├── Dockerfile              # PHP API container
├── docker-compose.yml      # Orchestration
├── .dockerignore          # Ignore files
├── database.sql           # Database schema
└── src/
    └── Database.php       # Sử dụng ENV variables
```

---

## 🐛 Troubleshooting:

### Container không khởi động:
```bash
docker-compose logs api
docker-compose logs db
```

### Reset database:
```bash
docker-compose down -v
docker-compose up -d
```

### Rebuild containers:
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Kiểm tra kết nối database:
```bash
docker exec -it warranty_db mysql -uroot -proot123 -e "SHOW DATABASES;"
```

---

## 📝 Test với curl:

### GET all claims:
```bash
curl http://localhost:8080/api/warranty-claims
```

### POST new claim:
```bash
curl -X POST http://localhost:8080/api/warranty-claims \
  -H "Content-Type: application/json" \
  -d '{"vin":"TEST123","customer_id":999,"description":"Test claim"}'
```

---

## 🔐 Bảo mật (Production):

⚠️ Đây là cấu hình development. Cho production cần:
- Thay đổi password database
- Sử dụng `.env` file
- Enable HTTPS
- Cấu hình CORS properly
- Remove phpMyAdmin
