# 🏗️ EVM Warranty Frontend - Cấu Trúc Tối Ưu

## 📁 **Cấu Trúc Thư Mục Hoàn Chỉnh**

```
frontend/src/
├── app/                          # Next.js App Router
│   ├── admin/                    # 👑 Admin Routes
│   │   ├── page.tsx             # Dashboard tổng quan
│   │   ├── accounts/            # 👤 Quản lý tài khoản & phân quyền  
│   │   │   └── page.tsx
│   │   └── service-centers/     # 🏢 Quản lý Service Centers
│   │       └── page.tsx
│   ├── evm-staff/               # 🏭 EVM Staff Routes
│   │   └── page.tsx             # Dashboard chính
│   ├── sc-staff/                # 🏢 SC Staff Routes
│   │   └── page.tsx             # Vehicle Registration & Management
│   ├── technician/              # 🔧 Technician Routes
│   │   └── page.tsx             # Claim Queue & Assignments
│   ├── customer/                # 👨‍👩‍👧‍👦 Customer Routes
│   │   └── page.tsx             # My Vehicles & Claims
│   ├── login/                   # 🔐 Authentication
│   │   └── page.tsx             # Login with demo accounts
│   ├── test/                    # 🧪 System Testing
│   │   └── page.tsx             # System health testing
│   ├── layout.tsx               # Root layout
│   ├── page.tsx                 # Home page (Role Router)
│   └── globals.css              # Global styles
├── components/                   # React Components
│   ├── layout/                  # 🎨 Layout Components
│   │   └── BaseLayout.tsx       # Shared layout với role-based sidebar
│   ├── ui/                      # 🧩 UI Components Library
│   │   └── index.tsx            # Card, EmptyState, Button, etc.
│   ├── admin/                   # 👑 Admin Components
│   │   ├── AdminDashboard.tsx
│   │   ├── AccountManagement.tsx
│   │   └── ServiceCenterManagement.tsx
│   ├── evm-staff/               # 🏭 EVM Staff Components
│   │   └── EVMStaffDashboard.tsx
│   ├── sc-staff/                # 🏢 SC Staff Components
│   │   └── SCStaffDashboard.tsx
│   ├── technician/              # 🔧 Technician Components
│   │   └── TechnicianDashboard.tsx
│   ├── customer/                # 👤 Customer Components
│   │   └── CustomerDashboard.tsx
│   ├── RoleBasedRouter.tsx      # Role-based routing logic
│   └── SystemTestDashboard.tsx  # System testing component
├── contexts/                     # React Contexts
│   └── AuthContext.tsx          # Authentication context với mock login
├── lib/                         # Utilities & Services
## 🎯 **Chi Tiết UI Specifications theo Vai Trò**

### 👑 **1. Admin UI** (`/admin`)
```
🧭 Dashboard tổng quan
├── Số lượng xe, SC, nhân sự, claims, phụ tùng
├── Thống kê claims theo trạng thái 
└── Biểu đồ claims từng SC/dòng xe

� Account & Role Management (`/admin/accounts`)
├── Tạo/xóa/sửa tài khoản (EVM Staff, SC Staff, Technician)
├── Gán vai trò, quyền truy cập (RBAC)
└── Reset mật khẩu

� Service Center Management (`/admin/service-centers`)
├── Danh sách SC (tên, địa chỉ, người phụ trách)
├── Thêm/ngừng hoạt động/cập nhật SC
└── Theo dõi hiệu suất từng SC
```

### 🏭 **2. EVM Staff UI** (`/evm-staff`)
```
🧾 Claim Management
├── Danh sách claims chờ duyệt/đang xử lý/hoàn tất
├── Bộ lọc theo SC, dòng xe, trạng thái, ngày
├── Xem chi tiết claim (ảnh/video, lỗi, phụ tùng)
└── Thao tác: Duyệt/Từ chối/Yêu cầu bổ sung

⚙️ Policy & Warranty Rules (future)
🔩 Parts & Inventory (future)
📊 Dashboard & Reports (future)
🔔 Recall & Maintenance (future)
```

### 🏢 **3. SC Staff UI** (`/sc-staff`)
```
🚘 Vehicle Registration
├── Đăng ký xe mới (VIN, khách hàng, ngày mua)
├── Tự động gắn thông tin xe & khách hàng
└── Tìm kiếm xe theo VIN/khách/biển số

🧾 Claim Management (future)
🔩 Parts Inventory (future)  
👥 Technician Assignment (future)
```

### 🔧 **4. Technician UI** (`/technician`)
```
🔔 Claim Queue
├── Danh sách claims được gán
└── Lọc theo trạng thái/ngày

🧩 Diagnosis & Repair (future)
🪛 Work Log (future)
```

### 👨‍👩‍👧‍👦 **5. Customer UI** (`/customer`)
```
🚘 My Vehicles
├── Danh sách xe sở hữu
├── Chi tiết model, ngày mua, bảo hành còn lại
└── Thông tin SC phụ trách

🧾 My Warranty Claims (future)
🔔 Notifications (future)
📅 Booking (future)
```

## 🔐 **Authentication System**

### Demo Accounts:
```
👑 Admin:       admin@evm.com          / admin123
🏭 EVM Staff:   staff@evm.com          / staff123  
🏢 SC Staff:    sc-staff@evm.com       / sc123
🔧 Technician:  tech@evm.com           / tech123
👤 Customer:    nguyenvana@example.com / password123
```

### Role-Based Routing:
- Login → Auto redirect dựa theo user role
- Mỗi role có dedicated dashboard & navigation
- Consistent sidebar design với role-specific colors
- Logout từ bất kỳ dashboard nào

## 🎨 **Design System**

### Color Coding theo Role:
- **Admin**: Blue (`bg-blue-600`)
- **EVM Staff**: Green (`bg-green-600`)
- **SC Staff**: Purple (`bg-purple-600`) 
- **Technician**: Orange (`bg-orange-600`)
- **Customer**: Indigo (`bg-indigo-600`)

### Components:
- **BaseLayout**: Shared layout với sidebar navigation
- **Card**: Content containers với optional titles
- **EmptyState**: Placeholder cho future features
- **PageContainer**: Consistent page padding
- **LoadingSpinner**: Loading states

## � **Navigation Flow**

```
HomePage (/) 
    ↓
RoleBasedRouter
    ↓
Check Authentication
    ↓
Redirect to Role-specific Dashboard:
├── /admin           → Admin Dashboard
├── /evm-staff      → EVM Staff Dashboard  
├── /sc-staff       → SC Staff Dashboard
├── /technician     → Technician Dashboard
└── /customer       → Customer Dashboard
```

## 🚀 **API Integration**

### Current Integrations:
- Authentication (mock login)
- Service health checks
- Vehicle data
- Customer data  
- Warranty claims
- Notifications

### API Client Features:
- Automatic token management
- Error handling & interceptors
- Service health monitoring
- Role-based permissions (future)

## 📋 **Development Status**

### ✅ Completed:
- Role-based authentication system
- Optimized folder structure
- UI components cho tất cả roles
- Responsive design foundation
- Navigation system hoàn chỉnh
- Mock data integration

### 🔄 In Progress:
- API service integration
- Real-time features
- Form implementations

### 📝 Planned:
- Advanced filtering & search
- File upload functionality
- Charts & analytics
- Mobile optimization
- Testing suite

## 🎯 **Usage Instructions**

1. **Start the system:**
   ```bash
   npm run dev
   ```

2. **Access the application:**
   - URL: http://localhost:3000
   - Auto-redirects to login page

3. **Test different roles:**
   - Sử dụng demo accounts từ login page
   - Mỗi account hiển thị UI khác nhau
   - Navigation tự động adapt theo user role

4. **Development:**
   - Thêm pages mới trong respective `/app/[role]/` folders
   - Tạo components trong `/components/[role]/` folders
   - Update navigation trong BaseLayout component
   - Extend types trong `/types/index.ts`

## 📖 **Next Development Steps**

1. **Feature Implementation**: Fill EmptyState placeholders với real functionality
2. **API Integration**: Connect UI với backend services  
3. **Testing**: Add unit tests và E2E testing
4. **Performance**: Optimize loading và bundle size
5. **Mobile**: Enhance mobile responsiveness

---

## 🎉 **Kết Luận**

Frontend structure hiện tại đã hoàn thiện:
- ✅ **Role-Based Organization**: Mỗi vai trò có structure riêng biệt
- ✅ **Scalable Architecture**: Dễ dàng mở rộng và thêm features
- ✅ **Type-Safe Development**: TypeScript cho code quality
- ✅ **Complete Authentication**: Mock login system với 5 user roles
- ✅ **Responsive Design**: Modern UI với Tailwind CSS
- ✅ **API Ready**: Sẵn sàng connect với backend microservices

Hệ thống frontend đã sẵn sàng cho việc phát triển features chi tiết và integration với backend!
- ✅ **Consistent Design**: Design system thống nhất
- ✅ **Ready for Development**: Foundation sẵn sàng cho phase tiếp theo

**Next Steps**: Chọn một role cụ thể để phát triển chi tiết các components và business logic!