@echo off
REM EVM Warranty Management System - Windows Setup Script

echo 🚗 EVM Warranty Management System Setup
echo =======================================

REM Check if Docker is installed
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not installed. Please install Docker Desktop first.
    pause
    exit /b 1
)

docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker Compose is not installed. Please install Docker Compose first.
    pause
    exit /b 1
)

echo ✅ Prerequisites check passed

REM Copy environment files
echo 📄 Setting up environment files...

for %%s in (customer-service warranty-service vehicle-service admin-service notification-service) do (
    if exist "%%s\.env.example" (
        if not exist "%%s\.env" (
            copy "%%s\.env.example" "%%s\.env" >nul
            echo ✅ Created .env file for %%s
        ) else (
            echo ℹ️  .env file already exists for %%s
        )
    )
)

REM Create necessary directories
echo 📁 Creating necessary directories...
if not exist "logs" mkdir logs
if not exist "logs\customer-service" mkdir logs\customer-service
if not exist "logs\warranty-service" mkdir logs\warranty-service
if not exist "logs\vehicle-service" mkdir logs\vehicle-service
if not exist "logs\admin-service" mkdir logs\admin-service
if not exist "logs\notification-service" mkdir logs\notification-service

if not exist "storage" mkdir storage
if not exist "storage\customer-service" mkdir storage\customer-service
if not exist "storage\warranty-service" mkdir storage\warranty-service
if not exist "storage\vehicle-service" mkdir storage\vehicle-service
if not exist "storage\admin-service" mkdir storage\admin-service
if not exist "storage\notification-service" mkdir storage\notification-service

REM Build and start services
echo 🐳 Building and starting Docker containers...
docker-compose up -d --build

REM Wait for services to be ready
echo ⏳ Waiting for services to be ready...
timeout /t 30 /nobreak >nul

echo.
echo 🎉 EVM Warranty Management System setup complete!
echo.
echo Services are available at:
echo 🌐 API Gateway (Kong^): http://localhost:8000
echo 👥 Customer Service: http://localhost:8001
echo 🔧 Warranty Service: http://localhost:8002
echo 🚗 Vehicle Service: http://localhost:8003
echo 👑 Admin Service: http://localhost:8004
echo 📱 Notification Service: http://localhost:8005
echo 📧 Mailpit (Email testing^): http://localhost:8025
echo.
echo 📚 API Documentation:
echo    GET /api/health - Health check
echo    POST /api/auth/login - Login
echo    GET /api/customers - List customers
echo    GET /api/warranties - List warranty claims
echo    GET /api/vehicles - List vehicles
echo.
echo 🛠️  To stop all services: docker-compose down
echo 🔍 To view logs: docker-compose logs -f [service-name]
echo 🗄️  To access databases: Use ports 3306-3310
echo.
pause