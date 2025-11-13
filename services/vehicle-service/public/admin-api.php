<?php
/*
===============================================
OEM EV Warranty Management System
Admin Portal API - System Administration Functions
===============================================
Functions for System Administrators:
- System-wide analytics and reporting
- User management and role assignments
- AI-powered failure analysis and predictions
- Cost management and financial analytics
- System configuration and settings
- Performance monitoring
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
$route = str_replace('/api/admin', '', $request_uri);

// Authentication (Basic implementation - should be JWT in production)
function getCurrentUser() {
    // For demo purposes, return sample Admin user
    return [
        'id' => 1,
        'username' => 'admin',
        'role' => 'admin',
        'service_center_id' => null,
        'full_name' => 'System Administrator'
    ];
}

// Helper function for AI analysis (mock implementation)
function performAIAnalysis($data, $analysis_type) {
    // Mock AI analysis - in production, this would connect to ML services
    switch($analysis_type) {
        case 'failure_prediction':
            return [
                'risk_score' => rand(1, 100) / 100,
                'predicted_failures' => [
                    ['component' => 'Battery Pack', 'probability' => 0.15, 'timeframe' => '6 months'],
                    ['component' => 'Inverter', 'probability' => 0.08, 'timeframe' => '12 months']
                ],
                'recommendations' => [
                    'Increase battery monitoring frequency',
                    'Schedule preventive inverter maintenance'
                ]
            ];
            
        case 'cost_analysis':
            return [
                'predicted_monthly_cost' => rand(100000000, 500000000),
                'cost_trends' => [
                    'battery_costs' => 'increasing',
                    'motor_costs' => 'stable',
                    'electronic_costs' => 'decreasing'
                ],
                'optimization_opportunities' => [
                    'Bulk parts procurement could save 15%',
                    'Regional service center consolidation could reduce overhead'
                ]
            ];
            
        case 'quality_insights':
            return [
                'defect_rate' => rand(1, 5) / 100,
                'common_issues' => [
                    ['issue' => 'Battery capacity degradation', 'frequency' => rand(20, 50)],
                    ['issue' => 'Inverter noise', 'frequency' => rand(10, 30)]
                ],
                'quality_score' => rand(80, 95) / 100
            ];
            
        default:
            return ['message' => 'Analysis completed', 'timestamp' => date('Y-m-d H:i:s')];
    }
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
        'service' => 'Admin Portal API',
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => getCurrentUser()
    ]);
    exit();
}

// ===============================================
// ROUTE: System Dashboard Overview
// ===============================================
if ($route === '/dashboard/overview' && $request_method === 'GET') {
    try {
        // System statistics
        $stats = [];
        
        // Total vehicles in system
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM vehicles");
        $stmt->execute();
        $stats['total_vehicles'] = $stmt->fetch()['count'];
        
        // Active warranty claims
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM warranty_claims WHERE status IN ('submitted', 'under_review', 'approved', 'in_progress')");
        $stmt->execute();
        $stats['active_claims'] = $stmt->fetch()['count'];
        
        // Service centers
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service_centers WHERE status = 'active'");
        $stmt->execute();
        $stats['active_service_centers'] = $stmt->fetch()['count'];
        
        // Total users
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $stmt->execute();
        $stats['active_users'] = $stmt->fetch()['count'];
        
        // Financial overview (last 30 days)
        $stmt = $pdo->prepare("
            SELECT 
                SUM(approved_cost) as total_approved,
                SUM(actual_cost) as total_spent,
                AVG(actual_cost) as avg_claim_cost
            FROM warranty_claims 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND status = 'completed'
        ");
        $stmt->execute();
        $financial = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Recent activities
        $stmt = $pdo->prepare("
            SELECT vh.action, vh.description, vh.performed_by, vh.created_at,
                   v.vin, vm.name as model_name
            FROM vehicle_history vh
            JOIN vehicles v ON vh.vehicle_id = v.id
            JOIN vehicle_models vm ON v.model_id = vm.id
            ORDER BY vh.created_at DESC
            LIMIT 20
        ");
        $stmt->execute();
        $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'statistics' => $stats,
                'financial_overview' => $financial,
                'recent_activities' => $recent_activities
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch dashboard overview: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: User Management
// ===============================================
if ($route === '/users' && $request_method === 'GET') {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, u.full_name, u.phone, u.employee_id,
                   u.status, u.last_login, u.created_at,
                   r.name as role_name, r.display_name as role_display,
                   sc.name as service_center_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            LEFT JOIN service_centers sc ON u.service_center_id = sc.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $users,
            'count' => count($users)
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch users: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Create User
// ===============================================
if ($route === '/users/create' && $request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['username', 'email', 'full_name', 'role_id'];
    $missing = validateRequired($input, $required);
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit();
    }
    
    try {
        // Check if username/email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$input['username'], $input['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Username or email already exists");
        }
        
        // Default password (should be changed on first login)
        $default_password = 'EVM2024!';
        $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, email, password_hash, full_name, phone, 
                role_id, service_center_id, employee_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $input['username'],
            $input['email'],
            $password_hash,
            $input['full_name'],
            $input['phone'] ?? null,
            $input['role_id'],
            $input['service_center_id'] ?? null,
            $input['employee_id'] ?? null
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user_id' => $user_id,
            'default_password' => $default_password
        ]);
        
    } catch(Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: AI Failure Analysis
// ===============================================
if ($route === '/ai/failure-analysis' && $request_method === 'GET') {
    $model_id = $_GET['model_id'] ?? '';
    $timeframe = $_GET['timeframe'] ?? '30'; // days
    
    try {
        // Get failure data
        $sql = "
            SELECT wc.issue_description, wc.failure_date, wc.actual_cost,
                   v.model_id, vm.name as model_name,
                   p.name as part_name, pc.name as category_name
            FROM warranty_claims wc
            JOIN vehicles v ON wc.vehicle_id = v.id
            JOIN vehicle_models vm ON v.model_id = vm.id
            LEFT JOIN vehicle_parts vp ON wc.vehicle_part_id = vp.id
            LEFT JOIN parts p ON vp.part_id = p.id
            LEFT JOIN parts_categories pc ON p.category_id = pc.id
            WHERE wc.status = 'completed'
            AND wc.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        $params = [$timeframe];
        
        if (!empty($model_id)) {
            $sql .= " AND v.model_id = ?";
            $params[] = $model_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $failure_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Perform AI analysis
        $ai_analysis = performAIAnalysis($failure_data, 'failure_prediction');
        
        // Calculate real statistics
        $failure_stats = [
            'total_failures' => count($failure_data),
            'avg_cost' => $failure_data ? array_sum(array_column($failure_data, 'actual_cost')) / count($failure_data) : 0,
            'most_common_issues' => []
        ];
        
        // Count common issues
        $issue_counts = [];
        foreach ($failure_data as $failure) {
            $category = $failure['category_name'] ?? 'General';
            $issue_counts[$category] = ($issue_counts[$category] ?? 0) + 1;
        }
        arsort($issue_counts);
        $failure_stats['most_common_issues'] = array_slice($issue_counts, 0, 5, true);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'analysis_period' => $timeframe . ' days',
                'statistics' => $failure_stats,
                'ai_predictions' => $ai_analysis,
                'model_filter' => $model_id
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to perform AI analysis: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Cost Analytics & Predictions
// ===============================================
if ($route === '/analytics/cost-analysis' && $request_method === 'GET') {
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    try {
        // Monthly cost trends
        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                   COUNT(*) as claim_count,
                   SUM(approved_cost) as total_approved,
                   SUM(actual_cost) as total_actual
            FROM warranty_claims
            WHERE created_at BETWEEN ? AND ?
            AND status = 'completed'
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmt->execute([$start_date, $end_date]);
        $monthly_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cost by vehicle model
        $stmt = $pdo->prepare("
            SELECT vm.name as model_name,
                   COUNT(wc.id) as claim_count,
                   AVG(wc.actual_cost) as avg_cost,
                   SUM(wc.actual_cost) as total_cost
            FROM warranty_claims wc
            JOIN vehicles v ON wc.vehicle_id = v.id
            JOIN vehicle_models vm ON v.model_id = vm.id
            WHERE wc.created_at BETWEEN ? AND ?
            AND wc.status = 'completed'
            GROUP BY vm.id
            ORDER BY total_cost DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $cost_by_model = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cost by parts category
        $stmt = $pdo->prepare("
            SELECT pc.name as category_name,
                   COUNT(wc.id) as claim_count,
                   AVG(wc.actual_cost) as avg_cost,
                   SUM(wc.actual_cost) as total_cost
            FROM warranty_claims wc
            LEFT JOIN vehicle_parts vp ON wc.vehicle_part_id = vp.id
            LEFT JOIN parts p ON vp.part_id = p.id
            LEFT JOIN parts_categories pc ON p.category_id = pc.id
            WHERE wc.created_at BETWEEN ? AND ?
            AND wc.status = 'completed'
            GROUP BY pc.id
            ORDER BY total_cost DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $cost_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // AI cost predictions
        $ai_cost_analysis = performAIAnalysis([
            'historical_data' => $monthly_trends,
            'model_costs' => $cost_by_model
        ], 'cost_analysis');
        
        echo json_encode([
            'success' => true,
            'data' => [
                'period' => ['start' => $start_date, 'end' => $end_date],
                'monthly_trends' => $monthly_trends,
                'cost_by_model' => $cost_by_model,
                'cost_by_category' => array_filter($cost_by_category, fn($item) => $item['category_name']),
                'ai_predictions' => $ai_cost_analysis
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate cost analysis: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Quality Analytics
// ===============================================
if ($route === '/analytics/quality-metrics' && $request_method === 'GET') {
    try {
        // Overall quality metrics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT v.id) as total_vehicles,
                COUNT(wc.id) as total_claims,
                (COUNT(wc.id) / COUNT(DISTINCT v.id)) as claims_per_vehicle,
                AVG(CASE WHEN wc.customer_satisfaction_rating IS NOT NULL THEN wc.customer_satisfaction_rating END) as avg_satisfaction
            FROM vehicles v
            LEFT JOIN warranty_claims wc ON v.id = wc.vehicle_id
            WHERE v.registration_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ");
        $stmt->execute();
        $quality_overview = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Defect rates by model
        $stmt = $pdo->prepare("
            SELECT vm.name as model_name,
                   COUNT(DISTINCT v.id) as vehicle_count,
                   COUNT(wc.id) as claim_count,
                   (COUNT(wc.id) / COUNT(DISTINCT v.id)) as defect_rate
            FROM vehicle_models vm
            JOIN vehicles v ON vm.id = v.model_id
            LEFT JOIN warranty_claims wc ON v.id = wc.vehicle_id
            WHERE v.registration_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
            GROUP BY vm.id
            ORDER BY defect_rate DESC
        ");
        $stmt->execute();
        $defect_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Service center performance
        $stmt = $pdo->prepare("
            SELECT sc.name as service_center_name,
                   COUNT(wc.id) as processed_claims,
                   AVG(DATEDIFF(wc.completed_date, wc.created_at)) as avg_resolution_days,
                   AVG(CASE WHEN wc.customer_satisfaction_rating IS NOT NULL THEN wc.customer_satisfaction_rating END) as avg_satisfaction
            FROM service_centers sc
            LEFT JOIN warranty_claims wc ON sc.id = wc.service_center_id AND wc.status = 'completed'
            WHERE wc.completed_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY sc.id
            ORDER BY avg_satisfaction DESC
        ");
        $stmt->execute();
        $sc_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // AI quality insights
        $ai_quality_analysis = performAIAnalysis([
            'quality_data' => $quality_overview,
            'defect_rates' => $defect_rates
        ], 'quality_insights');
        
        echo json_encode([
            'success' => true,
            'data' => [
                'quality_overview' => $quality_overview,
                'defect_rates_by_model' => $defect_rates,
                'service_center_performance' => $sc_performance,
                'ai_insights' => $ai_quality_analysis
            ]
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate quality metrics: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: System Configuration
// ===============================================
if ($route === '/config' && $request_method === 'GET') {
    try {
        // Get system settings (mock data - in production would be from config table)
        $config = [
            'system' => [
                'maintenance_mode' => false,
                'auto_approval_threshold' => 5000000, // 5M VND
                'notification_settings' => [
                    'email_enabled' => true,
                    'sms_enabled' => false,
                    'push_enabled' => true
                ]
            ],
            'warranty' => [
                'default_vehicle_warranty_months' => 24,
                'default_battery_warranty_months' => 96,
                'claim_auto_close_days' => 30
            ],
            'parts' => [
                'low_stock_threshold' => 5,
                'auto_reorder_enabled' => false,
                'central_warehouse_enabled' => true
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $config
        ]);
        
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch system configuration: ' . $e->getMessage()]);
    }
    exit();
}

// ===============================================
// ROUTE: Export Data
// ===============================================
if ($route === '/export' && $request_method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $export_type = $input['type'] ?? '';
    $format = $input['format'] ?? 'csv';
    $start_date = $input['start_date'] ?? date('Y-m-01');
    $end_date = $input['end_date'] ?? date('Y-m-d');
    
    try {
        $data = [];
        
        switch($export_type) {
            case 'warranty_claims':
                $stmt = $pdo->prepare("
                    SELECT wc.claim_number, wc.status, wc.priority, wc.created_at, wc.completed_date,
                           wc.estimated_cost, wc.actual_cost,
                           v.vin, vm.name as model_name, c.name as customer_name,
                           sc.name as service_center_name
                    FROM warranty_claims wc
                    JOIN vehicles v ON wc.vehicle_id = v.id
                    JOIN vehicle_models vm ON v.model_id = vm.id
                    JOIN customers c ON v.customer_id = c.id
                    JOIN service_centers sc ON wc.service_center_id = sc.id
                    WHERE wc.created_at BETWEEN ? AND ?
                    ORDER BY wc.created_at DESC
                ");
                $stmt->execute([$start_date, $end_date]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'vehicles':
                $stmt = $pdo->prepare("
                    SELECT v.vin, v.license_plate, v.year, v.color, v.status,
                           v.registration_date, v.warranty_end_date,
                           vm.name as model_name, c.name as customer_name,
                           sc.name as service_center_name
                    FROM vehicles v
                    JOIN vehicle_models vm ON v.model_id = vm.id
                    JOIN customers c ON v.customer_id = c.id
                    JOIN service_centers sc ON v.service_center_id = sc.id
                    WHERE v.registration_date BETWEEN ? AND ?
                    ORDER BY v.registration_date DESC
                ");
                $stmt->execute([$start_date, $end_date]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            default:
                throw new Exception("Invalid export type");
        }
        
        // Generate filename
        $filename = $export_type . '_' . date('Y-m-d_H-i-s') . '.' . $format;
        
        echo json_encode([
            'success' => true,
            'message' => 'Export completed',
            'filename' => $filename,
            'records_count' => count($data),
            'download_url' => '/api/admin/download/' . $filename
        ]);
        
    } catch(Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
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
        'GET /api/admin/health' => 'Health check',
        'GET /api/admin/dashboard/overview' => 'System dashboard overview',
        'GET /api/admin/users' => 'Get all users',
        'POST /api/admin/users/create' => 'Create new user',
        'GET /api/admin/ai/failure-analysis' => 'AI failure analysis',
        'GET /api/admin/analytics/cost-analysis' => 'Cost analytics and predictions',
        'GET /api/admin/analytics/quality-metrics' => 'Quality metrics and insights',
        'GET /api/admin/config' => 'System configuration',
        'POST /api/admin/export' => 'Export system data'
    ]
]);
?>