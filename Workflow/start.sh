#!/bin/bash

echo "🚀 Starting OEM EV Warranty Management System..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Stop existing containers
echo "📦 Stopping existing containers..."
docker-compose down

# Build and start containers
echo "🔨 Building and starting containers..."
docker-compose up -d --build

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 30

# Install dependencies
echo "📚 Installing PHP dependencies..."
docker-compose exec -T php composer install --no-interaction

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T php php artisan key:generate --force

# Run migrations
echo "🗄️ Running database migrations..."
docker-compose exec -T php php artisan migrate --force

# Create storage symlink
echo "🔗 Creating storage symlink..."
docker-compose exec -T php php artisan storage:link

# Seed sample data (optional)
echo "🌱 Creating sample data..."
docker-compose exec -T php php artisan db:seed --force

echo ""
echo "✅ Setup completed successfully!"
echo ""
echo "🌐 Access the application:"
echo "   - Web Interface: http://localhost:8080"
echo "   - API Documentation: http://localhost:8080/api"
echo "   - phpMyAdmin: http://localhost:8081"
echo ""
echo "📊 Database credentials:"
echo "   - Host: localhost:3306"
echo "   - Database: warranty_db"
echo "   - Username: warranty_user"
echo "   - Password: warranty_password"
echo ""
echo "🔧 Useful commands:"
echo "   - View logs: docker-compose logs -f"
echo "   - Stop system: docker-compose down"
echo "   - Restart: docker-compose restart"
echo ""