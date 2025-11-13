@echo off
echo ========================================
echo  EV Warranty Management - Docker Setup
echo ========================================
echo.

echo [1/4] Checking Docker...
docker --version
if %errorlevel% neq 0 (
    echo ERROR: Docker not found! Please install Docker Desktop.
    pause
    exit /b 1
)

echo.
echo [2/4] Stopping existing containers...
docker-compose -f docker-compose.simple.yml down 2>nul

echo.
echo [3/4] Building and starting containers...
echo This may take a few minutes on first run...
docker-compose -f docker-compose.simple.yml up -d --build

if %errorlevel% neq 0 (
    echo.
    echo ERROR: Failed to start containers!
    echo Please check DOCKER_TROUBLESHOOTING.md for solutions.
    echo.
    echo Common fix: Restart Docker Desktop and try again.
    pause
    exit /b 1
)

echo.
echo [4/4] Waiting for services to start...
timeout /t 10 /nobreak >nul

echo.
echo ========================================
echo  SUCCESS! Containers are running!
echo ========================================
echo.
echo Services:
echo   - API:      http://localhost:8080/api/warranty-claims
echo   - MySQL:    localhost:3307
echo   - Test UI:  http://localhost:8080/../test-api.html
echo.
echo Check status: docker ps
echo View logs:    docker logs warranty_api_simple -f
echo Stop:         docker-compose -f docker-compose.simple.yml down
echo.
pause
