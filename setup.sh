#!/bin/bash

# EVM Warranty Management System - Setup Script

echo "🚗 EVM Warranty Management System Setup"
echo "======================================="

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check prerequisites
echo "📋 Checking prerequisites..."

if ! command_exists docker; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command_exists docker-compose; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✅ Prerequisites check passed"

# Copy environment files
echo "📄 Setting up environment files..."

services=("customer-service" "warranty-service" "vehicle-service" "admin-service" "notification-service")

for service in "${services[@]}"; do
    if [ -f "$service/.env.example" ]; then
        if [ ! -f "$service/.env" ]; then
            cp "$service/.env.example" "$service/.env"
            echo "✅ Created .env file for $service"
        else
            echo "ℹ️  .env file already exists for $service"
        fi
    fi
done

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p logs/{customer-service,warranty-service,vehicle-service,admin-service,notification-service}
mkdir -p storage/{customer-service,warranty-service,vehicle-service,admin-service,notification-service}

# Generate JWT secrets
echo "🔐 Generating JWT secrets..."
JWT_SECRET=$(openssl rand -base64 32)
echo "Generated JWT Secret: $JWT_SECRET"
echo "Please update your .env files with this JWT secret"

# Build and start services
echo "🐳 Building and starting Docker containers..."
docker-compose up -d --build

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 30

# Check service health
echo "🏥 Checking service health..."

services_ports=("8001:customer-service" "8002:warranty-service" "8003:vehicle-service" "8004:admin-service" "8005:notification-service")

for service_port in "${services_ports[@]}"; do
    port="${service_port%%:*}"
    service_name="${service_port##*:}"
    
    if curl -f http://localhost:$port/api/health >/dev/null 2>&1; then
        echo "✅ $service_name is healthy (port $port)"
    else
        echo "⚠️  $service_name might not be ready yet (port $port)"
    fi
done

# Check Kong Gateway
if curl -f http://localhost:8000 >/dev/null 2>&1; then
    echo "✅ Kong Gateway is running (port 8000)"
else
    echo "⚠️  Kong Gateway might not be ready yet"
fi

echo ""
echo "🎉 EVM Warranty Management System setup complete!"
echo ""
echo "Services are available at:"
echo "🌐 API Gateway (Kong): http://localhost:8000"
echo "👥 Customer Service: http://localhost:8001"
echo "🔧 Warranty Service: http://localhost:8002"
echo "🚗 Vehicle Service: http://localhost:8003"
echo "👑 Admin Service: http://localhost:8004"
echo "📱 Notification Service: http://localhost:8005"
echo "📧 Mailpit (Email testing): http://localhost:8025"
echo ""
echo "📚 API Documentation:"
echo "   GET /api/health - Health check"
echo "   POST /api/auth/login - Login"
echo "   GET /api/customers - List customers"
echo "   GET /api/warranties - List warranty claims"
echo "   GET /api/vehicles - List vehicles"
echo ""
echo "🛠️  To stop all services: docker-compose down"
echo "🔍 To view logs: docker-compose logs -f [service-name]"
echo "🗄️  To access databases: Use ports 3306-3310"