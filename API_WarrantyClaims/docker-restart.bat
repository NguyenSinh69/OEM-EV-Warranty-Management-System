@echo off
echo ========================================
echo  Restart Docker Desktop
echo ========================================
echo.

echo [1/3] Stopping Docker Desktop...
taskkill /IM "Docker Desktop.exe" /F 2>nul
timeout /t 3 /nobreak >nul

echo.
echo [2/3] Waiting for Docker to stop completely...
timeout /t 5 /nobreak >nul

echo.
echo [3/3] Starting Docker Desktop...
start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"

echo.
echo Waiting for Docker to start (30 seconds)...
timeout /t 30 /nobreak

echo.
echo Done! Docker Desktop should be restarting now.
echo Wait until you see "Docker Desktop is running" notification.
echo.
pause
