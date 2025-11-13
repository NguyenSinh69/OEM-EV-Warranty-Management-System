# ⚠️ Docker Troubleshooting Guide

## Vấn đề hiện tại:
Docker Desktop đang gặp lỗi I/O với storage. Cần khắc phục trước khi chạy containers.

---

## 🔧 Giải pháp:

### Cách 1: Restart Docker Desktop (Khuyến nghị)
1. Mở Docker Desktop
2. Click **Settings** (biểu tượng bánh răng)
3. Click **Troubleshoot** tab
4. Click **Clean / Purge data**
5. Hoặc đơn giản: **Restart Docker Desktop**

### Cách 2: Reset Docker qua PowerShell (Admin)
```powershell
# Dừng Docker
Stop-Service docker

# Khởi động lại
Start-Service docker
```

### Cách 3: Reset Docker hoàn toàn (Nếu cách trên không work)
1. Tắt Docker Desktop
2. Xóa thư mục: `C:\Users\<YourUser>\AppData\Local\Docker`
3. Khởi động lại Docker Desktop
4. Docker sẽ tự động khởi tạo lại

---

## 🚀 Sau khi fix, chạy lệnh này:

### Option 1: Sử dụng Docker Compose đơn giản
```bash
cd d:\OEM-EV-Warranty-Management-System-main\API_WarrantyClaims
docker-compose -f docker-compose.simple.yml up -d --build
```

### Option 2: Sử dụng Docker Compose đầy đủ (có phpMyAdmin)
```bash
cd d:\OEM-EV-Warranty-Management-System-main\API_WarrantyClaims
docker-compose up -d --build
```

---

## ✅ Kiểm tra containers đang chạy:
```bash
docker ps
```

Bạn sẽ thấy:
- `warranty_api` hoặc `warranty_api_simple` - PHP API
- `warranty_db` hoặc `warranty_db_simple` - MySQL
- `warranty_phpmyadmin` - phpMyAdmin (nếu dùng compose đầy đủ)

---

## 🌐 Truy cập:

**Simple version:**
- API: http://localhost:8080/api/warranty-claims
- MySQL: localhost:3307

**Full version:**
- API: http://localhost:8080/api/warranty-claims
- MySQL: localhost:3306
- phpMyAdmin: http://localhost:8081

---

## 📝 Test API:
```bash
# GET all claims
curl http://localhost:8080/api/warranty-claims

# POST new claim
curl -X POST http://localhost:8080/api/warranty-claims \
  -H "Content-Type: application/json" \
  -d '{"vin":"TEST123","customer_id":999,"description":"Test"}'
```

---

## 🐛 Xem logs:
```bash
# Xem logs của API
docker logs warranty_api_simple -f

# Xem logs của Database
docker logs warranty_db_simple -f
```

---

## 🛑 Dừng containers:
```bash
# Simple version
docker-compose -f docker-compose.simple.yml down

# Full version
docker-compose down
```

---

## 🔄 Nếu vẫn lỗi:

### Chạy thủ công từng container:

```bash
# 1. Tạo network
docker network create warranty_network

# 2. Chạy MySQL
docker run -d \
  --name warranty_db_manual \
  --network warranty_network \
  -p 3307:3306 \
  -e MYSQL_ROOT_PASSWORD=root123 \
  -e MYSQL_DATABASE=warranty_db \
  -v ${PWD}/database.sql:/docker-entrypoint-initdb.d/database.sql \
  mysql:8.0

# 3. Build API image
docker build -f Dockerfile.simple -t warranty-api .

# 4. Chạy API
docker run -d \
  --name warranty_api_manual \
  --network warranty_network \
  -p 8080:8000 \
  -e DB_HOST=warranty_db_manual \
  -e DB_NAME=warranty_db \
  -e DB_USER=root \
  -e DB_PASSWORD=root123 \
  -v ${PWD}:/app \
  warranty-api
```

---

## ℹ️ Thông tin hệ thống:

Phát hiện Docker version: **28.5.1**
Docker Compose version: **v2.40.2**

Lỗi: `input/output error` - Thường do:
- Docker Desktop cần restart
- Storage corruption
- Insufficient disk space
- Antivirus blocking

**Khuyến nghị: Restart Docker Desktop trước!**
