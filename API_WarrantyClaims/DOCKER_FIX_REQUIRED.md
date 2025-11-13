# ⚠️ CẢNH BÁO: Docker Storage bị hỏng

## Vấn đề:
```
input/output error
blob expected at /var/lib/desktop-containerd/...
```

Docker Desktop của bạn có **storage corruption nghiêm trọng**.

---

## ✅ GIẢI PHÁP DUY NHẤT:

### Cách 1: Reset Docker Desktop (Khuyến nghị nhất)

1. **Mở Docker Desktop**
2. Click **Settings** (biểu tượng bánh răng)
3. Chọn tab **Troubleshoot** (hoặc **Resources**)
4. Click **"Reset to factory defaults"** hoặc **"Clean / Purge data"**
5. Confirm và đợi Docker reset (mất 2-5 phút)
6. Khởi động lại Docker Desktop

⚠️ **Lưu ý:** Điều này sẽ xóa tất cả containers, images, volumes hiện có!

---

### Cách 2: Uninstall và Reinstall Docker Desktop

Nếu reset không work:

1. **Uninstall Docker Desktop:**
   - Settings → Apps → Docker Desktop → Uninstall

2. **Xóa dữ liệu còn sót:**
   ```powershell
   # Chạy PowerShell as Administrator
   Remove-Item -Recurse -Force "$env:LOCALAPPDATA\Docker"
   Remove-Item -Recurse -Force "$env:APPDATA\Docker"
   Remove-Item -Recurse -Force "$env:ProgramData\Docker"
   ```

3. **Restart máy tính**

4. **Reinstall Docker Desktop:**
   - Download: https://www.docker.com/products/docker-desktop/
   - Cài đặt lại

---

### Cách 3: Chạy Docker trên WSL2 (Advanced)

Nếu Windows Docker không ổn định:

1. Enable WSL2
2. Install Docker trong WSL2
3. Sử dụng Docker từ WSL2 terminal

---

## 🚀 SAU KHI FIX DOCKER:

Chạy lệnh sau để khởi động ứng dụng:

```bash
cd d:\OEM-EV-Warranty-Management-System-main\API_WarrantyClaims
docker-compose -f docker-compose.simple.yml up -d --build
```

Hoặc sử dụng:
```bash
docker-start.bat
```

---

## 💡 TẠM THỜI: Chạy KHÔNG CẦN Docker

Trong khi chờ fix Docker, bạn có thể chạy ứng dụng local:

### Yêu cầu:
- ✅ PHP 8.0+ (đã có)
- ✅ MySQL 8.0+
- ✅ Xampp/WAMP (hoặc MySQL standalone)

### Bước 1: Setup MySQL

**Nếu dùng XAMPP:**
1. Mở XAMPP Control Panel
2. Start Apache và MySQL
3. Mở phpMyAdmin: http://localhost/phpmyadmin
4. Import file `database.sql`

**Nếu dùng MySQL standalone:**
```bash
mysql -u root -p < database.sql
```

### Bước 2: Kiểm tra Database config

File `src/Database.php` đã được cấu hình:
- Host: localhost
- Database: warranty_db
- User: root
- Password: (trống hoặc password của bạn)

### Bước 3: Chạy PHP Server

```bash
cd d:\OEM-EV-Warranty-Management-System-main\API_WarrantyClaims\public
php -S localhost:8000 router.php
```

### Bước 4: Test API

Mở trình duyệt:
- API: http://localhost:8000/api/warranty-claims
- Test UI: http://localhost:8000/../test-api.html

---

## 🔍 Kiểm tra Docker đã OK chưa:

```bash
# Test Docker
docker --version
docker ps
docker run hello-world

# Nếu lệnh trên chạy OK, Docker đã sẵn sàng!
```

---

## ❓ Cần trợ giúp?

- Docker documentation: https://docs.docker.com/desktop/troubleshoot/
- Docker forums: https://forums.docker.com/
- Stack Overflow: https://stackoverflow.com/questions/tagged/docker

---

**Tóm tắt:** Docker storage bị lỗi, cần reset hoặc reinstall Docker Desktop!
