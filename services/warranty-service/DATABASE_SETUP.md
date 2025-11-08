# Hướng dẫn cài đặt Database cho Warranty Service

## Phương án 1: Sử dụng XAMPP (Khuyến nghị)

### Bước 1: Tải và cài đặt XAMPP
1. Truy cập: https://www.apachefriends.org/download.html
2. Tải XAMPP cho Windows (version mới nhất)
3. Chạy file cài đặt và làm theo hướng dẫn

### Bước 2: Khởi động MySQL
1. Mở XAMPP Control Panel
2. Click "Start" cho Apache và MySQL
3. Đảm bảo cả 2 service đều chạy (màu xanh)

### Bước 3: Tạo Database
1. Mở trình duyệt, truy cập: http://localhost/phpmyadmin
2. Click "SQL" tab
3. Copy nội dung file `setup_database.sql` và paste vào
4. Click "Go" để chạy script

### Bước 4: Cập nhật cấu hình
Sửa file `.env` với thông tin:
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=warranty
DB_USERNAME=root
DB_PASSWORD=
```

## Phương án 2: MySQL độc lập

### Cài đặt qua Chocolatey
```powershell
# Cài Chocolatey (nếu chưa có)
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Cài MySQL
choco install mysql
```

### Cài đặt qua WinGet
```powershell
winget install Oracle.MySQL
```

## Phương án 3: Sử dụng Docker (nếu Docker hoạt động)

```powershell
docker run --name warranty-mysql -e MYSQL_ROOT_PASSWORD=password -e MYSQL_DATABASE=warranty -p 3306:3306 -d mysql:8.0
```

## Kiểm tra kết nối

Sau khi cài đặt xong, truy cập: http://localhost:8000/claims để kiểm tra API.