@echo off
echo ========================================
echo Starting EVM Frontend (Next.js)
echo ========================================
echo.

cd /d "%~dp0frontend"

echo Current directory: %CD%
echo.

echo Installing/Checking dependencies...
call "C:\Program Files\nodejs\npm.cmd" install

echo.
echo Starting development server...
echo Frontend will be available at: http://localhost:3000
echo.

call "C:\Program Files\nodejs\npm.cmd" run dev

pause
