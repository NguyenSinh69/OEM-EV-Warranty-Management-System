#!/bin/bash

# Database Setup Script for Vehicle Service
# This script creates the database and imports the schema

DB_NAME="vehicle_service_db"
DB_USER="root"
DB_PASS=""
DB_HOST="localhost"

echo "🗄️  Setting up Vehicle Service Database"
echo "====================================="

# Check if MySQL is running
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL is not installed or not in PATH"
    exit 1
fi

# Test MySQL connection
if ! mysql -h$DB_HOST -u$DB_USER ${DB_PASS:+-p$DB_PASS} -e "SELECT 1;" &> /dev/null; then
    echo "❌ Cannot connect to MySQL. Please check credentials."
    exit 1
fi

echo "✅ MySQL connection successful"

# Create database
echo "📦 Creating database '$DB_NAME'..."
mysql -h$DB_HOST -u$DB_USER ${DB_PASS:+-p$DB_PASS} -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✅ Database '$DB_NAME' created successfully"
else
    echo "❌ Failed to create database"
    exit 1
fi

# Import schema
if [ -f "database/schema.sql" ]; then
    echo "📋 Importing database schema..."
    mysql -h$DB_HOST -u$DB_USER ${DB_PASS:+-p$DB_PASS} $DB_NAME < database/schema.sql
    
    if [ $? -eq 0 ]; then
        echo "✅ Schema imported successfully"
    else
        echo "❌ Failed to import schema"
        exit 1
    fi
else
    echo "❌ Schema file 'database/schema.sql' not found"
    exit 1
fi

# Verify tables
echo "🔍 Verifying tables..."
table_count=$(mysql -h$DB_HOST -u$DB_USER ${DB_PASS:+-p$DB_PASS} $DB_NAME -e "SHOW TABLES;" | wc -l)
table_count=$((table_count - 1)) # Subtract header

echo "📊 Found $table_count tables:"
mysql -h$DB_HOST -u$DB_USER ${DB_PASS:+-p$DB_PASS} $DB_NAME -e "SHOW TABLES;"

# Check sample data
echo ""
echo "📊 Sample data counts:"
mysql -h$DB_HOST -u$DB_USER ${DB_PASS:+-p$DB_PASS} $DB_NAME -e "
SELECT 
    'ev_components' as table_name, 
    COUNT(*) as record_count 
FROM ev_components
UNION ALL
SELECT 
    'warranty_policies' as table_name, 
    COUNT(*) as record_count 
FROM warranty_policies  
UNION ALL
SELECT 
    'campaigns' as table_name, 
    COUNT(*) as record_count 
FROM campaigns
UNION ALL
SELECT 
    'campaign_progress' as table_name, 
    COUNT(*) as record_count 
FROM campaign_progress;
"

echo ""
echo "🎉 Database setup completed successfully!"
echo ""
echo "Database Details:"
echo "- Host: $DB_HOST"
echo "- Database: $DB_NAME"
echo "- User: $DB_USER"
echo ""
echo "Next steps:"
echo "1. Start the PHP server: php -S localhost:8081 -t public/"
echo "2. Test APIs: ./test_apis.sh"
echo "3. Health check: curl http://localhost:8081/api/health"