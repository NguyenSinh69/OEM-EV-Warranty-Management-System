# EVM Warranty Management System Makefile

.PHONY: help setup build up down restart logs clean test

# Colors for output
YELLOW := \033[33m
GREEN := \033[32m
RED := \033[31m
RESET := \033[0m

help: ## Show this help message
	@echo "$(YELLOW)EVM Warranty Management System$(RESET)"
	@echo "================================="
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-20s$(RESET) %s\n", $$1, $$2}'

setup: ## Initial setup - copy env files and create directories
	@echo "$(YELLOW)Setting up EVM Warranty System...$(RESET)"
	@for service in services/customer-service services/warranty-service services/vehicle-service services/admin-service services/notification-service; do \
		if [ -f "$$service/.env.example" ] && [ ! -f "$$service/.env" ]; then \
			cp "$$service/.env.example" "$$service/.env" && \
			echo "$(GREEN)✅ Created .env for $$service$(RESET)"; \
		fi; \
	done
	@mkdir -p logs/{customer-service,warranty-service,vehicle-service,admin-service,notification-service}
	@mkdir -p storage/{customer-service,warranty-service,vehicle-service,admin-service,notification-service}
	@echo "$(GREEN)✅ Setup completed$(RESET)"

build: ## Build all Docker images
	@echo "$(YELLOW)Building Docker images...$(RESET)"
	docker-compose build
	@echo "$(GREEN)✅ Build completed$(RESET)"

up: ## Start all services
	@echo "$(YELLOW)Starting EVM Warranty System...$(RESET)"
	docker-compose up -d
	@echo "$(GREEN)✅ Services started$(RESET)"
	@echo "🌐 API Gateway: http://localhost:8000"
	@echo "👥 Customer Service: http://localhost:8001"
	@echo "🔧 Warranty Service: http://localhost:8002"
	@echo "🚗 Vehicle Service: http://localhost:8003"
	@echo "👑 Admin Service: http://localhost:8004"
	@echo "📱 Notification Service: http://localhost:8005"

down: ## Stop all services
	@echo "$(YELLOW)Stopping all services...$(RESET)"
	docker-compose down
	@echo "$(GREEN)✅ Services stopped$(RESET)"

restart: down up ## Restart all services

logs: ## Show logs for all services
	docker-compose logs -f

logs-customer: ## Show customer service logs
	docker-compose logs -f customer-service

logs-warranty: ## Show warranty service logs
	docker-compose logs -f warranty-service

logs-vehicle: ## Show vehicle service logs
	docker-compose logs -f vehicle-service

logs-admin: ## Show admin service logs
	docker-compose logs -f admin-service

logs-notification: ## Show notification service logs
	docker-compose logs -f notification-service

logs-kong: ## Show Kong gateway logs
	docker-compose logs -f kong

status: ## Check status of all services
	@echo "$(YELLOW)Checking service status...$(RESET)"
	@echo "Service Health Checks:"
	@for port in 8001 8002 8003 8004 8005; do \
		if curl -f -s http://localhost:$$port/api/health >/dev/null 2>&1; then \
			echo "$(GREEN)✅ Port $$port: Healthy$(RESET)"; \
		else \
			echo "$(RED)❌ Port $$port: Not responding$(RESET)"; \
		fi; \
	done
	@echo ""
	@echo "Docker Container Status:"
	@docker-compose ps

clean: ## Clean up containers, volumes, and images
	@echo "$(YELLOW)Cleaning up...$(RESET)"
	docker-compose down -v --remove-orphans
	docker system prune -f
	@echo "$(GREEN)✅ Cleanup completed$(RESET)"

install-deps: ## Install dependencies for all services
	@echo "$(YELLOW)Installing dependencies...$(RESET)"
	@for service in customer-service warranty-service vehicle-service admin-service notification-service; do \
		if [ -f "$$service/composer.json" ]; then \
			echo "Installing dependencies for $$service..."; \
			cd $$service && composer install && cd ..; \
		fi; \
	done
	@echo "$(GREEN)✅ Dependencies installed$(RESET)"

migrate: ## Run database migrations for all services
	@echo "$(YELLOW)Running database migrations...$(RESET)"
	docker-compose exec customer-service php artisan migrate --force
	docker-compose exec warranty-service php artisan migrate --force
	docker-compose exec vehicle-service php artisan migrate --force
	docker-compose exec admin-service php artisan migrate --force
	docker-compose exec notification-service php artisan migrate --force
	@echo "$(GREEN)✅ Migrations completed$(RESET)"

seed: ## Seed databases with sample data
	@echo "$(YELLOW)Seeding databases...$(RESET)"
	docker-compose exec customer-service php artisan db:seed --force
	docker-compose exec warranty-service php artisan db:seed --force
	docker-compose exec vehicle-service php artisan db:seed --force
	docker-compose exec admin-service php artisan db:seed --force
	docker-compose exec notification-service php artisan db:seed --force
	@echo "$(GREEN)✅ Database seeding completed$(RESET)"

test: ## Run tests for all services
	@echo "$(YELLOW)Running tests...$(RESET)"
	@for service in customer-service warranty-service vehicle-service admin-service notification-service; do \
		echo "Running tests for $$service..."; \
		docker-compose exec $$service php artisan test; \
	done
	@echo "$(GREEN)✅ Tests completed$(RESET)"

shell-customer: ## Access customer service shell
	docker-compose exec customer-service bash

shell-warranty: ## Access warranty service shell
	docker-compose exec warranty-service bash

shell-vehicle: ## Access vehicle service shell
	docker-compose exec vehicle-service bash

shell-admin: ## Access admin service shell
	docker-compose exec admin-service bash

shell-notification: ## Access notification service shell
	docker-compose exec notification-service bash

db-customer: ## Access customer database
	docker-compose exec customer-db mysql -u evm_user -pevm_password evm_customer_db

db-warranty: ## Access warranty database
	docker-compose exec warranty-db mysql -u evm_user -pevm_password evm_warranty_db

db-vehicle: ## Access vehicle database
	docker-compose exec vehicle-db mysql -u evm_user -pevm_password evm_vehicle_db

kong-config: ## Reload Kong configuration
	docker-compose exec kong kong reload

dev: setup build up migrate seed ## Full development setup