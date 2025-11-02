#!/bin/bash

# Vehicle Service API Test Script
# Test all 7 required API endpoints

BASE_URL="http://localhost:8081"
API_BASE="$BASE_URL/api"

echo "🚗 Testing Vehicle Service APIs"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test function
test_endpoint() {
    local name="$1"
    local method="$2"
    local url="$3"
    local data="$4"
    
    echo -e "\n${BLUE}Testing: $name${NC}"
    echo "Method: $method"
    echo "URL: $url"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "%{http_code}" -X GET "$url")
    else
        response=$(curl -s -w "%{http_code}" -X "$method" "$url" \
            -H "Content-Type: application/json" \
            -d "$data")
    fi
    
    http_code="${response: -3}"
    body="${response%???}"
    
    if [ "$http_code" -ge 200 ] && [ "$http_code" -lt 300 ]; then
        echo -e "${GREEN}✓ Success (HTTP $http_code)${NC}"
        echo "$body" | python3 -m json.tool 2>/dev/null || echo "$body"
    else
        echo -e "${RED}✗ Failed (HTTP $http_code)${NC}"
        echo "$body"
    fi
}

# 1. Health Check
echo -e "${YELLOW}1. Health Check${NC}"
test_endpoint "Health Check" "GET" "$API_BASE/health"

# 2. POST /api/components - Thêm linh kiện EV mới
echo -e "\n${YELLOW}2. POST /api/components - Thêm linh kiện EV mới${NC}"
component_data='{
    "component_type": "battery",
    "component_name": "Test Lithium Battery",
    "model": "TEST-LIB-2024",
    "specifications": {
        "capacity": "75kWh",
        "voltage": "400V",
        "cells": 300,
        "chemistry": "LiFePO4"
    },
    "warranty_period": 84,
    "supplier_id": 1,
    "status": "active"
}'
test_endpoint "Create Component" "POST" "$API_BASE/components" "$component_data"

# 3. GET /api/components - Danh sách linh kiện
echo -e "\n${YELLOW}3. GET /api/components - Danh sách linh kiện${NC}"
test_endpoint "Get All Components" "GET" "$API_BASE/components"

# Test with filters
test_endpoint "Get Components by Type" "GET" "$API_BASE/components?component_type=battery"

# 4. POST /api/warranty-policies - Tạo chính sách bảo hành
echo -e "\n${YELLOW}4. POST /api/warranty-policies - Tạo chính sách bảo hành${NC}"
policy_data='{
    "component_id": 1,
    "policy_name": "Test Battery Warranty Policy",
    "warranty_duration": 84,
    "coverage_details": {
        "coverage": ["capacity_degradation", "manufacturing_defects"],
        "degradation_limit": "75%"
    },
    "conditions": {
        "usage": "normal_driving",
        "temperature": "-15C_to_55C"
    },
    "exclusions": {
        "misuse": ["overcharging", "physical_damage"]
    },
    "effective_date": "2024-10-26",
    "status": "active"
}'
test_endpoint "Create Warranty Policy" "POST" "$API_BASE/warranty-policies" "$policy_data"

# Get warranty policies
test_endpoint "Get All Warranty Policies" "GET" "$API_BASE/warranty-policies"

# 5. POST /api/campaigns - Tạo chiến dịch recall
echo -e "\n${YELLOW}5. POST /api/campaigns - Tạo chiến dịch recall${NC}"
campaign_data='{
    "title": "Test Battery Inspection Campaign",
    "description": "Inspection campaign for testing battery performance",
    "campaign_type": "service_campaign",
    "affected_models": ["Model-X-2024", "Model-Y-2024"],
    "affected_components": [1],
    "priority_level": "medium",
    "start_date": "2024-11-01",
    "end_date": "2024-12-31",
    "instructions": "Perform battery diagnostic test and update firmware if needed. Estimated time: 1 hour.",
    "status": "active"
}'
test_endpoint "Create Campaign" "POST" "$API_BASE/campaigns" "$campaign_data"

# Get campaigns
test_endpoint "Get All Campaigns" "GET" "$API_BASE/campaigns"

# 6. GET /api/campaigns/{id}/vehicles - Xe bị ảnh hưởng
echo -e "\n${YELLOW}6. GET /api/campaigns/{id}/vehicles - Xe bị ảnh hưởng${NC}"
test_endpoint "Get Affected Vehicles" "GET" "$API_BASE/campaigns/1/vehicles"

# 7. POST /api/campaigns/{id}/notify - Gửi thông báo
echo -e "\n${YELLOW}7. POST /api/campaigns/{id}/notify - Gửi thông báo${NC}"
test_endpoint "Send Campaign Notifications" "POST" "$API_BASE/campaigns/1/notify" "{}"

# 8. GET /api/campaigns/{id}/progress - Tiến độ campaign  
echo -e "\n${YELLOW}8. GET /api/campaigns/{id}/progress - Tiến độ campaign${NC}"
test_endpoint "Get Campaign Progress" "GET" "$API_BASE/campaigns/1/progress"

# Additional tests
echo -e "\n${YELLOW}Additional Tests${NC}"
echo "=================="

# Test specific component
test_endpoint "Get Component by ID" "GET" "$API_BASE/components/1"

# Test specific campaign
test_endpoint "Get Campaign by ID" "GET" "$API_BASE/campaigns/1"

# Test filters
test_endpoint "Get Active Campaigns" "GET" "$API_BASE/campaigns?status=active"

echo -e "\n${BLUE}🏁 Test completed!${NC}"
echo ""
echo "Summary of tested endpoints:"
echo "1. ✓ POST /api/components - Thêm linh kiện EV mới"
echo "2. ✓ GET /api/components - Danh sách linh kiện"  
echo "3. ✓ POST /api/warranty-policies - Tạo chính sách bảo hành"
echo "4. ✓ POST /api/campaigns - Tạo chiến dịch recall"
echo "5. ✓ GET /api/campaigns/{id}/vehicles - Xe bị ảnh hưởng"
echo "6. ✓ POST /api/campaigns/{id}/notify - Gửi thông báo"
echo "7. ✓ GET /api/campaigns/{id}/progress - Tiến độ campaign"