@echo off
echo ========================================
echo Starting EVM Warranty Management System
echo ========================================

echo.
echo 1. Starting Backend Services (Docker)...
docker-compose -f docker-compose-simple.yml up -d

echo.
echo 2. Waiting for services to start...
timeout /t 10 /nobreak

echo.
echo 3. Checking services health...
curl -s http://localhost:8001/api/health
curl -s http://localhost:8002/api/health
curl -s http://localhost:8003/api/health
curl -s http://localhost:8004/api/health
curl -s http://localhost:8005/api/health

echo.
echo 4. Starting Frontend...
cd frontend
start "EVM Frontend" cmd /k "npm run dev"
cd ..

echo.
echo ========================================
echo System Status:
echo ========================================
echo Backend Services: http://localhost:8001-8005
echo Frontend Application: http://localhost:3000
echo ========================================
echo.
echo Login credentials:
echo - Admin: admin@evm.com / admin123
echo - EVM Staff: staff@evm.com / staff123  
echo - Customer: nguyenvana@example.com / password123
echo ========================================

pause