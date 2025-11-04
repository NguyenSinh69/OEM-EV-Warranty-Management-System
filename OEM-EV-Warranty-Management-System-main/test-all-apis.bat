@echo off
echo ========================================
echo EVM Warranty System - API Testing
echo ========================================
echo.

REM Test all service health checks
echo 1. Testing all service health endpoints...
echo.

echo Customer Service Health:
curl -s http://localhost:8001/api/health
echo.

echo Warranty Service Health:
curl -s http://localhost:8002/api/health
echo.

echo Vehicle Service Health:
curl -s http://localhost:8003/api/health
echo.

echo Admin Service Health:
curl -s http://localhost:8004/api/health
echo.

echo Notification Service Health:
curl -s http://localhost:8005/api/health
echo.

echo.
echo ========================================
echo 2. Testing Authentication
echo ========================================
echo.

echo Customer Login:
curl -X POST http://localhost:8001/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"nguyenvana@example.com\",\"password\":\"password123\"}"
echo.

echo.
echo Customer Registration:
curl -X POST http://localhost:8001/api/auth/register ^
  -H "Content-Type: application/json" ^
  -d "{\"name\":\"Nguyen Van Test\",\"email\":\"test@example.com\",\"phone\":\"0987654321\",\"address\":\"HCM\",\"date_of_birth\":\"1990-01-01\",\"id_number\":\"123456789\",\"password\":\"password123\",\"password_confirmation\":\"password123\"}"
echo.

echo.
echo ========================================
echo 3. Testing Customer Management
echo ========================================
echo.

echo Get All Customers:
curl -s http://localhost:8001/api/customers
echo.

echo.
echo Get Customer Vehicles:
curl -s http://localhost:8001/api/customers/1/vehicles
echo.

echo.
echo ========================================
echo 4. Testing Vehicle Management
echo ========================================
echo.

echo Get All Vehicles:
curl -s http://localhost:8003/api/vehicles
echo.

echo.
echo Get Vehicle by VIN:
curl -s http://localhost:8003/api/vehicles/VF3ABCDEF12345678
echo.

echo.
echo Get Vehicle Warranty:
curl -s http://localhost:8003/api/vehicles/VF3ABCDEF12345678/warranty
echo.

echo.
echo ========================================
echo 5. Testing Warranty Management
echo ========================================
echo.

echo Get All Warranty Claims:
curl -s http://localhost:8002/api/warranties
echo.

echo.
echo Create Warranty Claim:
curl -X POST http://localhost:8002/api/warranties ^
  -H "Content-Type: application/json" ^
  -d "{\"customer_id\":1,\"vehicle_vin\":\"VF3ABCDEF12345678\",\"description\":\"Pin khong sac duoc\",\"issue_type\":\"battery\",\"priority\":\"high\"}"
echo.

echo.
echo ========================================
echo 6. Testing Admin Service
echo ========================================
echo.

echo Get System Statistics:
curl -s http://localhost:8004/api/admin/stats
echo.

echo.
echo Get Warranty Policies:
curl -s http://localhost:8004/api/policies
echo.

echo.
echo ========================================
echo 7. Testing Notification Service
echo ========================================
echo.

echo Get Notifications:
curl -s http://localhost:8005/api/notifications
echo.

echo.
echo Send Notification:
curl -X POST http://localhost:8005/api/notifications ^
  -H "Content-Type: application/json" ^
  -d "{\"customer_id\":1,\"type\":\"warranty_update\",\"message\":\"Test notification\"}"
echo.

echo.
echo ========================================
echo Testing completed!
echo ========================================
pause