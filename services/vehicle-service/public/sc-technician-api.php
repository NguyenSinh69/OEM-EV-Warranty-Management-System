<?php
/*
===============================================
OEM EV Warranty Management System
SC Technician Portal API - Service Center Technician Functions
===============================================
Functions for Service Center Technicians:
- View assigned warranty claims
- Update repair progress and status
- Parts installation and replacement
- Diagnostic reports and technical documentation
- Complete warranty work
- Campaign/recall execution
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
$route = str_replace('/api/sc-technician', '', $request_uri);

// Authentication (Basic implementation - should be JWT in production)
function getCurrentUser() {
    // For demo purposes, return sample SC Technician user
    return [
        'id' => 4,
        'username' => 'sc.hn.tech',
        'role' => 'sc_technician',
        'service_center_id' => 1,
        'full_name' => 'Lê Văn Tech HN'
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
        'service' => 'SC Technician Portal API',
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => getCurrentUser()
    ]);
    exit();
}

// ===============================================
// ROUTE: Dashboard - My Assignments
// ===============================================
if ($route === '/dashboard/assignments' && $request_method === 'GET') {
    $user = getCurrentUser();
    
    try {
        // Get assigned warranty claims
        $stmt = $pdo->prepare("
            SELECT wc.id, wc.claim_number, wc.status, wc.priority, wc.issue_description,
                   wc.estimated_cost, wc.created_at,
                   v.vin, v.license_plate, vm.name as model_name,
                   c.name as customer_name
            FROM warranty_claims wc
            JOIN vehicles v ON wc.vehicle_id = v.id
            JOIN vehicle_models vm ON v.model_id = vm.id
            JOIN customers c ON v.customer_id = c.id
            WHERE wc.assigned_technician_id = ? 
            AND wc.status IN ('approved', 'parts_ordered', 'in_progress')
            ORDER BY wc.priority DESC, wc.created_at ASC
        ");
        $stmt->execute([$user['id']]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get campaign assignments
        $stmt = $pdo->prepare("
            SELECT cv.id, cv.campaign_id, cv.status, cv.appointment_date,
                   c.campaign_number, c.title, c.campaign_type, c.estimated_repair_time_hours,
                   v.vin, v.license_plate, vm.name as model_name,
                   cust.name as customer_name
            FROM campaign_vehicles cv
            JOIN campaigns c ON cv.campaign_id = c.id
            JOIN vehicles v ON cv.vehicle_id = v.id
            JOIN vehicle_models vm ON v.model_id = vm.id
            JOIN customers cust ON v.customer_id = cust.id
            WHERE cv.assigned_technician_id = ? 
            AND cv.status IN ('scheduled', 'in_progress')
            ORDER BY cv.appointment_date ASC
        ");
        $stmt->execute([$user['id']]);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Statistics
        $stats = [
            'active_warranty_claims' => count(array_filter($assignments, fn($a) => $a['status'] !== 'completed')),
            'active_campaigns' => count(array_filter($campaigns, fn($c) => $c['status'] !== 'completed')),
            'high_priority_claims' => count(array_filter($assignments, fn($a) => $a['priority'] === 'high' || $a['priority'] === 'critical')),
            'total_assignments' => count($assignments) + count($campaigns)
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'warranty_claims' => $assignments,
                'campaigns' => $campaigns
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch assignments: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Get Warranty Claim Details
// ===============================================
if (preg_match('/^\/warranty-claims\/(\d+)$/', $route, $matches) && $request_method === 'GET') {
    $claim_id = $matches[1];
    $user = getCurrentUser();
    
    try {
        // Get claim details
        $stmt = $pdo->prepare("
            SELECT wc.*, v.vin, v.license_plate, v.mileage, v.year, v.color,
                   vm.name as model_name, vm.full_name as model_full_name,
                   c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                   staff.full_name as created_by_name,
                   reviewer.full_name as reviewed_by_name
            FROM warranty_claims wc
            JOIN vehicles v ON wc.vehicle_id = v.id
            JOIN vehicle_models vm ON v.model_id = vm.id
            JOIN customers c ON v.customer_id = c.id
            JOIN users staff ON wc.created_by_user_id = staff.id
            LEFT JOIN users reviewer ON wc.reviewed_by_user_id = reviewer.id
            WHERE wc.id = ? AND (wc.assigned_technician_id = ? OR wc.service_center_id = ?)
        ");
        $stmt->execute([$claim_id, $user['id'], $user['service_center_id']]);
        $claim = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$claim) {
            http_response_code(404);
            echo json_encode(['error' => 'Warranty claim not found or not accessible']);
            exit();
        }
        
        // Get affected part if specified
        if ($claim['vehicle_part_id']) {
            $stmt = $pdo->prepare("
                SELECT vp.*, p.name as part_name, p.part_number, pc.name as category_name
                FROM vehicle_parts vp
                JOIN parts p ON vp.part_id = p.id
                JOIN parts_categories pc ON p.category_id = pc.id
                WHERE vp.id = ?
            ");
            $stmt->execute([$claim['vehicle_part_id']]);
            $affected_part = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $affected_part = null;
        }
        
        // Get claim attachments
        $stmt = $pdo->prepare("
            SELECT wca.*, u.full_name as uploaded_by_name
            FROM warranty_claim_attachments wca
            JOIN users u ON wca.uploaded_by_user_id = u.id
            WHERE wca.warranty_claim_id = ?
            ORDER BY wca.created_at DESC
        ");
        $stmt->execute([$claim_id]);
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'claim' => $claim,
                'affected_part' => $affected_part,
                'attachments' => $attachments
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch claim details: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Update Warranty Claim Progress
// ===============================================
if (preg_match('/^\/warranty-claims\/(\d+)\/progress$/', $route, $matches) && $request_method === 'PUT') {
    $claim_id = $matches[1];
    $user = getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $allowed_statuses = ['in_progress', 'parts_ordered', 'completed'];
    
    if (!isset($input['status']) || !in_array($input['status'], $allowed_statuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status. Allowed: ' . implode(', ', $allowed_statuses)]);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verify claim is assigned to this technician
        $stmt = $pdo->prepare("SELECT vehicle_id FROM warranty_claims WHERE id = ? AND assigned_technician_id = ?");
        $stmt->execute([$claim_id, $user['id']]);
        $claim = $stmt->fetch();
        
        if (!$claim) {
            throw new Exception("Warranty claim not found or not assigned to you");
        }
        
        // Update claim status and progress
        $update_fields = ['status' => $input['status']];
        $params = [$input['status']];
        
        if (isset($input['diagnosis_notes'])) {
            $update_fields['diagnosis_notes'] = '?';
            $params[] = $input['diagnosis_notes'];
        }
        
        if (isset($input['labor_hours'])) {
            $update_fields['labor_hours'] = '?';
            $params[] = $input['labor_hours'];
        }
        
        if (isset($input['actual_cost'])) {
            $update_fields['actual_cost'] = '?';
            $params[] = $input['actual_cost'];
        }
        
        if ($input['status'] === 'completed') {
            $update_fields['completed_date'] = 'NOW()';
            if (isset($input['completion_notes'])) {
                $update_fields['completion_notes'] = '?';
                $params[] = $input['completion_notes'];
            }
        }
        
        $params[] = $claim_id;
        
        $set_clause = implode(', ', array_map(fn($k, $v) => "$k = $v", array_keys($update_fields), $update_fields));
        $stmt = $pdo->prepare("UPDATE warranty_claims SET $set_clause WHERE id = ?");
        $stmt->execute($params);
        
        // Add to vehicle history
        $action = $input['status'] === 'completed' ? 'warranty_completed' : 'warranty_progress';
        $description = "Warranty claim progress updated: " . $input['status'];
        if (isset($input['diagnosis_notes'])) {
            $description .= " - " . substr($input['diagnosis_notes'], 0, 100);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_history (vehicle_id, action, description, performed_by, service_center_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$claim['vehicle_id'], $action, $description, $user['full_name'], $user['service_center_id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Warranty claim progress updated successfully'
        ]);
        
    } catch(Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Install/Replace Parts
// ===============================================
if ($route === '/parts/install' && $request_method === 'POST') {
    $user = getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['vehicle_id', 'part_id', 'serial_number', 'installation_date', 'location_on_vehicle'];
    $missing = validateRequired($input, $required);
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verify vehicle is in this service center
        $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND service_center_id = ?");
        $stmt->execute([$input['vehicle_id'], $user['service_center_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Vehicle not found or not in this service center");
        }
        
        // Check if replacing existing part
        $old_part_id = null;
        if (isset($input['replacing_part_id'])) {
            $stmt = $pdo->prepare("
                UPDATE vehicle_parts 
                SET status = 'replaced', replacement_reason = ? 
                WHERE id = ? AND vehicle_id = ?
            ");
            $stmt->execute([$input['replacement_reason'] ?? 'Part replacement', $input['replacing_part_id'], $input['vehicle_id']]);
            $old_part_id = $input['replacing_part_id'];
        }
        
        // Get part warranty period
        $stmt = $pdo->prepare("SELECT warranty_months FROM parts WHERE id = ?");
        $stmt->execute([$input['part_id']]);
        $part_warranty = $stmt->fetch();
        
        $warranty_start = $input['installation_date'];
        $warranty_end = date('Y-m-d', strtotime($warranty_start . ' +' . $part_warranty['warranty_months'] . ' months'));
        
        // Install new part
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_parts (
                vehicle_id, part_id, serial_number, installation_date,
                installed_by_user_id, installation_mileage, warranty_start_date,
                warranty_end_date, location_on_vehicle, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'installed')
        ");
        
        $stmt->execute([
            $input['vehicle_id'],
            $input['part_id'],
            $input['serial_number'],
            $input['installation_date'],
            $user['id'],
            $input['installation_mileage'] ?? 0,
            $warranty_start,
            $warranty_end,
            $input['location_on_vehicle']
        ]);
        
        $new_part_id = $pdo->lastInsertId();
        
        // Update replacement reference if applicable
        if ($old_part_id) {
            $stmt = $pdo->prepare("UPDATE vehicle_parts SET replaced_by_part_id = ? WHERE id = ?");
            $stmt->execute([$new_part_id, $old_part_id]);
        }
        
        // Add to vehicle history
        $stmt = $pdo->prepare("
            SELECT p.name, p.part_number FROM parts p WHERE p.id = ?
        ");
        $stmt->execute([$input['part_id']]);
        $part_info = $stmt->fetch();
        
        $action = $old_part_id ? 'part_replacement' : 'part_installation';
        $description = ($old_part_id ? 'Replaced' : 'Installed') . " part: {$part_info['name']} ({$part_info['part_number']}) - Serial: {$input['serial_number']}";
        
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_history (vehicle_id, action, description, performed_by, service_center_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$input['vehicle_id'], $action, $description, $user['full_name'], $user['service_center_id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Part installed successfully',
            'part_id' => $new_part_id,
            'warranty_end_date' => $warranty_end
        ]);
        
    } catch(Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Get Available Parts Inventory
// ===============================================
if ($route === '/parts/inventory' && $request_method === 'GET') {
    $user = getCurrentUser();
    $category_id = $_GET['category_id'] ?? '';
    
    try {
        $sql = "
            SELECT p.id, p.part_number, p.name, p.unit_cost,
                   pc.name as category_name, pc.critical_part,
                   pi.quantity_available, pi.quantity_reserved
            FROM parts p
            JOIN parts_categories pc ON p.category_id = pc.id
            LEFT JOIN parts_inventory pi ON p.id = pi.part_id AND pi.service_center_id = ?
            WHERE p.status = 'active'
        ";
        
        $params = [$user['service_center_id']];
        
        if (!empty($category_id)) {
            $sql .= " AND pc.id = ?";
            $params[] = $category_id;
        }
        
        $sql .= " ORDER BY pc.critical_part DESC, pc.name, p.name";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $parts,
            'count' => count($parts)
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch parts inventory: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Execute Campaign Work
// ===============================================
if (preg_match('/^\/campaigns\/(\d+)\/execute$/', $route, $matches) && $request_method === 'PUT') {
    $campaign_vehicle_id = $matches[1];
    $user = getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $allowed_statuses = ['in_progress', 'completed'];
    
    if (!isset($input['status']) || !in_array($input['status'], $allowed_statuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status. Allowed: ' . implode(', ', $allowed_statuses)]);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verify campaign assignment
        $stmt = $pdo->prepare("
            SELECT cv.vehicle_id, c.title 
            FROM campaign_vehicles cv
            JOIN campaigns c ON cv.campaign_id = c.id
            WHERE cv.id = ? AND cv.assigned_technician_id = ?
        ");
        $stmt->execute([$campaign_vehicle_id, $user['id']]);
        $campaign_info = $stmt->fetch();
        
        if (!$campaign_info) {
            throw new Exception("Campaign work not found or not assigned to you");
        }
        
        // Update campaign progress
        $update_fields = ['status' => $input['status']];
        $params = [$input['status']];
        
        if ($input['status'] === 'in_progress' && !isset($input['work_started_date'])) {
            $update_fields['work_started_date'] = 'CURDATE()';
        }
        
        if ($input['status'] === 'completed') {
            $update_fields['work_completed_date'] = 'CURDATE()';
            if (isset($input['work_notes'])) {
                $update_fields['work_notes'] = '?';
                $params[] = $input['work_notes'];
            }
            if (isset($input['labor_hours'])) {
                $update_fields['labor_hours'] = '?';
                $params[] = $input['labor_hours'];
            }
            if (isset($input['parts_used'])) {
                $update_fields['parts_used'] = '?';
                $params[] = json_encode($input['parts_used']);
            }
        }
        
        $params[] = $campaign_vehicle_id;
        
        $set_clause = implode(', ', array_map(fn($k, $v) => "$k = $v", array_keys($update_fields), $update_fields));
        $stmt = $pdo->prepare("UPDATE campaign_vehicles SET $set_clause WHERE id = ?");
        $stmt->execute($params);
        
        // Add to vehicle history
        $action = $input['status'] === 'completed' ? 'campaign_completed' : 'campaign_progress';
        $description = "Campaign work updated: {$campaign_info['title']} - " . $input['status'];
        
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_history (vehicle_id, action, description, performed_by, service_center_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$campaign_info['vehicle_id'], $action, $description, $user['full_name'], $user['service_center_id']]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Campaign work updated successfully'
        ]);
        
    } catch(Exception $e) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Get Reference Data (Parts Categories, etc.)
// ===============================================
if ($route === '/reference-data' && $request_method === 'GET') {
    try {
        // Get parts categories
        $stmt = $pdo->prepare("SELECT * FROM parts_categories ORDER BY critical_part DESC, name");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
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
        'GET /api/sc-technician/health' => 'Health check',
        'GET /api/sc-technician/dashboard/assignments' => 'Get my assignments',
        'GET /api/sc-technician/warranty-claims/{id}' => 'Get warranty claim details',
        'PUT /api/sc-technician/warranty-claims/{id}/progress' => 'Update warranty claim progress',
        'POST /api/sc-technician/parts/install' => 'Install/replace parts',
        'GET /api/sc-technician/parts/inventory' => 'Get parts inventory',
        'PUT /api/sc-technician/campaigns/{id}/execute' => 'Execute campaign work',
        'GET /api/sc-technician/reference-data' => 'Get reference data'
    ]
]);
?>