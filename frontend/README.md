# Hệ thống quản lý bảo hành xe điện (EV Warranty Management System)

## Mô tả dự án

Hệ thống quản lý bảo hành xe điện được xây dựng bằng PHP và chạy trên Docker. Hệ thống cung cấp đầy đủ các tính năng:

### ✅ Tính năng đã hoàn thành:

1. **Giao diện quản lý yêu cầu bảo hành**
   - Danh sách yêu cầu bảo hành với tìm kiếm, lọc
   - Chi tiết yêu cầu với lịch sử trạng thái
   - Tạo yêu cầu bảo hành mới
   - Cập nhật trạng thái yêu cầu

2. **Quy trình phê duyệt/từ chối yêu cầu bảo hành**
   - Workflow hoàn chỉnh: Chờ xử lý → Đang xem xét → Phê duyệt/Từ chối → Đang xử lý → Hoàn thành
   - Gán nhân viên xử lý
   - Theo dõi lịch sử thay đổi trạng thái
   - Ghi chú và lý do cho mỗi bước

3. **Trang giám sát đăng ký xe**
   - Dashboard theo dõi số lượng xe đăng ký
   - Thống kê theo hãng xe, theo tháng
   - Danh sách xe sắp hết bảo hành
   - Xe đăng ký gần đây
   - Biểu đồ trực quan

4. **Công cụ hỗ trợ khách hàng**
   - Tra cứu thông tin bảo hành bằng VIN/biển số
   - Form gửi yêu cầu hỗ trợ
   - FAQ (Câu hỏi thường gặp) với tìm kiếm
   - Thông tin liên hệ hotline, email
   - Trang bảo hành cá nhân cho khách hàng

5. **Hệ thống Authentication & Authorization**
   - Đăng nhập phân quyền: Admin, Staff, Customer
   - Bảo mật session và phân quyền truy cập
   - Giao diện khác nhau theo role

## Cấu trúc dự án

```
frontend/
├── Dockerfile                          # Container PHP Apache
├── docker-compose.yml                  # Orchestration với MySQL, phpMyAdmin
├── apache-config.conf                  # Cấu hình Apache
├── database/
│   └── init.sql                       # Database schema và dữ liệu mẫu
├── public/
│   └── index.php                      # Entry point, routing
├── src/
│   ├── Database.php                   # Kết nối database
│   ├── models/
│   │   └── WarrantyRequest.php        # Model yêu cầu bảo hành
│   └── views/                         # Các trang giao diện
│       ├── login.php                  # Đăng nhập
│       ├── dashboard.php              # Trang chủ
│       ├── warranty_requests.php      # Quản lý yêu cầu bảo hành
│       ├── warranty_detail.php        # Chi tiết yêu cầu bảo hành
│       ├── vehicle_monitoring.php     # Giám sát xe
│       ├── customer_support.php       # Hỗ trợ khách hàng
│       ├── customer_warranties.php    # Bảo hành của khách hàng
│       └── faq.php                   # FAQ
└── assets/
    ├── css/
    │   └── style.css                  # Custom CSS
    └── js/
        └── main.js                    # JavaScript utilities
```

## Cài đặt và chạy ứng dụng

### Yêu cầu hệ thống
- Docker Desktop
- Docker Compose
- Trình duyệt web hiện đại

### Bước 1: Clone/Download dự án
```bash
# Nếu có git
git clone <repo-url>
cd frontend

# Hoặc extract file zip vào thư mục frontend
```

### Bước 2: Chạy Docker
```bash
# Khởi động containers
docker-compose up -d

# Kiểm tra containers đang chạy
docker-compose ps
```

### Bước 3: Truy cập ứng dụng

- **Ứng dụng chính**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
  - Username: root
  - Password: root123

### Bước 4: Đăng nhập hệ thống

Hệ thống có sẵn 3 tài khoản demo:

1. **Admin**: 
   - Username: `admin`
   - Password: `password`
   - Quyền: Quản lý toàn bộ hệ thống

2. **Staff** (Nhân viên):
   - Username: `staff1`
   - Password: `password`  
   - Quyền: Xử lý yêu cầu bảo hành

3. **Customer** (Khách hàng):
   - Username: `customer1`
   - Password: `password`
   - Quyền: Tạo và theo dõi yêu cầu bảo hành

## Tính năng chính

### 1. Dashboard
- Thống kê tổng quan về xe và yêu cầu bảo hành
- Biểu đồ trạng thái yêu cầu
- Danh sách yêu cầu mới nhất
- Thao tác nhanh

### 2. Quản lý yêu cầu bảo hành
- **Danh sách yêu cầu**: Hiển thị tất cả yêu cầu với filters
- **Chi tiết yêu cầu**: Thông tin đầy đủ, lịch sử trạng thái
- **Workflow phê duyệt**: 
  - Pending → In Review → Approved/Rejected → In Progress → Completed
- **Gán nhân viên**: Assign staff cho từng yêu cầu
- **Timeline**: Theo dõi lịch sử thay đổi

### 3. Giám sát xe
- **Thống kê xe**: Tổng số, hoạt động, sắp hết bảo hành
- **Biểu đồ**: Đăng ký theo tháng, phân bổ theo hãng xe
- **Cảnh báo**: Xe sắp hết bảo hành trong 60 ngày
- **Đăng ký xe mới**: Form đăng ký xe cho khách hàng

### 4. Hỗ trợ khách hàng
- **Tra cứu bảo hành**: Tìm kiếm bằng VIN hoặc biển số
- **Gửi yêu cầu hỗ trợ**: Form liên hệ với categorization
- **FAQ**: Câu hỏi thường gặp với tìm kiếm
- **Thông tin liên hệ**: Hotline, email, địa chỉ

### 5. Trang khách hàng
- **Xe của tôi**: Danh sách xe đã đăng ký
- **Trạng thái bảo hành**: Kiểm tra thời hạn bảo hành
- **Lịch sử yêu cầu**: Theo dõi các yêu cầu đã gửi
- **Tạo yêu cầu mới**: Form tạo yêu cầu bảo hành

## Cơ sở dữ liệu

### Các bảng chính:
- `users`: Người dùng (admin, staff, customer)
- `manufacturers`: Hãng xe
- `vehicle_models`: Mẫu xe
- `vehicle_registrations`: Đăng ký xe
- `warranty_requests`: Yêu cầu bảo hành
- `warranty_status_history`: Lịch sử trạng thái
- `issue_categories`: Danh mục sự cố
- `spare_parts`: Phụ tùng
- `faqs`: Câu hỏi thường gặp
- `support_tickets`: Yêu cầu hỗ trợ

### Dữ liệu mẫu có sẵn:
- 3 hãng xe: VinFast, Tesla, BYD
- 4 mẫu xe với thông tin pin, quãng đường
- 6 danh mục sự cố
- Phụ tùng và linh kiện
- FAQ mẫu

## API Endpoints (Có thể mở rộng)

Hệ thống đã chuẩn bị sẵn cấu trúc để phát triển API:

```
GET    /api/warranty-requests     # Lấy danh sách yêu cầu
POST   /api/warranty-requests     # Tạo yêu cầu mới  
GET    /api/warranty-requests/:id # Chi tiết yêu cầu
PUT    /api/warranty-requests/:id # Cập nhật yêu cầu
GET    /api/vehicles              # Danh sách xe
GET    /api/customers             # Danh sách khách hàng
```

## Công nghệ sử dụng

### Backend:
- **PHP 8.2**: Ngôn ngữ chính
- **Apache**: Web server
- **MySQL 8.0**: Cơ sở dữ liệu
- **PDO**: Database abstraction

### Frontend:
- **Bootstrap 5**: CSS framework
- **Font Awesome 6**: Icons
- **Chart.js**: Biểu đồ
- **Vanilla JavaScript**: Interactions

### DevOps:
- **Docker**: Containerization
- **Docker Compose**: Multi-container orchestration
- **phpMyAdmin**: Database management

## Tính năng nâng cao

### Đã triển khai:
- Responsive design cho mobile
- Search và filter realtime
- Validation form phía client
- Timeline interface cho lịch sử
- Status badges với color coding
- Modal dialogs cho UX tốt hơn

### Có thể mở rộng:
- PWA (Progressive Web App)
- Push notifications
- File upload cho attachments
- Email notifications
- Report generation (PDF/Excel)
- Multi-language support
- Dark mode
- Advanced analytics

## Troubleshooting

### Container không khởi động:
```bash
# Kiểm tra logs
docker-compose logs

# Restart containers
docker-compose restart

# Rebuild nếu cần
docker-compose down
docker-compose up --build
```

### Database connection error:
- Kiểm tra MySQL container đã chạy: `docker-compose ps`
- Verify credentials trong `.env` hoặc `docker-compose.yml`
- Đợi MySQL khởi động hoàn toàn (có thể mất 1-2 phút)

### Port conflicts:
- Thay đổi ports trong `docker-compose.yml` nếu bị conflict
- Default ports: 8080 (web), 3306 (MySQL), 8081 (phpMyAdmin)

## Bảo mật

### Implemented:
- Session-based authentication
- Role-based access control
- SQL injection prevention (PDO prepared statements)
- XSS protection (htmlspecialchars)
- CSRF protection cơ bản

### Khuyến nghị production:
- HTTPS/SSL certificates
- Environment variables cho credentials
- Rate limiting
- Input validation server-side
- Logging và monitoring
- Regular security updates

## Liên hệ & Hỗ trợ

Để được hỗ trợ về dự án này:
- Email: support@evwarranty.com
- Hotline: 1900-1234
- GitHub Issues (nếu có repo)

---

**Lưu ý**: Đây là phiên bản demo/development. Để triển khai production cần bổ sung thêm các tính năng bảo mật và tối ưu hóa hiệu suất.