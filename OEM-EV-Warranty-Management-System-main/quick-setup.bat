@echo off
echo ========================================
echo EVM Warranty System - Quick Setup
echo ========================================
echo.

echo 1. Checking Docker...
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker not found! Please install Docker Desktop first.
    pause
    exit /b 1
)
echo ✅ Docker found

echo.
echo 2. Building services...
docker-compose build
if %errorlevel% neq 0 (
    echo ❌ Build failed!
    pause
    exit /b 1
)
echo ✅ Build completed

echo.
echo 3. Starting services...
docker-compose up -d
if %errorlevel% neq 0 (
    echo ❌ Start failed!
    pause
    exit /b 1
)
echo ✅ Services started

echo.
echo 4. Checking service health...
timeout /t 10 >nul
powershell -Command "try { Invoke-WebRequest -Uri 'http://localhost:8001/api/health' -UseBasicParsing | Out-Null; Write-Host '✅ Backend services healthy' } catch { Write-Host '⚠️ Backend services starting...' }"

echo.
echo ========================================
echo 🎉 EVM Warranty System is ready!
echo ========================================
echo.
echo 🌐 Frontend: http://localhost:3000
echo 🚪 API Gateway: http://localhost:8000  
echo 📧 Email UI: http://localhost:8025
echo.
echo Demo Accounts:
echo 👑 Admin: admin@evm.com / admin123
echo 🏭 Staff: staff@evm.com / staff123
echo 🏢 SC Staff: sc-staff@evm.com / sc123
echo 🔧 Technician: tech@evm.com / tech123
echo 👤 Customer: nguyenvana@example.com / password123
echo.
echo Next step: Start frontend with 'cd frontend && npm install && npm run dev'
echo.
pause