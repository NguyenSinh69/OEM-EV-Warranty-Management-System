@echo off
REM Backend API Testing Script for Person 1
REM Tests all Customer Portal and SC Staff APIs

echo =====================================
echo Backend API Testing - Person 1
echo =====================================
echo.

REM Set base URLs
set CUSTOMER_URL=http://localhost:8001
set VEHICLE_URL=http://localhost:8003
set UPLOAD_URL=http://localhost:8006

echo [1/12] Testing Customer Service Health...
curl -s %CUSTOMER_URL%/api/health
echo.
echo.

echo [2/12] Testing Vehicle Service (SC Staff) Health...
curl -s %VEHICLE_URL%/api/sc-staff/health
echo.
echo.

echo [3/12] Testing File Upload Service Health...
curl -s %UPLOAD_URL%/api/upload/health
echo.
echo.

echo [4/12] Testing Login...
curl -s -X POST %CUSTOMER_URL%/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"nguyenvana@example.com\",\"password\":\"password123\"}" ^
  -o login_response.json
echo.
type login_response.json
echo.
echo.

REM Extract token (simplified - in real scenario use jq or similar)
echo [5/12] Testing Get Customer Vehicles...
curl -s %CUSTOMER_URL%/api/customer/vehicles ^
  -H "Authorization: Bearer mock_token_12345"
echo.
echo.

echo [6/12] Testing Get Customer Claims...
curl -s %CUSTOMER_URL%/api/customer/claims ^
  -H "Authorization: Bearer mock_token_12345"
echo.
echo.

echo [7/12] Testing Get Customer Appointments...
curl -s %CUSTOMER_URL%/api/customer/appointments ^
  -H "Authorization: Bearer mock_token_12345"
echo.
echo.

echo [8/12] Testing Get Customer Notifications...
curl -s %CUSTOMER_URL%/api/customer/notifications ^
  -H "Authorization: Bearer mock_token_12345"
echo.
echo.

echo [9/12] Testing SC Staff Dashboard Stats...
curl -s %VEHICLE_URL%/api/sc-staff/dashboard/stats
echo.
echo.

echo [10/12] Testing SC Staff Search Vehicles...
curl -s "%VEHICLE_URL%/api/sc-staff/vehicles/search?q=VF3&type=vin"
echo.
echo.

echo [11/12] Testing SC Staff Get Reference Data...
curl -s %VEHICLE_URL%/api/sc-staff/reference-data
echo.
echo.

echo [12/12] Testing SC Staff Get Warranty Claims...
curl -s %VEHICLE_URL%/api/sc-staff/warranty-claims
echo.
echo.

REM Cleanup
if exist login_response.json del login_response.json

echo =====================================
echo Testing Complete!
echo =====================================
echo.
echo Summary:
echo - Customer Service (8001): Health, Login, Vehicles, Claims, Appointments, Notifications
echo - Vehicle Service (8003): Health, Dashboard, Search, Reference Data, Claims
echo - Upload Service (8006): Health
echo.
echo Next steps:
echo 1. Start backend services: docker-compose up -d
echo 2. Run this test script again
echo 3. Check frontend integration at http://localhost:3001
echo.
pause
