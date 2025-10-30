# OEM EV Warranty Management System - Warranty Service

Hệ thống quản lý bảo hành cho xe điện OEM - Phần dịch vụ bảo hành API

## Yêu cầu hệ thống

- PHP >= 8.0
- MySQL/MariaDB
- Composer
- Docker (tùy chọn)

## Cấu trúc thư mục

```
├── public/             # Public directory
│   └── index.php      # Entry point
├── src/               # Source code
│   ├── controllers/   # API Controllers
│   ├── models/        # Data models
│   └── middleware/    # Middleware components
├── migrations/        # Database migrations
└── logs/             # Application logs
```

## Cài đặt

1. Cài đặt các dependency:
```bash
composer install
```

2. Cấu hình môi trường:
- Sao chép file `.env.example` thành `.env`
- Cập nhật các thông số kết nối database:
  ```
  DB_HOST=localhost
  DB_NAME=warranty_db
  DB_USER=your_username
  DB_PASS=your_password
  ```

3. Khởi tạo database:
- Tạo database mới
- Chạy script migration:
  ```bash
  mysql -u your_username -p warranty_db < migrations/001_create_claims_table.sql
  ```

## Chạy ứng dụng 

### Sử dụng PHP built-in server:
```bash
php -S 0.0.0.0:8000 -t public
```

### Hoặc sử dụng Docker:
```bash
docker-compose up -d
```

## API Endpoints

### Claims

- `GET /claims` - Lấy danh sách yêu cầu bảo hành
- `GET /claims/{id}` - Lấy chi tiết một yêu cầu bảo hành
- `POST /claims` - Tạo yêu cầu bảo hành mới
- `PUT /claims/{id}` - Cập nhật yêu cầu bảo hành
- `DELETE /claims/{id}` - Xóa yêu cầu bảo hành
