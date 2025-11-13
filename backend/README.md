# OEM EV Warranty Management System - Backend

Hệ thống quản lý bảo hành cho xe điện OEM được xây dựng bằng PHP và chạy trên Docker.

## 🚀 Tính năng chính

- ✅ **API Warranty Service** - Quản lý yêu cầu bảo hành
- ✅ **Quy trình phê duyệt** - Workflow approval đa cấp
- ✅ **Tích hợp Customer Service** - Đồng bộ dữ liệu với hệ thống khách hàng
- ✅ **Hệ thống thông báo** - Email/SMS notifications
- ✅ **JWT Authentication** - Xác thực và phân quyền
- ✅ **Docker Support** - Containerized deployment

## 🏗️ Kiến trúc hệ thống

```
backend/
├── app/
│   ├── Core/           # Framework core classes
│   ├── Controllers/    # API controllers
│   ├── Models/         # Database models
│   └── Services/       # Business logic services
├── database/
│   └── migrations/     # Database schema files
├── docker/             # Docker configuration
├── public/             # Web root
├── routes/             # API routes
└── storage/            # Logs and uploads
```

## 🐳 Chạy với Docker

### 1. Khởi động dịch vụ

```bash
# Build và start containers
docker-compose up -d --build

# Kiểm tra trạng thái
docker-compose ps
```

### 2. Truy cập ứng dụng

- **API**: http://localhost:8080
- **Database**: localhost:3307
- **phpMyAdmin**: http://localhost:8081
- **MailHog**: http://localhost:8025
- **Redis**: localhost:6380

### 3. Database setup

```bash
# Chạy migrations
docker-compose exec app php database/run-migrations.php
```

## 📚 API Documentation

### Authentication

```http
POST /api/auth/login
POST /api/auth/register
POST /api/auth/refresh
POST /api/auth/logout
GET /api/auth/me
```

### Warranty Claims

```http
GET /api/warranty-claims
POST /api/warranty-claims
GET /api/warranty-claims/{id}
PUT /api/warranty-claims/{id}
PATCH /api/warranty-claims/{id}/status
DELETE /api/warranty-claims/{id}
```

### Approval Workflow

```http
GET /api/approvals/pending
POST /api/approvals/claim/{claimId}
GET /api/approvals/claim/{claimId}
```

### Customer Service Integration

```http
POST /api/customer-service/sync-customer
POST /api/customer-service/sync-vehicle
GET /api/customer-service/customer/{customerCode}
```

### Notifications

```http
GET /api/notifications
POST /api/notifications/send
PATCH /api/notifications/{id}/read
```

## 🔧 Configuration

### Environment Variables

```env
# Database
DB_HOST=db
DB_PORT=3306
DB_DATABASE=warranty_db
DB_USERNAME=warranty_user
DB_PASSWORD=warranty_pass

# JWT
JWT_SECRET=your-super-secret-jwt-key
JWT_EXPIRATION=3600

# Mail
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@oem-ev.com

# Customer Service Integration
CUSTOMER_SERVICE_API_URL=http://localhost:8082/api
CUSTOMER_SERVICE_API_KEY=your-api-key
```

## 📊 Database Schema

### Core Tables

- `users` - User accounts and authentication
- `customers` - Customer information
- `vehicles` - Vehicle records
- `warranty_policies` - Warranty policy definitions
- `vehicle_warranties` - Active warranties
- `warranty_claims` - Warranty claims
- `claim_approvals` - Approval workflow
- `notifications` - Notification logs

## 🔐 Authentication & Authorization

### Roles & Permissions

- **Admin**: Full system access
- **Manager**: Claim management, approvals, reports
- **Technician**: Claim processing, level 1 approvals
- **Customer Service**: Customer/vehicle management
- **Customer**: View own claims and data

### JWT Token Structure

```json
{
  "user_id": 1,
  "username": "admin",
  "email": "admin@oem-ev.com",
  "role": "admin",
  "exp": 1699200000
}
```

## 🔄 Approval Workflow

### Approval Levels

1. **Level 1**: Technician (always required)
2. **Level 2**: Supervisor (medium+ priority or cost > $1,000)
3. **Level 3**: Manager (high+ priority or cost > $5,000)
4. **Level 4**: Director (critical priority or cost > $20,000)

### Status Flow

```
draft → submitted → under_review → investigating → approved/rejected → in_progress → completed
```

## 📧 Notification System

### Types

- **Email**: HTML formatted emails via PHPMailer
- **SMS**: Integration ready (Twilio compatible)
- **System**: In-app notifications
- **Push**: Mobile push notifications (future)

### Triggers

- Claim created/updated
- Status changes
- Approval requests
- Warranty expiration alerts

## 🔗 Customer Service Integration

### Sync Operations

- **Customer data**: Bidirectional sync
- **Vehicle data**: Bidirectional sync
- **Claim updates**: Push to Customer Service
- **Bulk operations**: Mass data synchronization

### API Endpoints

```http
# Sync from Customer Service
POST /api/customer-service/sync-customer
{
  "customer_code": "CUST20250001",
  "action": "sync_from_cs"
}

# Sync to Customer Service
POST /api/customer-service/sync-customer
{
  "customer_code": "CUST20250001",
  "action": "sync_to_cs"
}
```

## 🛠️ Development

### Local Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Code linting
composer lint

# Code fixing
composer fix
```

### Adding New Features

1. Create model in `app/Models/`
2. Create controller in `app/Controllers/`
3. Add routes in `routes/api.php`
4. Create database migration
5. Add tests

## 📝 Logs

### Application Logs

- **System logs**: `storage/logs/app.log`
- **Error logs**: `storage/logs/error.log`
- **Access logs**: Nginx access logs

### Database Logs

- **System activity**: `system_logs` table
- **Integration logs**: `integration_logs` table
- **Notification logs**: `notifications` table

## 🚀 Deployment

### Production Setup

1. Update environment variables
2. Enable SSL/TLS
3. Configure reverse proxy
4. Set up monitoring
5. Configure backups

### Security Considerations

- Change JWT secret key
- Use strong database passwords
- Enable HTTPS
- Set up rate limiting
- Configure CORS properly

## 📞 Support

Để được hỗ trợ hoặc báo cáo lỗi, vui lòng tạo issue trong repository.

## 📄 License

MIT License - see LICENSE file for details.