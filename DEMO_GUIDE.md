# 🎯 Demo Guide - Customer Portal Features

## Truy cập hệ thống

**URL:** http://localhost:3001/customer

**Test Account:**
- Username: `customer@test.com`
- Password: `password123`

---

## 📱 Tính năng đã hoàn thành

### 1. Dashboard (Trang chủ)
**URL:** `/customer`

**Xem được:**
- ✅ Tổng số xe đang sở hữu
- ✅ Số lượng warranty claims đang hoạt động
- ✅ Lịch hẹn sắp tới
- ✅ Danh sách tất cả các xe với trạng thái bảo hành
- ✅ Nút quick action: Tạo claim, xem claims, đặt lịch, thông báo

**Demo:**
1. Mở trình duyệt: http://localhost:3001/customer
2. Xem 3 thẻ thống kê ở đầu trang
3. Click vào các nút quick action
4. Cuộn xuống xem danh sách xe
5. Mỗi xe hiển thị:
   - Hãng, model, năm
   - Trạng thái bảo hành (Active/Expiring/Expired)
   - VIN number
   - Dung lượng pin
   - Ngày hết hạn bảo hành
   - 2 nút: "File Claim" và "Book Service"

---

### 2. My Claims (Danh sách yêu cầu bảo hành)
**URL:** `/customer/claims`

**Tính năng:**
- ✅ Hiển thị tất cả warranty claims
- ✅ Lọc theo trạng thái (All, Pending, Approved, In Progress, Completed, Rejected)
- ✅ Status badge với màu sắc (Pending=vàng, Approved=xanh, Rejected=đỏ)
- ✅ Click vào claim để xem chi tiết
- ✅ Nút "New Claim" để tạo mới

**Demo:**
1. Vào: http://localhost:3001/customer/claims
2. Thử chọn các filter khác nhau
3. Click vào một claim để xem chi tiết
4. Click "New Claim" để tạo yêu cầu mới

---

### 3. New Claim (Tạo yêu cầu bảo hành mới)
**URL:** `/customer/claims/new`

**Form gồm:**
- ✅ Dropdown chọn xe (từ danh sách xe của user)
- ✅ Thông tin xe tự động hiển thị khi chọn
- ✅ Dropdown chọn bộ phận hỏng (Battery, Motor, Inverter, etc.)
- ✅ Textarea mô tả chi tiết lỗi
- ✅ Date picker chọn ngày xảy ra lỗi
- ✅ Input nhập số km hiện tại
- ✅ Upload hình ảnh (tối đa 5 ảnh, mỗi ảnh 5MB)

**Validation:**
- Required fields có dấu sao đỏ
- Không cho phép chọn ngày tương lai
- Validate file size và file type
- Hiển thị preview các file đã chọn

**Demo:**
1. Vào: http://localhost:3001/customer/claims/new
2. Chọn một xe từ dropdown
3. Xem thông tin xe hiển thị tự động
4. Điền form:
   - Component: "Battery"
   - Description: "Battery not charging properly, shows error on dashboard"
   - Failure Date: Chọn ngày hôm qua
   - Mileage: "15000"
5. Thử upload vài ảnh (hoặc bỏ qua)
6. Click "Submit Claim"
7. Nếu thành công, redirect về /customer/claims

---

### 4. Claim Details (Chi tiết yêu cầu bảo hành)
**URL:** `/customer/claims/[id]`

**Hiển thị:**
- ✅ Claim number và status badge
- ✅ Thông tin chi tiết (Component, Failure Date, Mileage, Submitted Date)
- ✅ Mô tả đầy đủ vấn đề
- ✅ Gallery ảnh đã upload
- ✅ Status notes từ admin (nếu có)
- ✅ Rejection reason (nếu bị reject)

**Actions theo status:**
- **Pending**: Hiển thị message "Under review"
- **Approved**: Nút "Book Appointment"
- **Rejected**: Hiển thị lý do từ chối

**Demo:**
1. Từ claims list, click vào một claim
2. Xem tất cả thông tin chi tiết
3. Nếu status là Approved, click "Book a Service Appointment"

---

### 5. Booking (Đặt lịch dịch vụ)
**URL:** `/customer/booking`

**Form gồm:**
- ✅ Dropdown chọn xe
- ✅ Thông tin xe tự động hiển thị
- ✅ Dropdown chọn loại dịch vụ:
  - Warranty Claim Service
  - Regular Maintenance
  - Vehicle Inspection
  - Battery Health Check
  - Software Update
  - Other Service
- ✅ Date picker (chỉ cho phép chọn từ ngày mai trở đi)
- ✅ Time slot dropdown (8:00-17:00)
- ✅ Textarea ghi chú thêm
- ✅ Thông tin service center

**Demo:**
1. Vào: http://localhost:3001/customer/booking
2. Chọn xe
3. Chọn Service Type: "Warranty Claim Service"
4. Chọn ngày (minimum là ngày mai)
5. Chọn giờ: "09:00"
6. Ghi chú: "Please check battery voltage"
7. Click "Confirm Appointment"

**Auto-fill từ URL:**
- Từ claim details: `?claim=123` → auto-chọn "Warranty Claim Service"
- Từ vehicle card: `?vin=XXX` → auto-chọn xe

---

### 6. Notifications (Thông báo)
**URL:** `/customer/notifications`

**Tính năng:**
- ✅ Hiển thị tất cả thông báo
- ✅ Filter: All / Unread
- ✅ Unread count badge
- ✅ Icon theo loại (info=blue, success=green, warning=orange, error=red)
- ✅ Mark as read
- ✅ Delete notification
- ✅ Mark all as read

**Demo:**
1. Vào: http://localhost:3001/customer/notifications
2. Xem danh sách thông báo (hiện tại là mock data)
3. Click "Mark as read" trên một thông báo
4. Click filter "Unread"
5. Click "Mark all as read"
6. Click "Delete" trên một thông báo

---

## 🎨 UI/UX Features

### Responsive Design
- ✅ **Desktop**: Full layout với sidebar
- ✅ **Tablet**: Responsive grid, 2 columns
- ✅ **Mobile**: Single column, hamburger menu

**Test:**
1. Mở DevTools (F12)
2. Click icon mobile/tablet
3. Thử các breakpoints: 320px, 768px, 1024px, 1920px

---

### Loading States
Mỗi trang có loading spinner khi fetch data:
- Spinning circle màu xanh
- Center screen
- Hiển thị trong 2-3 giây đầu

---

### Empty States
Khi chưa có data:
- Icon lớn màu xám
- Message rõ ràng
- Action button (nếu applicable)

**Ví dụ:**
- No vehicles: "No vehicles registered"
- No claims: "No claims found" + "Create Your First Claim" button

---

### Form Validation
- Required fields: Red asterisk (*)
- Date validation: Không cho phép past dates (booking)
- File validation: Size (5MB), Type (images only)
- Real-time error display

---

### Status Colors
```
Pending   → Yellow (bg-yellow-100, text-yellow-800)
Approved  → Green  (bg-green-100, text-green-800)
Rejected  → Red    (bg-red-100, text-red-800)
In Progress → Blue  (bg-blue-100, text-blue-800)
Completed → Gray   (bg-gray-100, text-gray-800)
```

---

## 🔧 Additional Components

### 1. FileUpload Component
**Tính năng:**
- Drag & drop
- Multiple file selection
- Preview với file size
- Remove before upload
- Progress tracking
- Error messages

**Dùng trong:** New Claim page

---

### 2. VehicleSearch Component
**Tính năng:**
- Real-time search (debounce 300ms)
- Autocomplete dropdown
- Search by VIN or license plate
- Highlight matching text
- Vehicle details in results

**Dùng trong:** SC Staff pages (Person 1 đã tạo sẵn)

---

### 3. BarcodeScanner Component
**Tính năng:**
- Barcode simulation
- Manual entry
- Enter key submit
- Visual feedback

**Dùng trong:** SC Staff pages

---

### 4. WarrantyCertificate Component
**Tính năng:**
- Professional design
- Print functionality
- Customer & vehicle info
- Warranty coverage breakdown
- QR code placeholder

**Dùng trong:** SC Staff vehicle registration

---

## 📋 Test Scenarios

### Scenario 1: Khách hàng tạo warranty claim mới
1. Login vào customer portal
2. Click "New Claim" từ dashboard
3. Chọn xe: "VF8 2024"
4. Chọn component: "Battery"
5. Nhập description: "Battery draining faster than normal"
6. Chọn failure date: 3 ngày trước
7. Nhập mileage: "12500"
8. Upload 2 ảnh
9. Submit
10. ✅ Redirect về claims list với claim mới

---

### Scenario 2: Xem claim details và book appointment
1. Vào "My Claims"
2. Click vào một claim có status "Approved"
3. Xem thông tin chi tiết
4. Click "Book a Service Appointment"
5. Form tự động fill: VIN và Service Type
6. Chọn date: Ngày mai
7. Chọn time: 10:00
8. Nhập notes: "Available all day"
9. Submit
10. ✅ Confirmation và redirect về dashboard

---

### Scenario 3: Check notifications
1. Click notification icon (có badge số 3)
2. Xem 3 unread notifications
3. Mark một notification as read
4. Check unread count giảm xuống 2
5. Mark all as read
6. Check unread count = 0

---

## 🚀 Production Readiness

### ✅ Completed
- [x] All 6 pages functional
- [x] API integration ready
- [x] TypeScript type-safe
- [x] No compile errors
- [x] Responsive design
- [x] Loading states
- [x] Error handling
- [x] Form validation
- [x] Empty states
- [x] Status colors

### 🔄 Pending (Backend)
- [ ] Real API endpoints
- [ ] Authentication/Authorization
- [ ] File upload service
- [ ] Database integration
- [ ] Email notifications

---

## 💻 Developer Notes

### Tech Stack
- **Framework**: Next.js 15.5.4 (App Router)
- **React**: 19.1.0
- **TypeScript**: Strict mode
- **Styling**: Tailwind CSS
- **Icons**: Heroicons
- **HTTP**: Axios

### File Structure
```
frontend/src/
├── app/
│   └── customer/
│       ├── page.tsx                    # Dashboard
│       ├── layout.tsx                  # Customer layout
│       ├── claims/
│       │   ├── page.tsx               # Claims list
│       │   ├── new/page.tsx           # New claim
│       │   └── [id]/page.tsx          # Claim details
│       ├── booking/page.tsx           # Booking
│       └── notifications/page.tsx      # Notifications
├── components/
│   ├── shared/
│   │   └── FileUpload.tsx
│   └── sc-staff/
│       ├── VehicleSearch.tsx
│       ├── BarcodeScanner.tsx
│       └── WarrantyCertificate.tsx
├── lib/
│   └── api.ts                         # API client
└── types/
    └── index.ts                       # TypeScript types
```

---

## 📞 Support

**Issues?** Check:
1. Dev server running: `npm run dev`
2. Port 3001 available
3. Browser console for errors
4. Network tab for API calls

**Contact:** Person 1 (Frontend Lead)

---

## 🎉 Summary

**Customer Portal hoàn toàn sẵn sàng với:**
- ✅ 6 pages đầy đủ chức năng
- ✅ UI/UX chuyên nghiệp
- ✅ Responsive trên mọi thiết bị
- ✅ Type-safe với TypeScript
- ✅ Ready for backend integration

**Demo ngay:** http://localhost:3001/customer
