# Backend Deployment Guide - Person 1

## Quick Start

### 1. Start Backend Services

```bash
# Start all services with Docker
docker-compose up -d

# Or start specific services
docker-compose up -d customer-service vehicle-service file-upload-service
```

### 2. Verify Services

```bash
# Check if all containers are running
docker-compose ps

# Check logs
docker-compose logs -f customer-service
docker-compose logs -f file-upload-service
```

### 3. Test APIs

```bash
# Windows
test-person1-apis.bat

# Linux/Mac
chmod +x test-person1-apis.sh
./test-person1-apis.sh
```

---

## Service Ports

| Service | Port | Endpoint |
|---------|------|----------|
| Customer Service | 8001 | http://localhost:8001/api |
| Vehicle Service | 8003 | http://localhost:8003/api/sc-staff |
| File Upload Service | 8006 | http://localhost:8006/api/upload |

---

## Environment Configuration

### Customer Service

Create `.env` in `services/customer-service/`:

```env
DB_HOST=customer-db
DB_PORT=3306
DB_DATABASE=evm_customer_db
DB_USERNAME=evm_user
DB_PASSWORD=evm_password

JWT_SECRET=your-secret-key-here
JWT_EXPIRATION=3600
```

### File Upload Service

No special configuration needed. Files are stored in:
```
services/file-upload-service/uploads/
├── claims/
├── vehicles/
└── temp/
```

---

## Database Setup

### Initialize Databases

The databases are automatically created by Docker, but you can initialize them manually:

```bash
# Connect to customer database
docker exec -it <customer-db-container> mysql -u root -p

# Run migrations (if needed)
docker exec -it customer-service php artisan migrate
```

### Mock Data

Currently, all services use **mock data** hardcoded in PHP. To use real database:

1. Update SQL queries in service files
2. Create database tables
3. Seed initial data
4. Remove mock data arrays

---

## Troubleshooting

### Port Already in Use

If ports 8001, 8003, or 8006 are in use:

```bash
# Windows - Check what's using the port
netstat -ano | findstr :8001

# Kill the process
taskkill /PID <PID> /F

# Or change port in docker-compose.yml
```

### CORS Issues

If you get CORS errors, ensure `.htaccess` files exist in `public/` directories:

```apache
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
```

### Database Connection Failed

```bash
# Check if database container is running
docker-compose ps

# Restart database
docker-compose restart customer-db

# Check database logs
docker-compose logs customer-db
```

### File Upload Fails

```bash
# Check uploads directory permissions
docker exec -it file-upload-service ls -la /var/www/uploads

# Fix permissions
docker exec -it file-upload-service chmod -R 755 /var/www/uploads
docker exec -it file-upload-service chown -R www-data:www-data /var/www/uploads
```

---

## API Testing

### Using cURL

#### Test Customer Login
```bash
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "nguyenvana@example.com", "password": "password123"}'
```

#### Test Get Vehicles (with auth)
```bash
TOKEN="your_token_here"
curl http://localhost:8001/api/customer/vehicles \
  -H "Authorization: Bearer $TOKEN"
```

#### Test File Upload
```bash
curl -X POST http://localhost:8006/api/upload/file \
  -F "file=@/path/to/image.jpg" \
  -F "category=claims"
```

### Using Postman

1. Import the Postman collection: `postman/Person1-APIs.json`
2. Set environment variables:
   - `base_url`: http://localhost
   - `token`: (get from login response)
3. Run collection tests

### Using Frontend

1. Start frontend: `cd frontend && npm run dev`
2. Navigate to: http://localhost:3001/customer
3. Use the UI to test all features:
   - View vehicles
   - Create warranty claims
   - Book appointments
   - Check notifications

---

## Monitoring

### View Real-time Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f customer-service

# Last 100 lines
docker-compose logs --tail=100 file-upload-service
```

### Check Service Health

```bash
# Customer Service
curl http://localhost:8001/api/health

# Vehicle Service
curl http://localhost:8003/api/sc-staff/health

# Upload Service
curl http://localhost:8006/api/upload/health
```

### Database Monitoring

```bash
# Connect to database
docker exec -it customer-db mysql -u root -p

# Show databases
SHOW DATABASES;

# Use database
USE evm_customer_db;

# Show tables
SHOW TABLES;

# Check data
SELECT * FROM customers LIMIT 10;
```

---

## Stopping Services

```bash
# Stop all services
docker-compose down

# Stop and remove volumes (WARNING: deletes data)
docker-compose down -v

# Stop specific service
docker-compose stop customer-service
```

---

## Rebuilding Services

After code changes:

```bash
# Rebuild all services
docker-compose build

# Rebuild specific service
docker-compose build customer-service

# Rebuild and restart
docker-compose up -d --build customer-service
```

---

## Production Deployment

### Security Checklist

- [ ] Change all default passwords
- [ ] Set strong JWT secret
- [ ] Enable HTTPS
- [ ] Configure proper CORS origins
- [ ] Add rate limiting
- [ ] Implement proper authentication
- [ ] Add input validation
- [ ] Enable error logging
- [ ] Set up monitoring/alerts
- [ ] Regular backups
- [ ] Security headers

### Environment Variables

Set these in production `.env`:

```env
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=<strong-random-secret>
DB_PASSWORD=<strong-password>
CORS_ALLOWED_ORIGINS=https://yourdomain.com
```

### Database Optimization

```sql
-- Add indexes
CREATE INDEX idx_vehicles_vin ON vehicles(vin);
CREATE INDEX idx_claims_status ON warranty_claims(status);
CREATE INDEX idx_claims_customer ON warranty_claims(customer_id);

-- Optimize tables
OPTIMIZE TABLE vehicles;
OPTIMIZE TABLE warranty_claims;
```

---

## Performance Tuning

### PHP Configuration

In `Dockerfile`, add:

```dockerfile
# Increase memory limit
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini

# Increase upload size
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/custom.ini
RUN echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/custom.ini
```

### MySQL Configuration

```sql
-- Increase connection limit
SET GLOBAL max_connections = 200;

-- Enable query cache
SET GLOBAL query_cache_size = 67108864;
```

### Apache Configuration

Enable compression and caching:

```apache
# Enable gzip compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Enable browser caching
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
</IfModule>
```

---

## Backup & Restore

### Backup Database

```bash
# Backup customer database
docker exec customer-db mysqldump -u root -p evm_customer_db > backup_customer_$(date +%Y%m%d).sql

# Backup all databases
docker exec customer-db mysqldump -u root -p --all-databases > backup_all_$(date +%Y%m%d).sql
```

### Backup Uploaded Files

```bash
# Backup uploads directory
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz services/file-upload-service/uploads/
```

### Restore Database

```bash
# Restore from backup
docker exec -i customer-db mysql -u root -p evm_customer_db < backup_customer_20241112.sql
```

---

## Support

**Created by:** Person 1 (Frontend Lead)  
**Documentation:** BACKEND_API_DOCS.md  
**Test Script:** test-person1-apis.bat  
**Date:** November 12, 2024
