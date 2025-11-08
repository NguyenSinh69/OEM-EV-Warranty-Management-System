@echo off
echo ========================================
echo Stopping EVM Warranty Management System
echo ========================================

echo.
echo 1. Stopping Docker services...
docker-compose -f docker-compose-simple.yml down

echo.
echo 2. Stopping frontend (if running)...
taskkill /f /im node.exe 2>nul

echo.
echo ========================================
echo System stopped successfully!
echo ========================================

pause