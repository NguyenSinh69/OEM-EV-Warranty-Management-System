@echo off
echo Starting OEM EV Warranty Management System - Admin Service Test

echo.
echo ========================================
echo TICKET 2.1 - ADMIN SYSTEM SETUP
echo ========================================
echo.

echo [1/5] Creating test database structure...
cd services\admin-service

echo [2/5] Setting up PHP environment...
if not exist "database" mkdir database
copy "..\..\database\schema.sql" "database\schema.sql"

echo [3/5] Starting local PHP server for admin service...
echo Admin Service will run on: http://localhost:8004
echo.
echo Admin Login Credentials:
echo Email: admin@evm.com
echo Password: admin123
echo.
echo [4/5] Testing API endpoints...
echo Available endpoints:
echo - POST /api/login
echo - GET /api/dashboard/summary  
echo - GET /api/users
echo - GET /api/roles
echo - GET /api/analytics/*
echo.

echo [5/5] Starting admin service...
echo You can test the admin APIs using:
echo curl -X POST http://localhost:8004/api/login -H "Content-Type: application/json" -d "{\"username\":\"admin\",\"password\":\"admin123\"}"
echo.

pause
echo.

cd public
php -S 0.0.0.0:8004 index.php

echo.
echo Admin service stopped.
pause