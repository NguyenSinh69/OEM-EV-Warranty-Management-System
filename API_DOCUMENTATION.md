# Tài liệu hướng dẫn sử dụng API: Hệ thống Thông báo EVM

## 1. Tổng quan

API Hệ thống Thông báo EVM (Electric Vehicle Management Notification System) được sử dụng để quản lý toàn bộ hệ sinh thái thông báo cho hệ thống quản lý xe điện VinFast. Hệ thống bao gồm 9 endpoint chính phục vụ cho việc gửi thông báo, quản lý lịch hẹn, theo dõi tồn kho phụ tùng và triển khai chiến dịch marketing. Dữ liệu trả về ở định dạng JSON chuẩn REST API.

Hệ thống được xây dựng trên kiến trúc microservices với PHP 8.2, MySQL, Redis Queue và Next.js frontend, đảm bảo khả năng mở rộng và hiệu suất cao.

## 2. Endpoints

### 2.1. Quản lý Thông báo
- **Gửi thông báo:** `POST /api/notifications/send`
- **Lấy thông báo khách hàng:** `GET /api/notifications/{customer_id}`
- **Tạo chiến dịch thông báo:** `POST /api/notifications/campaign`

### 2.2. Quản lý Lịch hẹn
- **Tạo lịch hẹn mới:** `POST /api/appointments`
- **Xem lịch hẹn theo tháng:** `GET /api/appointments/calendar`

### 2.3. Quản lý Tồn kho
- **Xem danh sách tồn kho:** `GET /api/inventory`
- **Cập nhật tồn kho:** `POST /api/inventory/update`
- **Phân bổ phụ tùng:** `POST /api/inventory/allocate`
- **Xem cảnh báo tồn kho:** `GET /api/inventory/alerts`

## 3. Cấu trúc Response

Tất cả API responses đều tuân theo cấu trúc chuẩn:

```json
{
  "success": true|false,
  "message": "Thông báo kết quả",
  "data": { ... },
  "pagination": { ... } // (nếu có)
}
```

## 4. Hướng dẫn test với Postman

1. Mở Postman, tạo Collection mới tên "EVM Notification API"
2. Chọn phương thức tương ứng (GET/POST)
3. Nhập URL: `http://localhost:8005/api/...`
4. Với POST requests: chọn Body → raw → JSON và nhập dữ liệu
5. Nhấn Send để gửi request
6. Xem kết quả trả về ở tab Body (dạng JSON)

## 5. Chi tiết các API

### 5.1. Gửi Thông báo

**Endpoint:** `POST /api/notifications/send`

**Mô tả:** Gửi thông báo đến khách hàng qua email, SMS hoặc in-app notification. Hệ thống sử dụng queue để xử lý gửi thông báo bất đồng bộ.

**Request Body:**
```json
{
  "customer_id": 1,
  "type": "appointment",
  "priority": "medium",
  "title": "Lịch hẹn bảo dưỡng",
  "message": "Bạn có lịch hẹn bảo dưỡng xe VF8 vào ngày mai lúc 9:00",
  "channels": ["email", "in_app"],
  "data": {
    "appointment_id": 123,
    "appointment_date": "2025-11-06",
    "service_center": "VinFast Hà Nội"
  }
}
```

**Tham số:**
- `customer_id` (required): ID khách hàng
- `type` (required): Loại thông báo - "info", "warning", "success", "error", "warranty_claim", "appointment", "maintenance", "campaign"
- `priority` (required): Mức độ ưu tiên - "low", "medium", "high", "urgent"
- `title` (required): Tiêu đề thông báo
- `message` (required): Nội dung thông báo
- `channels` (required): Kênh gửi - ["email", "sms", "push", "in_app"]
- `data` (optional): Dữ liệu bổ sung

**Response mẫu:**
```json
{
  "success": true,
  "message": "Notification sent successfully",
  "data": {
    "id": 1,
    "customer_id": 1,
    "title": "Lịch hẹn bảo dưỡng",
    "message": "Bạn có lịch hẹn bảo dưỡng xe VF8 vào ngày mai lúc 9:00",
    "type": "appointment",
    "priority": "medium",
    "status": "sent",
    "channels": ["email", "in_app"],
    "created_at": "2025-11-05T11:30:00Z",
    "email_status": "queued",
    "sms_status": null
  }
}
```

**Postman test:** ( cần chèn ảnh Postman collection )

### 5.2. Lấy thông báo khách hàng

**Endpoint:** `GET /api/notifications/{customer_id}`

**Mô tả:** Lấy danh sách thông báo của khách hàng với phân trang, hỗ trợ lọc theo loại, trạng thái và mức độ ưu tiên.

**Tham số URL:**
- `customer_id` (required): ID khách hàng

**Query Parameters:**
- `page` (optional): Trang hiện tại (default: 1)
- `per_page` (optional): Số lượng item mỗi trang (default: 20)
- `type` (optional): Lọc theo loại thông báo
- `status` (optional): Lọc theo trạng thái - "pending", "sent", "delivered", "read", "failed"
- `priority` (optional): Lọc theo mức độ ưu tiên

**Request:**
```
GET http://localhost:8005/api/notifications/1?page=1&per_page=10&status=unread
```

**Response mẫu:**
```json
{
  "success": true,
  "message": "Notifications retrieved successfully",
  "data": {
    "notifications": [
      {
        "id": 1,
        "title": "Lịch hẹn bảo dưỡng",
        "message": "Bạn có lịch hẹn bảo dưỡng xe VF8 vào ngày mai lúc 9:00",
        "type": "appointment",
        "priority": "medium",
        "status": "delivered",
        "is_read": false,
        "created_at": "2025-11-05T11:30:00Z",
        "read_at": null,
        "data": {
          "appointment_id": 123,
          "service_center": "VinFast Hà Nội"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 25,
      "total_pages": 3
    },
    "unread_count": 5
  }
}
```

**Postman test:** ( cần chèn ảnh Postman collection )

### 5.3. Tạo lịch hẹn mới

**Endpoint:** `POST /api/appointments`

**Mô tả:** Tạo lịch hẹn bảo dưỡng hoặc sửa chữa xe cho khách hàng. Hệ thống tự động kiểm tra xung đột thời gian và gửi thông báo xác nhận.

**Request Body:**
```json
{
  "customer_id": 1,
  "vehicle_vin": "VF8ABC123456789",
  "service_center_id": 1,
  "title": "Bảo dưỡng định kỳ 10,000km",
  "description": "Thay dầu, kiểm tra phanh, rotặt lốp",
  "type": "maintenance",
  "priority": "medium",
  "appointment_date": "2025-11-15",
  "start_time": "09:00",
  "end_time": "11:00",
  "technician_id": 1
}
```

**Tham số:**
- `customer_id` (required): ID khách hàng
- `vehicle_vin` (required): Số khung xe VIN
- `service_center_id` (required): ID trung tâm dịch vụ
- `title` (required): Tiêu đề lịch hẹn
- `description` (optional): Mô tả chi tiết
- `type` (required): Loại dịch vụ - "maintenance", "repair", "warranty", "inspection", "consultation"
- `priority` (required): Mức độ ưu tiên
- `appointment_date` (required): Ngày hẹn (YYYY-MM-DD)
- `start_time` (required): Giờ bắt đầu (HH:MM)
- `end_time` (required): Giờ kết thúc (HH:MM)
- `technician_id` (optional): ID kỹ thuật viên

**Response mẫu:**
```json
{
  "success": true,
  "message": "Appointment created successfully",
  "data": {
    "id": 1,
    "customer_id": 1,
    "vehicle_vin": "VF8ABC123456789",
    "service_center_id": 1,
    "technician_id": 1,
    "title": "Bảo dưỡng định kỳ 10,000km",
    "description": "Thay dầu, kiểm tra phanh, rotặt lốp",
    "type": "maintenance",
    "priority": "medium",
    "appointment_date": "2025-11-15",
    "start_time": "09:00",
    "end_time": "11:00",
    "status": "scheduled",
    "created_at": "2025-11-05T11:30:00Z",
    "customer_name": "Nguyễn Văn A",
    "vehicle_model": "VinFast VF8",
    "service_center_name": "VinFast Hà Nội",
    "technician_name": "Trần Văn B"
  }
}
```

**Postman test:** ( cần chèn ảnh Postman collection )

### 5.4. Xem lịch hẹn theo tháng

**Endpoint:** `GET /api/appointments/calendar`

**Mô tả:** Lấy lịch hẹn theo tháng dạng calendar view, hiển thị theo ngày với thống kê tổng quan.

**Query Parameters:**
- `start_date` (required): Ngày bắt đầu (YYYY-MM-DD)
- `end_date` (required): Ngày kết thúc (YYYY-MM-DD)
- `service_center_id` (optional): Lọc theo trung tâm dịch vụ
- `technician_id` (optional): Lọc theo kỹ thuật viên
- `status` (optional): Lọc theo trạng thái

**Request:**
```
GET http://localhost:8005/api/appointments/calendar?start_date=2025-11-01&end_date=2025-11-30&service_center_id=1
```

**Response mẫu:**
```json
{
  "success": true,
  "data": {
    "calendar": {
      "2025-11-15": [
        {
          "id": 1,
          "title": "Bảo dưỡng định kỳ 10,000km",
          "type": "maintenance",
          "priority": "medium",
          "start_time": "09:00",
          "end_time": "11:00",
          "status": "scheduled",
          "customer_name": "Nguyễn Văn A",
          "vehicle_model": "VinFast VF8",
          "technician_name": "Trần Văn B"
        }
      ]
    },
    "stats": {
      "total_appointments": 25,
      "scheduled": 10,
      "confirmed": 8,
      "in_progress": 2,
      "completed": 5,
      "cancelled": 0
    }
  }
}
```

**Postman test:** ( cần chèn ảnh Postman collection )

### 5.5. Xem danh sách tồn kho

**Endpoint:** `GET /api/inventory`

**Mô tả:** Lấy danh sách phụ tùng trong kho với thông tin tồn kho, giá cả và trạng thái. Hỗ trợ tìm kiếm và lọc.

**Query Parameters:**
- `page` (optional): Trang hiện tại
- `per_page` (optional): Số lượng item mỗi trang
- `search` (optional): Tìm kiếm theo tên hoặc mã phụ tùng
- `category` (optional): Lọc theo danh mục
- `status` (optional): Lọc theo trạng thái tồn kho
- `service_center_id` (optional): Lọc theo trung tâm dịch vụ

**Request:**
```
GET http://localhost:8005/api/inventory?page=1&per_page=20&category=battery&status=available
```

**Response mẫu:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "part_number": "BATT-VF8-001",
        "part_name": "VinFast VF8 Battery Pack",
        "description": "Pin lithium 87.7kWh cho VF8",
        "category": "battery",
        "current_stock": 15,
        "min_stock_level": 5,
        "max_stock_level": 50,
        "unit_price": 450000000,
        "currency": "VND",
        "supplier": "CATL",
        "location": "Kho A-01",
        "status": "available",
        "last_updated": "2025-11-05T10:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 45,
      "total_pages": 3
    },
    "stats": {
      "total_items": 45,
      "available_items": 38,
      "low_stock_items": 5,
      "out_of_stock_items": 2,
      "total_value": 12500000000,
      "currency": "VND"
    }
  }
}
```

**Postman test:** ( cần chèn ảnh Postman collection )

### 5.6. Cập nhật tồn kho

**Endpoint:** `POST /api/inventory/update`

**Mô tả:** Cập nhật số lượng tồn kho phụ tùng. Hệ thống tự động ghi log giao dịch và kiểm tra ngưỡng cảnh báo.

**Request Body:**
```json
{
  "inventory_id": 1,
  "type": "stock_in",
  "quantity": 10,
  "reason": "Nhập kho từ nhà cung cấp CATL",
  "updated_by": "admin_user",
  "reference_document": "PO-2025-001",
  "notes": "Lô hàng mới, kiểm tra chất lượng đạt"
}
```

**Tham số:**
- `inventory_id` (required): ID phụ tùng
- `type` (required): Loại giao dịch - "stock_in", "stock_out", "adjustment", "return"
- `quantity` (required): Số lượng thay đổi
- `reason` (required): Lý do thay đổi
- `updated_by` (required): Người thực hiện
- `reference_document` (optional): Số chứng từ tham chiếu
- `notes` (optional): Ghi chú bổ sung

**Response mẫu:**
```json
{
  "success": true,
  "message": "Stock updated successfully",
  "data": {
    "item": {
      "id": 1,
      "part_name": "VinFast VF8 Battery Pack",
      "previous_stock": 15,
      "new_stock": 25,
      "change_amount": 10,
      "updated_at": "2025-11-05T11:45:00Z"
    },
    "transaction": {
      "id": 1,
      "type": "stock_in",
      "quantity": 10,
      "reason": "Nhập kho từ nhà cung cấp CATL",
      "updated_by": "admin_user",
      "created_at": "2025-11-05T11:45:00Z"
    },
    "alerts_generated": []
  }
}
```

**Postman test:** ( cần chèn ảnh Postman collection )

### 5.7. Phân bổ phụ tùng

**Endpoint:** `POST /api/inventory/allocate`

**Mô tả:** Phân bổ phụ tùng cho lịch hẹn bảo dưỡng/sửa chữa. Hệ thống kiểm tra tồn kho và đặt trạng thái "reserved".

**Request Body:**
```json
{
  "allocations": [
    {
      "inventory_id": 1,
      "quantity": 1
    },
    {
      "inventory_id": 2,
      "quantity": 4
    }
  ],
  "reference_type": "appointment",
  "reference_id": 123,
  "allocated_by": "technician_user",
  "notes": "Phụ tùng cho bảo dưỡng VF8 - Khách hàng Nguyễn Văn A"
}
```

**Tham số:**
- `allocations` (required): Mảng phụ tùng cần phân bổ
  - `inventory_id`: ID phụ tùng
  - `quantity`: Số lượng cần
- `reference_type` (required): Loại tham chiếu - "appointment", "warranty_claim", "repair_order"
- `reference_id` (required): ID tham chiếu
- `allocated_by` (required): Người thực hiện phân bổ
- `notes` (optional): Ghi chú

**Response mẫu:**
```json
{
  "success": true,
  "message": "Parts allocated successfully",
  "data": {
    "allocations": [
      {
        "inventory_id": 1,
        "part_name": "VinFast VF8 Battery Pack",
        "allocated_quantity": 1,
        "remaining_stock": 24,
        "status": "reserved"
      },
      {
        "inventory_id": 2,
        "part_name": "VF8 Brake Pad Set",
        "allocated_quantity": 4,
        "remaining_stock": 16,
        "status": "reserved"
      }
    ],
    "total_allocated_items": 2,
    "reference_type": "appointment",
    "reference_id": 123,
    "allocated_at": "2025-11-05T11:50:00Z"
  }
}
```

**Postman test:** ( cần chèn ảnh Postman collection )

### 5.8. Xem cảnh báo tồn kho

**Endpoint:** `GET /api/inventory/alerts`

**Mô tả:** Lấy danh sách cảnh báo về tình trạng tồn kho như sắp hết hàng, hết hàng, hoặc tồn kho dư thừa.

**Query Parameters:**
- `service_center_id` (optional): Lọc theo trung tâm dịch vụ
- `alert_type` (optional): Loại cảnh báo - "low_stock", "out_of_stock", "overstocked"
- `category` (optional): Lọc theo danh mục phụ tùng

**Request:**
```
GET http://localhost:8005/api/inventory/alerts?service_center_id=1&alert_type=low_stock
```

**Response mẫu:**
```json
{
  "success": true,
  "data": {
    "alerts": {
      "critical": [
        {
          "inventory_id": 3,
          "part_number": "TIRE-VF8-001",
          "part_name": "VF8 Tire 255/45R20",
          "current_stock": 0,
          "min_stock_level": 8,
          "alert_type": "out_of_stock",
          "days_out_of_stock": 3,
          "last_restock": "2025-10-28T09:00:00Z"
        }
      ],
      "warning": [
        {
          "inventory_id": 4,
          "part_number": "BRAKE-VF8-001",
          "part_name": "VF8 Brake Pad Set",
          "current_stock": 2,
          "min_stock_level": 5,
          "alert_type": "low_stock",
          "estimated_days_until_empty": 7,
          "reorder_quantity": 20
        }
      ],
      "info": []
    },
    "counts": {
      "critical": 1,
      "warning": 1,
      "info": 0,
      "total": 2
    },
    "generated_at": "2025-11-05T11:55:00Z"
  }
}
```

**Postman test:** ( cần chèn ảnh Postman collection )

### 5.9. Tạo chiến dịch thông báo

**Endpoint:** `POST /api/notifications/campaign`

**Mô tả:** Tạo chiến dịch thông báo marketing hàng loạt đến nhóm khách hàng mục tiêu. Hỗ trợ lên lịch gửi và theo dõi hiệu quả.

**Request Body:**
```json
{
  "name": "Khuyến mãi bảo dưỡng tháng 11",
  "description": "Chiến dịch giảm giá 20% dịch vụ bảo dưỡng định kỳ",
  "type": "promotion",
  "priority": "medium",
  "title": "🎉 Ưu đại đặc biệt tháng 11",
  "message": "Giảm 20% chi phí bảo dưỡng xe VF8/VF9. Đặt lịch ngay để nhận ưu đãi!",
  "target_criteria": {
    "customer_segments": ["premium", "regular"],
    "service_centers": [1, 2],
    "vehicle_models": ["VF8", "VF9"],
    "last_service_days_ago": 180
  },
  "channels": ["email", "sms", "in_app"],
  "schedule_type": "scheduled",
  "scheduled_at": "2025-11-07T09:00:00Z",
  "created_by": 1
}
```

**Tham số:**
- `name` (required): Tên chiến dịch
- `description` (required): Mô tả chiến dịch
- `type` (required): Loại - "marketing", "maintenance_reminder", "recall_notice", "promotion", "system_update", "warranty_expiry"
- `priority` (required): Mức độ ưu tiên
- `title` (required): Tiêu đề thông báo
- `message` (required): Nội dung thông báo
- `target_criteria` (required): Tiêu chí nhắm mục tiêu
- `channels` (required): Kênh gửi
- `schedule_type` (required): Kiểu lên lịch - "immediate", "scheduled"
- `scheduled_at` (optional): Thời gian gửi (nếu scheduled)
- `created_by` (required): ID người tạo

**Response mẫu:**
```json
{
  "success": true,
  "message": "Campaign created successfully",
  "data": {
    "id": 1,
    "name": "Khuyến mãi bảo dưỡng tháng 11",
    "type": "promotion",
    "status": "scheduled",
    "target_count": 1250,
    "scheduled_at": "2025-11-07T09:00:00Z",
    "created_at": "2025-11-05T12:00:00Z",
    "estimated_reach": {
      "email": 1200,
      "sms": 1100,
      "in_app": 1250
    }
  }
}
```

**Postman test:** ( cần chèn ảnh Postman collection )

## 6. Xử lý lỗi

### 6.1. Mã lỗi HTTP
- `200`: Thành công
- `201`: Tạo mới thành công
- `400`: Bad Request - Dữ liệu đầu vào không hợp lệ
- `401`: Unauthorized - Chưa xác thực
- `403`: Forbidden - Không có quyền truy cập
- `404`: Not Found - Không tìm thấy resource
- `422`: Unprocessable Entity - Dữ liệu không hợp lệ
- `500`: Internal Server Error - Lỗi server

### 6.2. Định dạng lỗi
```json
{
  "success": false,
  "message": "Mô tả lỗi",
  "error": "Chi tiết lỗi kỹ thuật",
  "errors": {
    "field_name": ["Lỗi validation cụ thể"]
  }
}
```

### 6.3. Ví dụ lỗi thường gặp
```json
{
  "success": false,
  "message": "Missing required fields",
  "missing_fields": ["customer_id", "title", "message"]
}
```

## 7. Authentication & Security

### 7.1. API Key (Coming Soon)
- Sử dụng header: `Authorization: Bearer YOUR_API_KEY`
- Rate limiting: 1000 requests/hour

### 7.2. CORS
- Allowed origins: localhost, staging, production domains
- Allowed methods: GET, POST, PUT, DELETE
- Allowed headers: Content-Type, Authorization

## 8. Environment & Configuration

### 8.1. Development Environment
- **Base URL:** `http://localhost:8005`
- **Database:** MySQL 8.0 (ports 3306-3310)
- **Cache:** Redis (port 6379)
- **Email Testing:** Mailpit (http://localhost:8025)

### 8.2. Service Dependencies
- **Frontend:** Next.js (http://localhost:3000)
- **Customer Service:** http://localhost:8001
- **Vehicle Service:** http://localhost:8003
- **Warranty Service:** http://localhost:8002
- **Admin Service:** http://localhost:8004

## 9. Monitoring & Logging

### 9.1. Health Check
```
GET http://localhost:8005/health
```

### 9.2. API Metrics
- Response time: < 200ms average
- Uptime: 99.9%
- Error rate: < 1%

### 9.3. Log Files
- Application logs: `/var/log/notification-service/`
- Access logs: Nginx/Apache logs
- Error logs: PHP error logs

## 10. Testing & Validation

### 10.1. Automated Test Suite
Chạy test suite đầy đủ:
```bash
cd D:\OEM-EV-Warranty-Management-System
node tests/api-test-suite.js
```

### 10.2. Test Data
( cần chèn dữ liệu test mẫu )

### 10.3. Performance Testing
- Load testing với Apache Bench
- Stress testing với 1000 concurrent users
- Memory usage monitoring

## 11. Troubleshooting

### 11.1. Lỗi thường gặp

**Database connection failed:**
```bash
# Kiểm tra Docker containers
docker-compose ps

# Restart notification service
docker-compose restart notification-service
```

**Queue not processing:**
```bash
# Kiểm tra Redis
docker logs oem-ev-warranty-management-system-redis-1

# Restart queue worker
docker-compose restart notification-service
```

**Email not sending:**
```bash
# Kiểm tra Mailpit
curl http://localhost:8025/api/v1/messages
```

### 11.2. Debug Mode
Bật debug trong `.env`:
```
APP_ENV=development
APP_DEBUG=true
```

## 12. Best Practices

### 12.1. API Usage
- Sử dụng pagination cho danh sách lớn
- Cache responses khi có thể
- Xử lý lỗi gracefully
- Validate input data

### 12.2. Performance
- Batch operations khi có thể
- Sử dụng queue cho tasks nặng
- Monitor response times
- Optimize database queries

### 12.3. Security
- Không expose sensitive data
- Validate tất cả input
- Rate limiting
- Secure headers

## 13. Changelog & Updates

### Version 1.0.0 (November 2025)
- ✅ Initial release
- ✅ 9 core API endpoints
- ✅ Queue system integration
- ✅ Email/SMS notifications
- ✅ Real-time inventory tracking

### Upcoming Features
- 🔄 Webhook notifications
- 🔄 Advanced analytics
- 🔄 Mobile push notifications
- 🔄 Multi-language support

## 14. Support & Contact

### 14.1. Documentation
- **API Docs:** [Internal Documentation]
- **GitHub:** [Repository Link]
- **Postman Collection:** ( cần chèn link collection )

### 14.2. Technical Support
- **Email:** tech-support@vinfast.vn
- **Slack:** #evm-notification-system
- **On-call:** 24/7 support hotline

---

**Document Version:** 1.0  
**Last Updated:** November 5, 2025  
**Author:** EVM Development Team  
**Review Status:** ✅ Approved for Production Use

---

*Tài liệu này cung cấp hướng dẫn đầy đủ để tích hợp và sử dụng API Hệ thống Thông báo EVM. Để được hỗ trợ thêm, vui lòng liên hệ team phát triển.*