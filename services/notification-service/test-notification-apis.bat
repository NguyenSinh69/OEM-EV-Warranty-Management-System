@echo off
echo ========================================
echo EVM Notification Service - API Testing
echo Testing all 9 endpoints
echo ========================================
echo.

REM Set base URL
set BASE_URL=http://localhost:8005

echo 1. Testing Health Check...
curl -s %BASE_URL%/api/health
echo.
echo.

echo ========================================
echo 2. Testing Notification Endpoints
echo ========================================
echo.

echo Sending notification:
curl -X POST %BASE_URL%/api/notifications/send ^
  -H "Content-Type: application/json" ^
  -d "{\"customer_id\":1,\"title\":\"Test Notification\",\"message\":\"This is a test notification from API test\",\"type\":\"info\",\"channels\":[\"email\",\"sms\"],\"priority\":\"medium\"}"
echo.
echo.

echo Getting customer notifications:
curl -s "%BASE_URL%/api/notifications/1?limit=5"
echo.
echo.

echo Marking notification as read:
curl -X PUT %BASE_URL%/api/notifications/1/read
echo.
echo.

echo ========================================
echo 3. Testing Appointment Endpoints  
echo ========================================
echo.

echo Creating appointment:
curl -X POST %BASE_URL%/api/appointments ^
  -H "Content-Type: application/json" ^
  -d "{\"customer_id\":1,\"vehicle_vin\":\"VF3ABCDEF12345678\",\"service_center_id\":1,\"title\":\"Battery Check\",\"type\":\"maintenance\",\"appointment_date\":\"2024-12-01\",\"start_time\":\"09:00:00\",\"duration_minutes\":90,\"contact_phone\":\"+84901234567\",\"customer_notes\":\"Please check battery health\"}"
echo.
echo.

echo Getting calendar:
curl -s "%BASE_URL%/api/appointments/calendar?start_date=2024-12-01&end_date=2024-12-31"
echo.
echo.

echo ========================================
echo 4. Testing Inventory Endpoints
echo ========================================
echo.

echo Getting inventory:
curl -s "%BASE_URL%/api/inventory?limit=5"
echo.
echo.

echo Updating stock:
curl -X POST %BASE_URL%/api/inventory/update ^
  -H "Content-Type: application/json" ^
  -d "{\"inventory_id\":1,\"type\":\"stock_in\",\"quantity\":10,\"notes\":\"New shipment received\",\"performed_by\":1}"
echo.
echo.

echo Allocating parts:
curl -X POST %BASE_URL%/api/inventory/allocate ^
  -H "Content-Type: application/json" ^
  -d "{\"allocations\":[{\"inventory_id\":1,\"quantity\":2},{\"inventory_id\":2,\"quantity\":1}],\"reference_type\":\"appointment\",\"reference_id\":1,\"performed_by\":1}"
echo.
echo.

echo Getting inventory alerts:
curl -s %BASE_URL%/api/inventory/alerts
echo.
echo.

echo ========================================
echo 5. Testing Campaign Endpoint
echo ========================================
echo.

echo Creating campaign:
curl -X POST %BASE_URL%/api/notifications/campaign ^
  -H "Content-Type: application/json" ^
  -d "{\"name\":\"December Maintenance Reminder\",\"description\":\"Monthly maintenance reminder campaign\",\"type\":\"maintenance_reminder\",\"title\":\"Time for your monthly vehicle check\",\"message\":\"Your VinFast vehicle is due for its monthly maintenance check. Book your appointment today!\",\"target_criteria\":{\"last_service\":\">30_days\",\"vehicle_type\":\"electric\"},\"priority\":\"medium\",\"created_by\":1}"
echo.
echo.

echo ========================================
echo API Testing Complete!
echo ========================================
echo.
echo All 9 endpoints tested:
echo ✅ POST /api/notifications/send
echo ✅ GET /api/notifications/{customer_id} 
echo ✅ PUT /api/notifications/{id}/read
echo ✅ POST /api/appointments
echo ✅ GET /api/appointments/calendar
echo ✅ GET /api/inventory
echo ✅ POST /api/inventory/update
echo ✅ POST /api/inventory/allocate
echo ✅ GET /api/inventory/alerts
echo ✅ POST /api/notifications/campaign
echo.
echo Check the responses above for any errors.
echo Service should be running on %BASE_URL%
echo.
pause