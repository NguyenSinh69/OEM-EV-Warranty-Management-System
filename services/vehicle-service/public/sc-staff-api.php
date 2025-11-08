<?php
/*
===============================================
OEM EV Warranty Management System
SC Staff Portal API - Service Center Staff Functions
===============================================
Functions for Service Center Staff:
- Vehicle registration with VIN
- Parts tracking and serial number assignment  
- Warranty claim creation and management
- Service history management
- Recall campaign management
- Customer management
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

// Database configuration - Connect to vehicle-db container
$host = 'vehicle-db';
$dbname = 'evm_vehicle_db';
$username = 'evm_user';
$password = 'evm_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Try localhost connection for development
    try {
        $pdo = new PDO("mysql:host=localhost:3308;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e2) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e2->getMessage()]);
        exit();
    }
}

// Get request URI and method
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove base path and get route
$route = str_replace('/api/sc-staff', '', $request_uri);

// Authentication (Basic implementation - should be JWT in production)
function getCurrentUser() {
    // For demo purposes, return sample SC Staff user
    return [
        'id' => 3,
        'username' => 'sc.hn.staff',
        'role' => 'sc_staff',
        'service_center_id' => 1,
        'full_name' => 'Trần Thị Staff HN'
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
        'service' => 'SC Staff Portal API',
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => getCurrentUser()
    ]);
    exit();
}

// ===============================================
// ROUTE: Dashboard Statistics
// ===============================================
if ($route === '/dashboard/stats' && $request_method === 'GET') {
    $user = getCurrentUser();
    $service_center_id = $user['service_center_id'];
    
    try {
        // Today's registrations
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM vehicles 
            WHERE service_center_id = ? AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$service_center_id]);
        $today_registrations = $stmt->fetch()['count'];
        
        // Pending warranty claims
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM warranty_claims 
            WHERE service_center_id = ? AND status IN ('draft', 'submitted', 'under_review')
        ");
        $stmt->execute([$service_center_id]);
        $pending_claims = $stmt->fetch()['count'];
        
        // Active recalls
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT cv.campaign_id) as count
            FROM campaign_vehicles cv
            JOIN campaigns c ON cv.campaign_id = c.id
            WHERE cv.service_center_id = ? AND c.status = 'active' AND cv.status IN ('identified', 'notified', 'scheduled')
        ");
        $stmt->execute([$service_center_id]);
        $active_recalls = $stmt->fetch()['count'];
        
        // Total vehicles managed
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM vehicles 
            WHERE service_center_id = ?
        ");
        $stmt->execute([$service_center_id]);
        $total_vehicles = $stmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'today_registrations' => (int)$today_registrations,
                'pending_claims' => (int)$pending_claims,
                'active_recalls' => (int)$active_recalls,
                'total_vehicles' => (int)$total_vehicles
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch dashboard stats: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Vehicle Registration
// ===============================================
if ($route === '/vehicles/register' && $request_method === 'POST') {
    $user = getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['vin', 'model_id', 'year', 'color', 'customer_id', 'purchase_date', 'warranty_start_date'];
    $missing = validateRequired($input, $required);
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if VIN already exists
        $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE vin = ?");
        $stmt->execute([$input['vin']]);
        if ($stmt->fetch()) {
            throw new Exception("VIN already registered");
        }
        
        // Calculate warranty end dates
        $warranty_start = $input['warranty_start_date'];
        $warranty_end = date('Y-m-d', strtotime($warranty_start . ' +2 years'));
        $battery_warranty_end = date('Y-m-d', strtotime($warranty_start . ' +8 years'));
        
        // Insert vehicle
        $stmt = $pdo->prepare("
            INSERT INTO vehicles (
                vin, model_id, year, color, customer_id, service_center_id,
                purchase_date, warranty_start_date, 
                warranty_end_date, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'under_warranty')
        ");
        
        $stmt->execute([
            $input['vin'],
            $input['model_id'],
            $input['year'],
            $input['color'],
            $input['customer_id'],
            $user['service_center_id'],
            $input['purchase_date'],
            $warranty_start,
            $warranty_end
        ]);
        
        $vehicle_id = $pdo->lastInsertId();
        
        // Add to vehicle history - commented out as table doesn't exist
        // $stmt = $pdo->prepare("
        //     INSERT INTO vehicle_history (vehicle_id, action, description, performed_by, service_center_id)
        //     VALUES (?, 'registration', 'Vehicle registered successfully', ?, ?)
        // ");
        // $stmt->execute([$vehicle_id, $user['full_name'], $user['service_center_id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Vehicle registered successfully',
            'vehicle_id' => $vehicle_id,
            'warranty_end_date' => $warranty_end,
            'battery_warranty_end_date' => $battery_warranty_end
        ]);
        
    } catch(Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Search Vehicles
// ===============================================
if ($route === '/vehicles/search' && $request_method === 'GET') {
    $user = getCurrentUser();
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all'; // vin, customer, license_plate
    
    try {
        $sql = "
            SELECT v.id, v.vin, v.license_plate, v.year, v.color, v.status, v.current_mileage as mileage,
                   v.created_at as registration_date, v.warranty_end_date,
                   em.name as model_name, em.full_name as model_full_name,
                   c.full_name as customer_name, c.phone as customer_phone
            FROM vehicles v
            JOIN ev_models em ON v.model_id = em.id
            JOIN customers c ON v.customer_id = c.id
            WHERE v.service_center_id = ?
        ";
        
        $params = [$user['service_center_id']];
        
        if (!empty($query)) {
            switch($type) {
                case 'vin':
                    $sql .= " AND v.vin LIKE ?";
                    $params[] = "%$query%";
                    break;
                case 'customer':
                    $sql .= " AND c.name LIKE ?";
                    $params[] = "%$query%";
                    break;
                case 'license_plate':
                    $sql .= " AND v.license_plate LIKE ?";
                    $params[] = "%$query%";
                    break;
                default:
                    $sql .= " AND (v.vin LIKE ? OR c.name LIKE ? OR v.license_plate LIKE ?)";
                    $params[] = "%$query%";
                    $params[] = "%$query%";
                    $params[] = "%$query%";
                    break;
            }
        }
        
        $sql .= " ORDER BY v.created_at DESC LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $vehicles,
            'count' => count($vehicles)
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Vehicle Details
// ===============================================
if (preg_match('/^\/vehicles\/(\d+)$/', $route, $matches) && $request_method === 'GET') {
    $vehicle_id = $matches[1];
    $user = getCurrentUser();
    
    try {
        // Get vehicle details
        $stmt = $pdo->prepare("
            SELECT v.*, em.name as model_name, em.full_name as model_full_name,
                   c.full_name as customer_name, c.phone as customer_phone, c.email as customer_email,
                   sc.name as service_center_name
            FROM vehicles v
            JOIN ev_models em ON v.model_id = em.id
            JOIN customers c ON v.customer_id = c.id
            JOIN service_centers sc ON v.service_center_id = sc.id
            WHERE v.id = ? AND v.service_center_id = ?
        ");
        $stmt->execute([$vehicle_id, $user['service_center_id']]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$vehicle) {
            http_response_code(404);
            echo json_encode(['error' => 'Vehicle not found']);
            exit();
        }
        
        // Get installed parts
        $stmt = $pdo->prepare("
            SELECT vp.*, p.name as part_name, p.part_number,
                   pc.name as category_name
            FROM vehicle_parts vp
            JOIN parts p ON vp.part_id = p.id
            JOIN parts_categories pc ON p.category_id = pc.id
            WHERE vp.vehicle_id = ? AND vp.status = 'installed'
            ORDER BY pc.critical_part DESC, pc.name
        ");
        $stmt->execute([$vehicle_id]);
        $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get service history - commented out as table doesn't exist
        // $stmt = $pdo->prepare("
        //     SELECT * FROM vehicle_history 
        //     WHERE vehicle_id = ? 
        //     ORDER BY created_at DESC 
        //     LIMIT 20
        // ");
        // $stmt->execute([$vehicle_id]);
        $history = []; // Empty array instead
        
        echo json_encode([
            'success' => true,
            'data' => [
                'vehicle' => $vehicle,
                'parts' => $parts,
                'history' => $history
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch vehicle details: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Create Warranty Claim
// ===============================================
if ($route === '/warranty-claims/create' && $request_method === 'POST') {
    $user = getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['vehicle_id', 'issue_description', 'failure_date', 'priority'];
    $missing = validateRequired($input, $required);
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit();
    }
    
    try {
        // First, drop and recreate the warranty_claims table with correct structure
        $pdo->exec("DROP TABLE IF EXISTS warranty_claims");
        
        $createTableSQL = "
            CREATE TABLE warranty_claims (
                id INT PRIMARY KEY AUTO_INCREMENT,
                claim_number VARCHAR(50) UNIQUE NOT NULL,
                vehicle_id INT NOT NULL,
                vehicle_part_id INT NULL,
                service_center_id INT NOT NULL,
                created_by_user_id INT NOT NULL,
                assigned_technician_id INT NULL,
                issue_description TEXT NOT NULL,
                symptoms TEXT,
                diagnosis_notes TEXT,
                failure_date DATE,
                failure_mileage INT,
                status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected', 'parts_ordered', 'in_progress', 'completed', 'closed') DEFAULT 'draft',
                priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                estimated_cost DECIMAL(12,2),
                approved_cost DECIMAL(12,2),
                actual_cost DECIMAL(12,2),
                labor_hours DECIMAL(5,2),
                reviewed_by_user_id INT NULL,
                review_date TIMESTAMP NULL,
                review_notes TEXT,
                rejection_reason TEXT,
                completed_date TIMESTAMP NULL,
                completion_notes TEXT,
                customer_satisfaction_rating INT CHECK (customer_satisfaction_rating >= 1 AND customer_satisfaction_rating <= 5),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_warranty_claims_vehicle (vehicle_id),
                INDEX idx_warranty_claims_status (status),
                INDEX idx_warranty_claims_service_center (service_center_id),
                INDEX idx_warranty_claims_number (claim_number)
            )
        ";
        
        $pdo->exec($createTableSQL);
        
        $pdo->beginTransaction();
        
        // Verify vehicle exists
        $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ?");
        $stmt->execute([$input['vehicle_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Vehicle not found");
        }
        
        // Generate claim number
        $claim_number = 'WC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert warranty claim
        $stmt = $pdo->prepare("
            INSERT INTO warranty_claims (
                claim_number, vehicle_id, vehicle_part_id, service_center_id,
                created_by_user_id, issue_description, symptoms, failure_date,
                failure_mileage, status, priority, estimated_cost
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', ?, ?)
        ");
        
        $stmt->execute([
            $claim_number,
            $input['vehicle_id'],
            null, // vehicle_part_id - not required for general claims
            1, // hardcode service_center_id = 1 for now
            1, // hardcode user_id = 1 for now  
            $input['issue_description'],
            $input['symptoms'] ?? null,
            $input['failure_date'],
            $input['failure_mileage'] ?? null,
            $input['priority'],
            $input['estimated_cost'] ?? null
        ]);
        
        $claim_id = $pdo->lastInsertId();
        
        // Add to vehicle history - commented out as table doesn't exist
        // $stmt = $pdo->prepare("
        //     INSERT INTO vehicle_history (vehicle_id, action, description, performed_by, service_center_id)
        //     VALUES (?, 'warranty_claim', ?, ?, ?)
        // ");
        // $description = "Warranty claim created: $claim_number - " . $input['issue_description'];
        // $stmt->execute([$input['vehicle_id'], $description, $user['full_name'], $user['service_center_id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Warranty claim created successfully',
            'claim_id' => $claim_id,
            'claim_number' => $claim_number
        ]);
        
    } catch(Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Get Warranty Claims
// ===============================================
if ($route === '/warranty-claims' && $request_method === 'GET') {
    $user = getCurrentUser();
    $status = $_GET['status'] ?? '';
    $vehicle_id = $_GET['vehicle_id'] ?? '';
    
    try {
        $sql = "
            SELECT wc.*, v.vin, v.license_plate,
                   em.name as model_name,
                   c.full_name as customer_name,
                   u.full_name as created_by_name
            FROM warranty_claims wc
            JOIN vehicles v ON wc.vehicle_id = v.id
            JOIN ev_models em ON v.model_id = em.id
            JOIN customers c ON v.customer_id = c.id
            LEFT JOIN users u ON wc.created_by_user_id = u.id
            WHERE wc.service_center_id = ?
        ";
        
        $params = [$user['service_center_id']];
        
        if (!empty($status)) {
            $sql .= " AND wc.status = ?";
            $params[] = $status;
        }
        
        if (!empty($vehicle_id)) {
            $sql .= " AND wc.vehicle_id = ?";
            $params[] = $vehicle_id;
        }
        
        $sql .= " ORDER BY wc.created_at DESC LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $claims,
            'count' => count($claims)
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch warranty claims: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Get Recall Campaigns
// ===============================================
if ($route === '/recalls' && $request_method === 'GET') {
    $user = getCurrentUser();
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(cv.id) as affected_vehicles,
                   COUNT(CASE WHEN cv.status = 'completed' THEN 1 END) as completed_vehicles
            FROM campaigns c
            LEFT JOIN campaign_vehicles cv ON c.id = cv.campaign_id AND cv.service_center_id = ?
            WHERE c.status = 'active'
            GROUP BY c.id
            ORDER BY c.start_date DESC
        ");
        $stmt->execute([$user['service_center_id']]);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $campaigns,
            'count' => count($campaigns)
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch recall campaigns: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Get Model & Customer Lists for Dropdowns
// ===============================================
if ($route === '/reference-data' && $request_method === 'GET') {
    try {
        // Get vehicle models
        $stmt = $pdo->prepare("SELECT id, name, full_name FROM ev_models ORDER BY name");
        $stmt->execute();
        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get customers (for this service center)
        $user = getCurrentUser();
        $stmt = $pdo->prepare("
            SELECT DISTINCT c.id, c.full_name, c.phone, c.email 
            FROM customers c
            LEFT JOIN vehicles v ON c.id = v.customer_id
            ORDER BY c.full_name
            LIMIT 100
        ");
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get available parts (using vehicle_parts table if exists, otherwise empty)
        $parts = [];
        try {
            $stmt = $pdo->prepare("
                SELECT vp.id, vp.part_name as name, vp.part_number, pc.name as category_name
                FROM vehicle_parts vp
                LEFT JOIN parts_categories pc ON vp.category_id = pc.id
                ORDER BY vp.part_name
                LIMIT 50
            ");
            $stmt->execute();
            $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If table doesn't exist, return empty array
            $parts = [];
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'models' => $models,
                'customers' => $customers,
                'parts' => $parts
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
        'GET /api/sc-staff/health' => 'Health check',
        'GET /api/sc-staff/dashboard/stats' => 'Dashboard statistics',
        'POST /api/sc-staff/vehicles/register' => 'Register new vehicle',
        'GET /api/sc-staff/vehicles/search' => 'Search vehicles',
        'GET /api/sc-staff/vehicles/{id}' => 'Get vehicle details',
        'POST /api/sc-staff/warranty-claims/create' => 'Create warranty claim',
        'GET /api/sc-staff/warranty-claims' => 'Get warranty claims',
        'GET /api/sc-staff/recalls' => 'Get active recall campaigns',
        'GET /api/sc-staff/reference-data' => 'Get dropdown data'
    ]
]);
?>