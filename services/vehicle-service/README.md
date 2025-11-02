# Vehicle Service API Documentation

## Overview
Vehicle Service quản lý sản phẩm EV, chính sách bảo hành, và campaigns recall cho hệ thống OEM EV Warranty Management.

## Architecture
- **Models**: EVComponent, WarrantyPolicy, Campaign
- **Controllers**: ComponentsController
- **Database**: MySQL với 3 bảng chính + 1 bảng tracking

## Database Schema

### ev_components
Quản lý linh kiện EV (pin, motor, BMS, inverter, charger, etc.)
```sql
- id (PRIMARY KEY)
- component_type (ENUM: battery, motor, bms, inverter, charger, controller, other)
- component_name (VARCHAR 100)
- model (VARCHAR 50)
- specifications (JSON)
- warranty_period (INT - months)
- supplier_id (INT)
- status (ENUM: active, discontinued, recalled)
```

### warranty_policies
Chính sách bảo hành cho từng linh kiện
```sql
- id (PRIMARY KEY)
- component_id (FOREIGN KEY to ev_components)
- policy_name (VARCHAR 100)
- warranty_duration (INT - months)
- coverage_details (JSON)
- conditions (JSON)
- exclusions (JSON)
- effective_date (DATE)
- expiry_date (DATE)
- status (ENUM: active, inactive, expired)
```

### campaigns
Chiến dịch recall và service campaigns
```sql
- id (PRIMARY KEY)
- title (VARCHAR 200)
- description (TEXT)
- campaign_type (ENUM: recall, service_campaign, maintenance)
- affected_models (JSON)
- affected_vins (JSON)
- affected_components (JSON)
- priority_level (ENUM: low, medium, high, critical)
- start_date (DATE)
- end_date (DATE)
- instructions (TEXT)
- status (ENUM: draft, active, paused, completed, cancelled)
```

## API Endpoints

### 1. EV Components Management

#### POST /api/components
Thêm linh kiện EV mới

**Request Body:**
```json
{
    "component_type": "battery",
    "component_name": "Lithium Ion Battery Pack",
    "model": "LIB-2024-60kWh",
    "specifications": {
        "capacity": "60kWh",
        "voltage": "400V",
        "cells": 288,
        "chemistry": "LiFePO4"
    },
    "warranty_period": 96,
    "supplier_id": 1,
    "status": "active"
}
```

**Response:**
```json
{
    "message": "Component created successfully",
    "data": {
        "id": 1,
        "component_type": "battery",
        "component_name": "Lithium Ion Battery Pack",
        "model": "LIB-2024-60kWh",
        "specifications": {
            "capacity": "60kWh",
            "voltage": "400V",
            "cells": 288,
            "chemistry": "LiFePO4"
        },
        "warranty_period": 96,
        "supplier_id": 1,
        "status": "active",
        "created_at": "2024-10-26 10:00:00"
    }
}
```

#### GET /api/components
Danh sách linh kiện EV

**Query Parameters:**
- `component_type` - Lọc theo loại linh kiện
- `status` - Lọc theo trạng thái
- `model` - Tìm kiếm theo model
- `limit` - Giới hạn số lượng kết quả

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "component_type": "battery",
            "component_name": "Lithium Ion Battery Pack",
            "model": "LIB-2024-60kWh",
            "specifications": {...},
            "warranty_period": 96,
            "status": "active"
        }
    ],
    "total": 1
}
```

#### GET /api/components/{id}
Lấy thông tin linh kiện theo ID

#### PUT /api/components/{id}
Cập nhật thông tin linh kiện

#### DELETE /api/components/{id}
Xóa linh kiện

### 2. Warranty Policies Management

#### POST /api/warranty-policies
Tạo chính sách bảo hành

**Request Body:**
```json
{
    "component_id": 1,
    "policy_name": "EV Battery Standard Warranty",
    "warranty_duration": 96,
    "coverage_details": {
        "coverage": ["capacity_degradation", "manufacturing_defects", "thermal_runaway"],
        "degradation_limit": "80%"
    },
    "conditions": {
        "usage": "normal_driving",
        "temperature": "-20C_to_60C",
        "charging": "standard_protocols"
    },
    "exclusions": {
        "misuse": ["overcharging", "physical_damage", "water_damage"]
    },
    "effective_date": "2024-01-01",
    "status": "active"
}
```

#### GET /api/warranty-policies
Danh sách chính sách bảo hành

**Query Parameters:**
- `component_id` - Lọc theo linh kiện
- `status` - Lọc theo trạng thái
- `component_type` - Lọc theo loại linh kiện

### 3. Campaigns Management

#### POST /api/campaigns
Tạo chiến dịch recall

**Request Body:**
```json
{
    "title": "Battery Cooling System Recall",
    "description": "Recall for potential coolant leak in battery cooling system",
    "campaign_type": "recall",
    "affected_models": ["Model-X-2024", "Model-Y-2024"],
    "affected_components": [1, 2],
    "priority_level": "high",
    "start_date": "2024-11-01",
    "end_date": "2025-03-01",
    "instructions": "Inspect battery cooling connections and replace coolant lines if necessary. Estimated time: 2-3 hours.",
    "status": "active"
}
```

#### GET /api/campaigns
Danh sách campaigns

#### GET /api/campaigns/{id}/vehicles
Xe bị ảnh hưởng bởi campaign

**Response:**
```json
{
    "data": [
        {
            "vin": "1HGCM82633A123456",
            "model": "Model-X-2024",
            "year": 2024,
            "customer_name": "John Doe",
            "customer_email": "john.doe@email.com",
            "customer_phone": "+1234567890"
        }
    ],
    "total": 1
}
```

#### POST /api/campaigns/{id}/notify
Gửi thông báo campaign

**Response:**
```json
{
    "message": "Notifications sent successfully",
    "data": {
        "success": true,
        "notifications_sent": 2,
        "total_affected": 2
    }
}
```

#### GET /api/campaigns/{id}/progress
Tiến độ campaign

**Response:**
```json
{
    "data": {
        "statistics": {
            "total_affected": 2,
            "identified": 2,
            "notified": 2,
            "scheduled": 1,
            "in_progress": 0,
            "completed": 0,
            "cancelled": 0
        },
        "progress_records": [
            {
                "id": 1,
                "campaign_id": 1,
                "vin": "1HGCM82633A123456",
                "status": "notified",
                "notification_sent_date": "2024-10-26 10:30:00",
                "notes": null
            }
        ]
    }
}
```

## Environment Variables

```env
DB_HOST=localhost
DB_NAME=vehicle_service_db
DB_USER=root
DB_PASS=
```

## Setup Instructions

1. **Database Setup:**
```bash
# Tạo database
mysql -u root -p -e "CREATE DATABASE vehicle_service_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p vehicle_service_db < database/schema.sql
```

2. **Dependencies:**
```bash
# Install PHP dependencies
composer install

# Hoặc nếu không có composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php composer.phar install
```

3. **Start Service:**
```bash
# Development server
php -S localhost:8081 -t public/

# Production: Configure Apache/Nginx to point to public/ directory
```

## Testing

### Health Check
```bash
curl http://localhost:8081/api/health
```

### Test Components API
```bash
# Create component
curl -X POST http://localhost:8081/api/components \
  -H "Content-Type: application/json" \
  -d '{
    "component_type": "battery",
    "component_name": "Test Battery",
    "model": "TEST-2024",
    "specifications": {"capacity": "50kWh"},
    "warranty_period": 60
  }'

# Get all components
curl http://localhost:8081/api/components

# Get component by ID
curl http://localhost:8081/api/components/1
```

### Test Campaigns API
```bash
# Create campaign
curl -X POST http://localhost:8081/api/campaigns \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Campaign",
    "campaign_type": "recall",
    "affected_models": ["Model-X"],
    "priority_level": "medium",
    "start_date": "2024-11-01"
  }'

# Get affected vehicles
curl http://localhost:8081/api/campaigns/1/vehicles

# Send notifications
curl -X POST http://localhost:8081/api/campaigns/1/notify

# Get progress
curl http://localhost:8081/api/campaigns/1/progress
```

## Error Handling

API trả về HTTP status codes standard:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

Error responses có format:
```json
{
    "error": "Error message",
    "message": "Detailed description (optional)"
}
```

## Integration with Other Services

- **Customer Service**: Lấy thông tin xe và khách hàng cho campaigns
- **Notification Service**: Gửi thông báo campaigns
- **Warranty Service**: Liên kết policies với warranty claims
- **Admin Service**: Báo cáo và thống kê components/campaigns