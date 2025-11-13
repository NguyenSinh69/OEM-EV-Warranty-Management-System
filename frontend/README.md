# Hệ thống quản lý bảo hành xe điện (EV Warranty Management System)This is a [Next.js](https://nextjs.org) project bootstrapped with [`create-next-app`](https://nextjs.org/docs/app/api-reference/cli/create-next-app).



## Mô tả dự án## Getting Started



Hệ thống quản lý bảo hành xe điện được xây dựng bằng PHP và chạy trên Docker. Hệ thống cung cấp đầy đủ các tính năng:First, run the development server:



### ✅ Tính năng đã hoàn thành:```bash

npm run dev

1. **Giao diện quản lý yêu cầu bảo hành**# or

   - Danh sách yêu cầu bảo hành với tìm kiếm, lọcyarn dev

   - Chi tiết yêu cầu với lịch sử trạng thái# or

   - Tạo yêu cầu bảo hành mớipnpm dev

   - Cập nhật trạng thái yêu cầu# or

bun dev

2. **Quy trình phê duyệt/từ chối yêu cầu bảo hành**```

   - Workflow hoàn chỉnh: Chờ xử lý → Đang xem xét → Phê duyệt/Từ chối → Đang xử lý → Hoàn thành

   - Gán nhân viên xử lýOpen [http://localhost:3000](http://localhost:3000) with your browser to see the result.

   - Theo dõi lịch sử thay đổi trạng thái

   - Ghi chú và lý do cho mỗi bướcYou can start editing the page by modifying `app/page.tsx`. The page auto-updates as you edit the file.



3. **Trang giám sát đăng ký xe**This project uses [`next/font`](https://nextjs.org/docs/app/building-your-application/optimizing/fonts) to automatically optimize and load [Geist](https://vercel.com/font), a new font family for Vercel.

   - Dashboard theo dõi số lượng xe đăng ký

   - Thống kê theo hãng xe, theo tháng## Learn More

   - Danh sách xe sắp hết bảo hành

   - Xe đăng ký gần đâyTo learn more about Next.js, take a look at the following resources:

   - Biểu đồ trực quan

- [Next.js Documentation](https://nextjs.org/docs) - learn about Next.js features and API.

4. **Công cụ hỗ trợ khách hàng**- [Learn Next.js](https://nextjs.org/learn) - an interactive Next.js tutorial.

   - Tra cứu thông tin bảo hành bằng VIN/biển số

   - Form gửi yêu cầu hỗ trợYou can check out [the Next.js GitHub repository](https://github.com/vercel/next.js) - your feedback and contributions are welcome!

   - FAQ (Câu hỏi thường gặp) với tìm kiếm

   - Thông tin liên hệ hotline, email## Deploy on Vercel

   - Trang bảo hành cá nhân cho khách hàng

The easiest way to deploy your Next.js app is to use the [Vercel Platform](https://vercel.com/new?utm_medium=default-template&filter=next.js&utm_source=create-next-app&utm_campaign=create-next-app-readme) from the creators of Next.js.

5. **Hệ thống Authentication & Authorization**

   - Đăng nhập phân quyền: Admin, Staff, CustomerCheck out our [Next.js deployment documentation](https://nextjs.org/docs/app/building-your-application/deploying) for more details.

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
│   └── index.php                      # Entry point của ứng dụng
├── src/
│   ├── Database.php                   # Kết nối database PDO
│   ├── models/
│   │   └── WarrantyRequest.php        # Model xử lý warranty requests
│   └── views/                         # Templates HTML
│       ├── login.php                  # Trang đăng nhập
│       ├── dashboard.php              # Dashboard chính
│       ├── warranty_requests.php      # Quản lý yêu cầu bảo hành
│       ├── warranty_detail.php        # Chi tiết yêu cầu
│       ├── vehicle_monitoring.php     # Giám sát đăng ký xe
│       ├── customer_support.php       # Hỗ trợ khách hàng
│       ├── faq.php                    # FAQ
│       └── customer_warranties.php    # Bảo hành cá nhân
├── assets/
│   ├── css/
│   │   └── style.css                  # Custom CSS
│   └── js/
│       └── main.js                    # Custom JavaScript
└── test-frontend.html                 # Frontend test với theme xanh ngọc
```

## Công nghệ sử dụng

- **Backend**: PHP 8.1 với Apache
- **Database**: MySQL 5.7 
- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5
- **Containerization**: Docker & Docker Compose
- **Additional**: phpMyAdmin cho quản lý database

## Cách chạy dự án

### 1. Yêu cầu hệ thống
- Docker Desktop
- Git

### 2. Clone và chạy
```bash
git clone https://github.com/NguyenSinh69/OEM-EV-Warranty-Management-System.git
cd OEM-EV-Warranty-Management-System/frontend
docker-compose up -d
```

### 3. Truy cập ứng dụng

#### Web Application (Laravel API):
- **URL**: http://localhost:8090
- **API Health Check**: http://localhost:8090/api/health
- **Test Frontend**: http://localhost:8090/test-frontend.html

#### Management Tools:
- **phpMyAdmin**: http://localhost:8081
  - Username: `warranty_user`
  - Password: `warranty_pass`
- **MailHog** (Email testing): http://localhost:8025

#### API Endpoints chính:
- `GET /api/warranty-claims` - Danh sách yêu cầu bảo hành
- `POST /api/warranty-claims` - Tạo yêu cầu mới
- `PATCH /api/warranty-claims/{id}/status` - Cập nhật trạng thái
- `GET /api/vehicles` - Danh sách xe
- `GET /api/customers` - Danh sách khách hàng
- `GET /api/approvals/pending` - Yêu cầu chờ phê duyệt

### 4. Tài khoản mặc định
- **Admin**: username: `admin`, password: `password123`
- **Customer**: username: `customer`, password: `password123`

## Features & Screenshots

### 🎨 Theme màu xanh ngọc đậm (Teal)
- Sidebar gradient xanh ngọc sang trọng
- Button và badge với màu chủ đạo #0d9488
- Card header với background gradient nhẹ nhàng
- Responsive design hoàn hảo trên mọi thiết bị

### 📊 Dashboard Analytics
- Thống kê realtime về warranty claims
- Biểu đồ số liệu trực quan
- Quick actions cho các tác vụ thường dùng
- Recent claims với status tracking

### 🔧 Warranty Management
- CRUD hoàn chỉnh cho warranty claims
- Approval workflow với status updates
- Priority levels và categorization
- Search và filter functionality

### 🚗 Vehicle Monitoring
- Vehicle registration tracking
- VIN-based lookup system
- Warranty period monitoring
- Customer-vehicle relationships

### 🎯 Customer Support
- FAQ system với search
- Support ticket creation
- Customer warranty lookup
- Contact information management

## Database Schema

### Bảng chính:
- `users` - Quản lý người dùng và phân quyền
- `customers` - Thông tin khách hàng
- `vehicles` - Đăng ký xe và thông tin kỹ thuật
- `warranty_requests` - Yêu cầu bảo hành
- `faqs` - Câu hỏi thường gặp

### Sample Data:
Database được populate sẵn với:
- 3 users (admin, staff, customer)
- 5 customers mẫu
- 10 vehicles với các hãng khác nhau
- 15 warranty requests với trạng thái đa dạng
- 10 FAQ entries

## API Documentation

### Authentication
```bash
POST /api/auth/login
{
  "username": "admin",
  "password": "password123"
}
```

### Warranty Claims
```bash
# Lấy tất cả claims
GET /api/warranty-claims

# Tạo claim mới
POST /api/warranty-claims
{
  "vehicle_warranty_id": 1,
  "customer_id": 1,
  "claim_type": "repair",
  "priority": "high",
  "issue_description": "Mô tả vấn đề"
}

# Cập nhật trạng thái
PATCH /api/warranty-claims/{id}/status
{
  "status": "approved"
}
```

### Vehicle Management
```bash
# Lấy vehicle theo VIN
GET /api/vehicles/vin/{vin}

# Tạo vehicle mới
POST /api/vehicles
{
  "vin": "1HGBH41JXMN109186",
  "customer_id": 1,
  "make": "Tesla",
  "model": "Model 3",
  "year": 2023
}
```

## Docker Services

### Container Architecture:
- **warranty_app**: PHP 8.1 + Nginx application server
- **warranty_db**: MariaDB 10.6 database
- **warranty_phpmyadmin**: phpMyAdmin interface
- **warranty_redis**: Redis caching layer
- **warranty_mailhog**: Email testing service

### Ports:
- `8090`: Main application
- `8081`: phpMyAdmin
- `3307`: MariaDB
- `6380`: Redis
- `8025`: MailHog web UI
- `1025`: MailHog SMTP

## Development

### Code Structure:
- **MVC Pattern**: Models, Views, Controllers separation
- **API-First**: RESTful API với JSON responses
- **Responsive Design**: Bootstrap 5 với custom CSS
- **Error Handling**: Try-catch với proper error responses
- **Security**: PDO prepared statements, input validation

### Best Practices:
- PSR-4 autoloading
- Environment variables cho config
- CORS headers cho cross-origin requests
- Database connection pooling
- Clean code với proper commenting

## Deployment

### Production Ready:
- Docker containers optimized cho production
- Environment variables cho sensitive data
- Health check endpoints
- Error logging và monitoring
- Database migration scripts

### Scaling:
- Redis caching layer
- Stateless application design
- Load balancer ready
- API rate limiting capability

## Contributing

1. Fork repository
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## License

Distributed under the MIT License. See `LICENSE` for more information.

## Contact

- **Developer**: NguyenSinh69
- **Project Link**: https://github.com/NguyenSinh69/OEM-EV-Warranty-Management-System
- **Live Demo**: http://localhost:8090/test-frontend.html

---

**Status**: ✅ Production Ready - Full-featured EV Warranty Management System với beautiful teal theme!