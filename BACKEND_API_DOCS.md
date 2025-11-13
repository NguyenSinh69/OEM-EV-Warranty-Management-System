# Backend API Documentation - Person 1

## Overview
Backend implementation for **Person 1** (Frontend Lead) features including:
- Customer Portal APIs
- SC Staff Dashboard APIs  
- File Upload Service

All services are built with **PHP 8.1** and use **MySQL 8.0** databases.

---

## Services Architecture

```
Port 8001: Customer Service (Customer Portal APIs)
Port 8003: Vehicle Service (SC Staff APIs)
Port 8006: File Upload Service (Image/Document Upload)
```

---

## 1. Customer Service (Port 8001)

### Base URL
```
http://localhost:8001/api
```

### Authentication
All customer endpoints require Bearer token in header:
```
Authorization: Bearer <token>
```

### Endpoints

#### Get Customer's Vehicles
```http
GET /api/customer/vehicles
Authorization: Bearer <token>
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "vin": "VF3ABCDEF12345678",
      "license_plate": "29A-12345",
      "model": "VinFast VF8",
      "make": "VinFast",
      "year": 2024,
      "color": "Đỏ",
      "purchase_date": "2024-01-15",
      "warranty_start_date": "2024-01-15",
      "warranty_end_date": "2026-01-15",
      "warranty_months": 24,
      "mileage": 15000,
      "status": "under_warranty",
      "customer_id": 1
    }
  ],
  "message": "Vehicles retrieved successfully"
}
```

---

#### Get Customer's Claims
```http
GET /api/customer/claims
Authorization: Bearer <token>
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "claim_number": "WC-2024-001",
      "vin": "VF3ABCDEF12345678",
      "vehicle_model": "VinFast VF8",
      "component": "Battery",
      "failure_description": "Pin sạc không đầy",
      "failure_date": "2024-10-01",
      "mileage": 14500,
      "status": "under_review",
      "status_notes": "Đang chờ kỹ thuật viên kiểm tra",
      "submission_date": "2024-10-02",
      "images": ["url1", "url2"]
    }
  ],
  "message": "Claims retrieved successfully"
}
```

---

#### Create New Claim
```http
POST /api/customer/claims
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "vehicle_id": 1,
  "vin": "VF3ABCDEF12345678",
  "component": "Battery",
  "failure_description": "Pin sạc không đầy, chỉ sạc được tối đa 80%",
  "failure_date": "2024-10-01",
  "mileage": 14500,
  "images": ["image_url1", "image_url2"]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "claim_number": "WC-2024-123",
    "status": "submitted",
    "submission_date": "2024-11-12"
  },
  "message": "Claim created successfully"
}
```

---

#### Get Claim Details
```http
GET /api/customer/claims/{claimId}
Authorization: Bearer <token>
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "claim_number": "WC-2024-001",
    "vin": "VF3ABCDEF12345678",
    "vehicle_model": "VinFast VF8",
    "component": "Battery",
    "failure_description": "Pin sạc không đầy",
    "failure_date": "2024-10-01",
    "mileage": 14500,
    "status": "under_review",
    "status_notes": "Đang chờ kỹ thuật viên kiểm tra",
    "images": ["url1", "url2"],
    "service_center": "Trung tâm bảo hành Hà Nội",
    "technician": "Nguyễn Văn Kỹ thuật"
  },
  "message": "Claim details retrieved successfully"
}
```

---

#### Book Appointment
```http
POST /api/customer/appointments
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "vehicle_id": 1,
  "service_type": "Bảo dưỡng định kỳ",
  "appointment_date": "2024-11-20",
  "appointment_time": "09:00",
  "notes": "Kiểm tra tổng thể và thay dầu"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "appointment_number": "APT-2024-456",
    "status": "scheduled",
    "service_center": "Trung tâm bảo hành Hà Nội",
    "service_center_address": "123 Đường ABC, Quận XYZ, Hà Nội",
    "service_center_phone": "024-1234-5678"
  },
  "message": "Appointment booked successfully"
}
```

---

#### Get Customer's Appointments
```http
GET /api/customer/appointments
Authorization: Bearer <token>
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "appointment_number": "APT-2024-001",
      "vehicle_model": "VinFast VF8",
      "service_type": "Bảo dưỡng định kỳ",
      "appointment_date": "2024-11-20",
      "appointment_time": "09:00",
      "status": "scheduled",
      "service_center": "Trung tâm bảo hành Hà Nội"
    }
  ],
  "message": "Appointments retrieved successfully"
}
```

---

#### Get Notifications
```http
GET /api/customer/notifications
Authorization: Bearer <token>
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "claim",
      "title": "Yêu cầu bảo hành được chấp nhận",
      "message": "Yêu cầu bảo hành WC-2024-002 của bạn đã được chấp nhận",
      "read": false,
      "created_at": "2024-11-10 09:30:00",
      "link": "/customer/claims/2"
    }
  ],
  "message": "Notifications retrieved successfully"
}
```

---

#### Mark Notification as Read
```http
PUT /api/customer/notifications/{notificationId}/read
Authorization: Bearer <token>
```

---

#### Delete Notification
```http
DELETE /api/customer/notifications/{notificationId}
Authorization: Bearer <token>
```

---

## 2. Vehicle Service - SC Staff APIs (Port 8003)

### Base URL
```
http://localhost:8003/api/sc-staff
```

### Endpoints

#### Dashboard Statistics
```http
GET /api/sc-staff/dashboard/stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "today_registrations": 15,
    "pending_claims": 8,
    "active_recalls": 2,
    "total_vehicles": 1250
  }
}
```

---

#### Register Vehicle
```http
POST /api/sc-staff/vehicles/register
Content-Type: application/json
```

**Request Body:**
```json
{
  "vin": "VF3ABCDEF12345678",
  "model_id": 1,
  "year": 2024,
  "color": "Đỏ",
  "customer_id": 123,
  "purchase_date": "2024-01-15",
  "warranty_start_date": "2024-01-15"
}
```

---

#### Search Vehicles
```http
GET /api/sc-staff/vehicles/search?q={query}&type={type}
```

**Parameters:**
- `q`: Search query
- `type`: Search type (vin, customer, license_plate, all)

---

#### Get Reference Data
```http
GET /api/sc-staff/reference-data
```

**Response:**
```json
{
  "success": true,
  "data": {
    "models": [...],
    "customers": [...],
    "parts": [...]
  }
}
```

---

#### Create Warranty Claim
```http
POST /api/sc-staff/warranty-claims/create
Content-Type: application/json
```

**Request Body:**
```json
{
  "vehicle_id": 1,
  "issue_description": "Pin sạc không đầy",
  "symptoms": "Chỉ sạc được 80%",
  "failure_date": "2024-10-01",
  "failure_mileage": 14500,
  "priority": "high",
  "estimated_cost": 5000000
}
```

---

#### Get Warranty Claims
```http
GET /api/sc-staff/warranty-claims?status={status}
```

---

#### Get Recall Campaigns
```http
GET /api/sc-staff/recalls
```

---

## 3. File Upload Service (Port 8006)

### Base URL
```
http://localhost:8006/api/upload
```

### Endpoints

#### Upload Single File
```http
POST /api/upload/file
Content-Type: multipart/form-data
```

**Form Data:**
- `file`: File to upload
- `category`: Storage category (claims, vehicles, temp)

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "filename": "image_1234567890_abc123.jpg",
    "original_name": "image.jpg",
    "size": 102400,
    "mime_type": "image/jpeg",
    "category": "claims",
    "url": "/api/upload/file/claims/image_1234567890_abc123.jpg",
    "uploaded_at": "2024-11-12 10:30:00"
  }
}
```

---

#### Upload Multiple Files
```http
POST /api/upload/files
Content-Type: multipart/form-data
```

**Form Data:**
- `files[]`: Array of files
- `category`: Storage category

---

#### Get File
```http
GET /api/upload/file/{category}/{filename}
```

Returns the actual file content.

---

#### Delete File
```http
DELETE /api/upload/file/{category}/{filename}
```

---

#### List Files
```http
GET /api/upload/files?category={category}
```

---

## Error Handling

### Standard Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": ["Error detail 1", "Error detail 2"]
}
```

### HTTP Status Codes
- `200`: Success
- `400`: Bad Request (validation error)
- `401`: Unauthorized (missing/invalid token)
- `404`: Not Found
- `500`: Internal Server Error

---

## CORS Configuration

All services are configured with CORS enabled:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
```

---

## Testing

### Health Check All Services
```bash
# Customer Service
curl http://localhost:8001/api/health

# Vehicle Service (SC Staff)
curl http://localhost:8003/api/sc-staff/health

# File Upload Service
curl http://localhost:8006/api/upload/health
```

### Test Customer API (with auth)
```bash
# Login first
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "nguyenvana@example.com", "password": "password123"}'

# Use token from response
TOKEN="<token_from_login>"

# Get vehicles
curl http://localhost:8001/api/customer/vehicles \
  -H "Authorization: Bearer $TOKEN"
```

### Test File Upload
```bash
curl -X POST http://localhost:8006/api/upload/file \
  -F "file=@/path/to/image.jpg" \
  -F "category=claims"
```

---

## Database Schema (Mock Data)

Currently using **mock data** in PHP. Real database integration pending.

### Tables Needed:
- `customers` - Customer information
- `vehicles` - Vehicle records
- `warranty_claims` - Warranty claims
- `appointments` - Service appointments
- `notifications` - User notifications
- `uploaded_files` - File metadata

---

## Docker Services

### Start All Services
```bash
docker-compose up -d
```

### View Logs
```bash
docker-compose logs -f customer-service
docker-compose logs -f file-upload-service
```

### Restart Service
```bash
docker-compose restart customer-service
```

---

## Frontend Integration

Frontend API client is configured in `frontend/src/lib/api.ts`:

```typescript
// Customer Portal
api.customer.getMyVehicles()
api.customer.getMyClaims()
api.customer.createClaim(claimData)
api.customer.bookAppointment(appointmentData)

// SC Staff
api.scStaff.getDashboardStats()
api.scStaff.registerVehicle(vehicleData)
api.scStaff.searchVehicles(query)

// File Upload
api.scStaff.uploadFile(file, 'claims')
```

---

## Next Steps

1. **Database Integration**: Replace mock data with real MySQL queries
2. **Authentication**: Implement JWT token validation
3. **Validation**: Add input validation and sanitization
4. **Testing**: Create automated API tests
5. **Documentation**: Add OpenAPI/Swagger specs
6. **Monitoring**: Add logging and error tracking

---

## Contact

**Developed by:** Person 1 (Frontend Lead)  
**Date:** November 12, 2024  
**Version:** 1.0.0
