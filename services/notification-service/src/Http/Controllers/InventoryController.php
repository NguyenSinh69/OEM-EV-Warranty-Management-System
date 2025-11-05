<?php

namespace App\Http\Controllers;

class InventoryController
{
    private $db;
    
    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    /**
     * GET /api/inventory - Tồn kho phụ tùng
     */
    public function index()
    {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $category = $_GET['category'] ?? null;
            $status = $_GET['status'] ?? null;
            $search = $_GET['search'] ?? null;
            $low_stock = $_GET['low_stock'] ?? false;
            
            $offset = ($page - 1) * $limit;
            
            // Build query
            $whereConditions = [];
            $params = [];
            
            if ($category) {
                $whereConditions[] = 'category = ?';
                $params[] = $category;
            }
            
            if ($status) {
                $whereConditions[] = 'status = ?';
                $params[] = $status;
            }
            
            if ($search) {
                $whereConditions[] = '(part_name LIKE ? OR part_number LIKE ? OR description LIKE ?)';
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
            
            if ($low_stock) {
                $whereConditions[] = 'current_stock <= min_stock_level';
            }
            
            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Get inventory items
            $sql = "SELECT 
                i.*,
                CASE 
                    WHEN current_stock <= 0 THEN 'out_of_stock'
                    WHEN current_stock <= min_stock_level THEN 'low_stock'
                    WHEN current_stock <= reorder_point THEN 'reorder_needed'
                    ELSE 'in_stock'
                END as stock_status,
                (current_stock * unit_cost) as total_value
            FROM inventory i 
            $whereClause 
            ORDER BY part_name
            LIMIT ? OFFSET ?";
            
            $params[] = (int) $limit;
            $params[] = (int) $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll();
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM inventory i $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute(array_slice($params, 0, -2));
            $total = $countStmt->fetch()['total'];
            
            // Get summary statistics
            $stats = $this->getInventoryStats();
            
            return jsonResponse([
                'success' => true,
                'data' => [
                    'items' => $items,
                    'pagination' => [
                        'current_page' => (int) $page,
                        'per_page' => (int) $limit,
                        'total' => (int) $total,
                        'total_pages' => ceil($total / $limit)
                    ],
                    'stats' => $stats
                ],
                'message' => 'Inventory retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to get inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/inventory/update - Cập nhật tồn kho
     */
    public function updateStock()
    {
        try {
            $data = getJsonInput();
            
            // Validate required fields
            $required = ['inventory_id', 'type', 'quantity'];
            $missing = validateRequired($data, $required);
            
            if (!empty($missing)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Missing required fields',
                    'missing_fields' => $missing
                ], 422);
            }
            
            $validTypes = ['stock_in', 'stock_out', 'adjustment', 'return'];
            if (!in_array($data['type'], $validTypes)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Invalid transaction type',
                    'valid_types' => $validTypes
                ], 422);
            }
            
            // Get current inventory item
            $sql = "SELECT * FROM inventory WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['inventory_id']]);
            $item = $stmt->fetch();
            
            if (!$item) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Inventory item not found'
                ], 404);
            }
            
            // Calculate new stock based on transaction type
            $previousStock = $item['current_stock'];
            $quantity = (int) $data['quantity'];
            
            switch ($data['type']) {
                case 'stock_in':
                case 'return':
                    $newStock = $previousStock + $quantity;
                    break;
                case 'stock_out':
                    $newStock = $previousStock - $quantity;
                    if ($newStock < 0) {
                        return jsonResponse([
                            'success' => false,
                            'message' => 'Insufficient stock',
                            'current_stock' => $previousStock,
                            'requested' => $quantity
                        ], 400);
                    }
                    break;
                case 'adjustment':
                    $newStock = $quantity; // Direct set to new value
                    break;
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            try {
                // Update inventory
                $updateSql = "UPDATE inventory SET 
                    current_stock = ?, 
                    last_restocked_at = CASE WHEN ? IN ('stock_in', 'return') THEN NOW() ELSE last_restocked_at END,
                    last_restocked_by = CASE WHEN ? IN ('stock_in', 'return') THEN ? ELSE last_restocked_by END,
                    updated_at = NOW()
                    WHERE id = ?";
                
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([
                    $newStock, 
                    $data['type'], 
                    $data['type'], 
                    $data['performed_by'] ?? null,
                    $data['inventory_id']
                ]);
                
                // Record transaction
                $transSql = "INSERT INTO inventory_transactions (
                    inventory_id, type, quantity, previous_stock, new_stock,
                    reference_type, reference_id, unit_cost, total_cost, notes, performed_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $transStmt = $this->db->prepare($transSql);
                $transStmt->execute([
                    $data['inventory_id'],
                    $data['type'],
                    $data['type'] === 'stock_out' ? -$quantity : $quantity,
                    $previousStock,
                    $newStock,
                    $data['reference_type'] ?? null,
                    $data['reference_id'] ?? null,
                    $data['unit_cost'] ?? $item['unit_cost'],
                    ($data['unit_cost'] ?? $item['unit_cost']) * abs($quantity),
                    $data['notes'] ?? null,
                    $data['performed_by'] ?? 1 // Default staff ID
                ]);
                
                $this->db->commit();
                
                // Check if stock alert needed
                $this->checkStockAlerts($data['inventory_id'], $newStock, $item['min_stock_level']);
                
                // Get updated item
                $updatedItem = $this->getInventoryItemById($data['inventory_id']);
                
                return jsonResponse([
                    'success' => true,
                    'message' => 'Stock updated successfully',
                    'data' => [
                        'item' => $updatedItem,
                        'transaction' => [
                            'type' => $data['type'],
                            'quantity' => $quantity,
                            'previous_stock' => $previousStock,
                            'new_stock' => $newStock
                        ]
                    ]
                ]);
                
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to update stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/inventory/allocate - Phân bổ phụ tùng
     */
    public function allocateParts()
    {
        try {
            $data = getJsonInput();
            
            // Validate required fields
            $required = ['allocations', 'reference_type', 'reference_id'];
            $missing = validateRequired($data, $required);
            
            if (!empty($missing)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Missing required fields',
                    'missing_fields' => $missing
                ], 422);
            }
            
            // Validate allocations array
            if (!is_array($data['allocations']) || empty($data['allocations'])) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Invalid allocations data'
                ], 422);
            }
            
            $this->db->beginTransaction();
            
            try {
                $results = [];
                
                foreach ($data['allocations'] as $allocation) {
                    if (!isset($allocation['inventory_id']) || !isset($allocation['quantity'])) {
                        throw new \Exception('Invalid allocation item: missing inventory_id or quantity');
                    }
                    
                    // Get current item
                    $sql = "SELECT * FROM inventory WHERE id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$allocation['inventory_id']]);
                    $item = $stmt->fetch();
                    
                    if (!$item) {
                        throw new \Exception("Inventory item {$allocation['inventory_id']} not found");
                    }
                    
                    $quantity = (int) $allocation['quantity'];
                    
                    // Check available stock
                    if ($item['available_stock'] < $quantity) {
                        throw new \Exception("Insufficient available stock for {$item['part_name']}. Available: {$item['available_stock']}, Requested: {$quantity}");
                    }
                    
                    // Update reserved stock
                    $updateSql = "UPDATE inventory SET reserved_stock = reserved_stock + ? WHERE id = ?";
                    $updateStmt = $this->db->prepare($updateSql);
                    $updateStmt->execute([$quantity, $allocation['inventory_id']]);
                    
                    // Record allocation transaction
                    $transSql = "INSERT INTO inventory_transactions (
                        inventory_id, type, quantity, previous_stock, new_stock,
                        reference_type, reference_id, notes, performed_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $transStmt = $this->db->prepare($transSql);
                    $transStmt->execute([
                        $allocation['inventory_id'],
                        'allocation',
                        $quantity,
                        $item['current_stock'],
                        $item['current_stock'], // Current stock doesn't change, only reserved
                        $data['reference_type'],
                        $data['reference_id'],
                        "Allocated for {$data['reference_type']} #{$data['reference_id']}",
                        $data['performed_by'] ?? 1
                    ]);
                    
                    $results[] = [
                        'inventory_id' => $allocation['inventory_id'],
                        'part_name' => $item['part_name'],
                        'allocated_quantity' => $quantity,
                        'remaining_available' => $item['available_stock'] - $quantity
                    ];
                }
                
                $this->db->commit();
                
                return jsonResponse([
                    'success' => true,
                    'message' => 'Parts allocated successfully',
                    'data' => [
                        'allocations' => $results,
                        'reference' => [
                            'type' => $data['reference_type'],
                            'id' => $data['reference_id']
                        ]
                    ]
                ]);
                
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to allocate parts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/inventory/alerts - Cảnh báo thiếu hàng
     */
    public function getAlerts()
    {
        try {
            // Get various types of alerts
            $alerts = [
                'out_of_stock' => $this->getOutOfStockItems(),
                'low_stock' => $this->getLowStockItems(),
                'reorder_needed' => $this->getReorderNeededItems(),
                'expired_reserved' => $this->getExpiredReservedItems()
            ];
            
            // Calculate alert counts
            $alertCounts = [
                'critical' => count($alerts['out_of_stock']),
                'warning' => count($alerts['low_stock']),
                'info' => count($alerts['reorder_needed']),
                'total' => array_sum([
                    count($alerts['out_of_stock']),
                    count($alerts['low_stock']),
                    count($alerts['reorder_needed']),
                    count($alerts['expired_reserved'])
                ])
            ];
            
            return jsonResponse([
                'success' => true,
                'data' => [
                    'alerts' => $alerts,
                    'counts' => $alertCounts,
                    'generated_at' => date('c')
                ],
                'message' => 'Inventory alerts retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to get inventory alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper methods
     */
    private function getInventoryStats()
    {
        $sql = "SELECT 
            COUNT(*) as total_items,
            SUM(current_stock * unit_cost) as total_value,
            COUNT(CASE WHEN current_stock <= 0 THEN 1 END) as out_of_stock_items,
            COUNT(CASE WHEN current_stock <= min_stock_level THEN 1 END) as low_stock_items,
            COUNT(CASE WHEN current_stock <= reorder_point THEN 1 END) as reorder_needed_items,
            SUM(current_stock) as total_units,
            SUM(reserved_stock) as total_reserved,
            COUNT(DISTINCT category) as categories
        FROM inventory WHERE status = 'active'";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
    
    private function getInventoryItemById($id)
    {
        $sql = "SELECT 
            i.*,
            CASE 
                WHEN current_stock <= 0 THEN 'out_of_stock'
                WHEN current_stock <= min_stock_level THEN 'low_stock'
                WHEN current_stock <= reorder_point THEN 'reorder_needed'
                ELSE 'in_stock'
            END as stock_status,
            (current_stock * unit_cost) as total_value
        FROM inventory i WHERE i.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    private function getOutOfStockItems()
    {
        $sql = "SELECT part_number, part_name, category, current_stock, min_stock_level 
                FROM inventory 
                WHERE current_stock <= 0 AND status = 'active'
                ORDER BY part_name";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    private function getLowStockItems()
    {
        $sql = "SELECT part_number, part_name, category, current_stock, min_stock_level 
                FROM inventory 
                WHERE current_stock > 0 AND current_stock <= min_stock_level AND status = 'active'
                ORDER BY (current_stock / min_stock_level), part_name";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    private function getReorderNeededItems()
    {
        $sql = "SELECT part_number, part_name, category, current_stock, reorder_point, reorder_quantity
                FROM inventory 
                WHERE current_stock <= reorder_point AND current_stock > min_stock_level AND status = 'active'
                ORDER BY part_name";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    private function getExpiredReservedItems()
    {
        // This would need additional logic for reservation expiry
        // For now, return empty array
        return [];
    }
    
    private function checkStockAlerts($inventoryId, $currentStock, $minStockLevel)
    {
        if ($currentStock <= $minStockLevel) {
            // Send low stock alert notification
            // This would integrate with the notification system
            error_log("Low stock alert for inventory ID: {$inventoryId}, Current stock: {$currentStock}");
        }
    }
}