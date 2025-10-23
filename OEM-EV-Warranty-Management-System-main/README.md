# 🚗 EVM Warranty Management System

Hệ thống quản lý bảo hành xe điện EVM với kiến trúc microservices, frontend Next.js và role-based authentication.

## 🏗️ **Kiến Trúc Hệ Thống**

```
Frontend (Next.js + TypeScript)
├── 👑 Admin Dashboard
├── 🏭 EVM Staff Portal  
├── 🏢 Service Center Portal
├── 🔧 Technician Tools
└── 👤 Customer Portal

Backend Microservices (PHP + MySQL)
├── 👥 Customer Service (Port 8001)
├── 🔧 Warranty Service (Port 8002)
├── 🚗 Vehicle Service (Port 8003)
├── 👑 Admin Service (Port 8004)
└── 📱 Notification Service (Port 8005)

Infrastructure
├── 🐳 Docker & Docker Compose
├── 🚪 Kong API Gateway (Port 8000)
├── 💾 MySQL Databases (Per Service)
├── 📧 Mailpit (Email Testing)
└── 🔄 Redis Cache
```

## 🚀 **Quick Start Guide**

### **Prerequisites**
- ✅ Docker Desktop 4.0+
- ✅ Docker Compose 2.0+
- ✅ Node.js 18+ (cho frontend development)
- ✅ Git

### **1️⃣ Clone Repository**
```bash
git clone <repository-url>
cd evm-warranty-system
```

### **2️⃣ Backend Setup (Docker)**
```bash
# Build tất cả services
docker-compose build

# Start tất cả services
docker-compose up -d

# Kiểm tra services đang chạy
docker-compose ps

# Xem logs nếu có lỗi
docker-compose logs -f
```

### **3️⃣ Frontend Setup**
```bash
# Chuyển đến thư mục frontend
cd frontend

# Cài đặt dependencies
npm install

# Start development server
npm run dev
```

### **4️⃣ Truy Cập Hệ Thống**
- 🌐 **Frontend**: http://localhost:3000
- 🚪 **API Gateway**: http://localhost:8000
- 📧 **Email UI**: http://localhost:8025

## 🔐 **Demo Accounts**

| Role | Email | Password | Dashboard |
|------|-------|----------|-----------|
| 👑 **Admin** | admin@evm.com | admin123 | System Overview & Management |
| 🏭 **EVM Staff** | staff@evm.com | staff123 | Claims Management & Approval |
| 🏢 **SC Staff** | sc-staff@evm.com | sc123 | Vehicle Registration & Claims |
| 🔧 **Technician** | tech@evm.com | tech123 | Repair Queue & Work Log |
| 👤 **Customer** | nguyenvana@example.com | password123 | My Vehicles & Claims |

## 📋 **Services Chi Tiết**

### **👥 Customer Service (Port 8001)**
**Chức năng:**
- User authentication & authorization
- Customer profile management  
- Account management
- JWT token handling

**Key Endpoints:**
```bash
GET  /api/health                    # Health check
POST /api/auth/login               # User login
POST /api/auth/register            # User registration
GET  /api/customers                # List customers
POST /api/customers                # Create customer
GET  /api/customers/{id}           # Get customer details
PUT  /api/customers/{id}           # Update customer
```

### **🚗 Vehicle Service (Port 8003)**
**Chức năng:**
- Vehicle registration & management
- VIN tracking & validation
- Vehicle-customer relationships
- Warranty information

**Key Endpoints:**
```bash
GET  /api/health                   # Health check
GET  /api/vehicles                 # List vehicles
POST /api/vehicles                 # Register new vehicle
GET  /api/vehicles/{vin}           # Get vehicle by VIN
GET  /api/vehicles/{vin}/warranty  # Get warranty info
PUT  /api/vehicles/{vin}           # Update vehicle
```

### **🔧 Warranty Service (Port 8002)**
**Chức năng:**
- Warranty claims management
- Claims approval workflow
- Repair tracking
- Parts management

**Key Endpoints:**
```bash
GET  /api/health                        # Health check
GET  /api/claims                        # List all claims
POST /api/claims                        # Create new claim
GET  /api/claims/{id}                   # Get claim details
PUT  /api/claims/{id}/approve           # Approve claim
PUT  /api/claims/{id}/reject            # Reject claim
GET  /api/claims/customer/{customer_id} # Customer claims
```

### **👑 Admin Service (Port 8004)**
**Chức năng:**
- System statistics & monitoring
- User & role management
- Service center management
- System configuration

**Key Endpoints:**
```bash
GET  /api/health              # Health check
GET  /api/admin/stats         # System statistics
GET  /api/admin/users         # User management
GET  /api/admin/service-centers # Service centers
POST /api/admin/users         # Create user
PUT  /api/admin/users/{id}    # Update user
```

### **📱 Notification Service (Port 8005)**
**Chức năng:**
- Email notifications
- System alerts
- User notifications
- Notification history

**Key Endpoints:**
```bash
GET  /api/health              # Health check
GET  /api/notifications       # List notifications
POST /api/notifications       # Send notification
GET  /api/notifications/user/{id} # User notifications
PUT  /api/notifications/{id}/read # Mark as read
```

## 🔧 **Development Guide**

### **Backend Development**
```bash
# Kiểm tra health của services
curl http://localhost:8001/api/health
curl http://localhost:8002/api/health
curl http://localhost:8003/api/health
curl http://localhost:8004/api/health
curl http://localhost:8005/api/health

# Xem logs của service cụ thể
docker-compose logs customer-service
docker-compose logs warranty-service -f

# Restart service cụ thể
docker-compose restart warranty-service

# Truy cập database
docker exec -it evm-warranty-system-customer-db-1 mysql -u evm_user -p
```

### **Frontend Development**
```bash
cd frontend

# Cài đặt dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build

# Lint code
npm run lint

# Type checking
npx tsc --noEmit
```

### **Thêm Service Mới**
1. Tạo thư mục service trong `services/`
2. Thêm Dockerfile và PHP code
3. Cập nhật `docker-compose.yml`
4. Thêm routes vào API client (`frontend/src/lib/api.ts`)
5. Cập nhật frontend components

## 🧪 **Testing & Debugging**

### **API Testing**
```bash
# Test tất cả service health endpoints (Windows)
test-all-apis.bat

# Test từng endpoint cụ thể
curl http://localhost:8001/api/customers
curl http://localhost:8003/api/vehicles
curl http://localhost:8002/api/claims

# Test với authentication
curl -H "Authorization: Bearer <jwt_token>" http://localhost:8001/api/customers
```

### **Frontend Testing**
```bash
cd frontend

# Chạy frontend và test login
npm run dev
# Truy cập http://localhost:3000
# Login với demo accounts

# Test role-based routing
# Login với các tài khoản khác nhau và verify dashboard
```

### **Database Testing**
```bash
# Truy cập database containers
docker exec -it evm-warranty-system-customer-db-1 mysql -u evm_user -p

# Xem data trong tables
USE evm_customer_db;
SHOW TABLES;
SELECT * FROM customers;
```

## 🐳 **Docker Commands**

### **Basic Operations**
```bash
# Start toàn bộ system
docker-compose up -d

# Stop toàn bộ system  
docker-compose down

# Rebuild tất cả services
docker-compose build

# Rebuild service cụ thể
docker-compose build customer-service

# Xem logs real-time
docker-compose logs -f

# Xem status containers
docker-compose ps

# Xóa volumes (reset databases)
docker-compose down -v
```

### **Development Workflow**
```bash
# Start chỉ databases
docker-compose up -d customer-db warranty-db vehicle-db admin-db notification-db

# Start service cụ thể với logs
docker-compose up customer-service

# Scale service (multiple instances)
docker-compose up --scale warranty-service=3

# Execute commands trong container
docker-compose exec customer-service bash
```

## 🗂️ **Project Structure**

```
evm-warranty-system/
├── services/                    # 🏗️ Backend microservices
│   ├── customer-service/        # 👥 Customer management
│   │   ├── src/app/Http/Controllers/
│   │   ├── src/routes/api.php
│   │   └── Dockerfile
│   ├── warranty-service/        # 🔧 Warranty claims
│   ├── vehicle-service/         # 🚗 Vehicle management  
│   ├── admin-service/           # 👑 Admin functions
│   └── notification-service/    # 📱 Notifications
├── frontend/                    # 🌐 Next.js frontend
│   ├── src/app/                 # App router pages
│   │   ├── admin/              # 👑 Admin pages
│   │   ├── evm-staff/          # 🏭 EVM staff pages
│   │   ├── sc-staff/           # 🏢 SC staff pages
│   │   ├── technician/         # 🔧 Technician pages
│   │   └── customer/           # 👤 Customer pages
│   ├── src/components/         # React components
│   │   ├── admin/              # Admin components
│   │   ├── layout/             # Layout components
│   │   └── ui/                 # UI components
│   ├── src/contexts/           # React contexts
│   └── src/lib/               # API client & utilities
├── api-gateway/               # 🚪 Kong configuration
├── shared/                    # 📁 Shared utilities
├── logs/                      # 📝 Service logs
└── docker-compose.yml        # 🐳 Docker configuration
```

## 🔍 **Troubleshooting**

### **Port Conflicts**
```bash
# Kiểm tra port đang được sử dụng
netstat -ano | findstr :8001
netstat -ano | findstr :3000

# Kill process (Windows)
taskkill /PID <process_id> /F

# Đổi port trong docker-compose.yml nếu cần
```

### **Database Issues**
```bash
# Reset tất cả databases
docker-compose down -v
docker-compose up -d

# Kiểm tra database connection
docker-compose exec customer-db mysql -u evm_user -p

# Import lại sample data nếu cần
docker-compose exec customer-service php artisan migrate:fresh --seed
```

### **Frontend Build Errors**
```bash
cd frontend

# Clear Next.js cache
rm -rf .next
rm -rf node_modules
npm install
npm run dev

# Kiểm tra TypeScript errors
npx tsc --noEmit
```

### **Service Not Responding**
```bash
# Kiểm tra logs của service
docker-compose logs service-name

# Restart service
docker-compose restart service-name

# Rebuild service nếu có code changes
docker-compose build service-name
docker-compose up -d service-name
```

## 📚 **API Documentation**

### **Authentication**
Tất cả protected endpoints cần JWT token trong header:
```bash
Authorization: Bearer <jwt_token>
```

### **Response Format**
**Success Response:**
```json
{
  "success": true,
  "data": [...],
  "message": "Success message",
  "timestamp": "2025-10-08T12:00:00Z"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE",
  "timestamp": "2025-10-08T12:00:00Z"
}
```

### **Status Codes**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Internal Server Error

## 🚀 **Production Deployment**

### **Environment Setup**
```bash
# Tạo .env files cho mỗi service
cp services/customer-service/.env.example services/customer-service/.env
cp services/warranty-service/.env.example services/warranty-service/.env
# ... repeat for all services

# Cập nhật production values
nano services/customer-service/.env
```

### **Production Build**
```bash
# Build frontend cho production
cd frontend
npm run build

# Start production containers
docker-compose -f docker-compose.prod.yml up -d
```

### **Security Checklist**
- ✅ Change default passwords
- ✅ Use strong JWT secrets
- ✅ Enable HTTPS
- ✅ Configure firewall rules
- ✅ Set up monitoring
- ✅ Enable backup system

## 🤝 **Contributing**

1. Fork repository
2. Tạo feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Tạo Pull Request

## 📞 **Support**

Gặp vấn đề? Hãy kiểm tra:
1. 🔍 **Troubleshooting section** ở trên
2. 📝 **Service logs**: `docker-compose logs service-name`
3. 🐳 **Container status**: `docker-compose ps`
4. 🌐 **Network**: Test API endpoints manually

---

**🚀 Ready to start? Chạy `docker-compose up -d` và truy cập http://localhost:3000!**