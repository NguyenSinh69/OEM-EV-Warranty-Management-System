<?php
/*
===============================================
OEM EV Warranty Management System
EVM Staff Portal API - Manufacturer Staff Functions
===============================================
Functions for EVM Staff (Manufacturer):
- Review and approve warranty claims
- Manage parts inventory and supply chain
- Create and manage recall campaigns
- Parts allocation to service centers
- Cost management and financial tracking
- Supply chain optimization
===============================================
*/

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'oem_warranty_db';
$username = 'root';
$password = 'password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Get request URI and method
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove base path and get route
$route = str_replace('/api/evm-staff', '', $request_uri);

// Authentication (Basic implementation - should be JWT in production)
function getCurrentUser() {
    // For demo purposes, return sample EVM Staff user
    return [
        'id' => 2,
        'username' => 'evm.manager',
        'role' => 'evm_staff',
        'service_center_id' => null,
        'full_name' => 'Nguyễn Văn Manager'
    ];
}

// Helper function to validate required fields
function validateRequired($data, $required_fields) {
    $missing = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    return $missing;
}

// ===============================================
// ROUTE: Health Check
// ===============================================
if ($route === '/health' && $request_method === 'GET') {
    echo json_encode([
        'status' => 'healthy',
        'service' => 'EVM Staff Portal API',
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => getCurrentUser()
    ]);
    exit();
}

// ===============================================
// ROUTE: Dashboard - EVM Overview
// ===============================================
if ($route === '/dashboard/overview' && $request_method === 'GET') {
    try {
        // Warranty claims statistics
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) as count, AVG(estimated_cost) as avg_cost
            FROM warranty_claims 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY status
        ");
        $stmt->execute();
        $claims_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parts inventory alerts (low stock)
        $stmt = $pdo->prepare("
            SELECT p.name, p.part_number, pc.name as category_name,
                   pi.quantity_available, pi.minimum_stock_level,
                   sc.name as service_center_name
            FROM parts_inventory pi
            JOIN parts p ON pi.part_id = p.id
            JOIN parts_categories pc ON p.category_id = pc.id
            LEFT JOIN service_centers sc ON pi.service_center_id = sc.id
            WHERE pi.quantity_available <= pi.minimum_stock_level
            ORDER BY pc.critical_part DESC, pi.quantity_available ASC
        ");
        $stmt->execute();
        $low_stock_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Active campaigns
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(cv.id) as affected_vehicles,
                   COUNT(CASE WHEN cv.status = 'completed' THEN 1 END) as completed_vehicles
            FROM campaigns c
            LEFT JOIN campaign_vehicles cv ON c.id = cv.campaign_id
            WHERE c.status = 'active'
            GROUP BY c.id
            ORDER BY c.start_date DESC
        ");
        $stmt->execute();
        $active_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Financial summary
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN status IN ('approved', 'in_progress', 'completed') THEN approved_cost END) as approved_costs,
                SUM(CASE WHEN status = 'completed' THEN actual_cost END) as actual_costs,
                COUNT(CASE WHEN status = 'under_review' THEN 1 END) as pending_approvals
            FROM warranty_claims
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $financial_summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'claims_stats' => $claims_stats,
                'low_stock_alerts' => $low_stock_alerts,
                'active_campaigns' => $active_campaigns,
                'financial_summary' => $financial_summary
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch dashboard overview: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Get Warranty Claims for Review
// ===============================================
if ($route === '/warranty-claims/pending-review' && $request_method === 'GET') {
    try {
        $stmt = $pdo->prepare("
            SELECT wc.*, v.vin, v.license_plate,
                   vm.name as model_name,
                   c.name as customer_name,
                   sc.name as service_center_name,
                   staff.full_name as created_by_name,
                   CASE WHEN vp.id IS NOT NULL THEN 
                       CONCAT(p.name, ' (', p.part_number, ')')
                   ELSE 'General Issue' END as affected_component
            FROM warranty_claims wc
            JOIN vehicles v ON wc.vehicle_id = v.id
            JOIN vehicle_models vm ON v.model_id = vm.id
            JOIN customers c ON v.customer_id = c.id
            JOIN service_centers sc ON wc.service_center_id = sc.id
            JOIN users staff ON wc.created_by_user_id = staff.id
            LEFT JOIN vehicle_parts vp ON wc.vehicle_part_id = vp.id
            LEFT JOIN parts p ON vp.part_id = p.id
            WHERE wc.status = 'under_review'
            ORDER BY wc.priority DESC, wc.created_at ASC
            LIMIT 50
        ");
        $stmt->execute();
        $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $claims,
            'count' => count($claims)
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch pending claims: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Approve/Reject Warranty Claim
// ===============================================
if (preg_match('/^\/warranty-claims\/(\d+)\/review$/', $route, $matches) && $request_method === 'PUT') {
    $claim_id = $matches[1];
    $user = getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['decision', 'review_notes'];
    $missing = validateRequired($input, $required);
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit();
    }
    
    if (!in_array($input['decision'], ['approved', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Decision must be "approved" or "rejected"']);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get claim details
        $stmt = $pdo->prepare("SELECT vehicle_id, estimated_cost FROM warranty_claims WHERE id = ? AND status = 'under_review'");
        $stmt->execute([$claim_id]);
        $claim = $stmt->fetch();
        
        if (!$claim) {
            throw new Exception("Claim not found or not in review status");
        }
        
        // Update claim
        $new_status = $input['decision'] === 'approved' ? 'approved' : 'rejected';
        $approved_cost = $input['decision'] === 'approved' ? ($input['approved_cost'] ?? $claim['estimated_cost']) : null;
        
        $stmt = $pdo->prepare("
            UPDATE warranty_claims 
            SET status = ?, reviewed_by_user_id = ?, review_date = NOW(), 
                review_notes = ?, approved_cost = ?, rejection_reason = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $new_status,
            $user['id'],
            $input['review_notes'],
            $approved_cost,
            $input['decision'] === 'rejected' ? $input['rejection_reason'] ?? $input['review_notes'] : null,
            $claim_id
        ]);
        
        // Add to vehicle history
        $action = $input['decision'] === 'approved' ? 'warranty_approved' : 'warranty_rejected';
        $description = "Warranty claim {$input['decision']} by EVM - " . substr($input['review_notes'], 0, 100);
        
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_history (vehicle_id, action, description, performed_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$claim['vehicle_id'], $action, $description, $user['full_name']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Warranty claim {$input['decision']} successfully",
            'approved_cost' => $approved_cost
        ]);
        
    } catch(Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Central Parts Inventory Management
// ===============================================
if ($route === '/parts/inventory' && $request_method === 'GET') {
    $service_center_id = $_GET['service_center_id'] ?? null;
    
    try {
        $sql = "
            SELECT p.id, p.part_number, p.name, p.unit_cost,
                   pc.name as category_name, pc.critical_part,
                   pi.quantity_available, pi.quantity_reserved, pi.minimum_stock_level,
                   sc.name as location_name
            FROM parts p
            JOIN parts_categories pc ON p.category_id = pc.id
            LEFT JOIN parts_inventory pi ON p.id = pi.part_id
            LEFT JOIN service_centers sc ON pi.service_center_id = sc.id
            WHERE p.status = 'active'
        ";
        
        $params = [];
        
        if ($service_center_id !== null) {
            if ($service_center_id === '0') {
                // Central warehouse only
                $sql .= " AND pi.service_center_id IS NULL";
            } else {
                // Specific service center
                $sql .= " AND pi.service_center_id = ?";
                $params[] = $service_center_id;
            }
        }
        
        $sql .= " ORDER BY pc.critical_part DESC, sc.name, p.name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $inventory,
            'count' => count($inventory)
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch inventory: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Allocate Parts to Service Centers
// ===============================================
if ($route === '/parts/allocate' && $request_method === 'POST') {
    $user = getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['part_id', 'service_center_id', 'quantity'];
    $missing = validateRequired($input, $required);
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check central warehouse stock
        $stmt = $pdo->prepare("
            SELECT quantity_available FROM parts_inventory 
            WHERE part_id = ? AND service_center_id IS NULL
        ");
        $stmt->execute([$input['part_id']]);
        $central_stock = $stmt->fetch();
        
        if (!$central_stock || $central_stock['quantity_available'] < $input['quantity']) {
            throw new Exception("Insufficient stock in central warehouse");
        }
        
        // Deduct from central warehouse
        $stmt = $pdo->prepare("
            UPDATE parts_inventory 
            SET quantity_available = quantity_available - ?
            WHERE part_id = ? AND service_center_id IS NULL
        ");
        $stmt->execute([$input['quantity'], $input['part_id']]);
        
        // Add to service center inventory
        $stmt = $pdo->prepare("
            INSERT INTO parts_inventory (part_id, service_center_id, quantity_available, cost_per_unit)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            quantity_available = quantity_available + VALUES(quantity_available)
        ");
        $stmt->execute([
            $input['part_id'],
            $input['service_center_id'],
            $input['quantity'],
            $input['cost_per_unit'] ?? 0
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully allocated {$input['quantity']} parts to service center"
        ]);
        
    } catch(Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Create Recall Campaign
// ===============================================
if ($route === '/campaigns/create' && $request_method === 'POST') {
    $user = getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['title', 'campaign_type', 'description', 'severity', 'start_date'];
    $missing = validateRequired($input, $required);
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Generate campaign number
        $campaign_number = strtoupper($input['campaign_type'][0]) . 'C-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Create campaign
        $stmt = $pdo->prepare("
            INSERT INTO campaigns (
                campaign_number, title, campaign_type, description, severity,
                start_date, end_date, completion_deadline, affected_models,
                affected_vin_ranges, estimated_repair_time_hours, parts_required,
                repair_instructions, status, created_by_user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)
        ");
        
        $stmt->execute([
            $campaign_number,
            $input['title'],
            $input['campaign_type'],
            $input['description'],
            $input['severity'],
            $input['start_date'],
            $input['end_date'] ?? null,
            $input['completion_deadline'] ?? null,
            json_encode($input['affected_models'] ?? []),
            json_encode($input['affected_vin_ranges'] ?? []),
            $input['estimated_repair_time_hours'] ?? null,
            json_encode($input['parts_required'] ?? []),
            $input['repair_instructions'] ?? null,
            $user['id']
        ]);
        
        $campaign_id = $pdo->lastInsertId();
        
        // If activated immediately, find affected vehicles
        if (isset($input['activate_now']) && $input['activate_now']) {
            $stmt = $pdo->prepare("UPDATE campaigns SET status = 'active' WHERE id = ?");
            $stmt->execute([$campaign_id]);
            
            // Find affected vehicles based on criteria
            $affected_vehicles = [];
            
            if (!empty($input['affected_models'])) {
                $model_placeholders = implode(',', array_fill(0, count($input['affected_models']), '?'));
                $stmt = $pdo->prepare("
                    SELECT v.id, v.service_center_id 
                    FROM vehicles v 
                    WHERE v.model_id IN ($model_placeholders) AND v.status = 'active'
                ");
                $stmt->execute($input['affected_models']);
                $affected_vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Add affected vehicles to campaign
            foreach ($affected_vehicles as $vehicle) {
                $stmt = $pdo->prepare("
                    INSERT INTO campaign_vehicles (campaign_id, vehicle_id, service_center_id, status)
                    VALUES (?, ?, ?, 'identified')
                ");
                $stmt->execute([$campaign_id, $vehicle['id'], $vehicle['service_center_id']]);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Campaign created successfully',
            'campaign_id' => $campaign_id,
            'campaign_number' => $campaign_number,
            'affected_vehicles_count' => count($affected_vehicles ?? [])
        ]);
        
    } catch(Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Get Campaign Analytics
// ===============================================
if (preg_match('/^\/campaigns\/(\d+)\/analytics$/', $route, $matches) && $request_method === 'GET') {
    $campaign_id = $matches[1];
    
    try {
        // Campaign details
        $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
        $stmt->execute([$campaign_id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campaign) {
            http_response_code(404);
            echo json_encode(['error' => 'Campaign not found']);
            exit();
        }
        
        // Progress by service center
        $stmt = $pdo->prepare("
            SELECT sc.name as service_center_name, cv.status,
                   COUNT(*) as count
            FROM campaign_vehicles cv
            JOIN service_centers sc ON cv.service_center_id = sc.id
            WHERE cv.campaign_id = ?
            GROUP BY sc.id, cv.status
            ORDER BY sc.name, cv.status
        ");
        $stmt->execute([$campaign_id]);
        $progress_by_sc = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Overall statistics
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) as count,
                   AVG(labor_hours) as avg_labor_hours
            FROM campaign_vehicles 
            WHERE campaign_id = ?
            GROUP BY status
        ");
        $stmt->execute([$campaign_id]);
        $overall_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'campaign' => $campaign,
                'progress_by_service_center' => $progress_by_sc,
                'overall_statistics' => $overall_stats
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch campaign analytics: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Get Financial Reports
// ===============================================
if ($route === '/reports/financial' && $request_method === 'GET') {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    try {
        // Warranty costs by category
        $stmt = $pdo->prepare("
            SELECT pc.name as category_name,
                   COUNT(wc.id) as claim_count,
                   SUM(wc.approved_cost) as total_approved,
                   SUM(wc.actual_cost) as total_actual,
                   AVG(wc.actual_cost) as avg_cost
            FROM warranty_claims wc
            LEFT JOIN vehicle_parts vp ON wc.vehicle_part_id = vp.id
            LEFT JOIN parts p ON vp.part_id = p.id
            LEFT JOIN parts_categories pc ON p.category_id = pc.id
            WHERE wc.created_at BETWEEN ? AND ?
            AND wc.status = 'completed'
            GROUP BY pc.id
            ORDER BY total_actual DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $costs_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Costs by service center
        $stmt = $pdo->prepare("
            SELECT sc.name as service_center_name,
                   COUNT(wc.id) as claim_count,
                   SUM(wc.actual_cost) as total_cost
            FROM warranty_claims wc
            JOIN service_centers sc ON wc.service_center_id = sc.id
            WHERE wc.created_at BETWEEN ? AND ?
            AND wc.status = 'completed'
            GROUP BY sc.id
            ORDER BY total_cost DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $costs_by_service_center = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'period' => ['start' => $start_date, 'end' => $end_date],
                'costs_by_category' => $costs_by_category,
                'costs_by_service_center' => $costs_by_service_center
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate financial report: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Get Reference Data
// ===============================================
if ($route === '/reference-data' && $request_method === 'GET') {
    try {
        // Service centers
        $stmt = $pdo->prepare("SELECT id, name, code, province, city FROM service_centers WHERE status = 'active'");
        $stmt->execute();
        $service_centers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Vehicle models
        $stmt = $pdo->prepare("SELECT id, name, full_name FROM vehicle_models WHERE status = 'active'");
        $stmt->execute();
        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parts categories
        $stmt = $pdo->prepare("SELECT * FROM parts_categories ORDER BY critical_part DESC, name");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'service_centers' => $service_centers,
                'vehicle_models' => $models,
                'parts_categories' => $categories
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch reference data: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// DEFAULT ROUTE
// ===============================================
http_response_code(404);
echo json_encode([
    'error' => 'Route not found',
    'available_routes' => [
        'GET /api/evm-staff/health' => 'Health check',
        'GET /api/evm-staff/dashboard/overview' => 'Dashboard overview',
        'GET /api/evm-staff/warranty-claims/pending-review' => 'Get claims pending review',
        'PUT /api/evm-staff/warranty-claims/{id}/review' => 'Approve/reject warranty claim',
        'GET /api/evm-staff/parts/inventory' => 'Get parts inventory',
        'POST /api/evm-staff/parts/allocate' => 'Allocate parts to service centers',
        'POST /api/evm-staff/campaigns/create' => 'Create recall campaign',
        'GET /api/evm-staff/campaigns/{id}/analytics' => 'Get campaign analytics',
        'GET /api/evm-staff/reports/financial' => 'Get financial reports',
        'GET /api/evm-staff/reference-data' => 'Get reference data'
    ]
]);
?>