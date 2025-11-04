@echo off
echo 🚀 Starting OEM EV Warranty Management System...

REM Check if Docker is running
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not running. Please start Docker first.
    pause
    exit /b 1
)

REM Stop existing containers
echo 📦 Stopping existing containers...
docker-compose down

REM Build and start containers
echo 🔨 Building and starting containers...
docker-compose up -d --build

REM Wait for MySQL to be ready
echo ⏳ Waiting for MySQL to be ready...
timeout /t 30 /nobreak

REM Install dependencies
echo 📚 Installing PHP dependencies...
docker-compose exec -T php composer install --no-interaction

REM Generate application key
echo 🔑 Generating application key...
docker-compose exec -T php php artisan key:generate --force

REM Run migrations
echo 🗄️ Running database migrations...
docker-compose exec -T php php artisan migrate --force

REM Create storage symlink
echo 🔗 Creating storage symlink...
docker-compose exec -T php php artisan storage:link

echo.
echo ✅ Setup completed successfully!
echo.
echo 🌐 Access the application:
echo    - Web Interface: http://localhost:8080
echo    - API Documentation: http://localhost:8080/api
echo    - phpMyAdmin: http://localhost:8081
echo.
echo 📊 Database credentials:
echo    - Host: localhost:3306
echo    - Database: warranty_db
echo    - Username: warranty_user
echo    - Password: warranty_password
echo.
echo 🔧 Useful commands:
echo    - View logs: docker-compose logs -f
echo    - Stop system: docker-compose down
echo    - Restart: docker-compose restart
echo.
pause